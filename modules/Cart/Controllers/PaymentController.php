<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name    : PaymentController.php
 * Module       : Payment Controller
 * Description  : จัดการอัปโหลดสลิปชำระเงินและบันทึกข้อมูลเข้าฐานข้อมูล
 * Author       : Software Engineering Team
 * Last Updated : 2026-11-10
 * ====================================================================
**/

declare(strict_types=1);

namespace Modules\Cart\Controllers;

use App\Core\Controller;

class PaymentController extends Controller
{
    public function uploadSlip(): void
    {
        if ($this->request->isPost()) {
            $orderNo = trim((string)$this->request->post('order_no', ''));
            
            if (isset($_FILES['slip_file']) && $_FILES['slip_file']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['slip_file']['tmp_name'];
                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['slip_file']['name']));
                $uploadDir = __DIR__ . '/../../../public/uploads/slips/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($fileTmp, $targetPath)) {
                    $db = $this->app->db();
                    $stmt = $db->prepare("UPDATE orders SET slip_file = :slip, payment_status = 'pending' WHERE order_no = :order_no");
                    $stmt->execute([':slip' => 'uploads/slips/' . $fileName, ':order_no' => $orderNo]);

                    $this->session->setFlash('success', 'อัปโหลดสลิปเรียบร้อยแล้ว เจ้าหน้าที่จะทำการตรวจสอบข้อมูลโดยเร็วที่สุด');
                } else {
                    $this->session->setFlash('error', 'ไม่สามารถบันทึกไฟล์สลิปได้');
                }
            }

            $this->redirect('/checkout/success?order_no=' . $orderNo);
        }
    }
}