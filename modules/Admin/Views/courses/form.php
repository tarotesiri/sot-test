<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : form.php
 * Description : Course Form v.5.32
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.32.0
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
                    <th rowspan="2" style="vertical-align:middle;white-space:nowrap">รุ่น</th>
                    <th rowspan="2" style="vertical-align:middle;white-space:nowrap">วันอบรม</th>
                    <th rowspan="2" style="vertical-align:middle;white-space:nowrap">เวลา</th>
                    <th rowspan="2" style="vertical-align:middle;white-space:nowrap">ราคา</th>
                    <th colspan="3" style="text-align:center;padding:6px 4px">แบบทดสอบ (ข้อสอบ)</th>
                    <th rowspan="2" style="vertical-align:middle;text-align:center;white-space:nowrap">ใบเซอร์</th>
                    <th rowspan="2" style="vertical-align:middle;text-align:center;white-space:nowrap">เอกสาร</th>
                    <th rowspan="2" style="vertical-align:middle;white-space:nowrap">สถานที่</th>
                    <th rowspan="2" style="vertical-align:middle;white-space:nowrap">สถานะ</th>
                    <th rowspan="2" style="width:180px"></th>
                </tr>
                <tr>
                    <th style="text-align:center;padding:6px 4px;white-space:nowrap">จำนวน</th>
                    <th style="text-align:center;padding:6px 4px;white-space:nowrap">เกณฑ์</th>
                    <th style="text-align:center;padding:6px 4px;white-space:nowrap">ทำซ้ำ</th>
                </tr>
            </thead>
            <tbody>
            <?php if($batches === []): ?><tr><td colspan="12" class="adm-empty">ยังไม่มีรุ่นอบรม</td></tr><?php endif; ?>
            <?php foreach($batches as $b): 
                $bid = (int)$b['id'];
                // คำนวณช่วงราคา (น้อยสุด-มากสุด)
                $prices = array_filter([$b['price_1'] ?? 0, $b['price_2'] ?? 0, $b['price_3'] ?? 0, $b['price_4'] ?? 0], fn($p)=>(float)$p > 0);
                $minPrice = $prices !== [] ? min($prices) : 0;
                $maxPrice = $prices !== [] ? max($prices) : 0;
                $priceStr = $minPrice === $maxPrice ? '฿' . H::money($minPrice) : '฿' . H::money($minPrice) . ' - ฿' . H::money($maxPrice);

                // นับจำนวนข้อสอบ / เกณฑ์ / ทำซ้ำ จาก topics_json
                $topics = H::decodeJsonSafe($b['topics_json'] ?? '');
                $hasQuiz = false;
                $quizCount = 0;
                $passGrades = [];
                $attemptLimits = [];

                foreach($topics as $top) {
                    if (!is_array($top)) {
                        continue;
                    }
                    foreach(($top['lessons'] ?? []) as $les) {
                        if (!is_array($les) || (($les['type'] ?? '') !== 'quiz')) {
                            continue;
                        }
                        $hasQuiz = true;
                        $descRaw = $les['description'] ?? '';
                        $cfg = [];
                        if (is_array($descRaw)) {
                            $cfg = $descRaw;
                        } elseif (is_string($descRaw) && trim($descRaw) !== '') {
                            $decodedDesc = json_decode($descRaw, true);
                            if (is_array($decodedDesc)) {
                                $cfg = $decodedDesc;
                            }
                        }

                        $qList = is_array($cfg['questions'] ?? null) ? $cfg['questions'] : [];
                        $quizCount += count($qList);

                        if (isset($cfg['pass_grade']) && is_numeric((string)$cfg['pass_grade'])) {
                            $passGrades[] = (float)$cfg['pass_grade'];
                        }
                        if (isset($cfg['attempts']) && is_numeric((string)$cfg['attempts'])) {
                            $attemptLimits[] = (int)$cfg['attempts'];
                        }
                    }
                }

                $quizDisplay = $hasQuiz ? (string)$quizCount : '-';
                $quizIsZero  = ($hasQuiz && $quizCount === 0);

                $passDisplay = '-';
                if ($passGrades !== []) {
                    $uniquePass = array_unique($passGrades);
                    $passDisplay = count($uniquePass) === 1
                        ? rtrim(rtrim(number_format((float)$uniquePass[0], 2, '.', ''), '0'), '.') . '%'
                        : implode('/', array_map(fn($p) => rtrim(rtrim(number_format((float)$p, 2, '.', ''), '0'), '.') . '%', $uniquePass));
                }

                $retryDisplay = '-';
                $retryIsZero  = false;
                if ($attemptLimits !== []) {
                    $uniqueAttempts = array_unique($attemptLimits);
                    $retryDisplay = count($uniqueAttempts) === 1
                        ? (string)(int)$uniqueAttempts[0]
                        : implode('/', array_map('intval', $uniqueAttempts));
                    $retryIsZero = in_array(0, array_map('intval', $uniqueAttempts), true);
                }

                $zeroBadgeStyle = 'display:inline-block;padding:2px 8px;border-radius:999px;font-size:0.76rem;font-weight:700;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;';

                $certFile = trim((string)($b['cert_template'] ?? ''));
                $certUrl  = $certFile !== '' ? H::uploadUrl($certFile) : '';
                $certExt  = $certFile !== '' ? strtolower(pathinfo($certFile, PATHINFO_EXTENSION)) : '';

                $docFile  = trim((string)($b['document_file'] ?? ''));
                $docTitle = trim((string)($b['document_title'] ?? ''));
                $docLabel = $docTitle !== '' ? $docTitle : ($docFile !== '' ? basename($docFile) : '');
                $docExt   = $docFile !== '' ? strtolower(pathinfo($docFile, PATHINFO_EXTENSION)) : '';
                $docIconMap = [
                    'pdf'  => 'pdf.png',
                    'doc'  => 'word.png',
                    'docx' => 'word.png',
                    'xls'  => 'excel.png',
                    'xlsx' => 'excel.png',
                    'csv'  => 'excel.png',
                    'ppt'  => 'powerpoint.png',
                    'pptx' => 'powerpoint.png',
                    'zip'  => 'zip.png',
                    'rar'  => 'rar.png',
                    'txt'  => 'text.png',
                    'jpg'  => 'jpg.png',
                    'jpeg' => 'jpg.png',
                    'png'  => 'jpg.png',
                    'webp' => 'jpg.png',
                ];
                $docIconFile = $docIconMap[$docExt] ?? 'text.png';
                $docIconUrl  = H::asset('img/icons/' . $docIconFile);
            ?>
                <tr>
                    <td class="adm-strong"><?= H::e($b['batch_no'] ?? 'รุ่น') ?></td>
                    <td><?= H::e($b['date_text'] ?? '-') ?></td>
                    <td><?= H::e($b['time_text'] ?? '-') ?></td>
                    <td><?= $priceStr ?></td>
                    <td style="text-align:center;color:#64748b">
                        <?php if ($quizIsZero): ?>
                            <span style="<?= $zeroBadgeStyle ?>"><?= H::e($quizDisplay) ?></span>
                        <?php else: ?>
                            <?= H::e($quizDisplay) ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;color:#64748b"><?= H::e($passDisplay) ?></td>
                    <td style="text-align:center;color:#64748b">
                        <?php if ($retryIsZero): ?>
                            <span style="<?= $zeroBadgeStyle ?>"><?= H::e($retryDisplay) ?></span>
                        <?php else: ?>
                            <?= H::e($retryDisplay) ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;color:#64748b">
                        <?php if ($certUrl !== ''): ?>
                            <?php if ($certExt === 'pdf'): ?>
                                <a href="<?= H::e($certUrl) ?>" target="_blank" title="เปิดดูใบเซอร์ (PDF)" style="display:inline-flex;align-items:center;justify-content:center">
                                    <img src="<?= H::e(H::asset('img/icons/pdf.png')) ?>" alt="PDF" style="width:20px;height:20px;object-fit:contain">
                                </a>
                            <?php else: ?>
                                <button type="button"
                                        onclick="openCertLightbox(<?= H::e(json_encode($certUrl)) ?>, <?= H::e(json_encode(($course['title'] ?? '') . ' - ' . ($b['batch_no'] ?? ''))) ?>)"
                                        title="คลิกเพื่อขยายดูใบเซอร์"
                                        style="background:none;border:none;padding:0;cursor:pointer;display:inline-flex;align-items:center;justify-content:center">
                                    <img src="<?= H::e($certUrl) ?>" alt="ใบเซอร์" style="width:42px;height:30px;object-fit:cover;border:1px solid #cbd5e1;border-radius:4px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.06)">
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;color:#64748b">
                        <?php if ($docFile !== ''): ?>
                            <a href="<?= H::e(H::uploadUrl($docFile)) ?>"
                               download="<?= H::e($docTitle !== '' ? $docTitle : basename($docFile)) ?>"
                               target="_blank"
                               title="<?= H::e($docLabel) ?>"
                               style="display:inline-flex;align-items:center;justify-content:center">
                                <img src="<?= H::e($docIconUrl) ?>" alt="<?= H::e($docExt ?: 'file') ?>" style="width:20px;height:20px;object-fit:contain">
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
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

<!-- กล่องขยายรูปใบเซอร์ -->
<div id="certLightboxModal"
     onclick="if(event.target===this){closeCertLightbox();}"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.75);align-items:center;justify-content:center;padding:20px">
    <div style="position:relative;max-width:92vw;max-height:90vh;background:#fff;border-radius:10px;padding:14px 16px 16px;box-shadow:0 20px 50px rgba(0,0,0,.35);display:flex;flex-direction:column;gap:10px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;border-bottom:1px solid #e2e8f0;padding-bottom:8px">
            <strong id="certLightboxTitle" style="font-size:14px;color:#0f172a">ตัวอย่างใบเซอร์</strong>
            <button type="button" onclick="closeCertLightbox()" aria-label="ปิด"
                    style="border:none;background:#f1f5f9;color:#334155;width:28px;height:28px;border-radius:6px;font-size:18px;line-height:1;cursor:pointer">&times;</button>
        </div>
        <div style="overflow:auto;display:flex;align-items:center;justify-content:center;max-height:calc(90vh - 76px)">
            <img id="certLightboxImg" src="" alt="ตัวอย่างใบเซอร์ขยาย" style="max-width:100%;max-height:calc(90vh - 84px);object-fit:contain;border-radius:4px">
        </div>
    </div>
</div>
<?php endif; ?>

<script>
if (document.getElementById('courseTitleInput')) {
    document.getElementById('courseTitleInput').addEventListener('input', function() {
        var title = this.value;
        var slug = title.toLowerCase()
                        .replace(/[^\w\u0E00-\u0E7F]+/g, '-')
                        .replace(/^-+|-+$/g, '');
        if (slug.length > 50) { slug = slug.substring(0, 50); }
        document.getElementById('courseSlugInput').value = slug;
        document.getElementById('slugTextSpan').innerText = slug;
    });
}

function editSlugPrompt() {
    var current = document.getElementById('courseSlugInput').value;
    var newSlug = prompt('แก้ไข Slug (URL):', current);
    if (newSlug !== null) {
        var clean = newSlug.toLowerCase().replace(/[^\w-]+/g, '-');
        document.getElementById('courseSlugInput').value = clean;
        document.getElementById('slugTextSpan').innerText = clean;
    }
}

function openCertLightbox(url, title) {
    var modal = document.getElementById("certLightboxModal");
    var img   = document.getElementById("certLightboxImg");
    var cap   = document.getElementById("certLightboxTitle");
    if (!modal || !img) return;
    img.src = url;
    if (cap) {
        cap.textContent = title ? ("ตัวอย่างใบเซอร์ — " + title) : "ตัวอย่างใบเซอร์";
    }
    modal.style.display = "flex";
}

function closeCertLightbox() {
    var modal = document.getElementById("certLightboxModal");
    var img   = document.getElementById("certLightboxImg");
    if (modal) modal.style.display = "none";
    if (img) img.src = "";
}

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        closeCertLightbox();
    }
});
</script>