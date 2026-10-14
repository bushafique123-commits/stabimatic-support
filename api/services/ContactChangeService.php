<?php

final class ContactChangeService
{
    private $users;
    private $otps;
    private $otpService;
    private $emailService;
    private $rateLimits;

    public function __construct(
        ?UserRepository $users = null,
        ?OtpVerificationRepository $otps = null,
        ?OtpService $otpService = null,
        ?EmailService $emailService = null,
        ?RateLimitRepository $rateLimits = null
    ) {
        $this->users = $users ?: new UserRepository();
        $this->otps = $otps ?: new OtpVerificationRepository();
        $this->otpService = $otpService ?: new OtpService(SmsProviderFactory::make());
        $this->emailService = $emailService ?: new EmailService();
        $this->rateLimits = $rateLimits ?: new RateLimitRepository();
    }

    public function sendMobileOtp(array $claims, array $input): array
    {
        $mobile = InputNormalizer::mobile($input['mobile'] ?? $input['new_mobile'] ?? '');
        $errors = Validator::validate(['mobile' => $mobile], ['mobile' => ['required', 'pak_mobile']]);
        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        return $this->sendOtp($claims, OTP_TYPE_MOBILE, $mobile);
    }

    public function verifyMobileOtp(array $claims, array $input): array
    {
        $data = [
            'mobile' => InputNormalizer::mobile($input['mobile'] ?? $input['new_mobile'] ?? ''),
            'otp' => preg_replace('/[^0-9]/', '', (string) ($input['otp'] ?? '')),
        ];
        $errors = Validator::validate($data, [
            'mobile' => ['required', 'pak_mobile'],
            'otp' => ['required', 'digits:6'],
        ]);
        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        return $this->verifyOtp($claims, OTP_TYPE_MOBILE, $data['mobile'], $data['otp']);
    }

    public function sendEmailOtp(array $claims, array $input): array
    {
        $email = InputNormalizer::email($input['email'] ?? $input['new_email'] ?? '');
        $errors = Validator::validate(['email' => $email], ['email' => ['required', 'email', 'max:190']]);
        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        return $this->sendOtp($claims, OTP_TYPE_EMAIL, $email);
    }

    public function verifyEmailOtp(array $claims, array $input): array
    {
        $data = [
            'email' => InputNormalizer::email($input['email'] ?? $input['new_email'] ?? ''),
            'otp' => preg_replace('/[^0-9]/', '', (string) ($input['otp'] ?? '')),
        ];
        $errors = Validator::validate($data, [
            'email' => ['required', 'email', 'max:190'],
            'otp' => ['required', 'digits:6'],
        ]);
        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        return $this->verifyOtp($claims, OTP_TYPE_EMAIL, $data['email'], $data['otp']);
    }

    private function sendOtp(array $claims, string $channel, string $target): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            $field = $channel === OTP_TYPE_EMAIL ? 'email' : 'mobile';
            if ($target === $user[$field]) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => [$field => 'Enter a different ' . $field]];
            }

            if ($this->users->existsByField($field, $target, (int) $user['id'])) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => [$field => ucfirst($field) . ' is already registered']];
            }

            $latest = $this->otps->latest($this->otpCriteria((int) $user['id'], $channel, $target));
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

            $rate = $this->rateLimits->hit($this->rateKey($user['id'], $channel, $target), 'contact_change_otp_send', 3600, 6, 900);
            if (!$rate['allowed']) {
                return [
                    'ok' => false,
                    'status' => 429,
                    'message' => 'Too many OTP requests',
                    'errors' => [$field => 'Too many OTP requests. Please try again later.'],
                ];
            }

            $pdo = db();
            $pdo->beginTransaction();

            $otp = $this->otpService->generateOtp();
            $this->otps->create([
                'user_id' => (int) $user['id'],
                'mobile' => $channel === OTP_TYPE_MOBILE ? $target : $user['mobile'],
                'email' => $channel === OTP_TYPE_EMAIL ? $target : $user['email'],
                'otp' => $this->otpService->hashOtp($otp),
                'type' => $channel,
                'purpose' => $channel === OTP_TYPE_MOBILE ? OTP_PURPOSE_CHANGE_MOBILE : OTP_PURPOSE_CHANGE_EMAIL,
                'expires_at' => $this->otpService->expiryDateTime(),
            ]);

            $sent = $channel === OTP_TYPE_EMAIL
                ? $this->emailService->sendOtp($target, $otp)
                : $this->otpService->sendMobileOtp($target, $otp);

            if (!$sent) {
                throw new RuntimeException('OTP provider rejected contact change request');
            }

            $pdo->commit();

            $action = $channel === OTP_TYPE_EMAIL ? 'EMAIL_CHANGE_OTP_SENT' : 'MOBILE_CHANGE_OTP_SENT';
            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_' . $action, 'profile', (int) $user['id'], 'Contact change OTP sent');

            $response = [
                'ok' => true,
                'channel' => $channel,
                'target' => $target,
                'masked_target' => $channel === OTP_TYPE_EMAIL ? $this->maskEmail($target) : $this->maskMobile($target),
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
            Logger::error('Contact change OTP unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        } catch (RuntimeException $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Contact change OTP send failed', ['detail' => $exception->getMessage()]);
            return [
                'ok' => false,
                'status' => 502,
                'message' => 'OTP could not be sent',
                'errors' => [$channel === OTP_TYPE_EMAIL ? 'email' : 'mobile' => 'Unable to send OTP right now'],
            ];
        }
    }

    private function verifyOtp(array $claims, string $channel, string $target, string $otp): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            $field = $channel === OTP_TYPE_EMAIL ? 'email' : 'mobile';
            if ($target === $user[$field]) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => [$field => 'Enter a different ' . $field]];
            }
            if ($this->users->existsByField($field, $target, (int) $user['id'])) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => [$field => ucfirst($field) . ' is already registered']];
            }

            $rateKey = $this->rateKey($user['id'], $channel, $target);
            $rate = $this->rateLimits->hit($rateKey, 'contact_change_otp_verify', 900, 10, 900);
            if (!$rate['allowed']) {
                return [
                    'ok' => false,
                    'status' => 429,
                    'message' => 'Too many verification attempts',
                    'errors' => ['otp' => 'Too many attempts. Please try again later.'],
                ];
            }

            $otpRow = $this->otps->latestPending($this->otpCriteria((int) $user['id'], $channel, $target));
            if (!$otpRow) {
                return ['ok' => false, 'status' => 422, 'message' => 'Verification code was not found', 'errors' => ['otp' => 'Verification code was not found. Please request a new OTP.']];
            }

            if ((int) $otpRow['attempt_count'] >= (int) config('otp.max_attempts')) {
                return ['ok' => false, 'status' => 429, 'message' => 'Maximum OTP attempts exceeded', 'errors' => ['otp' => 'Maximum OTP attempts exceeded. Please request a new OTP.']];
            }
            if ($this->otpService->isExpired($otpRow['expires_at'])) {
                return ['ok' => false, 'status' => 422, 'message' => 'OTP expired', 'errors' => ['otp' => 'OTP expired. Please request a new code.']];
            }
            if (!$this->otpService->verifyOtp($otp, $otpRow['otp'])) {
                $this->otps->incrementAttempts((int) $otpRow['id']);
                return ['ok' => false, 'status' => 422, 'message' => 'Invalid OTP', 'errors' => ['otp' => 'Invalid verification code']];
            }

            $pdo = db();
            $pdo->beginTransaction();

            $this->otps->markVerified((int) $otpRow['id']);
            if ($channel === OTP_TYPE_EMAIL) {
                $this->users->updateEmail((int) $user['id'], $target);
            } else {
                $this->users->updateMobile((int) $user['id'], $target);
            }

            $pdo->commit();
            $this->rateLimits->clear($rateKey, 'contact_change_otp_verify');

            $action = $channel === OTP_TYPE_EMAIL ? 'EMAIL_CHANGED' : 'MOBILE_CHANGED';
            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_' . $action, 'profile', (int) $user['id'], 'Contact detail changed');

            $updated = $this->users->findById((int) $user['id']);
            return ['ok' => true, 'profile' => $this->profile($updated ?: $user)];
        } catch (PDOException $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Contact change verification unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    private function activeUser(array $claims): ?array
    {
        $user = $this->users->findById((int) ($claims['sub'] ?? 0));
        if (!$user || !in_array($user['role'], [ROLE_DEALER, ROLE_INSTALLER], true) || !(int) $user['is_active']) {
            return null;
        }

        return $user;
    }

    private function otpCriteria(int $userId, string $channel, string $target): array
    {
        $criteria = [
            'user_id' => $userId,
            'type' => $channel,
            'purpose' => $channel === OTP_TYPE_MOBILE ? OTP_PURPOSE_CHANGE_MOBILE : OTP_PURPOSE_CHANGE_EMAIL,
        ];
        if ($channel === OTP_TYPE_EMAIL) {
            $criteria['email'] = $target;
        } else {
            $criteria['mobile'] = $target;
        }

        return $criteria;
    }

    private function profile(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'role' => $user['role'],
            'full_name' => $user['full_name'],
            'mobile' => $user['mobile'],
            'email' => $user['email'],
            'cnic' => $user['cnic'] ?? '',
            'bank_name' => $user['bank_name'],
            'bank_account_title' => $user['bank_account_title'],
            'bank_account_number' => $user['bank_account_number'],
            'iban' => $user['iban'],
            'mobile_verified_at' => $user['mobile_verified_at'],
            'email_verified_at' => $user['email_verified_at'],
            'is_verified' => (bool) $user['is_verified'],
            'is_active' => (bool) $user['is_active'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
        ];
    }

    private function rateKey($userId, string $channel, string $target): string
    {
        return (int) $userId . '|' . $channel . '|' . strtolower($target) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
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

    private function forbidden(): array
    {
        return ['ok' => false, 'status' => 403, 'message' => 'Contact changes are not available for this account', 'errors' => ['profile' => 'Contact changes are not available']];
    }

    private function unavailable(): array
    {
        return [
            'ok' => false,
            'status' => 503,
            'message' => 'Contact change service is unavailable',
            'errors' => ['profile' => 'Please try again later'],
        ];
    }
}
