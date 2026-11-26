<?php
/**
* ====================================================================
* Project Name : SIAM Online Training (SOT LMS)
* File Name    : CartController.php
* Module       : Cart Controller
* Description  : จัดการ Request ของตะกร้าสินค้า
* ====================================================================
**/

declare(strict_types=1);

namespace Modules\Cart\Controllers;

use App\Core\Controller;
use Modules\Cart\Services\CartService;

class CartController extends Controller
{
    private CartService $cartService;

    public function __construct($app = null)
    {
        parent::__construct($app);
        $this->cartService = new CartService();
    }

    public function index(): string
    {
        $items = $this->cartService->getItems();
        $totals = $this->cartService->getTotals();

        return view('Cart::index', [
            'items'       => $items,
            'totals'      => $totals,
            'breadcrumbs' => ['หน้าแรก' => '/', 'หลักสูตรทั้งหมด' => '/courses', 'ตะกร้าสินค้า' => '']
        ]);
    }

    public function add(): void
    {
        $req = request();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemData = [
                'course_id'    => (int)($_POST['course_id'] ?? 0),
                'course_title' => trim((string)($_POST['course_title'] ?? '')),
                'course_type'  => trim((string)($_POST['course_type'] ?? 'live_online')),
                'batch_id'     => (int)($_POST['batch_id'] ?? 0),
                'batch_no'     => trim((string)($_POST['batch_no'] ?? '')),
                'date_text'    => trim((string)($_POST['date_text'] ?? '')),
                'rate_tier'    => trim((string)($_POST['rate_tier'] ?? 'p1')),
                'tier_name'    => trim((string)($_POST['tier_name'] ?? 'ราคาบุคคลทั่วไป')),
                'unit_price'   => (float)($_POST['unit_price'] ?? 0),
                'quantity'     => max(1, (int)($_POST['quantity'] ?? 1)),
            ];

            $this->cartService->addItem($itemData);

            if (method_exists($req, 'isAjax') && $req->isAjax()) {
                echo json_encode(['success' => true, 'totals' => $this->cartService->getTotals()]);
                return;
            }

            redirect('/cart');
        }
    }

    public function update(): void
    {
        $req = request();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $key = (string)($_POST['key'] ?? '');
            $quantity = (int)($_POST['quantity'] ?? 1);

            $this->cartService->updateQuantity($key, $quantity);
            redirect('/cart');
        }
    }

    public function remove(): void
    {
        $req = request();
        $key = (string)($_GET['key'] ?? '');
        if ($key !== '') {
            $this->cartService->removeItem($key);
        }
        redirect('/cart');
    }

    public function clear(): void
    {
        $this->cartService->clear();
        redirect('/cart');
    }

    public function getMyProfileAjax(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }

        try {
            $db = $this->db();
            $userModel = new \Modules\Auth\Models\UserModel($db);
            $user = $userModel->findById((int)$_SESSION['user_id']);

            if ($user) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'prefix'             => $user['prefix'] ?? '',
                        'firstname'          => $user['firstname'] ?? '',
                        'lastname'           => $user['lastname'] ?? '',
                        'email'              => $user['email'] ?? '',
                        'phone'              => $user['phone'] ?? '',
                        'id_card'            => $user['id_card'] ?? '',
                        'position'           => $user['position'] ?? '',
                        'other_requirements' => $user['other_requirements'] ?? ''
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function syncAttendees(): void
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $key = $input['key'] ?? '';
        $attendees = $input['attendees'] ?? [];

        if (empty($key)) {
            echo json_encode(['success' => false, 'message' => 'Missing key']);
            exit;
        }

        $success = $this->cartService->syncAttendeesAjax($key, $attendees);

        echo json_encode(['success' => $success]);
        exit;
    }
    
    public function printDocument(): void
	{
	    if (session_status() === PHP_SESSION_NONE) {
	        session_start();
	    }

	    $hasUserSession  = !empty($_SESSION['user_id']);
	    $hasAdminSession = !empty($_SESSION['admin_id'])
	        || !empty($_SESSION['admin_user_id'])
	        || !empty($_SESSION['is_admin'])
	        || ((string)($_SESSION['role'] ?? '') === 'admin')
	        || ((string)($_SESSION['admin_role'] ?? '') !== '');

	    if (!$hasUserSession && !$hasAdminSession) {
	        http_response_code(403);
	        echo 'ไม่มีสิทธิ์เข้าถึงเอกสาร';
	        exit;
	    }

	    $orderId = (int)($_GET['order_id'] ?? $_GET['id'] ?? 0);
	    $type    = strtolower(trim((string)($_GET['type'] ?? 'quo')));
	    if (!in_array($type, ['quo', 'ive'], true)) {
	        $type = 'quo';
	    }

	    if ($orderId <= 0) {
	        http_response_code(400);
	        echo 'ไม่พบรหัสคำสั่งซื้อ';
	        exit;
	    }

	    if (!class_exists(\Modules\Cart\Services\DocumentService::class)) {
	        $docServiceFile = dirname(__DIR__) . '/Services/DocumentService.php';
	        if (is_file($docServiceFile)) {
	            require_once $docServiceFile;
	        }
	    }

	    $docService = new \Modules\Cart\Services\DocumentService($this->db());
	    $loaded = $docService->loadOrderWithItems($orderId);

	    if (empty($loaded['order'])) {
	        http_response_code(404);
	        echo 'ไม่พบข้อมูลคำสั่งซื้อ';
	        exit;
	    }

	    $docService->renderPdf($loaded['order'], $loaded['items'] ?? [], $type, 'I');
	    exit;
	}
	
	public function document(): void
	{
	    if (session_status() === PHP_SESSION_NONE) {
	        session_start();
	    }

	    $currentUserId = (int)($_SESSION['user_id'] ?? 0);
	    $adminId       = (int)($_SESSION['admin_id'] ?? $_SESSION['admin_user_id'] ?? 0);
	    $roleStr       = strtolower(trim((string)($_SESSION['role'] ?? $_SESSION['admin_role'] ?? $_SESSION['user_role'] ?? '')));

	    $isAdmin = ($adminId > 0)
	        || !empty($_SESSION['is_admin'])
	        || !empty($_SESSION['admin_logged_in'])
	        || in_array($roleStr, ['admin', 'superadmin', 'super_admin', 'staff', 'moderator'], true);

	    if (!$isAdmin && $currentUserId <= 0) {
	        http_response_code(403);
	        echo 'ไม่มีสิทธิ์เข้าถึงเอกสาร';
	        exit;
	    }

	    $orderId = (int)($_GET['order_id'] ?? $_GET['id'] ?? 0);
	    $type    = strtolower(trim((string)($_GET['type'] ?? 'quo')));
	    if (!in_array($type, ['quo', 'ive'], true)) {
	        $type = 'quo';
	    }

	    if ($orderId <= 0) {
	        http_response_code(400);
	        echo 'ไม่พบรหัสคำสั่งซื้อ';
	        exit;
	    }

	    if (!class_exists(\Modules\Cart\Services\DocumentService::class)) {
	        $docServiceFile = dirname(__DIR__) . '/Services/DocumentService.php';
	        if (is_file($docServiceFile)) {
	            require_once $docServiceFile;
	        }
	    }

	    $docService = new \Modules\Cart\Services\DocumentService($this->db());
	    $loaded = $docService->loadOrderWithItems($orderId);
	    $order = $loaded['order'] ?? [];

	    if (empty($order)) {
	        http_response_code(404);
	        echo 'ไม่พบข้อมูลคำสั่งซื้อ';
	        exit;
	    }

	    // ถ้าเป็น Admin พิมพ์ได้ทุก order ทันที ไม่ต้องเช็กเจ้าของคำสั่งซื้อ
	    if (!$isAdmin) {
	        $ownerUserId = (int)($order['user_id'] ?? 0);
	        $isLearnerInOrder = false;

	        foreach (($loaded['items'] ?? []) as $it) {
	            foreach (($it['attendees'] ?? []) as $att) {
	                if ((int)($att['user_id'] ?? 0) === $currentUserId) {
	                    $isLearnerInOrder = true;
	                    break 2;
	                }
	            }
	        }

	        if ($ownerUserId !== $currentUserId && !$isLearnerInOrder) {
	            http_response_code(403);
	            echo 'คุณไม่มีสิทธิ์ดูเอกสารของคำสั่งซื้อนี้';
	            exit;
	        }
	    }

	    $docService->renderPdf($order, $loaded['items'] ?? [], $type, 'I');
	    exit;
	}
}