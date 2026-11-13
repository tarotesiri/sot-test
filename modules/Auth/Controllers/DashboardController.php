<?php
/**
 * ==============================================================================
 * Project Name : SIAM Online Training
 * File Name    : DashboardController.php
 * Description  : Controller สำหรับจัดการหน้าแดชบอร์ดผู้ใช้งาน
 * ==============================================================================
 * Author       : TaRote SiRi
 * Email        : *******.***@*****.***
 * Created Date : 2026-11-01
 * Version      : 2.0.0
 * License      : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ==============================================================================
 */

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Core\App;

final class DashboardController
{
    public function index(): void
    {
        // ตรวจสอบการเข้าสู่ระบบ
        if (empty($_SESSION['user_id'])) {
            redirect(url('auth/login'));
            return;
        }

        // เรนเดอร์ผ่านระบบ View ของ Core โดยใช้ Syntax 'Module::view_name'
        // ระบบจะดึง layout.php และ partials (Header/Footer สีเขียว) มาครอบให้อัตโนมัติ
        echo App::instance()->view->render('Auth::dashboard', [
            'title' => 'Dashboard - ภาพรวมระบบ'
        ]);
    }
}