<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Csrf.php
 *    Description : สร้างและตรวจสอบ CSRF Token (เปรียบเทียบแบบ hash_equals ป้องกัน Timing Attack)
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

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verify(mixed $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals(self::token(), $token);
    }

    /** สร้าง Token ใหม่ (เช่น หลัง Login/Logout) */
    public static function rotate(): string
    {
        unset($_SESSION['_csrf']);
        return self::token();
    }
}