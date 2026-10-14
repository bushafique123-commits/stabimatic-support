<?php

final class AdminDashboardRepository
{
    public function stats(): array
    {
        $this->ensureCommissionImportSchema();

        return [
            'dealers' => $this->count('SELECT COUNT(*) FROM users WHERE role = "dealer"'),
            'active_dealers' => $this->count('SELECT COUNT(*) FROM users WHERE role = "dealer" AND is_active = 1'),
            'pending_dealers' => $this->count('SELECT COUNT(*) FROM users WHERE role = "dealer" AND is_verified = 0'),
            'installers' => $this->count('SELECT COUNT(*) FROM users WHERE role = "installer"'),
            'active_installers' => $this->count('SELECT COUNT(*) FROM users WHERE role = "installer" AND is_active = 1'),
            'pending_installers' => $this->count('SELECT COUNT(*) FROM users WHERE role = "installer" AND is_verified = 0'),
            'sales' => $this->count('SELECT COUNT(*) FROM sales WHERE deleted_at IS NULL'),
            'sales_today' => $this->count('SELECT COUNT(*) FROM sales WHERE deleted_at IS NULL AND sale_date = CURDATE()'),
            'installations' => $this->count('SELECT COUNT(*) FROM installations WHERE deleted_at IS NULL'),
            'installations_today' => $this->count('SELECT COUNT(*) FROM installations WHERE deleted_at IS NULL AND DATE(installed_at) = CURDATE()'),
            'matched_installations' => $this->countMatchedInstallations(true),
            'unmatched_installations' => $this->countMatchedInstallations(false),
        ];
    }

    public function recentActivity(int $limit = 8): array
    {
        $limit = max(1, min($limit, 20));
        $statement = admin_db()->query(
            "SELECT user_type, user_id, action, module, record_id, description, ip_address, created_at
             FROM activity_logs
             ORDER BY created_at DESC, id DESC
             LIMIT {$limit}"
        );

        return $statement->fetchAll();
    }

    public function settings(): array
    {
        $keys = [
            'company_name',
            'support_phone',
            'support_email',
            'otp_expiry_minutes',
            'otp_resend_seconds',
            'maximum_image_size',
            'require_sale_before_installation',
        ];

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $statement = admin_db()->prepare(
            "SELECT setting_key, setting_value, value_type
             FROM settings
             WHERE setting_key IN ({$placeholders})"
        );
        $statement->execute($keys);

        $settings = [];
        foreach ($statement->fetchAll() as $row) {
            $settings[$row['setting_key']] = $this->cast($row['setting_value'], $row['value_type']);
        }

        return $settings;
    }

    private function count(string $sql): int
    {
        return (int) admin_db()->query($sql)->fetchColumn();
    }

    private function countMatchedInstallations(bool $matched): int
    {
        $operator = $matched ? 'IS NOT NULL' : 'IS NULL';

        return $this->count(
            "SELECT COUNT(*)
             FROM installations i
             LEFT JOIN sales s ON s.device_serial = i.serial_number AND s.device_model = i.device_model AND s.deleted_at IS NULL
             WHERE i.deleted_at IS NULL
               AND s.id {$operator}"
        );
    }

    private function ensureCommissionImportSchema(): void
    {
        if (class_exists('AdminCommissionImportRepository')) {
            (new AdminCommissionImportRepository())->ensureSchema();
        }
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
