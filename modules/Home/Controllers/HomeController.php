<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : HomeController.php
 *    Description : HomeController: จัดการการแสดงผลหน้าแรก (พร้อม Widget Slot) และหน้าตรวจสถานะระบบ
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

namespace Modules\Home\Controllers;

use App\Core\Controller;

final class HomeController extends Controller
{
    public function index(): string
    {
        return $this->view('Home::index', [
            'title' => 'หน้าแรก',
        ]);
    }

    public function status(): string
    {
        $db = $this->db();
        $checks = [
            ['PHP Version >= 8.2', PHP_VERSION_ID >= 80200, PHP_VERSION],
            ['Database Connection', true, $db->serverVersion()],
            ['Table Prefix', true, '"' . $db->prefix() . '"'],
            ['Storage Logs Writable', is_writable(BASE_PATH . '/storage/logs'), BASE_PATH . '/storage/logs'],
            ['Storage PDF Writable', is_writable(BASE_PATH . '/storage/pdf'), BASE_PATH . '/storage/pdf'],
            ['Uploads Writable', is_writable(BASE_PATH . '/public/uploads'), BASE_PATH . '/public/uploads'],
        ];
        return $this->view('Home::status', [
            'title' => 'สถานะระบบและโครงสร้าง',
            'checks' => $checks,
            'modules' => $this->app->moduleNames(),
            'routes' => $this->app->router->list(),
        ]);
    }
}