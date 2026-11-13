<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : CatalogController.php
 *    Description : Controller แสดงหน้ารายการหลักสูตรและหน้ารายละเอียดหลักสูตร
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.1.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

namespace Modules\Catalog\Controllers;

use App\Core\Controller;
use Modules\Catalog\Models\CourseModel;
use Modules\Admin\Models\SettingAdminModel;
use Modules\Auth\Models\UserModel;

final class CatalogController extends Controller
{
    public function index(): string
    {
        $req = $this->request();
        $filters = [
            'q'        => $req->str('q'),
            'type'     => $req->str('type'),
            'category' => $req->str('category'),
            'status'   => $req->str('status'),
            'sort'     => $req->str('sort'),
        ];
        $page = max(1, $req->int('page', 1));
        $perPage = max(1, $req->int('per_page', 6));

        $model = new CourseModel($this->db());
        $paginated = $model->paginateBatches($filters, $page, $perPage);
        $categories = $model->getCategories();

        return $this->view('Catalog::index', [
            'title'       => 'หลักสูตรฝึกอบรมทั้งหมด',
            'subtitle'    => 'เลือกหลักสูตรและรุ่นที่ตรงกับความต้องการเพื่อพัฒนาทักษะของคุณ',
            'courses'     => $paginated['items'] ?? [],
            'total'       => $paginated['total'] ?? 0,
            'page'        => $paginated['page'] ?? 1,
            'pages'       => $paginated['pages'] ?? 1,
            'pager'       => [
                'total'        => $paginated['total'] ?? 0,
                'current_page' => $paginated['page'] ?? 1,
                'last_page'    => $paginated['pages'] ?? 1,
            ],
            'categories'  => $categories,
            'filters'     => $filters,
            'breadcrumbs' => ['หน้าแรก' => '/', 'หลักสูตรทั้งหมด' => ''],
        ]);
    }

    public function show(string $slug): string
    {
        $db = $this->db();
        $model = new CourseModel($db);
        $course = $model->findBySlug($slug);
        if (!$course) {
            abort(404, 'ไม่พบหลักสูตรที่คุณต้องการ');
        }

        $courseId = (int) $course['id'];
        $batches = $model->getBatches($courseId);
        $documents = $model->getDocuments($courseId);

        // ดึงค่าจำนวนวันตั้งค่ากลางจาก Setting มาส่งให้หน้า View
        $settingModel = new SettingAdminModel($db);
        $defaultPaymentDays = (int) $settingModel->get('default_payment_days', '20');

        // --- [เพิ่มใหม่] ดึงข้อมูลผู้ใช้ปัจจุบัน (ถ้า Login อยู่) เพื่อนำไปเติมเป็นคนที่ 1 อัตโนมัติ ---
        $loggedInUser = null;
        if (!empty($_SESSION['user_id'])) {
            try {
                $userModel = new UserModel($db);
                $loggedInUser = $userModel->findById((int)$_SESSION['user_id']) 
                              ?? (!empty($_SESSION['user_email']) ? $userModel->findByEmail($_SESSION['user_email']) : null);
            } catch (\Throwable $e) {
                $loggedInUser = null;
            }
        }
        // ----------------------------------------------------------------------------------------

        return $this->view('Catalog::show', [
            'title'              => $course['title'],
            'subtitle'           => $course['summary'],
            'course'             => $course,
            'batches'            => $batches,
            'documents'          => $documents,
            'defaultPaymentDays' => $defaultPaymentDays, // ส่งตัวแปรจำนวนวันไปใช้งาน
            'loggedInUser'       => $loggedInUser,       // [เพิ่มใหม่] ส่งข้อมูล User ที่ Login ไปให้ View
            'breadcrumbs'        => ['หน้าแรก' => '/', 'หลักสูตรทั้งหมด' => '/courses', ($course['category_name'] ?? 'หลักสูตร') => '/courses?category=' . ($course['category_slug'] ?? ''), $course['title'] => ''],
        ]);
    }
}