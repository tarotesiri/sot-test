<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Module.php
 *    Description : Module คาตาล็อกหลักสูตร ลงทะเบียน Route และ Widget หน้าแรก
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

namespace Modules\Catalog;

use App\Core\App;
use App\Core\ModuleInterface;
use Modules\Catalog\Controllers\CatalogController;
use Modules\Catalog\Models\CourseModel;

final class Module implements ModuleInterface
{
    public function register(App $app): void
    {
        // 1. ลงทะเบียน Route
        $router = $app->router;
        $router->get('/courses', [CatalogController::class, 'index'], 'courses.index');
        $router->get('/courses/{slug}', [CatalogController::class, 'show'], 'courses.show');

        // 2. แทนที่ Widget "Online" ในหน้าแรกด้วยข้อมูลจริงจากฐานข้อมูล
        $app->widgets->register('home', 'online', static function () use ($app): string {
            $courseModel = new CourseModel($app->db());
            $courses = $courseModel->getPopularLiveCourses(3);
            return $app->view->render('Catalog::widgets/online', ['courses' => $courses], null);
        }, 20);
    }
}