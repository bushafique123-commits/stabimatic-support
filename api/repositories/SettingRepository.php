<?php

final class SettingRepository extends BaseRepository
{
    public function all(): array
    {
        return $this->fetchAll('SELECT setting_key, setting_value, value_type FROM settings ORDER BY setting_key');
    }

    public function find(string $key)
    {
        $row = $this->fetchOne(
            'SELECT setting_value, value_type FROM settings WHERE setting_key = :setting_key LIMIT 1',
            [':setting_key' => $key]
        );

        return $row ? $this->cast($row['setting_value'], $row['value_type']) : null;
    }

    public function upsert(string $key, $value, string $type = 'string'): void
    {
        $this->execute(
            'INSERT INTO settings (setting_key, setting_value, value_type)
             VALUES (:setting_key, :setting_value, :value_type)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), value_type = VALUES(value_type)',
            [
                ':setting_key' => $key,
                ':setting_value' => is_array($value) ? json_encode($value) : (string) $value,
                ':value_type' => $type,
            ]
        );
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
