<?php

final class AuthController
{
    private $registration;
    private $mobileOtp;
    private $emailOtp;
    private $auth;
    private $passwordReset;

    public function __construct(
        ?RegistrationService $registration = null,
        ?MobileOtpService $mobileOtp = null,
        ?EmailOtpService $emailOtp = null,
        ?AuthenticationService $auth = null,
        ?PasswordResetService $passwordReset = null
    )
    {
        $this->registration = $registration ?: new RegistrationService();
        $this->mobileOtp = $mobileOtp ?: new MobileOtpService();
        $this->emailOtp = $emailOtp ?: new EmailOtpService();
        $this->auth = $auth ?: new AuthenticationService();
        $this->passwordReset = $passwordReset ?: new PasswordResetService();
    }

    public function register(Request $request): void
    {
        $result = $this->registration->register($request->input());

        if (!($result['ok'] ?? false)) {
            $meta = [];
            if (isset($result['retry_after_seconds'])) {
                header('Retry-After: ' . (int) $result['retry_after_seconds']);
                $meta['retry_after_seconds'] = (int) $result['retry_after_seconds'];
            }
            Response::error($result['message'] ?? 'Validation failed', $result['errors'] ?? [], $result['status'] ?? 422, $meta);
        }

        unset($result['ok']);
        Response::success('Registration successful. Mobile OTP sent.', $result, 201);
    }

    public function sendMobileOtp(Request $request): void
    {
        $result = $this->mobileOtp->resend($request->input());

        if (!($result['ok'] ?? false)) {
            $status = $result['status'] ?? 422;
            $errors = $result['errors'] ?? [];
            $meta = [];
            if (isset($result['retry_after_seconds'])) {
                header('Retry-After: ' . (int) $result['retry_after_seconds']);
                $meta['retry_after_seconds'] = (int) $result['retry_after_seconds'];
            }
            Response::error($result['message'] ?? 'Validation failed', $errors, $status, $meta);
        }

        unset($result['ok']);
        Response::success(($result['already_verified'] ?? false) ? 'Mobile number is already verified' : 'Mobile OTP sent.', $result);
    }

    public function verifyMobileOtp(Request $request): void
    {
        $result = $this->mobileOtp->verify($request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Validation failed', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        unset($result['ok']);
        Response::success('Mobile number verified successfully.', $result);
    }

    public function sendEmailOtp(Request $request): void
    {
        $result = $this->emailOtp->resend($request->input());

        if (!($result['ok'] ?? false)) {
            $meta = [];
            if (isset($result['retry_after_seconds'])) {
                header('Retry-After: ' . (int) $result['retry_after_seconds']);
                $meta['retry_after_seconds'] = (int) $result['retry_after_seconds'];
            }
            Response::error($result['message'] ?? 'Validation failed', $result['errors'] ?? [], $result['status'] ?? 422, $meta);
        }

        unset($result['ok']);
        Response::success(($result['already_verified'] ?? false) ? 'Email is already verified' : 'Email OTP sent.', $result);
    }

    public function verifyEmailOtp(Request $request): void
    {
        $result = $this->emailOtp->verify($request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Validation failed', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        unset($result['ok']);
        Response::success('Email verified successfully. Account is ready for login.', $result);
    }

    public function login(Request $request): void
    {
        $result = $this->auth->login($request->input());

        if (!($result['ok'] ?? false)) {
            $errors = $result['errors'] ?? [];
            $meta = [];
            if (isset($result['data'])) {
                $meta['data'] = $result['data'];
            }
            Response::error($result['message'] ?? 'Login failed', $errors, $result['status'] ?? 401, $meta);
        }

        unset($result['ok']);
        Response::success('Login successful', $result);
    }

    public function logout(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->auth->logout($claims);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Logout failed', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        Response::success('Logout successful');
    }

    public function forgotPassword(Request $request): void
    {
        $result = $this->passwordReset->request($request->input());

        if (!($result['ok'] ?? false)) {
            $meta = [];
            if (isset($result['retry_after_seconds'])) {
                header('Retry-After: ' . (int) $result['retry_after_seconds']);
                $meta['retry_after_seconds'] = (int) $result['retry_after_seconds'];
            }
            Response::error($result['message'] ?? 'Password reset OTP could not be sent', $result['errors'] ?? [], $result['status'] ?? 422, $meta);
        }

        unset($result['ok']);
        Response::success('Password reset OTP sent', $result);
    }

    public function verifyResetOtp(Request $request): void
    {
        $result = $this->passwordReset->verifyOtp($request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Password reset OTP could not be verified', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        unset($result['ok']);
        Response::success('Password reset OTP verified', $result);
    }

    public function resetPassword(Request $request): void
    {
        $result = $this->passwordReset->reset($request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Password could not be reset', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Password reset successfully');
    }
}
