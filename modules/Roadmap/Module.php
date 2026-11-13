<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Module.php
 *    Description : Module Roadmap แสดงแผนงานและสถานะการพัฒนาแต่ละ Module
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

namespace Modules\Roadmap;

use App\Core\App;
use App\Core\ModuleInterface;
use Modules\Roadmap\Controllers\RoadmapController;

final class Module implements ModuleInterface
{
    public function register(App $app): void
    {
        $router = $app->router;
        $router->get('/roadmap', [RoadmapController::class, 'index'], 'roadmap.index');
        $router->get('/roadmap/{id}', [RoadmapController::class, 'detail'], 'roadmap.detail');
    }
}