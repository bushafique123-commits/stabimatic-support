<?php

final class MobileOtpService
{
    private $users;
    private $otps;
    private $otpService;
    private $rateLimits;

    public function __construct(
        ?UserRepository $users = null,
        ?OtpVerificationRepository $otps = null,
        ?OtpService $otpService = null,
        ?RateLimitRepository $rateLimits = null
    ) {
        $this->users = $users ?: new UserRepository();
        $this->otps = $otps ?: new OtpVerificationRepository();
        $this->otpService = $otpService ?: new OtpService(SmsProviderFactory::make());
        $this->rateLimits = $rateLimits ?: new RateLimitRepository();
    }

    public function resend(array $input): array
    {
        $data = $this->normalizeContext($input);
        $errors = Validator::validate($data, [
            'user_id' => ['required', 'positive_int'],
            'mobile' => ['required', 'pak_mobile'],
            'purpose' => ['required', 'in:registration,forgot_password,change_mobile'],
        ]);

        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'status' => 422];
        }

        try {
            $user = $this->users->findPendingMobileVerification((int) $data['user_id'], $data['mobile']);
            if (!$user) {
                return ['ok' => false, 'status' => 404, 'message' => 'Account not found', 'errors' => ['mobile' => 'Account not found']];
            }
            if ($user['mobile_verified_at']) {
                return [
                    'ok' => true,
                    'already_verified' => true,
                    'next_step' => 'verify_email',
                    'mobile' => $user['mobile'],
                ];
            }

            $latest = $this->otps->latest([
                'user_id' => (int) $user['id'],
                'mobile' => $user['mobile'],
                'type' => OTP_TYPE_MOBILE,
                'purpose' => $data['purpose'],
            ]);

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

            $rate = $this->rateLimits->hit($user['mobile'], 'mobile_otp_resend', 3600, 6, 900);
            if (!$rate['allowed']) {
                return [
                    'ok' => false,
                    'status' => 429,
                    'message' => 'Too many OTP requests',
                    'errors' => ['mobile' => 'Too many OTP requests. Please try again later.'],
                ];
            }

            $pdo = db();
            $pdo->beginTransaction();

            $otp = $this->otpService->generateOtp();
            $this->otps->create([
                'user_id' => (int) $user['id'],
                'mobile' => $user['mobile'],
                'email' => $user['email'],
                'otp' => $this->otpService->hashOtp($otp),
                'type' => OTP_TYPE_MOBILE,
                'purpose' => $data['purpose'],
                'expires_at' => $this->otpService->expiryDateTime(),
            ]);
            if (!$this->otpService->sendMobileOtp($user['mobile'], $otp)) {
                throw new RuntimeException('SMS provider rejected OTP request');
            }

            $pdo->commit();

            Logger::activity($user['role'], (int) $user['id'], 'MOBILE_OTP_RESENT', 'auth', (int) $user['id'], 'Mobile OTP resent');

            $response = [
                'ok' => true,
                'mobile' => $user['mobile'],
                'otp_expires_in_minutes' => (int) config('otp.expiry_minutes'),
                'otp_resend_seconds' => (int) config('otp.resend_seconds'),
            ];
            if (config('otp.expose_in_response')) {
                $response['development_otp'] = $otp;
            }
            return $response;
        } catch (PDOException $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Mobile OTP resend unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        } catch (RuntimeException $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Mobile OTP resend failed', ['detail' => $exception->getMessage()]);
            return [
                'ok' => false,
                'status' => 502,
                'message' => 'Mobile OTP could not be sent',
                'errors' => ['mobile' => $this->smsErrorMessage($exception)],
            ];
        }
    }

    public function verify(array $input): array
    {
        $data = $this->normalizeContext($input);
        $data['otp'] = preg_replace('/[^0-9]/', '', (string) ($input['otp'] ?? ''));

        $errors = Validator::validate($data, [
            'user_id' => ['required', 'positive_int'],
            'mobile' => ['required', 'pak_mobile'],
            'purpose' => ['required', 'in:registration,forgot_password,change_mobile'],
            'otp' => ['required', 'digits:6'],
        ]);

        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'status' => 422];
        }

        try {
            $user = $this->users->findPendingMobileVerification((int) $data['user_id'], $data['mobile']);
            if (!$user) {
                return ['ok' => false, 'status' => 404, 'message' => 'Account not found', 'errors' => ['mobile' => 'Account not found']];
            }
            if ($user['mobile_verified_at']) {
                return [
                    'ok' => true,
                    'already_verified' => true,
                    'next_step' => 'verify_email',
                    'mobile_verified' => true,
                ];
            }

            $rate = $this->rateLimits->hit($user['mobile'], 'mobile_otp_verify', 900, 10, 900);
            if (!$rate['allowed']) {
                return [
                    'ok' => false,
                    'status' => 429,
                    'message' => 'Too many verification attempts',
                    'errors' => ['otp' => 'Too many attempts. Please try again later.'],
                ];
            }

            $otpRow = $this->otps->latestPending([
                'user_id' => (int) $user['id'],
                'mobile' => $user['mobile'],
                'type' => OTP_TYPE_MOBILE,
                'purpose' => $data['purpose'],
            ]);

            if (!$otpRow) {
                return ['ok' => false, 'status' => 422, 'errors' => ['otp' => 'Verification code was not found. Please request a new OTP.']];
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
            $this->users->markMobileVerified((int) $user['id']);
            $this->rateLimits->clear($user['mobile'], 'mobile_otp_verify');

            Logger::activity($user['role'], (int) $user['id'], 'MOBILE_VERIFIED', 'auth', (int) $user['id'], 'Mobile number verified');

            $emailOtp = (new EmailOtpService())->sendForUser($user, OTP_PURPOSE_REGISTRATION);

            return [
                'ok' => true,
                'mobile_verified' => true,
                'email' => $user['email'],
                'masked_email' => $this->maskEmail($user['email']),
                'email_otp_sent' => (bool) ($emailOtp['ok'] ?? false),
                'next_step' => 'verify_email',
            ];
        } catch (PDOException $exception) {
            Logger::error('Mobile OTP verification unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    private function normalizeContext(array $input): array
    {
        return [
            'user_id' => (int) ($input['user_id'] ?? 0),
            'mobile' => InputNormalizer::mobile($input['mobile'] ?? ''),
            'purpose' => InputNormalizer::string($input['purpose'] ?? OTP_PURPOSE_REGISTRATION),
        ];
    }

    private function unavailable(): array
    {
        return [
            'ok' => false,
            'status' => 503,
            'message' => 'OTP service is unavailable',
            'errors' => ['otp' => 'Please try again later'],
        ];
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }
        return substr($parts[0], 0, 1) . '***@' . $parts[1];
    }

    private function smsErrorMessage(RuntimeException $exception): string
    {
        $message = $exception->getMessage();
        if (strpos($message, 'SendPK OTP template ID is missing') === 0) {
            return 'SendPK OTP template ID is missing. Create and approve an OTP template in SendPK, then set SENDPK_OTP_TEMPLATE_ID.';
        }
        if (strpos($message, 'SendPK API key is missing') === 0) {
            return 'SendPK API key is missing. Set SENDPK_API_KEY in the API environment.';
        }
        if (strpos($message, 'SendPK rejected OTP request:') === 0 && (bool) config('app.debug', false)) {
            return $message;
        }
        if (strpos($message, 'SendPK request failed:') === 0 && (bool) config('app.debug', false)) {
            return $message;
        }
        return 'Unable to send OTP right now';
    }
}
