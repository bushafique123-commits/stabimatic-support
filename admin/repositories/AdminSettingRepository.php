<?php

final class AdminSettingRepository
{
    public function editable(): array
    {
        $statement = admin_db()->query(
            "SELECT setting_key, setting_value, value_type
             FROM settings
             WHERE setting_key IN (
                'company_name',
                'company_logo',
                'support_phone',
                'support_email',
                'otp_expiry_minutes',
                'otp_resend_seconds',
                'maximum_otp_attempts',
                'maximum_image_size',
                'require_sale_before_installation'
             )
             ORDER BY setting_key"
        );

        $settings = [];
        foreach ($statement->fetchAll() as $row) {
            $settings[$row['setting_key']] = $this->cast($row['setting_value'], $row['value_type']);
        }

        return $settings;
    }

    public function upsert(string $key, $value, string $type): void
    {
        $statement = admin_db()->prepare(
            'INSERT INTO settings (setting_key, setting_value, value_type)
             VALUES (:setting_key, :setting_value, :value_type)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), value_type = VALUES(value_type)'
        );
        $statement->execute([
            ':setting_key' => $key,
            ':setting_value' => (string) $value,
            ':value_type' => $type,
        ]);
    }

    public function logActivity(int $adminId, string $description): void
    {
        $statement = admin_db()->prepare(
            'INSERT INTO activity_logs (user_type, user_id, action, module, description, ip_address, user_agent)
             VALUES ("admin", :user_id, "SETTINGS_UPDATED", "settings", :description, :ip_address, :user_agent)'
        );
        $statement->execute([
            ':user_id' => $adminId,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    private function cast(?string $value, string $type)
    {
        if ($value === null) {
            return null;
        }
        if ($type === 'integer') {
            return (int) $value;
        }
        if ($type === 'boolean') {
            return in_array($value, ['1', 'true', 'yes'], true);
        }
        if ($type === 'json') {
            return json_decode($value, true);
        }
        return $value;
    }
}
