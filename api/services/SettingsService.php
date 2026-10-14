<?php

final class SettingsService
{
    private $settings;

    public function __construct(?SettingRepository $settings = null)
    {
        $this->settings = $settings ?: new SettingRepository();
    }

    public function get(string $key, $default = null)
    {
        $value = $this->settings->find($key);
        return $value === null ? $default : $value;
    }

    public function all(): array
    {
        $rows = $this->settings->all();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $this->get($row['setting_key']);
        }
        return $result;
    }
}
