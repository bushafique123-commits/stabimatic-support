<?php

final class PasswordResetService
{
    private $users;
    private $otps;
    private $otpService;
    private $emailService;
    private $rateLimits;
    private $tokens;

    public function __construct(
        ?UserRepository $users = null,
        ?OtpVerificationRepository $otps = null,
        ?OtpService $otpService = null,
        ?EmailService $emailService = null,
        ?RateLimitRepository $rateLimits = null,
        ?AuthTokenRepository $tokens = null
    ) {
        $this->users = $users ?: new UserRepository();
        $this->otps = $otps ?: new OtpVerificationRepository();
        $this->otpService = $otpService ?: new OtpService(SmsProviderFactory::make());
        $this->emailService = $emailService ?: new EmailService();
        $this->rateLimits = $rateLimits ?: new RateLimitRepository();
        $this->tokens = $tokens ?: new AuthTokenRepository();
    }

    public function request(array $input): array
    {
        $identifier = InputNormalizer::identifier($input['identifier'] ?? $input['mobile_email'] ?? '');
        $errors = Validator::validate(['identifier' => $identifier], ['identifier' => ['required']]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $rate = $this->rateLimits->hit($this->rateKey($identifier), 'forgot_password_request', 3600, 6, 900);
            if (!$rate['allowed']) {
                return [
                    'ok' => false,
                    'status' => 429,
                    'message' => 'Too many reset requests',
                    'errors' => ['identifier' => 'Too many reset requests. Please try again later.'],
                ];
            }

            $user = $this->users->findByIdentifier($identifier);
            if (!$user || !(int) $user['is_active']) {
                return ['ok' => false, 'status' => 404, 'message' => 'Account not found', 'errors' => ['identifier' => 'Account not found']];
            }

            $channel = strpos($identifier, '@') !== false ? OTP_TYPE_EMAIL : OTP_TYPE_MOBILE;
            $target = $channel === OTP_TYPE_EMAIL ? $user['email'] : $user['mobile'];
            $latest = $this->otps->latest($this->otpCriteria($user, $channel));

            if ($latest) {
                $wait = $this->otpService->secondsUntilResend($latest['created_at']);
                if ($wait > 0) {
                    return [
                        'ok' => false,
                        'status' => 429,
                        'message' => 'Please wait before requesting another OTP',
                        'errors' => ['resend' => 'Please wait before requesting another OTP'],
                        'retry_after_seconds' => $wait,
                    ];
                }
            }

            $pdo = db();
            $pdo->beginTransaction();

            $otp = $this->otpService->generateOtp();
            $this->otps->create([
                'user_id' => (int) $user['id'],
                'mobile' => $user['mobile'],
                'email' => $user['email'],
                'otp' => $this->otpService->hashOtp($otp),
                'type' => $channel,
                'purpose' => OTP_PURPOSE_FORGOT_PASSWORD,
                'expires_at' => $this->otpService->expiryDateTime(),
            ]);

            $sent = $channel === OTP_TYPE_EMAIL
                ? $this->emailService->sendOtp($user['email'], $otp)
                : $this->otpService->sendMobileOtp($user['mobile'], $otp);

            if (!$sent) {
                throw new RuntimeException('OTP provider rejected password reset request');
            }

            $pdo->commit();
            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_RESET_OTP_SENT', 'auth', (int) $user['id'], 'Password reset OTP sent');

            $response = [
                'ok' => true,
                'user_id' => (int) $user['id'],
                'channel' => $channel,
                'target' => $target,
                'masked_target' => $channel === OTP_TYPE_EMAIL ? $this->maskEmail($target) : $this->maskMobile($target),
                'otp_expires_in_minutes' => (int) config('otp.expiry_minutes'),
                'otp_resend_seconds' => (int) config('otp.resend_seconds'),
                'next_step' => 'verify_reset_otp',
            ];

            if (config('otp.expose_in_response')) {
                $response['development_otp'] = $otp;
            }

            return $response;
        } catch (PDOException $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Password reset request unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        } catch (RuntimeException $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Password reset OTP send failed', ['detail' => $exception->getMessage()]);
            return [
                'ok' => false,
                'status' => 502,
                'message' => 'Password reset OTP could not be sent',
                'errors' => ['identifier' => 'Unable to send OTP right now'],
            ];
        }
    }

    public function verifyOtp(array $input): array
    {
        $data = [
            'user_id' => (int) ($input['user_id'] ?? 0),
            'channel' => InputNormalizer::string($input['channel'] ?? ''),
            'otp' => preg_replace('/[^0-9]/', '', (string) ($input['otp'] ?? '')),
        ];
        $errors = Validator::validate($data, [
            'user_id' => ['required', 'positive_int'],
            'channel' => ['required', 'in:mobile,email'],
            'otp' => ['required', 'digits:6'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $user = $this->users->findById((int) $data['user_id']);
            if (!$user || !(int) $user['is_active']) {
                return ['ok' => false, 'status' => 404, 'message' => 'Account not found', 'errors' => ['identifier' => 'Account not found']];
            }

            $target = $data['channel'] === OTP_TYPE_EMAIL ? $user['email'] : $user['mobile'];
            $rate = $this->rateLimits->hit($this->rateKey((string) $target), 'forgot_password_verify', 900, 10, 900);
            if (!$rate['allowed']) {
                return [
                    'ok' => false,
                    'status' => 429,
                    'message' => 'Too many verification attempts',
                    'errors' => ['otp' => 'Too many attempts. Please try again later.'],
                ];
            }

            $otpRow = $this->otps->latestPending($this->otpCriteria($user, $data['channel']));
            if (!$otpRow) {
                return ['ok' => false, 'status' => 422, 'message' => 'Verification code was not found', 'errors' => ['otp' => 'Verification code was not found. Please request a new OTP.']];
            }

            if ((int) $otpRow['attempt_count'] >= (int) config('otp.max_attempts')) {
                return ['ok' => false, 'status' => 429, 'message' => 'Maximum OTP attempts exceeded', 'errors' => ['otp' => 'Maximum OTP attempts exceeded. Please request a new OTP.']];
            }

            if ($this->otpService->isExpired($otpRow['expires_at'])) {
                return ['ok' => false, 'status' => 422, 'message' => 'OTP expired', 'errors' => ['otp' => 'OTP expired. Please request a new code.']];
            }

            if (!$this->otpService->verifyOtp($data['otp'], $otpRow['otp'])) {
                $this->otps->incrementAttempts((int) $otpRow['id']);
                return ['ok' => false, 'status' => 422, 'message' => 'Invalid OTP', 'errors' => ['otp' => 'Invalid verification code']];
            }

            $this->otps->markVerified((int) $otpRow['id']);
            $this->rateLimits->clear($this->rateKey((string) $target), 'forgot_password_verify');
            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_RESET_OTP_VERIFIED', 'auth', (int) $user['id'], 'Password reset OTP verified');

            return [
                'ok' => true,
                'reset_token' => JwtHelper::issue([
                    'sub' => (int) $user['id'],
                    'role' => $user['role'],
                    'purpose' => 'password_reset',
                    'otp_id' => (int) $otpRow['id'],
                    'exp' => time() + 900,
                ]),
                'expires_in_seconds' => 900,
                'next_step' => 'reset_password',
            ];
        } catch (PDOException $exception) {
            Logger::error('Password reset OTP verification unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function reset(array $input): array
    {
        $data = [
            'reset_token' => (string) ($input['reset_token'] ?? ''),
            'password' => (string) ($input['password'] ?? ''),
            'confirm_password' => (string) ($input['confirm_password'] ?? ''),
        ];
        $errors = Validator::validate($data, [
            'reset_token' => ['required'],
            'password' => ['required', 'strong_password'],
            'confirm_password' => ['required', 'same:password'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        $claims = JwtHelper::verify($data['reset_token']);
        if (!$claims || ($claims['purpose'] ?? '') !== 'password_reset' || empty($claims['sub']) || empty($claims['otp_id'])) {
            return ['ok' => false, 'status' => 401, 'message' => 'Invalid or expired reset token', 'errors' => ['reset_token' => 'Invalid or expired reset token']];
        }

        try {
            $user = $this->users->findById((int) $claims['sub']);
            if (!$user || !(int) $user['is_active']) {
                return ['ok' => false, 'status' => 404, 'message' => 'Account not found', 'errors' => ['reset_token' => 'Account not found']];
            }

            $otpRow = $this->otps->findVerifiedReset((int) $claims['otp_id'], (int) $user['id']);
            if (!$otpRow || (int) $otpRow['attempt_count'] >= (int) config('otp.max_attempts')) {
                return ['ok' => false, 'status' => 401, 'message' => 'Invalid or expired reset token', 'errors' => ['reset_token' => 'Invalid or expired reset token']];
            }

            if (password_verify($data['password'], $user['password_hash'])) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => ['password' => 'New password must be different from current password']];
            }

            $this->users->updatePassword((int) $user['id'], password_hash($data['password'], PASSWORD_DEFAULT));
            $this->otps->markConsumed((int) $otpRow['id']);
            $this->tokens->revokeUserTokens((int) $user['id']);
            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_PASSWORD_RESET', 'auth', (int) $user['id'], 'User reset password');

            return ['ok' => true];
        } catch (PDOException $exception) {
            Logger::error('Password reset unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    private function otpCriteria(array $user, string $channel): array
    {
        $criteria = [
            'user_id' => (int) $user['id'],
            'type' => $channel,
            'purpose' => OTP_PURPOSE_FORGOT_PASSWORD,
        ];

        if ($channel === OTP_TYPE_EMAIL) {
            $criteria['email'] = $user['email'];
        } else {
            $criteria['mobile'] = $user['mobile'];
        }

        return $criteria;
    }

    private function rateKey(string $identifier): string
    {
        return strtolower($identifier) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private function maskMobile(string $mobile): string
    {
        if (strlen($mobile) < 7) {
            return $mobile ?: '***';
        }

        return substr($mobile, 0, 4) . '***' . substr($mobile, -2);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2 || $parts[0] === '') {
            return '***';
        }

        return substr($parts[0], 0, 1) . '***@' . $parts[1];
    }

    private function unavailable(): array
    {
        return [
            'ok' => false,
            'status' => 503,
            'message' => 'Password reset service is unavailable',
            'errors' => ['reset' => 'Please try again later'],
        ];
    }
}
