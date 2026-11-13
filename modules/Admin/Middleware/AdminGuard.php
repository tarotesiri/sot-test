<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : AdminGuard.php
 * Description : Admin Guard
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Middleware;

use Modules\Admin\Support\AdminHelper;

final class AdminGuard
{
    public static function check(): void
    {
        if (!empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
            return;
        }
        AdminHelper::redirect('auth/login');
    }
}