<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : ErrorHandler.php
 *    Description : จัดการ Error/Exception/Fatal ทั้งระบบ บันทึก Log ที่ storage/logs และแสดงหน้า 500 (โหมด Debug แสดงรายละเอียด)
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

final class ErrorHandler
{
    private static bool $debug = false;
    private static bool $handling = false;

    public static function register(bool $debug): void
    {
        self::$debug = $debug;
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function debug(): bool
    {
        return self::$debug;
    }

    /** แปลง Warning/Notice เป็น Exception (Deprecated แค่บันทึก Log) */
    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false; // ถูกปิดด้วย @
        }
        if ($level & (E_DEPRECATED | E_USER_DEPRECATED)) {
            self::write("[DEPRECATED] {$message} in {$file}:{$line}");
            return true;
        }
        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    /** จับ Fatal Error ที่ set_error_handler จับไม่ได้ */
    public static function handleShutdown(): void
    {
        $err = error_get_last();
        if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::handleException(new \ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']));
        }
    }

    public static function log(\Throwable $e): void
    {
        self::write(sprintf(
            "%s: %s in %s:%d\n%s",
            $e::class, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()
        ));
    }

    public static function write(string $text): void
    {
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir . '/app-' . date('Y-m-d') . '.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $text . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public static function handleException(\Throwable $e): void
    {
        if (self::$handling) {
            return; // กันวนซ้ำ
        }
        self::$handling = true;
        self::log($e);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $e->getMessage() . PHP_EOL . (self::$debug ? $e->getTraceAsString() . PHP_EOL : ''));
            exit(1);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
        }

        $data = ['title' => 'เกิดข้อผิดพลาด', 'exception' => self::$debug ? $e : null];
        $view = App::instance()?->view;
        try {
            if ($view === null) {
                throw new \RuntimeException('View not ready');
            }
            echo $view->render('errors/500', $data);
        } catch (\Throwable) {
            try {
                echo $view?->render('errors/500', $data, null) ?? throw new \RuntimeException('View not ready');
            } catch (\Throwable) {
                echo '<!doctype html><meta charset="utf-8"><h1 style="font-family:sans-serif">500 - ระบบขัดข้อง</h1>';
                if (self::$debug) {
                    echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
                }
            }
        }
    }
}