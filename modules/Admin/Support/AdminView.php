<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : AdminView.php
 * Description : Admin View
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Support;

use RuntimeException;
use Throwable;

final class AdminView
{
    public static function render(string $view, array $data = []): string
    {
        $file = dirname(__DIR__) . '/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('ไม่พบไฟล์ View: ' . $file);
        }
        $level = ob_get_level();
        ob_start();
        try {
            (static function (string $__file, array $__data): void {
                extract($__data, EXTR_SKIP);
                require $__file;
            })($file, $data);
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
        return (string) ob_get_clean();
    }
}