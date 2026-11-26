<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name    : PaymentController.php
 * Description  : Admin Payment Verification & Order Matching Controller
 * ====================================================================
 */
declare(strict_types=1);

namespace Modules\Admin\Controllers;

use InvalidArgumentException;
use Modules\Admin\Support\AdminHelper as H;
use Modules\Payment\Services\PaymentApprovalService;
use PDO;
use RuntimeException;
use Throwable;

final class PaymentController extends AdminBaseController
{
    private ?PaymentApprovalService $paymentService = null;
    private ?string $tablePrefix = null;

    private function pdo(): PDO
    {
        $db = $this->db();
        if ($db instanceof PDO) {
            return $db;
        }
        if (is_object($db)) {
            foreach (['getPdo', 'pdo', 'getDb', 'db'] as $method) {
                if (method_exists($db, $method)) {
                    $res = $db->$method();
                    if ($res instanceof PDO) {
                        return $res;
                    }
                }
            }
            $ref = new \ReflectionObject($db);
            foreach ($ref->getProperties() as $prop) {
                $prop->setAccessible(true);
                $val = $prop->getValue($db);
                if ($val instanceof PDO) {
                    return $val;
                }
            }
        }
        throw new RuntimeException('ไม่สามารถดึง PDO instance จากฐานข้อมูลได้');
    }

    private function paymentService(): PaymentApprovalService
    {
        if ($this->paymentService === null) {
            $this->paymentService = new PaymentApprovalService($this->pdo());
        }
        return $this->paymentService;
    }

    private function resolvePrefix(): string
    {
        if ($this->tablePrefix !== null) {
            return $this->tablePrefix;
        }
        $db = $this->db();
        if (is_object($db) && method_exists($db, 'prefix')) {
            $p = (string)$db->prefix();
            if ($p !== '') {
                return $this->tablePrefix = $p;
            }
        }
        return $this->tablePrefix = 'sot_';
    }

    private function table(string $baseName): string
    {
        $clean = ltrim($baseName, '_');
        $prefix = $this->resolvePrefix();
        if ($prefix !== '' && str_starts_with($clean, $prefix)) {
            return $clean;
        }
        return $prefix . $clean;
    }

    private function resolveAdminUserId(): int
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }
        return (int)($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 1);
    }

    /**
     * GET /admin/payments
     */
    public function index(): string
    {
        $this->admGuard();

        $pdo           = $this->pdo();
        $paymentsTable = $this->table('order_payments');
        $ordersTable   = $this->table('orders');

        $sql = "
            SELECT
                p.*,
                o.order_no       AS matched_order_no,
                o.quotation_no   AS matched_quotation_no,
                o.invoice_no     AS matched_invoice_no,
                o.customer_name  AS matched_customer_name,
                o.grand_total    AS matched_grand_total,
                o.payment_status AS current_order_status
            FROM `{$paymentsTable}` p
            LEFT JOIN `{$ordersTable}` o ON o.id = p.order_id
            ORDER BY p.id DESC
            LIMIT 200
        ";
        $payments = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $orders = $pdo->query("
            SELECT id, order_no, quotation_no, invoice_no, customer_name, grand_total, payment_status
            FROM `{$ordersTable}`
            ORDER BY id DESC
            LIMIT 200
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $this->admPage('payments/index', [
            'payments'            => $payments,
            'orders'              => $orders,
            'paymentStatusLabels' => PaymentApprovalService::PAYMENT_STATUS_LABELS,
            'statusLabels'        => PaymentApprovalService::PAYMENT_STATUS_LABELS,
            'saveAction'          => H::url('admin/payments/update'),
        ], 'ตรวจสอบการแจ้งชำระเงิน', 'payments');
    }

    /**
     * POST /admin/payments/update
     */
    public function update(): void
    {
        $this->admGuard();

        try {
            $paymentId   = H::inInt('payment_id', 0);
            $orderId     = H::inInt('order_id', 0);
            $newStatus   = H::in('new_status', 50);
            $adminNote   = H::in('admin_note', 500);
            $adminUserId = $this->resolveAdminUserId();

            if ($paymentId <= 0) {
                throw new InvalidArgumentException('ไม่พบรหัสรายการแจ้งชำระเงิน');
            }
            if ($orderId <= 0) {
                throw new InvalidArgumentException('กรุณาเลือกหรือระบุ order_id ของคำสั่งซื้อ');
            }
            if (!PaymentApprovalService::isValidStatus($newStatus)) {
                throw new InvalidArgumentException('สถานะที่ระบุไม่ถูกต้อง: ' . $newStatus);
            }

            $pdo           = $this->pdo();
            $paymentsTable = $this->table('order_payments');
            $ordersTable   = $this->table('orders');

            $stmtOrder = $pdo->prepare("SELECT id, order_no, payment_status FROM `{$ordersTable}` WHERE id = :oid LIMIT 1");
            $stmtOrder->execute([':oid' => $orderId]);
            $orderRow = $stmtOrder->fetch(PDO::FETCH_ASSOC);
            if (!$orderRow) {
                throw new RuntimeException("ไม่พบคำสั่งซื้อ order_id #{$orderId} ในระบบ");
            }

            $stmtUpdPay = $pdo->prepare("
                UPDATE `{$paymentsTable}`
                SET `order_id` = :oid
                WHERE `id` = :pid
            ");
            $stmtUpdPay->execute([
                ':oid' => $orderId,
                ':pid' => $paymentId,
            ]);

            $this->paymentService()->updateOrderPaymentStatus(
                $orderId,
                $newStatus,
                max(1, $adminUserId),
                $adminNote !== '' ? $adminNote : ("Admin ผูกสลิป #{$paymentId} กับ order_id #{$orderId} และเปลี่ยนสถานะการชำระเงินเป็น " . PaymentApprovalService::paymentStatusLabel($newStatus)),
                $paymentId
            );

            $label = PaymentApprovalService::paymentStatusLabel($newStatus);
            H::flash('success', "อัปเดตรายการแจ้งชำระ #{$paymentId} ผูกกับ order_id #{$orderId} และเปลี่ยนสถานะเป็น \"{$label}\" เรียบร้อยแล้ว");
        } catch (Throwable $e) {
            H::flash('error', $e->getMessage());
        }

        H::redirect('admin/payments');
    }
}