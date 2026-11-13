<?php
/**
* ====================================================================
* Project Name : SIAM Online Training (SOT LMS)
* File Name    : CartController.php
* Module       : Cart Controller
* Description  : จัดการ Request ของตะกร้าสินค้า (แสดงรายการ, เพิ่ม, อัปเดตจำนวน, ลบ)
* Author       : Software Engineering Team
* Last Updated : 2026-11-12
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
        parent::__construct($app); // ส่งอาร์กิวเมนต์เข้าคลาสแม่เพื่อป้องกัน ArgumentCountError
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
    public function syncAjax()
	{
	    $input = json_decode(file_get_contents('php://input'), true);
	    $key = $input['key'] ?? '';
	    $attendees = $input['attendees'] ?? [];

	    $cartService = new CartService();
	    $success = $cartService->syncAttendeesAjax($key, $attendees);

	    header('Content-Type: application/json');
	    echo json_encode(['success' => $success]);
	    exit;
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
}