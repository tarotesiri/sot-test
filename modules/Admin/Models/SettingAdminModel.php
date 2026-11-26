<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : SettingAdminModel.php
 * Description : Setting Model
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.24.0
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

    /**
     * @return array<string, bool>
     */
    private function settingColumns(): array
    {
        static $cols = null;
        if ($cols !== null) {
            return $cols;
        }
        $cols = [];
        try {
            foreach ($this->db->fetchAll("SHOW COLUMNS FROM {$this->t('settings')}") as $row) {
                if (!empty($row['Field'])) {
                    $cols[(string)$row['Field']] = true;
                }
            }
        } catch (\Throwable) {
            $cols = [];
        }
        return $cols;
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

    public function set(
        string $key,
        string $value,
        string $valueType = 'string',
        string $group = 'general',
        ?string $label = null
    ): void {
        try {
            $cols = $this->settingColumns();
            $exists = (int) $this->db->value("SELECT COUNT(*) FROM {$this->t('settings')} WHERE setting_key = ?", [$key]) > 0;

            if ($exists) {
                $updateData = ['setting_value' => $value];
                if (isset($cols['value_type'])) {
                    $updateData['value_type'] = $valueType;
                }
                if (isset($cols['setting_group'])) {
                    $updateData['setting_group'] = $group;
                }
                if ($label !== null && isset($cols['label'])) {
                    $updateData['label'] = $label;
                }
                if (isset($cols['updated_at'])) {
                    $updateData['updated_at'] = date('Y-m-d H:i:s');
                }
                $this->db->update('settings', $updateData, '`setting_key` = ?', [$key]);
            } else {
                $insertData = [
                    'setting_key'   => $key,
                    'setting_value' => $value,
                ];
                if (isset($cols['value_type'])) {
                    $insertData['value_type'] = $valueType;
                }
                if (isset($cols['setting_group'])) {
                    $insertData['setting_group'] = $group;
                }
                if (isset($cols['label'])) {
                    $insertData['label'] = $label;
                }
                if (isset($cols['updated_at'])) {
                    $insertData['updated_at'] = date('Y-m-d H:i:s');
                }
                $this->db->insert('settings', $insertData);
            }
        } catch (\Throwable) {}
    }
}