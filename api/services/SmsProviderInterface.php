<?php

interface SmsProviderInterface
{
    public function sendOtp(string $mobile, string $otp): bool;

    public function sendNotification(string $mobile, string $title, string $message): bool;
}
