<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : form.php
 * Description : Course Form v.5.22
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$isNew = $isNew ?? true; $course = $course ?? []; $categories = $categories ?? []; $batches = $batches ?? [];
$cid = (int)($course['id'] ?? 0); $base = 'admin/courses/' . $cid;
$actionUrl = $isNew ? H::url('admin/courses/store') : H::url($base . '/update');
$selectedCats = H::decodeJsonSafe($course['category_ids'] ?? '[]');
$baseUrlFull = rtrim(H::url(), '/') . '/courses/';
$pubVal = !empty($course['published_at']) ? date('Y-m-d\TH:i', strtotime($course['published_at'])) : date('Y-m-d\TH:i');
?>
<div class="adm-page-head">
    <h1><?= $isNew ? 'เพิ่มหลักสูตรใหม่' : H::e($course['title']) ?></h1>
    <a class="adm-btn is-light" href="<?= H::e(H::url('admin/courses')) ?>">&larr; กลับ</a>
</div>

<form id="course-form" class="adm-batch-layout" method="post" enctype="multipart/form-data" action="<?= H::e($actionUrl) ?>">
    <?= H::csrf() ?>
    
    <!-- คอลัมน์ซ้าย: ข้อมูลหลักสูตร -->
    <div class="adm-batch-main">
        <div class="adm-card">
            <div class="adm-card-head"><h2>ข้อมูลหลักสูตร</h2></div>
            <div class="adm-card-body">
                <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:14px">
                    <div style="width:180px">
                        <label class="adm-label">รูปแบบหลักสูตร</label>
                        <select class="adm-input" name="type" <?= $isNew ? '' : 'disabled' ?> title="<?= $isNew ? '' : 'บันทึกแล้วแก้ไขไม่ได้' ?>">
                            <?php foreach(H::courseTypes() as $k=>$l): ?>
                                <option value="<?= $k ?>"<?= H::sel($course['type'] ?? 'live_online', $k) ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(!$isNew): ?><input type="hidden" name="type" value="<?= H::e($course['type'] ?? 'live_online') ?>"><?php endif; ?>
                    </div>
                    <div style="flex:1">
                        <label class="adm-label">ชื่อหลักสูตร *</label>
                        <input class="adm-input" name="title" id="courseTitleInput" required value="<?= H::e($course['title'] ?? '') ?>" placeholder="พิมพ์ชื่อหลักสูตร...">
                        <div style="margin-top:4px;font-size:12px;color:#64748b">
                            URL: <span id="fullSlugDisplay"><?= $baseUrlFull ?><span id="slugTextSpan"><?= H::e($course['slug'] ?? '') ?></span></span>
                            <input type="hidden" name="slug" id="courseSlugInput" value="<?= H::e($course['slug'] ?? '') ?>">
                            <button type="button" class="adm-btn is-light is-sm" style="padding:1px 4px;font-size:10px;margin-left:6px" onclick="editSlugPrompt()">แก้ไข Slug</button>
                        </div>
                    </div>
                </div>

                <div class="adm-field is-full" style="margin-bottom:14px">
                    <span class="adm-label">สรุปย่อ</span>
                    <textarea class="adm-input" name="summary" rows="3"><?= H::e($course['summary'] ?? '') ?></textarea>
                </div>
                <div class="adm-field is-full">
                    <span class="adm-label">รายละเอียดหลักสูตร</span>
                    <textarea class="adm-input" name="description" rows="6"><?= H::e($course['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- คอลัมน์ขวา -->
    <div class="adm-batch-sidebar">
        <div class="adm-card" style="background:#fff;border-color:#cbd5e1">
            <div class="adm-card-head" style="background:#f8fafc"><h2>เผยแพร่หลักสูตร</h2></div>
            <div class="adm-card-body" style="font-size:13px;display:flex;flex-direction:column;gap:12px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-weight:600">สถานะ:</span>
                    <select class="adm-input" name="status" style="padding:4px 8px;font-size:13px;width:auto">
                        <?php foreach(H::courseStatuses() as $k=>$l): ?>
                            <option value="<?= $k ?>"<?= H::sel($course['status'] ?? 'draft', $k) ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                    <span style="font-weight:600;white-space:nowrap">วันที่เผยแพร่:</span>
                    <input class="adm-input" type="datetime-local" name="published_at" value="<?= $pubVal ?>" style="font-size:12px;padding:4px 6px;flex:1">
                </div>

                <?php if(!$isNew && !empty($course['updated_at'])): ?>
                    <div style="color:#475569;border-top:1px dashed #e2e8f0;padding-top:8px">
                        <strong>บันทึกล่าสุด:</strong> <?= H::e($course['updated_at']) ?>
                    </div>
                <?php endif; ?>

                <div style="border-top:1px solid #e2e8f0;padding-top:12px;display:flex;justify-content:flex-end">
                    <button type="submit" class="adm-btn is-primary" style="width:100%;justify-content:center;padding:10px;font-size:15px" onclick="return confirm('คุณต้องการบันทึกข้อมูลหลักสูตรนี้ใช่หรือไม่?');">
                        <?= $isNew ? 'บันทึกและไปเพิ่มรุ่นอบรม' : 'อัปเดต (บันทึก)' ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head"><h2>หมวดหมู่ (Tags)</h2></div>
            <div class="adm-card-body" style="max-height:220px;overflow-y:auto">
                <p class="adm-muted" style="margin-bottom:8px">เลือกหมวดหมู่ที่เกี่ยวข้อง (เลือกได้หลายรายการ)</p>
                <?php if(empty($categories)): ?>
                    <div class="adm-empty">ยังไม่มีหมวดหมู่ในระบบ</div>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        <?php foreach($categories as $cat): ?>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                                <input type="checkbox" name="category_ids[]" value="<?= $cat['id'] ?>"<?= in_array((string)$cat['id'], array_map('strval', $selectedCats), true) ? ' checked' : '' ?>>
                                <span><?= H::e($cat['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head"><h2>รูปภาพหน้าปกหลักสูตร</h2></div>
            <div class="adm-card-body">
                <label class="adm-field">
                    <input class="adm-input" type="file" name="cover_img" accept="image/*">
                </label>
                <?php if(!empty($course['cover_image'])): ?>
                    <div style="margin-top:10px;text-align:center">
                        <a href="<?= H::e(H::uploadUrl($course['cover_image'])) ?>" target="_blank" title="คลิกเพื่อขยาย">
                            <img src="<?= H::e(H::uploadUrl($course['cover_image'])) ?>" alt="Cover" style="max-width:100%;max-height:160px;border-radius:6px;border:1px solid #cbd5e1">
                        </a>
                        <div style="margin-top:6px">
                            <label style="font-size:13px;color:#b91c1c;cursor:pointer"><input type="checkbox" name="remove_cover" value="1"> ลบรูปหน้าปกนี้</label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<?php if(!$isNew): ?>
<section class="adm-card" style="margin-top:20px">
    <div class="adm-card-head" style="display:flex;justify-content:space-between;align-items:center">
        <h2>รุ่นของหลักสูตร (<?= count($batches) ?> รุ่น)</h2>
        <a class="adm-btn is-primary is-sm" href="<?= H::e(H::url($base . '/batches/create')) ?>">+ เพิ่มรุ่นใหม่</a>
    </div>
    <div class="adm-table-wrap">
        <table class="adm-table" id="batchTable">
            <thead>
                <tr>
                    <th onclick="sortBatchTable(0, 'text')" style="cursor:pointer">รุ่นที่ ⮡</th>
                    <th onclick="sortBatchTable(1, 'text')" style="cursor:pointer">วันที่อบรม ⮡</th>
                    <th onclick="sortBatchTable(2, 'text')" style="cursor:pointer">เวลา ⮡</th>
                    <th onclick="sortBatchTable(3, 'num')" style="cursor:pointer">ราคา (บาท) ⮡</th>
                    <th onclick="sortBatchTable(4, 'num')" style="cursor:pointer">บทเรียน & แบบทดสอบ ⮡</th>
                    <th onclick="sortBatchTable(5, 'text')" style="cursor:pointer">ใบเซอร์ ⮡</th>
                    <th onclick="sortBatchTable(6, 'text')" style="cursor:pointer">แบบประเมิน ⮡</th>
                    <th onclick="sortBatchTable(7, 'text')" style="cursor:pointer">แบบสอบถาม ⮡</th>
                    <th onclick="sortBatchTable(8, 'text')" style="cursor:pointer">สถานที่ ⮡</th>
                    <th onclick="sortBatchTable(9, 'text')" style="cursor:pointer">Status ⮡</th>
                    <th style="width:180px"></th>
                </tr>
            </thead>
            <tbody>
            <?php if($batches === []): ?><tr><td colspan="11" class="adm-empty">ยังไม่มีรุ่นอบรม</td></tr><?php endif; ?>
            <?php foreach($batches as $b): 
                $bid = (int)$b['id'];
                // คำนวณช่วงราคา (น้อยสุด-มากสุด)
                $prices = array_filter([$b['price_1'] ?? 0, $b['price_2'] ?? 0, $b['price_3'] ?? 0, $b['price_4'] ?? 0], fn($p)=>(float)$p > 0);
                $minPrice = $prices !== [] ? min($prices) : 0;
                $maxPrice = $prices !== [] ? max($prices) : 0;
                $priceStr = $minPrice === $maxPrice ? '฿' . H::money($minPrice) : '฿' . H::money($minPrice) . ' - ฿' . H::money($maxPrice);

                // นับจำนวนบทเรียน & แบบทดสอบจาก topics_json
                $topics = H::decodeJsonSafe($b['topics_json'] ?? '');
                $totalLessons = 0;
                foreach($topics as $top) {
                    $totalLessons += count($top['lessons'] ?? []);
                }

                $hasCert = !empty($b['cert_template']) ? 'เพิ่มรูปแล้ว' : '—';
                $hasEval = ((int)($b['evaluation_id'] ?? 0)) > 0 ? 'เลือกแล้ว' : '—';
                $hasSurvey = ((int)($b['survey_id'] ?? 0)) > 0 ? 'เลือกแล้ว' : '—';
            ?>
                <tr>
                    <td class="adm-strong"><?= H::e($b['batch_no'] ?? 'รุ่น') ?></td>
                    <td><?= H::e($b['date_text'] ?? '-') ?></td>
                    <td><?= H::e($b['time_text'] ?? '-') ?></td>
                    <td><?= $priceStr ?></td>
                    <td style="text-align:center"><?= $totalLessons ?> รายการ</td>
                    <td><?= $hasCert ?></td>
                    <td><?= $hasEval ?></td>
                    <td><?= $hasSurvey ?></td>
                    <td><?= H::e($b['location_text'] ?? '-') ?></td>
                    <td><?= H::badge($b['status'] ?? 'open') ?></td>
                    <td>
                        <div class="adm-actions">
                            <a class="adm-btn is-light is-sm" href="<?= H::e(H::url($base . '/batches/' . $bid . '/edit')) ?>">แก้ไข</a>
                            <form method="post" action="<?= H::e(H::url($base . '/batches/' . $bid . '/copy')) ?>" style="display:inline" data-confirm="ต้องการคัดลอกรุ่นนี้ใช่หรือไม่?"><?= H::csrf() ?><button class="adm-btn is-light is-sm" type="submit">คัดลอก</button></form>
                            <form method="post" action="<?= H::e(H::url($base . '/batches/' . $bid . '/delete')) ?>" data-confirm="ลบรุ่นนี้?"><?= H::csrf() ?><button class="adm-btn is-danger-ghost is-sm" type="submit">ลบ</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<script>
document.getElementById('courseTitleInput').addEventListener('input', function() {
    var title = this.value;
    var slug = title.toLowerCase()
                    .replace(/[^\w\u0E00-\u0E7F]+/g, '-')
                    .replace(/^-+|-+$/g, '');
    if (slug.length > 50) { slug = slug.substring(0, 50); }
    document.getElementById('courseSlugInput').value = slug;
    document.getElementById('slugTextSpan').innerText = slug;
});

function editSlugPrompt() {
    var current = document.getElementById('courseSlugInput').value;
    var newSlug = prompt('แก้ไข Slug (URL):', current);
    if (newSlug !== null) {
        var clean = newSlug.toLowerCase().replace(/[^\w-]+/g, '-');
        document.getElementById('courseSlugInput').value = clean;
        document.getElementById('slugTextSpan').innerText = clean;
    }
}

// ฟังก์ชันจัดเรียงตารางรุ่นอบรมในหน้าหลักสูตร
function sortBatchTable(colIdx, type) {
    var table = document.getElementById("batchTable");
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

            if (type === 'num') {
                xVal = parseFloat(xVal.replace(/[^\0-9.-]/g, '')) || 0;
                yVal = parseFloat(yVal.replace(/[^\0-9.-]/g, '')) || 0;
            }

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