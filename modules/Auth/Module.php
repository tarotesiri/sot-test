<?php
/**
 * ==============================================================================
 * Project Name : SIAM Online Training
 * File Name    : Module.php
 * Description  : Module Auth สำหรับระบบสมาชิกและการเข้าสู่ระบบ
 * ==============================================================================
 * Author       : TaRote SiRi
 * Created Date : 2026-10-29
 * Version      : 1.2.0
 * License      : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ==============================================================================
 */

declare(strict_types=1);

namespace Modules\Auth;

use App\Core\App;
use App\Core\ModuleInterface;
use Modules\Auth\Controllers\AuthController;
use Modules\Auth\Controllers\DashboardController;
use Modules\Auth\Controllers\MyPortalController;

final class Module implements ModuleInterface
{
    public function register(App $app): void
    {
        $router = $app->router;

        // Auth Routes
        $router->post('/auth/login-process', [AuthController::class, 'loginProcess'], 'auth.login.process');
        $router->post('/auth/forgot-process', [AuthController::class, 'forgotProcess'], 'auth.forgot.process');
        $router->post('/auth/register-process', [AuthController::class, 'registerProcess'], 'auth.register.process');
        $router->get('/auth/login', [AuthController::class, 'showLoginForm'], 'auth.login.page');
        $router->get('/auth/register', [AuthController::class, 'showRegisterForm'], 'auth.register.page');
        $router->get('/auth/logout', [AuthController::class, 'logout'], 'auth.logout');

        // Dashboard Route
        $router->get('/dashboard', [DashboardController::class, 'index'], 'user.dashboard');

        // Route สำหรับจำหน้าปัจจุบันก่อน Login
        $router->post('/auth/set-redirect', [AuthController::class, 'setRedirectSession'], 'auth.set.redirect');

        // Portal Routes
        $router->get('/my-orders', [MyPortalController::class, 'myOrders'], 'auth.myOrders');
        $router->get('/my-courses', [MyPortalController::class, 'myCourses'], 'auth.myCourses');
        $router->get('/my-orders/document', [MyPortalController::class, 'downloadDocument'], 'auth.myOrdersDocument');
        $router->get('/profile', [MyPortalController::class, 'editProfile'], 'auth.profile');
        $router->post('/profile/update', [MyPortalController::class, 'updateProfile'], 'auth.profile.update');
    }
}