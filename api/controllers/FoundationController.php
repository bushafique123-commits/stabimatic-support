<?php

final class FoundationController
{
    public function settings(Request $request): void
    {
        try {
            $settings = new SettingsService();
            $data = [
                'company_name' => $settings->get('company_name', 'STABIMATIC'),
                'support_phone' => $settings->get('support_phone', ''),
                'support_email' => $settings->get('support_email', ''),
                'otp_expiry_minutes' => $settings->get('otp_expiry_minutes', config('otp.expiry_minutes')),
                'otp_resend_seconds' => $settings->get('otp_resend_seconds', config('otp.resend_seconds')),
                'maximum_otp_attempts' => $settings->get('maximum_otp_attempts', config('otp.max_attempts')),
                'maximum_image_size' => $settings->get('maximum_image_size', config('upload.max_bytes')),
                'require_sale_before_installation' => $settings->get('require_sale_before_installation', true),
                'source' => 'database',
            ];
        } catch (Throwable $exception) {
            Logger::error('Public settings fallback used', ['detail' => $exception->getMessage()]);
            $data = [
                'company_name' => 'STABIMATIC',
                'support_phone' => '',
                'support_email' => '',
                'otp_expiry_minutes' => config('otp.expiry_minutes'),
                'otp_resend_seconds' => config('otp.resend_seconds'),
                'maximum_otp_attempts' => config('otp.max_attempts'),
                'maximum_image_size' => config('upload.max_bytes'),
                'require_sale_before_installation' => true,
                'source' => 'environment',
            ];
        }

        Response::success('Settings loaded', [
            'company_name' => $data['company_name'],
            'support_phone' => $data['support_phone'],
            'support_email' => $data['support_email'],
            'otp_expiry_minutes' => $data['otp_expiry_minutes'],
            'otp_resend_seconds' => $data['otp_resend_seconds'],
            'maximum_otp_attempts' => $data['maximum_otp_attempts'],
            'maximum_image_size' => $data['maximum_image_size'],
            'require_sale_before_installation' => $data['require_sale_before_installation'],
            'source' => $data['source'],
        ]);
    }
}
