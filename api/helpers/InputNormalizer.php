<?php

final class InputNormalizer
{
    public static function string($value): string
    {
        return trim((string) $value);
    }

    public static function email($value): string
    {
        return strtolower(self::string($value));
    }

    public static function mobile($value): string
    {
        $mobile = preg_replace('/[\s-]+/', '', self::string($value));
        if (strpos($mobile, '+923') === 0) {
            return '0' . substr($mobile, 3);
        }
        if (strpos($mobile, '923') === 0) {
            return '0' . substr($mobile, 2);
        }
        return $mobile;
    }

    public static function cnic($value): string
    {
        return preg_replace('/[^0-9]/', '', self::string($value));
    }

    public static function registration(array $input): array
    {
        return [
            'role' => self::string($input['role'] ?? ''),
            'full_name' => self::string($input['full_name'] ?? ''),
            'mobile' => self::mobile($input['mobile'] ?? ''),
            'email' => self::email($input['email'] ?? ''),
            'cnic' => self::cnic($input['cnic'] ?? ''),
            'bank_name' => self::string($input['bank_name'] ?? ''),
            'bank_account_title' => self::string($input['bank_account_title'] ?? ''),
            'bank_account_number' => self::string($input['bank_account_number'] ?? ''),
            'iban' => strtoupper(str_replace(' ', '', self::string($input['iban'] ?? ''))),
            'password' => (string) ($input['password'] ?? ''),
            'confirm_password' => (string) ($input['confirm_password'] ?? ''),
            'terms_accepted' => $input['terms_accepted'] ?? false,
        ];
    }

    public static function otpPurpose($value): string
    {
        return self::string($value ?: OTP_PURPOSE_REGISTRATION);
    }

    public static function identifier($value): string
    {
        $value = self::string($value);
        return strpos($value, '@') !== false ? self::email($value) : self::mobile($value);
    }

    public static function sale(array $input): array
    {
        return [
            'invoice_number' => strtoupper(self::string($input['invoice_number'] ?? '')),
            'sale_date' => self::string($input['sale_date'] ?? ''),
            'customer_name' => self::string($input['customer_name'] ?? ''),
            'customer_mobile' => self::mobile($input['customer_mobile'] ?? ''),
            'device_model' => strtoupper(self::string($input['device_model'] ?? $input['model_number'] ?? '')),
            'device_serial' => strtoupper(self::string($input['device_serial'] ?? '')),
        ];
    }

    public static function installation(array $input): array
    {
        return [
            'invoice_number' => strtoupper(self::string($input['invoice_number'] ?? '')),
            'device_model' => strtoupper(self::string($input['device_model'] ?? $input['model_number'] ?? '')),
            'customer_name' => self::string($input['customer_name'] ?? ''),
            'customer_mobile' => self::mobile($input['customer_mobile'] ?? ''),
            'customer_email' => self::email($input['customer_email'] ?? ''),
            'serial_number' => strtoupper(self::string($input['serial_number'] ?? '')),
            'installed_at' => self::dateTime($input['installed_at'] ?? ''),
        ];
    }

    public static function dateTime($value): string
    {
        $value = self::string($value);
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : $value;
    }
}
