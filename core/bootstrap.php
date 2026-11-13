<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : bootstrap.php
 *    Description : จุดเริ่มต้นระบบ: ลงทะเบียน Autoload (PSR-4), โหลด .env, ตั้งค่า Timezone, Error Handler และ Boot App
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

/* Autoload: App\Core\X -> core/X.php , Modules\Name\X -> modules/Name/X.php */
spl_autoload_register(static function (string $class): void {
    $map = [
        'App\\Core\\' => BASE_PATH . '/core/',
        'Modules\\'   => BASE_PATH . '/modules/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
            return;
        }
    }
});

/* Composer (TCPDF / PHPMailer จะใช้ใน Module ถัดไป) */
if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

require_once BASE_PATH . '/core/helpers.php';

App\Core\Env::load(BASE_PATH . '/.env');

$tz = (string) App\Core\Env::get('APP_TIMEZONE', 'Asia/Bangkok');
date_default_timezone_set(in_array($tz, timezone_identifiers_list(), true) ? $tz : 'Asia/Bangkok');
mb_internal_encoding('UTF-8');

App\Core\ErrorHandler::register(App\Core\Env::get('APP_DEBUG', false) === true);

return App\Core\App::boot();