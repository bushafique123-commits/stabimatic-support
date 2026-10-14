<?php

final class ProfileController
{
    private $profiles;
    private $contactChanges;

    public function __construct(?ProfileService $profiles = null, ?ContactChangeService $contactChanges = null)
    {
        $this->profiles = $profiles ?: new ProfileService();
        $this->contactChanges = $contactChanges ?: new ContactChangeService();
    }

    public function show(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->profiles->current($claims);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Profile unavailable', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        Response::success('Profile loaded', $result['profile']);
    }

    public function update(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->profiles->update($claims, $request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Profile could not be saved', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Profile updated successfully', $result['profile']);
    }

    public function changePassword(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->profiles->changePassword($claims, $request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Password could not be changed', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Password changed successfully');
    }

    public function sendMobileChangeOtp(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->contactChanges->sendMobileOtp($claims, $request->input());

        if (!($result['ok'] ?? false)) {
            $meta = [];
            if (isset($result['retry_after_seconds'])) {
                header('Retry-After: ' . (int) $result['retry_after_seconds']);
                $meta['retry_after_seconds'] = (int) $result['retry_after_seconds'];
            }
            Response::error($result['message'] ?? 'Mobile OTP could not be sent', $result['errors'] ?? [], $result['status'] ?? 422, $meta);
        }

        unset($result['ok']);
        Response::success('Mobile change OTP sent', $result);
    }

    public function verifyMobileChangeOtp(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->contactChanges->verifyMobileOtp($claims, $request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Mobile number could not be changed', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Mobile number changed successfully', $result['profile']);
    }

    public function sendEmailChangeOtp(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->contactChanges->sendEmailOtp($claims, $request->input());

        if (!($result['ok'] ?? false)) {
            $meta = [];
            if (isset($result['retry_after_seconds'])) {
                header('Retry-After: ' . (int) $result['retry_after_seconds']);
                $meta['retry_after_seconds'] = (int) $result['retry_after_seconds'];
            }
            Response::error($result['message'] ?? 'Email OTP could not be sent', $result['errors'] ?? [], $result['status'] ?? 422, $meta);
        }

        unset($result['ok']);
        Response::success('Email change OTP sent', $result);
    }

    public function verifyEmailChangeOtp(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->contactChanges->verifyEmailOtp($claims, $request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Email address could not be changed', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Email address changed successfully', $result['profile']);
    }
}
