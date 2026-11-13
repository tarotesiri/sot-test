<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Env.php
 *    Description : คลาสอ่านค่าจากไฟล์ .env (ค่าในเครื่องหมายคำพูดเป็น string เสมอ, true/false/null แปลงชนิดอัตโนมัติ)
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

final class Env
{
    /** @var array<string, string> */
    private static array $vars = [];
    /** @var array<string, bool> */
    private static array $quoted = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $len = strlen($value);
            if ($len >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[$len - 1] === $value[0]) {
                $value = substr($value, 1, -1);
                self::$quoted[$key] = true;
            }
            self::$vars[$key] = $value;
        }
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$vars);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, self::$vars)) {
            return $default;
        }
        $value = self::$vars[$key];
        if (isset(self::$quoted[$key])) {
            return $value;
        }
        return match (strtolower($value)) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => $value,
        };
    }
}