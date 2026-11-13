<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Settings.php
 *    Description : อ่าน/เขียนค่าตั้งค่าระบบจากตาราง {prefix}settings แปลงชนิดข้อมูลอัตโนมัติ (string/int/float/bool/json) และ Cache ภายใน Request
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

namespace App\Core;

/**
 * ตัวอย่าง:
 *   setting('vat_rate');                         // 7.0
 *   app()->settings()->set('vat_rate', 7.0, 'float', 'finance');
 *   app()->settings()->group('contact');         // ทุกค่าในกลุ่ม
 */
final class Settings
{
    private ?array $cache = null;
    private ?array $meta = null;

    public function __construct(private readonly Database $db) {}

    public function all(): array
    {
        if ($this->cache === null) {
            $this->cache = [];
            $this->meta = [];
            foreach ($this->db->fetchAll('SELECT setting_key, setting_value, value_type, setting_group, label FROM {prefix}settings ORDER BY setting_group, setting_key') as $r) {
                $this->cache[$r['setting_key']] = self::cast($r['setting_value'], $r['value_type']);
                $this->meta[$r['setting_key']] = $r;
            }
        }
        return $this->cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /** ค่าทั้งหมดในกลุ่ม [key => value] */
    public function group(string $group): array
    {
        $this->all();
        $out = [];
        foreach ($this->meta as $key => $row) {
            if ($row['setting_group'] === $group) {
                $out[$key] = $this->cache[$key];
            }
        }
        return $out;
    }

    /** ข้อมูลดิบพร้อม label (ใช้ทำหน้าตั้งค่าหลังบ้าน) */
    public function rows(?string $group = null): array
    {
        $this->all();
        return array_values(array_filter($this->meta, static fn (array $r): bool => $group === null || $r['setting_group'] === $group));
    }

    public function set(string $key, mixed $value, ?string $type = null, ?string $group = null, ?string $label = null): void
    {
        $type ??= match (true) {
            is_bool($value)  => 'bool',
            is_int($value)   => 'int',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default          => 'string',
        };
        $stored = match ($type) {
            'bool'  => $value ? '1' : '0',
            'json'  => json_encode($value, JSON_UNESCAPED_UNICODE),
            default => $value === null ? null : (string) $value,
        };
        $this->db->query(
            'INSERT INTO {prefix}settings (setting_key, setting_value, value_type, setting_group, label)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), value_type = VALUES(value_type),
                 setting_group = COALESCE(?, setting_group), label = COALESCE(?, label)',
            [$key, $stored, $type, $group ?? 'general', $label, $group, $label]
        );
        $this->cache = null;
        $this->meta = null;
    }

    public function forget(string $key): void
    {
        $this->db->delete('settings', 'setting_key = ?', [$key]);
        $this->cache = null;
        $this->meta = null;
    }

    private static function cast(?string $v, string $type): mixed
    {
        if ($v === null) {
            return null;
        }
        return match ($type) {
            'int'   => (int) $v,
            'float' => (float) $v,
            'bool'  => in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true),
            'json'  => json_decode($v, true),
            default => $v,
        };
    }
}