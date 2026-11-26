<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : AdminHelper.php
 * Description : Admin Helper
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Support;

use App\Core\Csrf;

final class AdminHelper
{
    private const FLASH_KEY = '_admin_flash';
    private const FORM_KEY  = '_admin_form';

    public static function decodeJsonSafe(mixed $val): array
    {
        if (is_array($val)) return $val;
        if (is_string($val) && $val !== '') {
            $dec = json_decode($val, true);
            if (is_array($dec)) return $dec;
        }
        return [];
    }

    public static function e(mixed $value): string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function str(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    public static function csrf(): string
    {
        return '<input type="hidden" name="_token" value="' . self::e(Csrf::token()) . '">';
    }

    public static function baseUrl(): string
    {
        $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
        $host  = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $dir   = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
        return ($https ? 'https' : 'http') . '://' . $host . $dir;
    }

    public static function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        if (function_exists('url')) {
            return (string) \url($path);
        }
        return self::baseUrl() . '/' . $path;
    }

    public static function asset(string $file): string
    {
        if (function_exists('asset')) {
            return (string) \asset($file);
        }
        return self::url('assets/' . $file);
    }

    public static function redirect(string $path, string $suffix = ''): never
    {
        header('Location: ' . self::url($path) . $suffix, true, 302);
        exit;
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION[self::FLASH_KEY][] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlash(): array
    {
        $items = $_SESSION[self::FLASH_KEY] ?? [];
        unset($_SESSION[self::FLASH_KEY]);
        return is_array($items) ? $items : [];
    }

    public static function keepForm(array $input, array $errors): void
    {
        unset($input['_token']);
        $_SESSION[self::FORM_KEY] = ['old' => $input, 'errors' => $errors];
    }

    public static function pullForm(): array
    {
        $form = $_SESSION[self::FORM_KEY] ?? [];
        unset($_SESSION[self::FORM_KEY]);
        return [
            'old'    => is_array($form['old'] ?? null) ? $form['old'] : [],
            'errors' => is_array($form['errors'] ?? null) ? $form['errors'] : [],
        ];
    }

    public static function err(array $errors, string $key): string
    {
        return isset($errors[$key]) ? '<div class="adm-error">' . self::e($errors[$key]) . '</div>' : '';
    }

    public static function sel(mixed $a, mixed $b): string
    {
        return self::str($a) === self::str($b) ? ' selected' : '';
    }

    public static function in(string $key, int $max = 0): string
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? '';
        $v = is_scalar($v) ? trim((string) $v) : '';
        return $max > 0 ? mb_substr($v, 0, $max) : $v;
    }

    public static function query(string $key): string
    {
        $v = $_GET[$key] ?? '';
        return is_scalar($v) ? trim((string) $v) : '';
    }

    public static function inInt(string $key, int $default = 0): int
    {
        $v = self::in($key);
        return preg_match('/^-?\d{1,9}$/', $v) === 1 ? (int) $v : $default;
    }

    public static function inFloat(string $key): float
    {
        $v = str_replace([',', ' '], '', self::in($key));
        return $v !== '' && is_numeric($v) ? (float) $v : 0.0;
    }

    public static function inArray(string $key): array
    {
        $v = $_POST[$key] ?? [];
        return is_array($v) ? $v : [];
    }

    public static function post(): array
    {
        $p = $_POST;
        unset($p['_token']);
        return $p;
    }

    public static function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public static function userName(): string
    {
        $name = trim(self::str($_SESSION['user_name'] ?? ''));
        return $name !== '' ? $name : 'ผู้ดูแลระบบ';
    }

    public static function slugify(string $text, int $max = 200): string
    {
        $slug = strtolower($text);
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim((string) preg_replace('/-+/', '-', $slug), '-');
        return trim(substr($slug, 0, $max), '-');
    }

    public static function money(mixed $value): string
    {
        return number_format((float) self::str($value), 2);
    }

    public static function uploadPath(string $rel = ''): string
    {
        $rel = trim(str_replace('\\', '/', $rel), '/');
        if (function_exists('upload_path')) {
            return rtrim(str_replace('\\', '/', (string) \upload_path($rel)), '/');
        }
        return dirname(__DIR__, 3) . '/public/uploads' . ($rel !== '' ? '/' . $rel : '');
    }

    public static function uploadUrl(mixed $path): string
    {
        $path = trim(str_replace('\\', '/', self::str($path)));
        if ($path === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }
        $p = ltrim($path, '/');
        if (str_starts_with($p, 'uploads/') || str_starts_with($p, 'assets/')) {
            return self::url($p);
        }
        if (function_exists('upload_url')) {
            return (string) \upload_url($p);
        }
        return self::url('uploads/' . $p);
    }

    public static function courseStatuses(): array
    {
        return [
            'open'      => 'เปิด',
            'closed'    => 'ปิด',
            'full'      => 'เต็ม',
            'cancelled' => 'ยกเลิก',
            'draft'     => 'ร่าง',
        ];
    }

    public static function courseTypes(): array
    {
        return ['live_online' => 'Live Online', 'clip_vdo' => 'คลิปวิดีโอ'];
    }

    public static function badge(mixed $status): string
    {
        $status = self::str($status);
        $labels = self::courseStatuses() + ['trashed' => 'ถังขยะ', 'active' => 'ใช้งาน', 'archived' => 'เก็บถาวร'];
        return '<span class="adm-badge is-' . self::e($status) . '">' . self::e($labels[$status] ?? $status) . '</span>';
    }
}