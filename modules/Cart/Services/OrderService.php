<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name    : OrderService.php
 * Module       : Order Processing Service
 * Description  : จัดการ DB Transaction บันทึกคำสั่งซื้อ, รายชื่อผู้เรียน, ข้อมูลภาษี และตัดโควตา
 * Author       : Software Engineering Team
 * Last Updated : 2026-11-10
 * ====================================================================
**/

declare(strict_types=1);

namespace Modules\Cart\Services;

use Exception;
use PDO;

class OrderService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createOrder(array $cartItems, array $totals, array $buyerInfo, array $attendeesData, array $invoiceData): array
    {
        $this->db->beginTransaction();

        try {
            // 1. สร้างรหัสคำสั่งซื้อ
            $orderNo = 'SOT-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));

            $stmt = $this->db->prepare("
                INSERT INTO orders (order_no, user_id, customer_name, customer_email, customer_phone, total_subtotal, total_vat, total_wht, grand_total, payment_status, created_at)
                VALUES (:order_no, :user_id, :customer_name, :customer_email, :customer_phone, :subtotal, :vat, :wht, :grand_total, 'pending', NOW())
            ");
            $stmt->execute([
                ':order_no'       => $orderNo,
                ':user_id'        => $buyerInfo['user_id'] ?? null,
                ':customer_name'  => $buyerInfo['name'],
                ':customer_email' => $buyerInfo['email'],
                ':customer_phone' => $buyerInfo['phone'],
                ':subtotal'       => $totals['subtotal'],
                ':vat'            => $totals['vat'],
                ':wht'            => $totals['wht'],
                ':grand_total'    => $totals['grand_total']
            ]);

            $orderId = (int)$this->db->lastInsertId();

            // 2. บันทึก Order Items และ Attendees
            foreach ($cartItems as $key => $item) {
                $itemSubtotal = $item['unit_price'] * $item['quantity'];

                $stmtItem = $this->db->prepare("
                    INSERT INTO order_items (order_id, course_id, batch_id, rate_tier, unit_price, quantity, subtotal, created_at)
                    VALUES (:order_id, :course_id, :batch_id, :rate_tier, :unit_price, :quantity, :subtotal, NOW())
                ");
                $stmtItem->execute([
                    ':order_id'   => $orderId,
                    ':course_id'  => $item['course_id'],
                    ':batch_id'   => $item['batch_id'],
                    ':rate_tier'  => $item['rate_tier'],
                    ':unit_price' => $item['unit_price'],
                    ':quantity'   => $item['quantity'],
                    ':subtotal'   => $itemSubtotal
                ]);

                $orderItemId = (int)$this->db->lastInsertId();

                // บันทึกรายชื่อผู้เข้าอบรมของ item นี้
                if (isset($attendeesData[$key]) && is_array($attendeesData[$key])) {
                    foreach ($attendeesData[$key] as $att) {
                        $stmtAtt = $this->db->prepare("
                            INSERT INTO order_attendees (order_item_id, user_id, prefix, first_name, last_name, email, phone, is_self_enrollment, created_at)
                            VALUES (:item_id, :user_id, :prefix, :first_name, :last_name, :email, :phone, :is_self, NOW())
                        ");
                        $stmtAtt->execute([
                            ':item_id'    => $orderItemId,
                            ':user_id'    => $att['user_id'] ?? null,
                            ':prefix'     => $att['prefix'] ?? '',
                            ':first_name' => $att['first_name'],
                            ':last_name'  => $att['last_name'],
                            ':email'      => $att['email'],
                            ':phone'      => $att['phone'] ?? '',
                            ':is_self'    => isset($att['is_self']) && $att['is_self'] ? 1 : 0
                        ]);
                    }
                }

                // ตัดโควตาที่นั่งในตาราง course_batches (ถ้ามีการเปิดจำกัดจำนวน)
                $tierNum = str_replace('p', '', $item['rate_tier']);
                $tierCol = 'capacity_' . $tierNum;
                $limitCol = 'limit_enabled_' . $tierNum;

                $stmtCap = $this->db->prepare("
                    UPDATE course_batches 
                    SET {$tierCol} = GREATEST(0, {$tierCol} - :qty)
                    WHERE id = :batch_id AND {$limitCol} = 1
                ");
                $stmtCap->execute([
                    ':qty'      => $item['quantity'],
                    ':batch_id' => $item['batch_id']
                ]);
            }

            // 3. บันทึกข้อมูลออกใบกำกับภาษี
            $stmtInv = $this->db->prepare("
                INSERT INTO order_invoices (order_id, tax_type, tax_id, company_name, branch, address, created_at)
                VALUES (:order_id, :tax_type, :tax_id, :company_name, :branch, :address, NOW())
            ");
            $stmtInv->execute([
                ':order_id'     => $orderId,
                ':tax_type'     => $invoiceData['tax_type'] ?? 'personal',
                ':tax_id'       => $invoiceData['tax_id'] ?? '',
                ':company_name' => $invoiceData['company_name'] ?? null,
                ':branch'       => $invoiceData['branch'] ?? null,
                ':address'      => $invoiceData['address'] ?? ''
            ]);

            $this->db->commit();

            return [
                'success'  => true,
                'order_id' => $orderId,
                'order_no' => $orderNo
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}