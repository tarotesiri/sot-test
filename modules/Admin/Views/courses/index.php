<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : index.php
 * Description : Course Index v.5.22
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$result = $result ?? []; $items = $result['items'] ?? []; $filters = $filters ?? [];
?>
<div class="adm-page-head">
    <h1>จัดการหลักสูตร (ภาพรวม)</h1>
    <a class="adm-btn is-primary" href="<?= H::e(H::url('admin/courses/create')) ?>">+ เพิ่มหลักสูตร</a>
</div>

<!-- ฟอร์มค้นหา -->
<div class="adm-card" style="padding:14px;margin-bottom:16px">
    <form method="get" action="<?= H::e(H::url('admin/courses')) ?>" style="display:flex;gap:10px">
        <input class="adm-input" type="text" name="q" value="<?= H::e($filters['q'] ?? '') ?>" placeholder="ค้นหาชื่อหลักสูตร หรือ Slug..." style="flex:1">
        <button class="adm-btn is-primary" type="submit">ค้นหา</button>
        <?php if(!empty($filters['q'])): ?>
            <a class="adm-btn is-light" href="<?= H::e(H::url('admin/courses')) ?>">ล้างคำค้น</a>
        <?php endif; ?>
    </form>
</div>

<div class="adm-card">
    <div class="adm-table-wrap">
        <table class="adm-table" id="courseOverviewTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0, 'text')" style="cursor:pointer">ชื่อหลักสูตร ⮡</th>
                    <th onclick="sortTable(1, 'text')" style="cursor:pointer">รูปแบบ ⮡</th>
                    <th onclick="sortTable(2, 'text')" style="cursor:pointer">จำนวนรุ่น (เปิด/ทั้งหมด) ⮡</th>
                    <th onclick="sortTable(3, 'text')" style="cursor:pointer">วันที่ใกล้อบรมที่สุด ⮡</th>
                    <th onclick="sortTable(4, 'text')" style="cursor:pointer">สถานะ ⮡</th>
                    <th style="width:160px"></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($items === []): ?><tr><td colspan="6" class="adm-empty">ไม่พบหลักสูตร</td></tr><?php endif; ?>
            <?php foreach ($items as $c): $cid = (int)$c['id']; ?>
                <tr>
                    <td>
                        <a class="adm-strong" href="<?= H::e(H::url('admin/courses/' . $cid . '/edit')) ?>"><?= H::e($c['title']) ?></a>
                        <div class="adm-muted adm-small">/courses/<?= H::e($c['slug']) ?></div>
                    </td>
                    <td><?= H::e(H::courseTypes()[$c['type']] ?? '-') ?></td>
                    <td><span style="font-weight:600;color:#1d4ed8"><?= (int)($c['open_batches'] ?? 0) ?></span> / <?= (int)($c['total_batches'] ?? 0) ?> รุ่น</td>
                    <td><?= !empty($c['nearest_open_date']) ? H::e($c['nearest_open_date']) : '<span class="adm-muted">—</span>' ?></td>
                    <td><?= H::badge($c['status']) ?></td>
                    <td>
                        <div class="adm-actions">
                            <a class="adm-btn is-light is-sm" href="<?= H::e(H::url('admin/courses/' . $cid . '/edit')) ?>">แก้ไข</a>
                            <form method="post" action="<?= H::e(H::url('admin/courses/' . $cid . '/trash')) ?>" data-confirm="ย้ายไปถังขยะ?"><?= H::csrf() ?><button class="adm-btn is-danger-ghost is-sm" type="submit">ลบ</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// ฟังก์ชันจัดเรียงตารางภาพรวม
function sortTable(colIdx, type) {
    var table = document.getElementById("courseOverviewTable");
    var switching = true;
    var dir = "asc";
    var switchCount = 0;
    
    while (switching) {
        switching = false;
        var rows = table.rows;
        for (var i = 1; i < (rows.length - 1); i++) {
            var shouldSwitch = false;
            var x = rows[i].getElementsByTagName("TD")[colIdx];
            var y = rows[i + 1].getElementsByTagName("TD")[colIdx];
            var xVal = x ? x.textContent.trim() : "";
            var yVal = y ? y.textContent.trim() : "";

            if (dir == "asc") {
                if (xVal > yVal) { shouldSwitch = true; break; }
            } else if (dir == "desc") {
                if (xVal < yVal) { shouldSwitch = true; break; }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchCount++;
        } else {
            if (switchCount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}
</script>