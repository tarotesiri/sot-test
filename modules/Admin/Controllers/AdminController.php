<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : AdminController.php
 * Description : Admin Controller
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Controllers;

final class AdminController extends AdminBaseController
{
    public function index(): string
    {
        $this->admGuard();
        $db = $this->db();
        $stats = [
            'courses'    => (int) $db->value("SELECT COUNT(*) FROM {$db->table('courses')} WHERE status <> 'trashed'"),
            'categories' => (int) $db->value("SELECT COUNT(*) FROM {$db->table('course_categories')}"),
            'batches'    => (int) $db->value("SELECT COUNT(*) FROM {$db->table('course_batches')}"),
            'trashed'    => (int) $db->value("SELECT COUNT(*) FROM {$db->table('courses')} WHERE status = 'trashed'"),
        ];
        return $this->admPage('dashboard', ['stats' => $stats], 'แดชบอร์ด', 'dashboard');
    }
}