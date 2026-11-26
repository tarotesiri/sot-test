<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Session.php
 *    Description : จัดการ Session แบบปลอดภัย (HttpOnly/SameSite/Strict Mode), Flash Message, Old Input และ Validation Errors
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

final class Session
{
    private static array $old = [];
    private static array $errors = [];

    public static function start(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name((string) Env::get('SESSION_NAME', 'SOTSESSID'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => url_base() . '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // old input / errors อยู่ได้ 1 request
        self::$old = $_SESSION['_old'] ?? [];
        self::$errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_old'], $_SESSION['_errors']);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $v = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $v;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** เรียกหลัง Login เพื่อป้องกัน Session Fixation */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** ใช้ตอน Logout */
    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'],
            'httponly' => $p['httponly'], 'samesite' => $p['samesite'] ?: 'Lax',
        ]);
        session_destroy();
    }

    /* ---------- Flash ---------- */

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlashes(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }

    /* ---------- Old input / Errors ---------- */

    public static function flashInput(array $input): void
    {
        unset($input['_token'], $input['password'], $input['password_confirmation'], $input['current_password']);
        $_SESSION['_old'] = $input;
    }

    public static function flashErrors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return self::$old[$key] ?? $default;
    }

    public static function errors(): array
    {
        return self::$errors;
    }
}