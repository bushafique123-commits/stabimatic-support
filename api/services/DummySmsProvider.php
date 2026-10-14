<?php

final class DummySmsProvider implements SmsProviderInterface
{
    public function sendOtp(string $mobile, string $otp): bool
    {
        Logger::info('Dummy SMS OTP generated', [
            'mobile' => $mobile,
            'otp' => config('otp.expose_in_response') ? $otp : '[hidden]',
        ]);

        return true;
    }

    public function sendNotification(string $mobile, string $title, string $message): bool
    {
        Logger::info('Dummy SMS notification requested', [
            'mobile' => $mobile,
            'title' => $title,
            'message_length' => strlen($message),
        ]);

        return true;
    }
}
