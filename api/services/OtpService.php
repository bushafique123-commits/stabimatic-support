<?php

final class OtpService
{
    private $smsProvider;

    public function __construct(SmsProviderInterface $smsProvider)
    {
        $this->smsProvider = $smsProvider;
    }

    public function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    public function hashOtp(string $otp): string
    {
        return password_hash($otp, PASSWORD_DEFAULT);
    }

    public function verifyOtp(string $otp, string $hash): bool
    {
        return password_verify($otp, $hash);
    }

    public function sendMobileOtp(string $mobile, string $otp): bool
    {
        return $this->smsProvider->sendOtp($mobile, $otp);
    }

    public function expiryDateTime(): string
    {
        return date('Y-m-d H:i:s', time() + ((int) config('otp.expiry_minutes') * 60));
    }

    public function secondsUntilResend(string $createdAt): int
    {
        $availableAt = strtotime($createdAt) + (int) config('otp.resend_seconds');
        return max(0, $availableAt - time());
    }

    public function isExpired(string $expiresAt): bool
    {
        return strtotime($expiresAt) <= time();
    }
}
