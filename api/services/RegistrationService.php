<?php

final class RegistrationService
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

    public function register(array $input): array
    {
        $data = InputNormalizer::registration($input);
        try {
            $errors = $this->validate($data);
        } catch (PDOException $exception) {
            Logger::error('Registration validation database failure', ['detail' => $exception->getMessage()]);
            return [
                'ok' => false,
                'status' => 503,
                'message' => 'Registration service is unavailable',
                'errors' => ['registration' => $this->databaseErrorMessage($exception)],
            ];
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        try {
            $rate = $this->rateLimits->hit(
                $data['mobile'],
                'registration',
                3600,
                6,
                900
            );

            if (!$rate['allowed']) {
                $smsConfigurationException = $this->smsConfigurationException();
                if ($smsConfigurationException) {
                    $this->rateLimits->clear($data['mobile'], 'registration');
                    return [
                        'ok' => false,
                        'status' => 502,
                        'message' => 'Mobile OTP could not be sent',
                        'errors' => ['mobile' => $this->smsErrorMessage($smsConfigurationException)],
                    ];
                }

                $retryAfter = $this->secondsUntil($rate['blocked_until'] ?? null);
                return [
                    'ok' => false,
                    'status' => 429,
                    'message' => 'Too many registration attempts',
                    'errors' => ['mobile' => $retryAfter > 0 ? "Too many registration attempts. Please try again in {$retryAfter} seconds." : 'Too many registration attempts. Please try again later.'],
                    'retry_after_seconds' => $retryAfter,
                ];
            }

            $pdo = db();
            $pdo->beginTransaction();

            $userId = $this->users->create($data);
            $otp = $this->otpService->generateOtp();
            $this->otps->create([
                'user_id' => $userId,
                'mobile' => $data['mobile'],
                'email' => $data['email'],
                'otp' => $this->otpService->hashOtp($otp),
                'type' => OTP_TYPE_MOBILE,
                'purpose' => OTP_PURPOSE_REGISTRATION,
                'expires_at' => $this->otpService->expiryDateTime(),
            ]);
            if (!$this->otpService->sendMobileOtp($data['mobile'], $otp)) {
                throw new RuntimeException('SMS provider rejected OTP request');
            }

            $pdo->commit();
            Logger::activity($data['role'], $userId, 'REGISTRATION_CREATED', 'auth', $userId, 'User registered and mobile OTP sent');

            $response = [
                'ok' => true,
                'user_id' => $userId,
                'role' => $data['role'],
                'mobile' => $data['mobile'],
                'email' => $data['email'],
                'next_step' => 'verify_mobile',
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
            Logger::error('Registration failed', ['detail' => $exception->getMessage()]);
            $errors = $this->duplicateErrorFromException($exception);
            return [
                'ok' => false,
                'status' => isset($errors['registration']) ? 503 : 422,
                'message' => isset($errors['registration']) ? 'Registration service is unavailable' : 'Validation failed',
                'errors' => $errors,
            ];
        } catch (RuntimeException $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($this->isSmsConfigurationError($exception)) {
                $this->rateLimits->clear($data['mobile'], 'registration');
            }
            Logger::error('Registration SMS failed', ['detail' => $exception->getMessage()]);
            return [
                'ok' => false,
                'status' => 502,
                'message' => 'Mobile OTP could not be sent',
                'errors' => ['mobile' => $this->smsErrorMessage($exception)],
            ];
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function validate(array $data): array
    {
        $errors = Validator::validate($data, [
            'role' => ['required', 'in:dealer,installer'],
            'full_name' => ['required', 'min:3', 'max:150'],
            'mobile' => ['required', 'pak_mobile', 'max:20'],
            'email' => ['required', 'email', 'max:190'],
            'cnic' => ['required', 'cnic'],
            'bank_name' => ['required', 'max:150'],
            'bank_account_title' => ['required', 'max:150'],
            'bank_account_number' => ['required', 'max:50'],
            'iban' => ['required', 'iban', 'max:34'],
            'password' => ['required', 'strong_password'],
            'confirm_password' => ['required', 'same:password'],
            'terms_accepted' => ['accepted'],
        ]);

        if (!isset($errors['mobile']) && $this->users->existsByField('mobile', $data['mobile'])) {
            $errors['mobile'] = 'Mobile number is already registered';
        }
        if (!isset($errors['email']) && $this->users->existsByField('email', $data['email'])) {
            $errors['email'] = 'Email is already registered';
        }
        if (!isset($errors['cnic']) && $this->users->existsByField('cnic', $data['cnic'])) {
            $errors['cnic'] = 'CNIC is already registered';
        }

        return $errors;
    }

    private function duplicateErrorFromException(PDOException $exception): array
    {
        $message = $exception->getMessage();
        if (strpos($message, 'uq_users_mobile') !== false) {
            return ['mobile' => 'Mobile number is already registered'];
        }
        if (strpos($message, 'uq_users_email') !== false) {
            return ['email' => 'Email is already registered'];
        }
        if (strpos($message, 'uq_users_cnic') !== false) {
            return ['cnic' => 'CNIC is already registered'];
        }
        return ['registration' => $this->databaseErrorMessage($exception)];
    }

    private function databaseErrorMessage(PDOException $exception): string
    {
        if (!(bool) config('app.debug', false)) {
            return 'Please try again later';
        }

        $message = $exception->getMessage();
        if (strpos($message, 'Invalid parameter number') !== false) {
            return 'Database query parameter mismatch. Upload the latest API files.';
        }
        if (strpos($message, 'Unknown database') !== false) {
            return 'Database name is not found. Check DB_NAME in api/.env.';
        }
        if (strpos($message, 'Base table or view not found') !== false) {
            return 'Required database table is missing. Import database/schema.sql and migrations.';
        }
        if (strpos($message, 'Access denied') !== false) {
            return 'Database login failed. Check DB_USER and DB_PASSWORD in api/.env.';
        }

        return $message;
    }

    private function smsErrorMessage(RuntimeException $exception): string
    {
        $message = $exception->getMessage();
        if ($this->isMissingOtpTemplateId($message)) {
            return 'SendPK OTP template ID is missing. Create and approve an OTP template in SendPK, then set SENDPK_OTP_TEMPLATE_ID.';
        }
        if ($this->isMissingApiKey($message)) {
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

    private function isSmsConfigurationError(RuntimeException $exception): bool
    {
        $message = $exception->getMessage();
        return $this->isMissingOtpTemplateId($message) || $this->isMissingApiKey($message);
    }

    private function isMissingOtpTemplateId(string $message): bool
    {
        return strpos($message, 'SendPK OTP template ID is missing') === 0;
    }

    private function isMissingApiKey(string $message): bool
    {
        return strpos($message, 'SendPK API key is missing') === 0;
    }

    private function smsConfigurationException(): ?RuntimeException
    {
        if (strtolower((string) config('sms.provider', 'dummy')) !== 'sendpk') {
            return null;
        }
        if (trim((string) config('sms.sendpk.api_key', '')) === '') {
            return new RuntimeException('SendPK API key is missing. Set SENDPK_API_KEY in the API environment.');
        }
        if (trim((string) config('sms.sendpk.otp_template_id', '')) === '') {
            return new RuntimeException('SendPK OTP template ID is missing. Create and approve an OTP template in SendPK, then set SENDPK_OTP_TEMPLATE_ID.');
        }
        return null;
    }

    private function secondsUntil(?string $dateTime): int
    {
        if (!$dateTime) {
            return 0;
        }
        return max(0, strtotime($dateTime) - time());
    }
}
