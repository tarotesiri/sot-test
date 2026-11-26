<?php
/**
 * ============================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name    : my-courses.php
 * Module       : Auth View (My Courses)
 * Description  : หน้า "หลักสูตรของฉัน" แยกดูได้ทั้งฝั่งผู้เรียนและฝั่งผู้ประสานงาน
 * ============================================================================
 */

declare(strict_types=1);

$courses            = is_array($courses ?? null) ? $courses : [];
$learnerCourses     = is_array($learnerCourses ?? null) ? $learnerCourses : $courses;
$coordinatorCourses = is_array($coordinatorCourses ?? null) ? $coordinatorCourses : [];
$activeRoleTab      = (string)($activeRoleTab ?? 'learner');
$isCoordTab         = ($activeRoleTab === 'coordinator');
?>

<div style="max-width: 1200px; margin: 30px auto; padding: 0 15px;">
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">

        <!-- เมนูด้านซ้าย (Sidebar) -->
        <div style="flex: 1; min-width: 250px; max-width: 280px;">
            <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
                <a href="<?= e(url('dashboard')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none;">ภาพรวมระบบ (Overview)</a>
                <a href="<?= e(url('my-orders')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none; border-top: 1px solid #dee2e6;">คำสั่งซื้อของฉัน</a>
                <a href="<?= e(url('my-courses')) ?>" style="display: block; padding: 12px 20px; background: #0d6efd; color: #fff; text-decoration: none; font-weight: 500; border-top: 1px solid #dee2e6;">หลักสูตรของฉัน</a>
                <a href="<?= e(url('profile')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none; border-top: 1px solid #dee2e6;">แก้ไขข้อมูลส่วนตัว</a>
                <a href="<?= e(url('auth/logout')) ?>" style="display: block; padding: 12px 20px; color: #dc3545; text-decoration: none; border-top: 1px solid #dee2e6;">ออกจากระบบ</a>
            </div>
        </div>

        <!-- พื้นที่แสดงข้อมูลฝั่งขวา -->
        <div style="flex: 3; min-width: 300px;">
            <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 25px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
                    <div>
                        <h3 style="margin:0;font-weight:700;color:#212529;">หลักสูตรของฉัน</h3>
                        <div style="font-size:0.85rem;color:#6c757d;margin-top:4px;">
                            <?= $isCoordTab ? 'แสดงรายการที่ท่านเป็นผู้ประสานงาน/สั่งซื้อ และได้รับการอนุมัติแล้ว' : 'แสดงเฉพาะหลักสูตรที่ท่านเป็นผู้เรียนและได้รับการอนุมัติแล้ว' ?>
                        </div>
                    </div>
                    <a href="<?= e(url('my-orders')) ?>" style="display:inline-block;padding:8px 14px;border-radius:6px;border:1px solid #ced4da;background:#f8f9fa;color:#212529;text-decoration:none;font-size:0.875rem;font-weight:500;">ดูคำสั่งซื้อทั้งหมด</a>
                </div>

                <!-- ปุ่มสลับมุมมอง: ฝั่งผู้เรียน / ฝั่งผู้ประสานงาน -->
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
                    <a href="<?= e(url('my-courses?role=learner')) ?>"
                       style="display:inline-block;padding:7px 14px;border-radius:6px;text-decoration:none;font-size:0.875rem;font-weight:600;border:1px solid <?= !$isCoordTab ? '#0d6efd' : '#ced4da' ?>;background:<?= !$isCoordTab ? '#0d6efd' : '#f8f9fa' ?>;color:<?= !$isCoordTab ? '#fff' : '#212529' ?>;">
                        หลักสูตรที่เข้าอบรม ( <?= count($learnerCourses) ?> )
                    </a>
                    <a href="<?= e(url('my-courses?role=coordinator')) ?>"
                       style="display:inline-block;padding:7px 14px;border-radius:6px;text-decoration:none;font-size:0.875rem;font-weight:600;border:1px solid <?= $isCoordTab ? '#0d6efd' : '#ced4da' ?>;background:<?= $isCoordTab ? '#0d6efd' : '#f8f9fa' ?>;color:<?= $isCoordTab ? '#fff' : '#212529' ?>;">
                        หลักสูตรที่สมัครให้ผู้อื่นเข้าอบรม ( <?= count($coordinatorCourses) ?> )
                    </a>
                </div>

                <?php if (empty($courses)): ?>
                    <div style="text-align:center;padding:36px 16px;color:#6c757d;border-top:1px solid #dee2e6;">
                        <?= $isCoordTab
                            ? 'ไม่มีหลักสูตรที่ได้รับอนุมัติให้ผู้สมัครเข้าอบรม'
                            : 'ไม่มีหลักสูตรที่ต้องเข้าอบรม' ?>
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                            <thead>
                                <tr style="background:#f8f9fa;border-top:1px solid #dee2e6;border-bottom:2px solid #dee2e6;text-align:left;">
                                    <th style="padding:10px 12px;">เลขที่คำสั่งซื้อ</th>
                                    <th style="padding:10px 12px;">หลักสูตร / รุ่น</th>
                                    <?php if ($isCoordTab): ?>
                                        <th style="padding:10px 12px;">ผู้เข้าอบรม</th>
                                    <?php endif; ?>
                                    <th style="padding:10px 12px;text-align:center;">รหัส PIN</th>
                                    <th style="padding:10px 12px;text-align:center;">สถานะการเข้าร่วม</th>
                                    <th style="padding:10px 12px;text-align:center;">เอกสาร</th>
                                    <th style="padding:10px 12px;">วันที่อนุมัติ / รุ่น</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $c): ?>
                                    <?php
                                    $pStatus = (string)($c['participation_status'] ?? 'pending');
                                    $pText = match ($pStatus) {
                                        'joined'     => 'เข้าร่วม',
                                        'not_joined' => 'ไม่เข้าร่วม',
                                        default      => 'รอเข้าร่วม',
                                    };
                                    $courseTitle = trim((string)($c['course_title'] ?? ''));
                                    $courseDisplay = $courseTitle !== ''
                                        ? $courseTitle
                                        : ('Course #' . (int)($c['course_id'] ?? 0));
                                    $batchCode = trim((string)($c['batch_code'] ?? ''));
                                    $batchDisplay = $batchCode !== ''
                                        ? $batchCode
                                        : (!empty($c['batch_id']) ? ('Batch #' . (int)$c['batch_id']) : '');
                                    $dateDisplay = trim((string)($c['batch_approved_at'] ?? $c['approved_at'] ?? $c['batch_date_text'] ?? $c['enrolled_at'] ?? ''));
                                    $attFullName = trim((string)($c['participant_first_name'] ?? '') . ' ' . (string)($c['participant_last_name'] ?? ''));

                                    $docRaw = trim((string)($c['batch_document_file'] ?? ''));
                                    $docClean = ltrim(str_replace(['..', '\\'], ['', '/'], $docRaw), '/');
                                    $docUrl = '';
                                    if ($docClean !== '') {
                                        $docUrl = preg_match('#^https?://#i', $docClean)
                                            ? $docClean
                                            : (str_starts_with($docClean, 'storage/') ? url($docClean) : url('storage/uploads/' . $docClean));
                                    }
                                    $docTitle = trim((string)($c['batch_document_title'] ?? ''));
                                    $docLabel = $docTitle !== '' ? $docTitle : 'ดาวน์โหลดเอกสาร';
                                    ?>
                                    <tr style="border-bottom:1px solid #dee2e6;">
                                        <td style="padding:10px 12px;font-weight:700;"><?= e((string)($c['order_no'] ?? '')) ?></td>
                                        <td style="padding:10px 12px;">
                                            <div style="font-weight:600;color:#212529;"><?= e($courseDisplay) ?></div>
                                            <?php if ($batchDisplay !== ''): ?>
                                                <div style="font-size:0.8rem;color:#6c757d;margin-top:2px;"><?= e($batchDisplay) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($isCoordTab): ?>
                                            <td style="padding:10px 12px;"><?= e($attFullName !== '' ? $attFullName : '-') ?></td>
                                        <?php endif; ?>
                                        <td style="padding:10px 12px;text-align:center;font-family:monospace;"><?= e((string)($c['participant_pin_code'] ?? '-')) ?></td>
                                        <td style="padding:10px 12px;text-align:center;font-weight:600;"><?= e($pText) ?></td>
                                        <td style="padding:10px 12px;text-align:center;">
                                            <?php if ($docUrl !== ''): ?>
                                                <a href="<?= e($docUrl) ?>" download="<?= e($docTitle !== '' ? $docTitle : basename($docClean)) ?>" style="display:inline-block;padding:5px 10px;border-radius:5px;background:#0d6efd;color:#fff;text-decoration:none;font-size:0.8rem;font-weight:500;">
                                                    <?= e($docLabel) ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="color:#6c757d;font-size:0.8rem;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:10px 12px;color:#6c757d;"><?= e($dateDisplay) ?></td>
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