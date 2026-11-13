<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : index.php
 *    Description : Front Controller จุดเข้าใช้งานเว็บหลัก ตรวจสอบการติดตั้ง (installed.lock) และเรียก bootstrap
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/

declare(strict_types=1);

// รองรับ PHP Built-in Server: php -S localhost:8000 -t public public/index.php
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file)) {
        return false;
    }
}

$root = dirname(__DIR__);
if (!is_file($root . '/storage/installed.lock')) {
    http_response_code(503);
    exit('<!doctype html><meta charset="utf-8"><p style="font-family:sans-serif;padding:2rem;text-align:center">ระบบยังไม่ได้ติดตั้ง — กรุณาเปิดไฟล์ <code>setup.php</code> เพื่อติดตั้งระบบก่อนใช้งาน</p>');
}


require_once $root . '/core/bootstrap.php';

\App\Core\App::instance()->run();


//TaRote-Update-SOT-Module4

print_r($_SESSION);
echo '<hr/>';
print_r($_COOKIE);

