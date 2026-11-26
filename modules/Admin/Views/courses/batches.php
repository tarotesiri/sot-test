<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : batches.php
 * Description : All Course Batches View
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.35.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;

$batches   = is_array($batches ?? null) ? $batches : [];
$filters   = is_array($filters ?? null) ? $filters : [];
$qVal      = (string)($filters['q'] ?? '');
$statusVal = (string)($filters['status'] ?? '');
?>
<div class="adm-page-head">
    <h1>หลักสูตร/รุ่นที่จัดอบรม (<?= count($batches) ?> รุ่น)</h1>
    <a class="adm-btn is-light" href="<?= H::e(H::url('admin/courses')) ?>">&larr; หลักสูตรทั้งหมด</a>
</div>

<section class="adm-card" style="margin-bottom:16px">
    <div class="adm-card-body">
        <form method="get" action="<?= H::e(H::url('admin/batches')) ?>" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input class="adm-input" type="text" name="q" value="<?= H::e($qVal) ?>" placeholder="ค้นหาชื่อหลักสูตร หรือรุ่น..." style="flex:1;min-width:220px">
            <select class="adm-input" name="status" style="width:auto;min-width:150px">
                <option value="">ทุกสถานะ</option>
                <?php foreach (H::courseStatuses() as $k => $l): ?>
                    <option value="<?= H::e($k) ?>"<?= H::sel($statusVal, $k) ?>><?= H::e($l) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="adm-btn is-primary is-sm">ค้นหา</button>
            <?php if ($qVal !== '' || $statusVal !== ''): ?>
                <a href="<?= H::e(H::url('admin/batches')) ?>" class="adm-btn is-light is-sm">ล้างตัวกรอง</a>
            <?php endif; ?>
        </form>
    </div>
</section>

<section class="adm-card">
    <div class="adm-table-wrap">
        <table class="adm-table" id="allBatchesTable" style="table-layout:fixed;width:100%">
            <colgroup>
                <col style="width:24%"> <!-- หลักสูตร -->
                <col style="width:8%">  <!-- รุ่น -->
                <col style="width:11%"> <!-- วันอบรม -->
                <col style="width:10%"> <!-- เวลา -->
                <col style="width:4%">  <!-- แบบทดสอบ: จำนวน -->
                <col style="width:4%">  <!-- แบบทดสอบ: เกณฑ์ -->
                <col style="width:4%">  <!-- แบบทดสอบ: ทำซ้ำ -->
                <col style="width:5%">  <!-- ใบเซอร์ -->
                <col style="width:4%">  <!-- เอกสาร -->
                <col style="width:4%">  <!-- จำนวนผู้เข้าอบรม: อนุมัติ -->
                <col style="width:4%">  <!-- จำนวนผู้เข้าอบรม: ผ่าน -->
                <col style="width:4%">  <!-- จำนวนผู้เข้าอบรม: ปริ้น -->
                <col style="width:7%">  <!-- Oreder รออนุมัติ -->
                <col style="width:5%">  <!-- สถานะ -->
                <col style="width:6%">  <!-- จัดการ -->
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align:middle;padding:8px 10px">หลักสูตร</th>
                    <th rowspan="2" style="vertical-align:middle;padding:8px 8px">รุ่น</th>
                    <th rowspan="2" style="vertical-align:middle;padding:8px 8px">วันอบรม</th>
                    <th rowspan="2" style="vertical-align:middle;padding:8px 8px">เวลา</th>
                    <th colspan="3" style="text-align:center;padding:6px 4px">แบบทดสอบ (ข้อสอบ)</th>
                    <th rowspan="2" style="vertical-align:middle;text-align:center;padding:8px 6px">ใบเซอร์</th>
                    <th rowspan="2" style="vertical-align:middle;text-align:center;padding:8px 6px">เอกสาร</th>
                    <th colspan="3" style="text-align:center;padding:6px 4px">จำนวนผู้เข้าอบรม</th>
                    <th rowspan="2" style="vertical-align:middle;text-align:center;padding:8px 4px">Oreder<br>รออนุมัติ</th>
                    <th rowspan="2" style="vertical-align:middle;text-align:center;padding:8px 6px">สถานะ</th>
                    <th rowspan="2" style="vertical-align:middle;text-align:center;padding:8px 6px"></th>
                </tr>
                <tr>
                    <th style="text-align:center;padding:6px 4px">จำนวน</th>
                    <th style="text-align:center;padding:6px 4px">เกณฑ์</th>
                    <th style="text-align:center;padding:6px 4px">ทำซ้ำ</th>
                    <th style="text-align:center;padding:6px 4px">อนุมัติ</th>
                    <th style="text-align:center;padding:6px 4px">ผ่าน</th>
                    <th style="text-align:center;padding:6px 4px">ปริ้น</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($batches === []): ?>
                <tr><td colspan="15" class="adm-empty">ไม่พบรายการรุ่นอบรม</td></tr>
            <?php endif; ?>
            <?php foreach ($batches as $b):
                $cid  = (int)($b['course_id'] ?? 0);
                $bid  = (int)($b['id'] ?? 0);
                $base = 'admin/courses/' . $cid;

                $topics = H::decodeJsonSafe($b['topics_json'] ?? '');
                $hasQuiz = false;
                $quizCount = 0;
                $passGrades = [];
                $attemptLimits = [];

                foreach ($topics as $top) {
                    if (!is_array($top)) {
                        continue;
                    }
                    foreach (($top['lessons'] ?? []) as $les) {
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

                $approvedCount = (int)($b['approved_count'] ?? 0);
                $passedCount   = (int)($b['passed_count'] ?? 0);
                $printedCount  = (int)($b['printed_count'] ?? 0);

                $pendingOrderCount = (int)($b['order_pending_orders_count'] ?? 0);

                $pendingOrderAttendeesCount = 0;
                foreach (['order_pending_attendees_count', 'pending_order_attendees_count', 'pending_attendees_count', 'pending_approval_count'] as $pKey) {
                    if (array_key_exists($pKey, $b) && is_numeric((string)$b[$pKey])) {
                        $pendingOrderAttendeesCount = (int)$b[$pKey];
                        break;
                    }
                }

                $batchOrdersUrl = H::url('admin/order_batches');
                $batchOrdersUrl .= (str_contains($batchOrdersUrl, '?') ? '&' : '?')
                    . 'course_id=' . $cid
                    . '&batch_id=' . $bid;
            ?>
                <tr>
                    <td style="padding:10px;line-height:1.45;word-break:break-word">
                        <a href="<?= H::e(H::url($base . '/edit')) ?>" style="font-weight:600;color:#0d6efd;text-decoration:none">
                            <?= H::e($b['course_title'] ?? ('หลักสูตร #' . $cid)) ?>
                        </a>
                    </td>
                    <td class="adm-strong" style="padding:10px 8px;line-height:1.4;word-break:break-word"><?= H::e($b['batch_no'] ?? 'รุ่น') ?></td>
                    <td style="padding:10px 8px;line-height:1.4;word-break:break-word"><?= H::e($b['date_text'] ?? '-') ?></td>
                    <td style="padding:10px 8px;line-height:1.4;word-break:break-word"><?= H::e($b['time_text'] ?? '-') ?></td>
                    <td style="text-align:center;padding:10px 4px;color:#64748b">
                        <?php if ($quizIsZero): ?>
                            <span style="<?= $zeroBadgeStyle ?>"><?= H::e($quizDisplay) ?></span>
                        <?php else: ?>
                            <?= H::e($quizDisplay) ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;padding:10px 4px;color:#64748b"><?= H::e($passDisplay) ?></td>
                    <td style="text-align:center;padding:10px 4px;color:#64748b">
                        <?php if ($retryIsZero): ?>
                            <span style="<?= $zeroBadgeStyle ?>"><?= H::e($retryDisplay) ?></span>
                        <?php else: ?>
                            <?= H::e($retryDisplay) ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;padding:10px 6px;color:#64748b">
                        <?php if ($certUrl !== ''): ?>
                            <?php if ($certExt === 'pdf'): ?>
                                <a href="<?= H::e($certUrl) ?>" target="_blank" title="เปิดดูใบเซอร์ (PDF)" style="display:inline-flex;align-items:center;justify-content:center">
                                    <img src="<?= H::e(H::asset('img/icons/pdf.png')) ?>" alt="PDF" style="width:20px;height:20px;object-fit:contain">
                                </a>
                            <?php else: ?>
                                <button type="button"
                                        onclick="openCertLightbox(<?= H::e(json_encode($certUrl)) ?>, <?= H::e(json_encode(($b['course_title'] ?? '') . ' - ' . ($b['batch_no'] ?? ''))) ?>)"
                                        title="คลิกเพื่อขยายดูใบเซอร์"
                                        style="background:none;border:none;padding:0;cursor:pointer;display:inline-flex;align-items:center;justify-content:center">
                                    <img src="<?= H::e($certUrl) ?>" alt="ใบเซอร์" style="width:42px;height:30px;object-fit:cover;border:1px solid #cbd5e1;border-radius:4px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.06)">
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;padding:10px 6px;color:#64748b">
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
                    <td style="text-align:center;padding:10px 4px"><?= $approvedCount ?></td>
                    <td style="text-align:center;padding:10px 4px"><?= $passedCount ?></td>
                    <td style="text-align:center;padding:10px 4px"><?= $printedCount ?></td>
                    <td style="text-align:center;padding:10px 4px;line-height:1.3">
                        <a href="<?= H::e($batchOrdersUrl) ?>"
                           title="ดูคำสั่งซื้อของรุ่นนี้"
                           style="display:inline-block;text-decoration:none;color:#1d4ed8">
                            <div style="font-weight:700;text-decoration:underline"><?= $pendingOrderCount ?> Order</div>
                            <div style="font-size:0.75rem;color:#64748b">(<?= $pendingOrderAttendeesCount ?> คน)</div>
                        </a>
                    </td>
                    <td style="text-align:center;padding:10px 6px"><?= H::badge($b['status'] ?? 'open') ?></td>
                    <td style="text-align:center;padding:10px 6px">
                        <div class="adm-actions" style="justify-content:center">
                            <a class="adm-btn is-light is-sm" href="<?= H::e(H::url($base . '/batches/' . $bid . '/edit')) ?>">แก้ไข</a>
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

<script>
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