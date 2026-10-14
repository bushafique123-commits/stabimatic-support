<?php

final class EmailOtpService
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

    public function resend(array $input): array
    {
        $data = $this->normalizeContext($input);
        $errors = Validator::validate($data, [
            'user_id' => ['required', 'positive_int'],
            'email' => ['required', 'email'],
            'purpose' => ['required', 'in:registration,forgot_password,change_email'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'errors' => $errors];
        }

        try {
            $user = $this->users->findPendingEmailVerification((int) $data['user_id'], $data['email']);
            $guard = $this->guardUserForEmailOtp($user, $data['purpose']);
            if ($guard) {
                return $guard;
            }

            if ($user['email_verified_at']) {
                return [
                    'ok' => true,
                    'already_verified' => true,
                    'next_step' => 'login',
                    'email' => $user['email'],
                ];
            }

            $latest = $this->otps->latest([
                'user_id' => (int) $user['id'],
                'email' => $user['email'],
                'type' => OTP_TYPE_EMAIL,
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

            $rate = $this->rateLimits->hit($user['email'], 'email_otp_resend', 3600, 6, 900);
            if (!$rate['allowed']) {
                return [
                    'ok' => false,
                    'status' => 429,
                    'message' => 'Too many OTP requests',
                    'errors' => ['email' => 'Too many OTP requests. Please try again later.'],
                ];
            }

            return $this->sendForUser($user, $data['purpose']);
        } catch (PDOException $exception) {
            Logger::error('Email OTP resend unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function verify(array $input): array
    {
        $data = $this->normalizeContext($input);
        $data['otp'] = preg_replace('/[^0-9]/', '', (string) ($input['otp'] ?? ''));

        $errors = Validator::validate($data, [
            'user_id' => ['required', 'positive_int'],
            'email' => ['required', 'email'],
            'purpose' => ['required', 'in:registration,forgot_password,change_email'],
            'otp' => ['required', 'digits:6'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'errors' => $errors];
        }

        try {
            $user = $this->users->findPendingEmailVerification((int) $data['user_id'], $data['email']);
            $guard = $this->guardUserForEmailOtp($user, $data['purpose']);
            if ($guard) {
                return $guard;
            }

            if ($user['email_verified_at']) {
                return [
                    'ok' => true,
                    'already_verified' => true,
                    'email_verified' => true,
                    'account_verified' => (bool) $user['is_verified'],
                    'next_step' => 'login',
                ];
            }

            $rate = $this->rateLimits->hit($user['email'], 'email_otp_verify', 900, 10, 900);
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
                'email' => $user['email'],
                'type' => OTP_TYPE_EMAIL,
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
            $this->users->markEmailVerified((int) $user['id']);
            $this->rateLimits->clear($user['email'], 'email_otp_verify');

            Logger::activity($user['role'], (int) $user['id'], 'EMAIL_VERIFIED', 'auth', (int) $user['id'], 'Email address verified');

            return [
                'ok' => true,
                'email_verified' => true,
                'account_verified' => true,
                'next_step' => 'login',
            ];
        } catch (PDOException $exception) {
            Logger::error('Email OTP verification unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function sendForUser(array $user, string $purpose = OTP_PURPOSE_REGISTRATION): array
    {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $otp = $this->otpService->generateOtp();
            $this->otps->create([
                'user_id' => (int) $user['id'],
                'mobile' => $user['mobile'],
                'email' => $user['email'],
                'otp' => $this->otpService->hashOtp($otp),
                'type' => OTP_TYPE_EMAIL,
                'purpose' => $purpose,
                'expires_at' => $this->otpService->expiryDateTime(),
            ]);

            if (!$this->emailService->sendOtp($user['email'], $otp)) {
                throw new RuntimeException('Email provider rejected OTP request');
            }

            $pdo->commit();
            Logger::activity($user['role'], (int) $user['id'], 'EMAIL_OTP_SENT', 'auth', (int) $user['id'], 'Email OTP sent');

            $response = [
                'ok' => true,
                'email' => $user['email'],
                'masked_email' => $this->maskEmail($user['email']),
                'otp_expires_in_minutes' => (int) config('otp.expiry_minutes'),
                'otp_resend_seconds' => (int) config('otp.resend_seconds'),
                'next_step' => 'verify_email',
            ];

            if (config('otp.expose_in_response')) {
                $response['development_otp'] = $otp;
            }

            return $response;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($exception instanceof PDOException) {
                throw $exception;
            }

            Logger::error('Email OTP send failed', ['detail' => $exception->getMessage()]);
            return [
                'ok' => false,
                'status' => 502,
                'message' => 'Email OTP could not be sent',
                'errors' => ['email' => 'Unable to send OTP right now'],
            ];
        }
    }

    private function normalizeContext(array $input): array
    {
        return [
            'user_id' => (int) ($input['user_id'] ?? 0),
            'email' => InputNormalizer::email($input['email'] ?? ''),
            'purpose' => InputNormalizer::otpPurpose($input['purpose'] ?? OTP_PURPOSE_REGISTRATION),
        ];
    }

    private function guardUserForEmailOtp(?array $user, string $purpose): ?array
    {
        if (!$user) {
            return ['ok' => false, 'status' => 404, 'message' => 'Account not found', 'errors' => ['email' => 'Account not found']];
        }

        if ($purpose === OTP_PURPOSE_REGISTRATION && !$user['mobile_verified_at']) {
            return [
                'ok' => false,
                'status' => 409,
                'message' => 'Mobile verification is required first',
                'errors' => ['mobile' => 'Verify mobile number before email'],
            ];
        }

        return null;
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
}
