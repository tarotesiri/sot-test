<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : upcoming_sessions.php
 * Description : Upcoming Sessions Widget
 * ====================================================================
 * Author : TaRote SiRi
 * Created Date : 2026-11-01
 * Version : 1.1.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$sessions = $sessions ?? [];
$sesLabel = H::sessionStatuses();
?>
<section class="adm-card">
    <div class="adm-card-head"><h2>รอบเรียนที่กำลังจะถึง</h2></div>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead><tr><th>วันที่</th><th>หลักสูตร</th><th>รอบ</th><th>เวลา</th><th>รับ (คน)</th><th>สถานะ</th></tr></thead>
            <tbody>
            <?php if ($sessions === []): ?>
                <tr><td colspan="6" class="adm-empty">ยังไม่มีรอบเรียนที่กำลังจะถึง</td></tr>
            <?php endif; ?>
            <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?= H::e(H::thaiDate($s['start_date'] ?? '')) ?><?= (string) ($s['end_date'] ?? '') !== (string) ($s['start_date'] ?? '') ? ' - ' . H::e(H::thaiDate($s['end_date'] ?? '')) : '' ?></td>
                    <td><a href="<?= H::e(H::url('admin/courses/' . (int) $s['course_id'] . '/edit') . '#sessions') ?>"><?= H::e($s['course_title'] ?? '') ?></a></td>
                    <td><?= H::str($s['name'] ?? '') !== '' ? H::e($s['name']) : 'รอบที่ ' . (int) ($s['session_no'] ?? 1) ?></td>
                    <td><?= H::str($s['time_text'] ?? '') !== '' ? H::e($s['time_text']) : '-' ?></td>
                    <td><?= (int) ($s['capacity'] ?? 0) ?></td>
                    <td><span class="adm-badge is-<?= H::e($s['status'] ?? '') ?>"><?= H::e($sesLabel[H::str($s['status'] ?? '')] ?? '') ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>