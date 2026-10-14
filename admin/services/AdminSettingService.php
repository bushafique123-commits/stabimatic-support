<?php

final class AdminSettingService
{
    private $settings;

    public function __construct(?AdminSettingRepository $settings = null)
    {
        $this->settings = $settings ?: new AdminSettingRepository();
    }

    public function form(): array
    {
        try {
            $settings = array_merge($this->defaults(), $this->settings->editable());
            $settings['maximum_image_size_mb'] = max(1, (int) round(((int) $settings['maximum_image_size']) / 1048576));

            return ['ok' => true, 'settings' => $settings, 'errors' => []];
        } catch (PDOException $exception) {
            error_log('Admin settings load failed: ' . $exception->getMessage());
            $settings = $this->defaults();
            $settings['maximum_image_size_mb'] = 5;

            return [
                'ok' => false,
                'settings' => $settings,
                'errors' => [],
                'message' => 'Settings are unavailable.',
            ];
        }
    }

    public function update(array $admin, array $input): array
    {
        $settings = $this->normalize($input);
        $errors = $this->validate($settings);

        if ($errors) {
            return ['ok' => false, 'settings' => $settings, 'errors' => $errors, 'message' => 'Validation failed.'];
        }

        try {
            $pdo = admin_db();
            $pdo->beginTransaction();
            foreach ($this->definition() as $key => $type) {
                $this->settings->upsert($key, $settings[$key], $type);
            }
            $this->settings->logActivity((int) $admin['id'], 'Super Admin updated system settings');
            $pdo->commit();

            $settings['maximum_image_size_mb'] = max(1, (int) round(((int) $settings['maximum_image_size']) / 1048576));

            return ['ok' => true, 'settings' => $settings, 'errors' => [], 'message' => 'Settings updated.'];
        } catch (PDOException $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Admin settings update failed: ' . $exception->getMessage());
            return ['ok' => false, 'settings' => $settings, 'errors' => [], 'message' => 'Settings could not be saved.'];
        }
    }

    private function normalize(array $input): array
    {
        $imageSizeMb = max(0, (int) ($input['maximum_image_size_mb'] ?? 0));

        return [
            'company_name' => trim((string) ($input['company_name'] ?? '')),
            'company_logo' => trim((string) ($input['company_logo'] ?? '')),
            'support_phone' => trim((string) ($input['support_phone'] ?? '')),
            'support_email' => strtolower(trim((string) ($input['support_email'] ?? ''))),
            'otp_expiry_minutes' => (int) ($input['otp_expiry_minutes'] ?? 0),
            'otp_resend_seconds' => (int) ($input['otp_resend_seconds'] ?? 0),
            'maximum_otp_attempts' => (int) ($input['maximum_otp_attempts'] ?? 0),
            'maximum_image_size' => $imageSizeMb * 1048576,
            'maximum_image_size_mb' => $imageSizeMb,
            'require_sale_before_installation' => isset($input['require_sale_before_installation']) ? 1 : 0,
        ];
    }

    private function validate(array $settings): array
    {
        $errors = [];

        if ($settings['company_name'] === '' || strlen($settings['company_name']) > 150) {
            $errors['company_name'] = 'Company name is required and must be 150 characters or fewer.';
        }
        if (strlen($settings['company_logo']) > 255) {
            $errors['company_logo'] = 'Company logo path is too long.';
        }
        if (strlen($settings['support_phone']) > 30) {
            $errors['support_phone'] = 'Support phone is too long.';
        }
        if ($settings['support_email'] !== '' && !filter_var($settings['support_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['support_email'] = 'Enter a valid support email address.';
        }
        if ($settings['otp_expiry_minutes'] < 1 || $settings['otp_expiry_minutes'] > 60) {
            $errors['otp_expiry_minutes'] = 'OTP expiry must be between 1 and 60 minutes.';
        }
        if ($settings['otp_resend_seconds'] < 15 || $settings['otp_resend_seconds'] > 600) {
            $errors['otp_resend_seconds'] = 'OTP resend delay must be between 15 and 600 seconds.';
        }
        if ($settings['maximum_otp_attempts'] < 1 || $settings['maximum_otp_attempts'] > 10) {
            $errors['maximum_otp_attempts'] = 'Maximum OTP attempts must be between 1 and 10.';
        }
        if ($settings['maximum_image_size_mb'] < 1 || $settings['maximum_image_size_mb'] > 20) {
            $errors['maximum_image_size_mb'] = 'Maximum image size must be between 1 and 20 MB.';
        }

        return $errors;
    }

    private function defaults(): array
    {
        return [
            'company_name' => 'STABIMATIC',
            'company_logo' => '',
            'support_phone' => '',
            'support_email' => '',
            'otp_expiry_minutes' => 10,
            'otp_resend_seconds' => 60,
            'maximum_otp_attempts' => 5,
            'maximum_image_size' => 5242880,
            'maximum_image_size_mb' => 5,
            'require_sale_before_installation' => 1,
        ];
    }

    private function definition(): array
    {
        return [
            'company_name' => 'string',
            'company_logo' => 'string',
            'support_phone' => 'string',
            'support_email' => 'string',
            'otp_expiry_minutes' => 'integer',
            'otp_resend_seconds' => 'integer',
            'maximum_otp_attempts' => 'integer',
            'maximum_image_size' => 'integer',
            'require_sale_before_installation' => 'boolean',
        ];
    }
}
