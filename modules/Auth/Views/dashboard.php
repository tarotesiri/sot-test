<?php
/**
 * ==============================================================================
 * Project Name : SIAM Online Training
 * File Name    : dashboard.php
 * Description  : หน้าแสดงผล Dashboard หลักของผู้ใช้งาน (จัดสไตล์ด้วย Bootstrap)
 * ==============================================================================
 * Author       : TaRote SiRi
 * Email        : *******.***@*****.***
 * Created Date : 2026-11-01
 * Version      : 1.6.0
 * License      : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ==============================================================================
 */

declare(strict_types=1);

$summary = is_array($summary ?? null) ? $summary : [];
$approvedCourses = (int)($summary['approved_courses'] ?? 0);
$totalOrders     = (int)($summary['total_orders'] ?? 0);
$pendingOrders   = (int)($summary['pending_orders'] ?? 0);
$recentOrders    = is_array($summary['recent_orders'] ?? null) ? $summary['recent_orders'] : [];

$orderStatusMap = [
    'pending'              => 'รอการชำระ',
    'awaiting_verification' => 'กำลังตรวจสอบ',
    'pending_approval'     => 'รออนุมัติ',
    'paid'                 => 'อนุมัติแล้ว',
    'cancelled'            => 'ยกเลิก',
    'refunded'             => 'คืนเงิน',
];
?>

<div style="max-width: 1200px; margin: 30px auto; padding: 0 15px;">
    <!-- โครงสร้าง Flexbox สำหรับแบ่งซ้าย-ขวา แทนที่ระบบ Grid เดิม -->
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        
        <!-- เมนูด้านซ้าย (Sidebar) -->
        <div style="flex: 1; min-width: 250px; max-width: 280px;">
            <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
                <a href="<?= e(url('dashboard')) ?>" style="display: block; padding: 12px 20px; background: #0d6efd; color: #fff; text-decoration: none; font-weight: 500;">ภาพรวมระบบ (Overview)</a>
                <a href="<?= e(url('my-orders')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none; border-top: 1px solid #dee2e6;">คำสั่งซื้อของฉัน</a>
                <a href="<?= e(url('my-courses')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none; border-top: 1px solid #dee2e6;">หลักสูตรของฉัน</a>
                <a href="<?= e(url('profile')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none; border-top: 1px solid #dee2e6;">แก้ไขข้อมูลส่วนตัว</a>
                <a href="<?= e(url('auth/logout')) ?>" style="display: block; padding: 12px 20px; color: #dc3545; text-decoration: none; border-top: 1px solid #dee2e6;">ออกจากระบบ</a>
            </div>
        </div>

        <!-- พื้นที่แสดงข้อมูลฝั่งขวา (Main Content) -->
        <div style="flex: 3; min-width: 300px;">
            
            <!-- การ์ดต้อนรับ -->
            <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 25px; margin-bottom: 20px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
                <h3 style="margin-top: 0; font-weight: bold; margin-bottom: 10px;">ยินดีต้อนรับคุณ, <?= e($_SESSION['user_name'] ?? 'ผู้ใช้งาน') ?></h3>
                <p style="color: #6c757d; margin-bottom: 20px;">สถานะบัญชี: <span style="background: #198754; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 0.85em;"><?= e($_SESSION['user_role'] ?? 'user') ?></span></p>
                <hr style="border: 0; border-top: 1px solid #dee2e6; margin-bottom: 20px;">
                
                <!-- กล่องสถิติ 3 ช่องเรียงแนวนอน -->
                <div style="display: flex; gap: 15px; flex-wrap: wrap; text-align: center;">
                    <a href="<?= e(url('my-courses')) ?>" style="flex: 1; min-width: 140px; padding: 20px; border: 1px solid #dee2e6; background: #fff; border-radius: 6px; text-decoration: none; color: inherit;">
                        <div style="color: #6c757d; font-size: 0.9em; margin-bottom: 8px;">หลักสูตรที่อนุมัติแล้ว</div>
                        <div style="font-size: 1.8em; font-weight: bold; color: #0d6efd;"><?= $approvedCourses ?></div>
                    </a>
                    <a href="<?= e(url('my-orders')) ?>" style="flex: 1; min-width: 140px; padding: 20px; border: 1px solid #dee2e6; background: #fff; border-radius: 6px; text-decoration: none; color: inherit;">
                        <div style="color: #6c757d; font-size: 0.9em; margin-bottom: 8px;">คำสั่งซื้อทั้งหมด</div>
                        <div style="font-size: 1.8em; font-weight: bold; color: #198754;"><?= $totalOrders ?></div>
                    </a>
                    <a href="<?= e(url('my-orders')) ?>" style="flex: 1; min-width: 140px; padding: 20px; border: 1px solid #dee2e6; background: #fff; border-radius: 6px; text-decoration: none; color: inherit;">
                        <div style="color: #6c757d; font-size: 0.9em; margin-bottom: 8px;">รอดำเนินการ / รออนุมัติ</div>
                        <div style="font-size: 1.8em; font-weight: bold; color: #ffc107;"><?= $pendingOrders ?></div>
                    </a>
                </div>
            </div>

            <!-- การ์ดคำสั่งซื้อล่าสุด -->
            <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 25px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                    <h4 style="margin: 0; font-weight: bold;">คำสั่งซื้อล่าสุด</h4>
                    <a href="<?= e(url('my-orders')) ?>" style="font-size: 0.875rem; color: #0d6efd; text-decoration: none; font-weight: 500;">ดูทั้งหมด</a>
                </div>

                <?php if (empty($recentOrders)): ?>
                    <p style="color: #6c757d; margin: 0;">ยังไม่มีคำสั่งซื้อในขณะนี้ คุณสามารถค้นหาหลักสูตรที่น่าสนใจได้จากช่องค้นหาด้านบน</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                            <thead>
                                <tr style="background: #f8f9fa; border-top: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6; text-align: left;">
                                    <th style="padding: 8px 10px;">เลขที่คำสั่งซื้อ</th>
                                    <th style="padding: 8px 10px;">สถานะ</th>
                                    <th style="padding: 8px 10px; text-align: right;">ยอดสุทธิ</th>
                                    <th style="padding: 8px 10px;">วันที่สร้าง</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $ord): ?>
                                    <?php
                                    $stKey = trim((string)($ord['payment_status'] ?? 'pending'));
                                    $stLabel = $orderStatusMap[$stKey] ?? $stKey;
                                    ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 8px 10px; font-weight: 600;"><?= e((string)($ord['order_no'] ?? '')) ?></td>
                                        <td style="padding: 8px 10px;"><?= e($stLabel) ?></td>
                                        <td style="padding: 8px 10px; text-align: right;"><?= number_format((float)($ord['grand_total'] ?? 0), 2) ?></td>
                                        <td style="padding: 8px 10px; color: #6c757d;"><?= e((string)($ord['created_at'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>