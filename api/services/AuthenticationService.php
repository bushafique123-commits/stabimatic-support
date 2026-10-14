<?php

final class AuthenticationService
{
    private $users;
    private $tokens;
    private $rateLimits;
    private $otps;
    private $otpService;

    public function __construct(
        ?UserRepository $users = null,
        ?TokenService $tokens = null,
        ?RateLimitRepository $rateLimits = null,
        ?OtpVerificationRepository $otps = null,
        ?OtpService $otpService = null
    ) {
        $this->users = $users ?: new UserRepository();
        $this->tokens = $tokens ?: new TokenService();
        $this->rateLimits = $rateLimits ?: new RateLimitRepository();
        $this->otps = $otps ?: new OtpVerificationRepository();
        $this->otpService = $otpService ?: new OtpService(SmsProviderFactory::make());
    }

    public function login(array $input): array
    {
        $data = [
            'identifier' => InputNormalizer::identifier($input['identifier'] ?? $input['mobile_email'] ?? ''),
            'password' => (string) ($input['password'] ?? ''),
        ];

        $errors = Validator::validate($data, [
            'identifier' => ['required'],
            'password' => ['required'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        $rateKey = $this->rateKey($data['identifier']);

        try {
            $rate = $this->rateLimits->hit($rateKey, 'login', 900, 8, 900);
            if (!$rate['allowed']) {
                return [
                    'ok' => false,
                    'status' => 429,
                    'message' => 'Too many login attempts',
                    'errors' => ['identifier' => 'Too many login attempts. Please try again later.'],
                ];
            }

            $user = $this->users->findByIdentifier($data['identifier']);
            if (!$user || !password_verify($data['password'], $user['password_hash'])) {
                return [
                    'ok' => false,
                    'status' => 401,
                    'message' => 'Invalid login credentials',
                    'errors' => ['identifier' => 'Invalid mobile/email or password'],
                ];
            }

            if (!(int) $user['is_active']) {
                return [
                    'ok' => false,
                    'status' => 403,
                    'message' => 'Account is inactive',
                    'errors' => ['account' => 'Your account is inactive. Please contact support.'],
                ];
            }

            if (!$user['mobile_verified_at'] || !$user['email_verified_at']) {
                return [
                    'ok' => false,
                    'status' => 403,
                    'message' => 'Account verification is required',
                    'errors' => ['verification' => 'Please complete account verification before login.'],
                    'data' => $this->verificationData($user),
                ];
            }

            if (!(int) $user['is_verified']) {
                $this->users->markAccountVerified((int) $user['id']);
                $user['is_verified'] = 1;
            }

            $token = $this->tokens->issueForUser($user);
            $this->rateLimits->clear($rateKey, 'login');

            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_LOGIN', 'auth', (int) $user['id'], 'User logged in');

            return [
                'ok' => true,
                'token' => $token,
                'user' => [
                    'id' => (int) $user['id'],
                    'name' => $user['full_name'],
                    'role' => $user['role'],
                ],
            ];
        } catch (PDOException $exception) {
            Logger::error('Login unavailable', ['detail' => $exception->getMessage()]);
            return [
                'ok' => false,
                'status' => 503,
                'message' => 'Login service is unavailable',
                'errors' => ['login' => $this->databaseErrorMessage($exception)],
            ];
        }
    }

    public function logout(array $claims): array
    {
        try {
            $this->tokens->revoke($claims);
            Logger::activity($claims['role'], (int) $claims['sub'], strtoupper($claims['role']) . '_LOGOUT', 'auth', (int) $claims['sub'], 'User logged out');
            return ['ok' => true];
        } catch (PDOException $exception) {
            Logger::error('Logout unavailable', ['detail' => $exception->getMessage()]);
            return [
                'ok' => false,
                'status' => 503,
                'message' => 'Logout service is unavailable',
                'errors' => ['logout' => 'Please try again later'],
            ];
        }
    }

    private function rateKey(string $identifier): string
    {
        return strtolower($identifier) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private function verificationData(array $user): array
    {
        $mobileVerified = (bool) $user['mobile_verified_at'];
        $emailVerified = (bool) $user['email_verified_at'];
        $channel = $mobileVerified ? OTP_TYPE_EMAIL : OTP_TYPE_MOBILE;
        $targetField = $channel === OTP_TYPE_EMAIL ? 'email' : 'mobile';
        $latest = $this->otps->latest([
            'user_id' => (int) $user['id'],
            $targetField => $user[$targetField],
            'type' => $channel,
            'purpose' => OTP_PURPOSE_REGISTRATION,
        ]);

        return [
            'verification_required' => true,
            'user_id' => (int) $user['id'],
            'mobile' => $user['mobile'],
            'email' => $user['email'],
            'masked_email' => $this->maskEmail($user['email']),
            'mobile_verified' => $mobileVerified,
            'email_verified' => $emailVerified,
            'next_step' => $channel === OTP_TYPE_EMAIL ? 'verify_email' : 'verify_mobile',
            'channel' => $channel,
            'purpose' => OTP_PURPOSE_REGISTRATION,
            'otp_resend_seconds' => $latest ? $this->otpService->secondsUntilResend($latest['created_at']) : 0,
        ];
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2 || $parts[0] === '') {
            return '';
        }

        return substr($parts[0], 0, 1) . '***@' . $parts[1];
    }

    private function databaseErrorMessage(PDOException $exception): string
    {
        if (!(bool) config('app.debug', false)) {
            return 'Please try again later';
        }

        $message = $exception->getMessage();
        if (strpos($message, 'Unknown database') !== false) {
            return 'Database name is not found. Check DB_NAME in api/.env and remove any backslashes from underscores.';
        }
        if (strpos($message, "Base table or view not found") !== false && strpos($message, 'auth_tokens') !== false) {
            return 'Database table auth_tokens is missing. Import database/schema.sql or database/migrations/002_phase2_core_foundation.sql.';
        }
        if (strpos($message, 'Access denied') !== false) {
            return 'Database login failed. Check DB_USER and DB_PASSWORD in api/.env.';
        }

        return $message;
    }
}
