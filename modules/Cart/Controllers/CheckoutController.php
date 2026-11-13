<?php
/**
* ====================================================================
* Project Name : SIAM Online Training (SOT LMS)
* File Name    : CheckoutController.php
* Module       : Checkout Controller
* Description  : จัดการแสดงหน้ากรอกรายชื่อผู้เรียน การออกใบกำกับภาษี และบันทึก Order
* Author       : Software Engineering Team
* Last Updated : 2026-11-11
* ====================================================================
**/

declare(strict_types=1);

namespace Modules\Cart\Controllers;

use App\Core\Controller;
use Modules\Cart\Services\CartService;
use Modules\Cart\Services\OrderService;

class CheckoutController extends Controller
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
		if (empty($items)) {
			$this->redirect('/cart');
		}

		$totals = $this->cartService->getTotals();
		$currentUser = (isset($this->auth) && method_exists($this->auth, 'user')) ? $this->auth->user() : null;

		// เปลี่ยนมาใช้ฟังก์ชัน view() helper ส่วนกลาง
		return view('Cart::checkout', [
			'items'       => $items,
			'totals'      => $totals,
			'currentUser' => $currentUser,
			'breadcrumbs' => ['หน้าแรก' => '/', 'ตะกร้าสินค้า' => '/cart', 'ยืนยันการสั่งซื้อ' => '']
		]);
	}

	public function placeOrder(): void
	{
		if ($this->request->isPost()) {
			$items = $this->cartService->getItems();
			$totals = $this->cartService->getTotals();

			if (empty($items)) {
				$this->redirect('/cart');
			}

			$currentUser = (isset($this->auth) && method_exists($this->auth, 'user')) ? $this->auth->user() : null;

			$buyerInfo = [
				'user_id' => $currentUser['id'] ?? null,
				'name'    => trim((string)$this->request->post('buyer_name', '')),
				'email'   => trim((string)$this->request->post('buyer_email', '')),
				'phone'   => trim((string)$this->request->post('buyer_phone', '')),
			];

			$attendeesData = $_POST['attendees'] ?? [];
			$invoiceData   = $_POST['invoice'] ?? [];

			$db = $this->app->db();
			$orderService = new OrderService($db);
			$result = $orderService->createOrder($items, $totals, $buyerInfo, $attendeesData, $invoiceData);

			if ($result['success']) {
				$this->cartService->clear();
				$this->redirect('/checkout/success?order_no=' . $result['order_no']);
			} else {
				$this->session->setFlash('error', 'เกิดข้อผิดพลาดในการบันทึกคำสั่งซื้อ: ' . $result['message']);
				$this->redirect('/checkout');
			}
		}
	}

	public function success(): string
	{
		$orderNo = trim((string)$this->request->get('order_no', ''));

		// เปลี่ยนมาใช้ฟังก์ชัน view() helper ส่วนกลาง
		return view('Cart::success', [
			'orderNo'     => $orderNo,
			'breadcrumbs' => ['หน้าแรก' => '/', 'หลักสูตรทั้งหมด' => '/courses', 'ผลการสั่งซื้อ' => '']
		]);
	}
}