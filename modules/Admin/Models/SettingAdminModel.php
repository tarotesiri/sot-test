<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : SettingAdminModel.php
 * Description : Setting Model
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Models;

final class SettingAdminModel
{
    public function __construct(private readonly object $db) {}

    private function t(string $name): string
    {
        return (string) $this->db->table($name);
    }

    public function get(string $key, string $default = ''): string
    {
        try {
            $val = $this->db->value("SELECT setting_value FROM {$this->t('settings')} WHERE setting_key = ? LIMIT 1", [$key]);
            return $val !== null ? (string) $val : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    public function set(string $key, string $value): void
    {
        try {
            $exists = (int) $this->db->value("SELECT COUNT(*) FROM {$this->t('settings')} WHERE setting_key = ?", [$key]) > 0;
            if ($exists) {
                $this->db->update('settings', ['setting_value' => $value], '`setting_key` = ?', [$key]);
            } else {
                $this->db->insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
            }
        } catch (\Throwable) {}
    }
}