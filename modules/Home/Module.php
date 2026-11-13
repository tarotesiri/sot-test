<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Module.php
 *    Description : Module Home: ลงทะเบียน Route หน้าแรก (/) และหน้าตรวจสอบสถานะระบบ (/system/status)
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

namespace Modules\Home;

use App\Core\App;
use App\Core\ModuleInterface;
use Modules\Home\Controllers\HomeController;

final class Module implements ModuleInterface
{
    public function register(App $app): void
    {
        $app->router->get('/', [HomeController::class, 'index'], 'home');
        $app->router->get('/system/status', [HomeController::class, 'status'], 'system.status');
    }
}