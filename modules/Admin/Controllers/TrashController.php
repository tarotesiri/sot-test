<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : TrashController.php
 * Description : Trash Controller
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Modules\Admin\Models\CategoryAdminModel;
use Modules\Admin\Models\CourseAdminModel;

final class TrashController extends AdminBaseController
{
    public function index(): string
    {
        $this->admGuard();
        $db = $this->db();
        return $this->admPage('trash/index', [
            'courses' => (new CourseAdminModel($db))->paginate(['status' => 'trashed'], 1, 500)['items'],
            'categories' => (new CategoryAdminModel($db))->all('trashed'),
        ], 'ถังขยะ', 'trash');
    }
}