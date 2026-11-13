<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : AdminBaseController.php
 * Description : Base Controller
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Controllers;

use App\Core\Controller;
use Modules\Admin\Middleware\AdminGuard;
use Modules\Admin\Support\AdminHelper as H;
use Modules\Admin\Support\AdminView;

abstract class AdminBaseController extends Controller
{
    protected function admGuard(): void { AdminGuard::check(); }

    protected function admPage(string $view, array $data, string $title, string $active, array $breadcrumbs = []): string
    {
        $content = AdminView::render($view, $data);
        return AdminView::render('layouts/admin', [
            'content'     => $content,
            'pageTitle'   => $title,
            'active'      => $active,
            'breadcrumbs' => $breadcrumbs,
            'flashes'     => H::pullFlash(),
        ]);
    }
}