<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : Module.php
 * Description : Module Admin
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin;

use App\Core\App;
use App\Core\ModuleInterface;
use Modules\Admin\Controllers\AdminController;
use Modules\Admin\Controllers\CategoryController;
use Modules\Admin\Controllers\CourseController;
use Modules\Admin\Controllers\SettingController;
use Modules\Admin\Controllers\TrashController;

final class Module implements ModuleInterface
{
    public function register(App $app): void
    {
        $r  = $app->router;
        $c  = CourseController::class;
        $g  = CategoryController::class;
        $s  = SettingController::class;
        $cp = '/admin/courses/{id}';
        $gp = '/admin/categories/{id}';

        $r->get('/admin', [AdminController::class, 'index'], 'admin.dashboard');
        $r->get('/admin/courses', [$c, 'index'], 'admin.courses.index');
        $r->get('/admin/courses/create', [$c, 'create'], 'admin.courses.create');
        $r->post('/admin/courses/store', [$c, 'store'], 'admin.courses.store');
        $r->get($cp . '/edit', [$c, 'edit'], 'admin.courses.edit');
        $r->post($cp . '/update', [$c, 'update'], 'admin.courses.update');
        $r->post($cp . '/trash', [$c, 'trash'], 'admin.courses.trash');
        $r->post($cp . '/restore', [$c, 'restore'], 'admin.courses.restore');
        $r->post($cp . '/destroy', [$c, 'destroy'], 'admin.courses.destroy');

        $r->get($cp . '/batches/create', [$c, 'createBatch'], 'admin.courses.batches.create');
        $r->post($cp . '/batches/store', [$c, 'storeBatch'], 'admin.courses.batches.store');
        $r->get($cp . '/batches/{bid}/edit', [$c, 'editBatch'], 'admin.courses.batches.edit');
        $r->post($cp . '/batches/{bid}/update', [$c, 'updateBatch'], 'admin.courses.batches.update');
        $r->post($cp . '/batches/{bid}/copy', [$c, 'copyBatch'], 'admin.courses.batches.copy');
        $r->post($cp . '/batches/{bid}/delete', [$c, 'deleteBatch'], 'admin.courses.batches.delete');

        $r->get('/admin/categories', [$g, 'index'], 'admin.categories.index');
        $r->post('/admin/categories/store', [$g, 'store'], 'admin.categories.store');
        $r->get($gp . '/edit', [$g, 'edit'], 'admin.categories.edit');
        $r->post($gp . '/update', [$g, 'update'], 'admin.categories.update');
        $r->post($gp . '/trash', [$g, 'trash'], 'admin.categories.trash');
        $r->post($gp . '/restore', [$g, 'restore'], 'admin.categories.restore');
        $r->post($gp . '/destroy', [$g, 'destroy'], 'admin.categories.destroy');

        $r->get('/admin/settings', [$s, 'index'], 'admin.settings.index');
        $r->post('/admin/settings/update', [$s, 'update'], 'admin.settings.update');

        $r->get('/admin/trash', [TrashController::class, 'index'], 'admin.trash');
    }
}