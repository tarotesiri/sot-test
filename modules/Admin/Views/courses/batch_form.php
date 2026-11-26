<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : batch_form.php
 * Description : Batch Form v.5.25
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.25.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;

$course = $course ?? []; $batch = $batch ?? []; $isNew = $isNew ?? true;
$cid = (int)$course['id']; $bid = (int)($batch['id'] ?? 0);
$base = 'admin/courses/' . $cid;
$actionUrl = $isNew ? H::url($base . '/batches/store') : H::url($base . '/batches/' . $bid . '/update');
$topics = H::decodeJsonSafe($batch['topics_json'] ?? '');
$certSet = H::decodeJsonSafe($batch['cert_settings'] ?? '');
if(!$certSet) { $certSet = ['name_x'=>100, 'name_y'=>200, 'date_x'=>100, 'date_y'=>300]; }
$pubVal = !empty($batch['published_at']) ? date('Y-m-d\TH:i', strtotime($batch['published_at'])) : date('Y-m-d\TH:i');

// รับค่าจำนวนวันตั้งค่ากลางที่ส่งมาจาก Controller
$defaultPaymentDays = (int)($defaultPaymentDays ?? 20);
?>
<div class="adm-page-head">
    <h1><?= $isNew ? 'เพิ่มรุ่นอบรม' : 'แก้ไขรุ่น: ' . H::e($batch['batch_no']) ?></h1>
    <a class="adm-btn is-light" href="<?= H::e(H::url($base . '/edit')) ?>">&larr; กลับหลักสูตร</a>
</div>

<form id="batch-form" class="adm-batch-layout" method="post" enctype="multipart/form-data" action="<?= H::e($actionUrl) ?>">
    <?= H::csrf() ?>
    
    <!-- คอลัมน์ซ้าย: ข้อมูลเนื้อหาหลักสูตร -->
    <div class="adm-batch-main">
        <div class="adm-card">
            <div class="adm-card-head"><h2>1. ข้อมูลรุ่นและรายละเอียด</h2></div>
            <div class="adm-card-body">
                <div class="adm-grid-3">
                    <label class="adm-field"><span class="adm-label">รุ่นที่ *</span><input class="adm-input" name="batch_no" required value="<?= H::e($batch['batch_no'] ?? '') ?>" placeholder="รุ่นที่ 1"></label>
                    <label class="adm-field"><span class="adm-label">วันที่เริ่มอบรมวันแรก</span><input class="adm-input" type="date" name="date_start" value="<?= H::e(!empty($batch['date_start']) ? substr((string)$batch['date_start'], 0, 10) : '') ?>"></label>
                    <label class="adm-field"><span class="adm-label">วันที่อบรม (Text)</span><input class="adm-input" name="date_text" value="<?= H::e($batch['date_text'] ?? '') ?>" placeholder="12-13 พ.ค. 2569"></label>
                </div>
                <div class="adm-grid-3" style="margin-top:14px">
                    <label class="adm-field"><span class="adm-label">เวลา (Text)</span><input class="adm-input" name="time_text" value="<?= H::e($batch['time_text'] ?? '') ?>" placeholder="09:00 - 16:00 น."></label>
                    <label class="adm-field"><span class="adm-label">ระยะเวลาอบรม</span><input class="adm-input" name="duration_text" value="<?= H::e($batch['duration_text'] ?? '') ?>" placeholder="2 วัน / 12 ชม."></label>
                    <label class="adm-field"><span class="adm-label">สถานที่อบรม</span><input class="adm-input" name="location_text" value="<?= H::e($batch['location_text'] ?? '') ?>" placeholder="Zoom / โรงแรม..."></label>
                </div>

                <label class="adm-field is-full" style="margin-top:14px"><span class="adm-label">รายละเอียดหลักสูตร (ประจำรุ่น)</span><textarea class="adm-input" name="description" rows="4"><?= H::e($batch['description'] ?? '') ?></textarea></label>

                <!-- โครงสร้างเนื้อหา Step 5.19 -->
                <div style="margin-top:20px;border-top:1px dashed #cbd5e1;padding-top:14px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <h3>1.1 & 1.2 โครงสร้างเนื้อหา (หัวข้อ, บทเรียน และแบบทดสอบ)</h3>
                        <button type="button" class="adm-btn is-primary is-sm" onclick="openTopicModalForAdd()">+ เพิ่มหัวข้อหลัก</button>
                    </div>
                    <p class="adm-muted" style="margin-bottom:14px">คลิกเพิ่มหัวข้อหลัก และกดขยายเพื่อจัดการบทเรียน/แบบทดสอบ (สลับตำแหน่งได้)</p>
                    
                    <div id="topics-container">
                        <?php foreach($topics as $ti => $top): ?>
                            <div class="topic-box" style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;margin-bottom:12px;overflow:hidden">
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:#f1f5f9;border-bottom:1px solid #e2e8f0">
                                    <div style="display:flex;align-items:center;gap:10px;cursor:pointer;flex:1" onclick="toggleTopicAccordion(this)">
                                        <span class="accordion-icon" style="font-weight:bold">&#9658;</span>
                                        <strong class="topic-title-text"><?= H::e($top['title'] ?? 'หัวข้อหลัก') ?></strong>
                                        <span class="adm-muted" style="font-size:13px">(<?= count($top['lessons'] ?? []) ?> รายการ)</span>
                                    </div>
                                    <div style="display:flex;gap:6px">
                                        <input type="hidden" name="topic_title[]" value="<?= H::e($top['title'] ?? '') ?>">
                                        <textarea style="display:none" name="topic_summary[]"><?= H::e($top['summary'] ?? '') ?></textarea>
                                        <button type="button" class="adm-btn is-light is-sm" onclick="openTopicModalForEdit(this)">แก้ไข</button>
                                        <button type="button" class="adm-btn is-danger-ghost is-sm" onclick="this.closest('.topic-box').remove()">ลบ</button>
                                    </div>
                                </div>
                                
                                <div class="topic-content-body" style="display:none;padding:16px">
                                    <div class="adm-muted topic-sum-text" style="margin-bottom:10px"><em>สรุปหัวข้อ:</em> <?= H::e($top['summary'] ?? '-') ?></div>
                                    <div class="lessons-box" style="padding:12px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;border-left:4px solid #2563eb">
                                        <div style="margin-bottom:8px"><strong>รายการบทเรียน / แบบทดสอบย่อย (จัดลำดับได้):</strong></div>
                                        <div class="lessons-list" ondragover="allowDrop(event)">
                                            <?php foreach(($top['lessons'] ?? []) as $li => $les): ?>
                                                <?php 
                                                    $lesData = is_array($les) ? $les : ['title' => (string)$les, 'type' => 'lesson', 'description' => ''];
                                                    $isQuiz = ($lesData['type'] ?? '') === 'quiz';
                                                    $qTitle = $lesData['title'] ?? '';
                                                    $qDesc = $lesData['description'] ?? '';
                                                ?>
                                                <div style="display:flex;gap:8px;margin-top:8px;align-items:center;background:#fff;padding:6px;border:1px solid #e2e8f0;border-radius:6px" class="lesson-row" draggable="true" ondragstart="drag(event)" ondragover="allowDrop(event)" ondrop="drop(event)">
                                                    <span style="cursor:grab;color:#94a3b8;font-weight:bold;padding:0 4px" title="ลากเพื่อสลับตำแหน่ง">☰</span>
                                                    <span class="adm-badge <?= $isQuiz ? 'is-draft' : 'is-active' ?>" style="min-width:80px;text-align:center"><?= $isQuiz ? 'แบบทดสอบ' : 'บทเรียน' ?></span>
                                                    <input type="hidden" name="lesson_type[<?= $ti ?>][]" value="<?= $isQuiz ? 'quiz' : 'lesson' ?>">
                                                    <input class="adm-input" name="lesson_title[<?= $ti ?>][]" value="<?= H::e($qTitle) ?>" readonly style="background:#f8fafc;cursor:pointer" onclick="openConfigModalFromRow(this)" title="คลิกเพื่อแก้ไข">
                                                    <input type="hidden" name="lesson_desc[<?= $ti ?>][]" value="<?= H::e(is_array($qDesc) ? json_encode($qDesc, JSON_UNESCAPED_UNICODE) : $qDesc) ?>">
                                                    
                                                    <div style="display:flex;flex-direction:column;gap:2px">
                                                        <button type="button" class="adm-btn is-light is-sm" style="padding:0 4px;font-size:10px;line-height:1" onclick="moveItem(this, 'up')">▲</button>
                                                        <button type="button" class="adm-btn is-light is-sm" style="padding:0 4px;font-size:10px;line-height:1" onclick="moveItem(this, 'down')">▼</button>
                                                    </div>

                                                    <button type="button" class="adm-btn is-light is-sm" onclick="openConfigModalFromRow(this)">แก้ไข</button>
                                                    <button type="button" class="adm-btn is-danger-ghost is-sm" onclick="this.closest('.lesson-row').remove()">ลบ</button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div style="display:flex;gap:8px;margin-top:10px">
                                            <button type="button" class="adm-btn is-light is-sm" onclick="openAddModal(this, <?= $ti ?>, 'lesson')">+ เพิ่มบทเรียน</button>
                                            <button type="button" class="adm-btn is-light is-sm" onclick="openAddModal(this, <?= $ti ?>, 'quiz')">+ เพิ่มแบบทดสอบ</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2 & 3. แบบประเมินผล และ แบบสอบถาม -->
        <div class="adm-card">
            <div class="adm-card-head"><h2>2 & 3. แบบประเมินผล และ แบบสอบถาม</h2></div>
            <div class="adm-card-body adm-form-grid">
                <label class="adm-field">
                    <span class="adm-label">เลือกรายการแบบประเมินผล (มีเมนูใหญ่แยกต่างหาก)</span>
                    <select class="adm-input" name="evaluation_id">
                        <option value="0">— เลือกแบบประเมินผล —</option>
                        <option value="1"<?= H::sel($batch['evaluation_id'] ?? 0, 1) ?>>แบบประเมินความพึงพอใจหลักสูตรมาตรฐาน</option>
                    </select>
                </label>
                <label class="adm-field">
                    <span class="adm-label">เลือกรายการแบบสอบถาม (มีเมนูใหญ่แยกต่างหาก)</span>
                    <select class="adm-input" name="survey_id">
                        <option value="0">— เลือกแบบสอบถาม —</option>
                        <option value="1"<?= H::sel($batch['survey_id'] ?? 0, 1) ?>>แบบสอบถามความต้องการอบรมเพิ่มเติม</option>
                    </select>
                </label>
            </div>
        </div>

        <!-- 4. รูปแบบฟอร์มใบเซอร์พร้อมปุ่มพรีวิวจำลองพิกัด -->
        <div class="adm-card">
            <div class="adm-card-head" style="display:flex;justify-content:space-between;align-items:center">
                <h2>4. รูปแบบฟอร์มของใบเซอร์ (Certificate template)</h2>
                <button type="button" class="adm-btn is-light is-sm" onclick="openCertPreviewModal()">ดูตัวอย่างใบเซอร์</button>
            </div>
            <div class="adm-card-body">
                <label class="adm-field" style="max-width:400px">
                    <span class="adm-label">4.1 Upload Template Certificate</span>
                    <input class="adm-input" type="file" name="cert_file" id="certInput" accept="image/*,.pdf" onchange="previewCertImg(this)">
                </label>
                <div style="margin-top:10px">
                    <img id="certPreview" src="<?= !empty($batch['cert_template']) ? H::e(H::uploadUrl($batch['cert_template'])) : '#' ?>" style="max-width:300px;width:auto;height:auto;border:1px solid #cbd5e1;border-radius:6px;display:<?= !empty($batch['cert_template']) ? 'block' : 'none' ?>">
                    <?php if(!empty($batch['cert_template'])): ?>
                        <div style="margin-top:6px">
                            <label style="font-size:13px;color:#b91c1c;cursor:pointer"><input type="checkbox" name="remove_cert" value="1"> ลบรูปใบเซอร์นี้</label>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h4 style="margin:16px 0 8px">4.2 ตั้งค่าตำแหน่งข้อมูลเพื่อปริ้นลงใบเซอร์</h4>
                <div class="adm-grid-2" style="background:#f8fafc;padding:12px;border-radius:8px">
                    <div>
                        <strong>4.2.1 ชื่อ (คำนำหน้า + ชื่อ + นามสกุล)</strong>
                        <div style="display:flex;gap:10px;margin-top:6px">
                            <input class="adm-input" type="number" name="cert_name_x" id="cert_name_x" value="<?= $certSet['name_x'] ?>" placeholder="พิกัด X">
                            <input class="adm-input" type="number" name="cert_name_y" id="cert_name_y" value="<?= $certSet['name_y'] ?>" placeholder="พิกัด Y">
                        </div>
                    </div>
                    <div>
                        <strong>4.2.2 วันที่ออกใบเซอร์</strong>
                        <div style="display:flex;gap:10px;margin-top:6px">
                            <input class="adm-input" type="number" name="cert_date_x" id="cert_date_x" value="<?= $certSet['date_x'] ?>" placeholder="พิกัด X">
                            <input class="adm-input" type="number" name="cert_date_y" id="cert_date_y" value="<?= $certSet['date_y'] ?>" placeholder="พิกัด Y">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ตั้งค่าราคาและเงื่อนไขการชำระเงิน -->
        <div class="adm-card">
            <div class="adm-card-head"><h2>ตั้งค่าราคาและเงื่อนไขการชำระเงิน</h2></div>
            <div class="adm-card-body adm-form-grid">
                <label class="adm-field">
                    <span class="adm-label">ประเภทราคา</span>
                    <select class="adm-input" name="price_type" id="price_type_sel">
                        <option value="single"<?= H::sel($batch['price_type'] ?? 'member_split', 'single') ?>>ราคาค่าอบรม</option>
                        <option value="member_split"<?= H::sel($batch['price_type'] ?? 'member_split', 'member_split') ?>>ราคาค่าอบรมใช้สิทธิสมาชิก</option>
                    </select>
                </label>
                <label class="adm-field">
                    <span class="adm-label">ประเภทการชำระเงิน</span>
                    <select class="adm-input" name="payment_type" id="pay_type_sel">
                        <option value="single"<?= H::sel($batch['payment_type'] ?? 'single', 'single') ?>>ชำระก่อนวันเริ่มอบรมวันแรก</option>
                        <option value="days_before"<?= H::sel($batch['payment_type'] ?? 'single', 'days_before') ?>>ชำระก่อนถึงวันอบรมวันแรก <?= $defaultPaymentDays ?> วัน</option>
                    </select>
                </label>
            </div>

            <div class="adm-card-body">
                <h3>รายการเรตราคา</h3>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead><tr><th>เรตราคา</th><th style="width:160px">ราคา (บาท)</th><th style="width:120px">สถานะ</th><th>จำนวนที่รับสมัคร</th></tr></thead>
                        <tbody>
                            <!-- แถวที่ 1 -->
                            <tr class="row-rate-1">
                                <td class="adm-strong" id="label-rate-1">ราคาค่าอบรม</td>
                                <td><input class="adm-input adm-price-input" type="number" step="0.01" min="0" name="price_1" value="<?= $batch['price_1'] ?? 0 ?>"></td>
                                <td><select class="adm-input" name="status_1"><option value="open"<?= H::sel($batch['status_1'] ?? 'open', 'open') ?>>เปิด</option><option value="closed"<?= H::sel($batch['status_1'] ?? 'open', 'closed') ?>>ปิด</option><option value="booked"<?= H::sel($batch['status_1'] ?? 'open', 'booked') ?>>จอง</option></select></td>
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px">
                                        <div style="display:flex;gap:10px;align-items:center">
                                            <label><input type="checkbox" name="limit_enabled_1" value="1"<?= !empty($batch['limit_enabled_1']) ? ' checked' : '' ?>> จำกัดจำนวน</label>
                                            <input class="adm-input adm-price-input" type="number" min="0" name="capacity_1" value="<?= (int)($batch['capacity_1'] ?? 0) ?>" placeholder="0">
                                        </div>
                                        <small class="adm-muted" style="font-style:italic">0 คือไม่จำกัด ตัวเลขมากกว่า 0 คือจำนวนที่รับเพิ่มคงเหลือ</small>
                                    </div>
                                </td>
                            </tr>
                            <!-- แถวที่ 2 -->
                            <tr class="row-rate-2" style="display:none">
                                <td class="adm-strong" id="label-rate-2">ราคาสำหรับสมาชิก</td>
                                <td><input class="adm-input adm-price-input" type="number" step="0.01" min="0" name="price_2" value="<?= $batch['price_2'] ?? 0 ?>"></td>
                                <td><select class="adm-input" name="status_2"><option value="open"<?= H::sel($batch['status_2'] ?? 'open', 'open') ?>>เปิด</option><option value="closed"<?= H::sel($batch['status_2'] ?? 'open', 'closed') ?>>ปิด</option><option value="booked"<?= H::sel($batch['status_2'] ?? 'open', 'booked') ?>>จอง</option></select></td>
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px">
                                        <div style="display:flex;gap:10px;align-items:center">
                                            <label><input type="checkbox" name="limit_enabled_2" value="1"<?= !empty($batch['limit_enabled_2']) ? ' checked' : '' ?>> จำกัดจำนวน</label>
                                            <input class="adm-input adm-price-input" type="number" min="0" name="capacity_2" value="<?= (int)($batch['capacity_2'] ?? 0) ?>" placeholder="0">
                                        </div>
                                        <small class="adm-muted" style="font-style:italic">0 คือไม่จำกัด ตัวเลขมากกว่า 0 คือจำนวนที่รับเพิ่มคงเหลือ</small>
                                    </div>
                                </td>
                            </tr>
                            <!-- แถวที่ 3 -->
                            <tr class="row-rate-3" style="display:none">
                                <td class="adm-strong" id="label-rate-3">ชำระก่อนถึงวันอบรมวันแรก <?= $defaultPaymentDays ?> วัน</td>
                                <td><input class="adm-input adm-price-input" type="number" step="0.01" min="0" name="price_3" value="<?= $batch['price_3'] ?? 0 ?>"></td>
                                <td><select class="adm-input" name="status_3"><option value="open"<?= H::sel($batch['status_3'] ?? 'open', 'open') ?>>เปิด</option><option value="closed"<?= H::sel($batch['status_3'] ?? 'open', 'closed') ?>>ปิด</option><option value="booked"<?= H::sel($batch['status_3'] ?? 'open', 'booked') ?>>จอง</option></select></td>
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px">
                                        <div style="display:flex;gap:10px;align-items:center">
                                            <label><input type="checkbox" name="limit_enabled_3" value="1"<?= !empty($batch['limit_enabled_3']) ? ' checked' : '' ?>> จำกัดจำนวน</label>
                                            <input class="adm-input adm-price-input" type="number" min="0" name="capacity_3" value="<?= (int)($batch['capacity_3'] ?? 0) ?>" placeholder="0">
                                        </div>
                                        <small class="adm-muted" style="font-style:italic">0 คือไม่จำกัด ตัวเลขมากกว่า 0 คือจำนวนที่รับเพิ่มคงเหลือ</small>
                                    </div>
                                </td>
                            </tr>
                            <!-- แถวที่ 4 -->
                            <tr class="row-rate-4" style="display:none">
                                <td class="adm-strong" id="label-rate-4">ราคาสำหรับสมาชิกชำระก่อนถึงวันอบรมวันแรก <?= $defaultPaymentDays ?> วัน</td>
                                <td><input class="adm-input adm-price-input" type="number" step="0.01" min="0" name="price_4" value="<?= $batch['price_4'] ?? 0 ?>"></td>
                                <td><select class="adm-input" name="status_4"><option value="open"<?= H::sel($batch['status_4'] ?? 'open', 'open') ?>>เปิด</option><option value="closed"<?= H::sel($batch['status_4'] ?? 'open', 'closed') ?>>ปิด</option><option value="booked"<?= H::sel($batch['status_4'] ?? 'open', 'booked') ?>>จอง</option></select></td>
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px">
                                        <div style="display:flex;gap:10px;align-items:center">
                                            <label><input type="checkbox" name="limit_enabled_4" value="1"<?= !empty($batch['limit_enabled_4']) ? ' checked' : '' ?>> จำกัดจำนวน</label>
                                            <input class="adm-input adm-price-input" type="number" min="0" name="capacity_4" value="<?= (int)($batch['capacity_4'] ?? 0) ?>" placeholder="0">
                                        </div>
                                        <small class="adm-muted" style="font-style:italic">0 คือไม่จำกัด ตัวเลขมากกว่า 0 คือจำนวนที่รับเพิ่มคงเหลือ</small>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- เอกสารแนบ Download (ไว้ก่อน Remark) -->
        <div class="adm-card">
            <div class="adm-card-head"><h2>เอกสารแนบ Download</h2></div>
            <div class="adm-card-body adm-form-grid">
                <label class="adm-field">
                    <span class="adm-label">ชื่อเอกสาร Download</span>
                    <input class="adm-input" name="document_title" value="<?= H::e($batch['document_title'] ?? '') ?>" placeholder="คู่มืออบรม.pdf">
                </label>
                <label class="adm-field">
                    <span class="adm-label">แนบไฟล์เอกสาร Download</span>
                    <input class="adm-input" type="file" name="doc_file">
                </label>
                <?php if(!empty($batch['document_file'])): ?>
                    <div class="adm-field is-full">
                        <p class="adm-small adm-muted">ไฟล์ปัจจุบัน: <a href="<?= H::e(H::uploadUrl($batch['document_file'])) ?>" target="_blank">ดาวน์โหลดไฟล์เดิม</a></p>
                        <label style="font-size:13px;color:#b91c1c;cursor:pointer"><input type="checkbox" name="remove_doc" value="1"> ลบไฟล์เอกสารนี้</label>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- หมายเหตุ (Remark) -->
        <div class="adm-card">
            <div class="adm-card-head"><h2>หมายเหตุ (Remark)</h2></div>
            <div class="adm-card-body">
                <label class="adm-field">
                    <textarea class="adm-input" name="remark" rows="3" placeholder="ระบุหมายเหตุเพิ่มเติม (ถ้ามี)"><?= H::e($batch['remark'] ?? '') ?></textarea>
                </label>
            </div>
        </div>
    </div>

    <!-- คอลัมน์ขวา: สไตล์ WordPress (สถานะ, วันที่เผยแพร่, บันทึกล่าสุด และปุ่มบันทึก) -->
    <div class="adm-batch-sidebar">
        <!-- กล่องสถานะและเวลา (WordPress Style) -->
        <div class="adm-card" style="background:#fff;border-color:#cbd5e1">
            <div class="adm-card-head" style="background:#f8fafc"><h2>เผยแพร่รุ่นอบรม</h2></div>
            <div class="adm-card-body" style="font-size:13px;display:flex;flex-direction:column;gap:12px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-weight:600">สถานะ:</span>
                    <select class="adm-input" name="status" style="padding:4px 8px;font-size:13px;width:auto">
                        <?php foreach(H::courseStatuses() as $k=>$l): ?>
                            <option value="<?= $k ?>"<?= H::sel($batch['status'] ?? 'open', $k) ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- เพิ่มฟิลด์ วันที่เผยแพร่ แก้ไขเองได้ -->
                <div class="adm-field">
                    <span style="font-weight:600;display:block;margin-bottom:4px">วันที่เผยแพร่:</span>
                    <input class="adm-input" type="datetime-local" name="published_at" value="<?= $pubVal ?>" style="font-size:13px;padding:4px 8px">
                </div>

                <?php if(!$isNew && !empty($batch['updated_at'])): ?>
                    <div style="color:#475569;border-top:1px dashed #e2e8f0;padding-top:8px">
                        <strong>บันทึกล่าสุด:</strong> <?= H::e($batch['updated_at']) ?>
                    </div>
                <?php endif; ?>

                <div style="border-top:1px solid #e2e8f0;padding-top:12px;display:flex;justify-content:flex-end">
                    <button type="submit" class="adm-btn is-primary" style="width:100%;justify-content:center;padding:10px;font-size:15px" onclick="return confirm('คุณต้องการบันทึกข้อมูลรุ่นอบรมนี้ใช่หรือไม่?');">บันทึกข้อมูลรุ่น</button>
                </div>
            </div>
        </div>

        <!-- รูปโบรชัวร์ (พรีวิวกว้างไม่เกิน 200px) -->
        <div class="adm-card">
            <div class="adm-card-head"><h2>รูปภาพสินค้า (โบรชัวร์)</h2></div>
            <div class="adm-card-body">
                <label class="adm-field">
                    <input class="adm-input" type="file" name="brochure_img" accept="image/*" onchange="previewBrochure(this)">
                </label>
                <div style="margin-top:10px;text-align:center">
                    <img id="brochurePreview" src="<?= !empty($batch['brochure_image']) ? H::e(H::uploadUrl($batch['brochure_image'])) : '#' ?>" style="max-width:200px;width:100%;height:auto;border-radius:6px;border:1px solid #cbd5e1;display:<?= !empty($batch['brochure_image']) ? 'block' : 'none' ?>">
                    <?php if(!empty($batch['brochure_image'])): ?>
                        <div style="margin-top:6px">
                            <label style="font-size:13px;color:#b91c1c;cursor:pointer"><input type="checkbox" name="remove_brochure" value="1"> ลบรูปโบรชัวร์นี้</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- แกลเลอรีรูปภาพอื่นๆ (เลือกภาพที่จะลบได้) -->
        <div class="adm-card">
            <div class="adm-card-head"><h2>แกลเลอรีรูปภาพอื่นๆ</h2></div>
            <div class="adm-card-body">
                <label class="adm-field">
                    <input class="adm-input" type="file" name="gallery_imgs[]" multiple accept="image/*" onchange="previewGallery(this)">
                </label>
                <div id="galleryPreviewContainer" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px">
                    <?php 
                    $galleries = H::decodeJsonSafe($batch['gallery_images'] ?? '');
                    if(!empty($galleries)):
                        foreach($galleries as $gImg): 
                    ?>
                        <div style="text-align:center;border:1px solid #cbd5e1;padding:4px;border-radius:4px;background:#fff">
                            <img src="<?= H::e(H::uploadUrl($gImg)) ?>" style="width:90px;height:90px;object-fit:cover;border-radius:3px;display:block">
                            <label style="font-size:11px;color:#b91c1c;cursor:pointer;display:block;margin-top:4px">
                                <input type="checkbox" name="remove_gallery[]" value="<?= H::e($gImg) ?>"> ลบรูปนี้
                            </label>
                        </div>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal แสดงตัวอย่างใบเซอร์ตามพิกัด X/Y -->
<div id="certPreviewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;width:750px;border-radius:10px;padding:20px;box-shadow:0 6px 20px rgba(0,0,0,0.3);position:relative;max-height:90vh;overflow:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:10px;margin-bottom:14px">
            <h3 style="margin:0">ตัวอย่างใบเซอร์ (จำลองพิกัดจริง)</h3>
            <button type="button" onclick="closeCertPreviewModal()" style="border:0;background:none;font-size:22px;cursor:pointer">&times;</button>
        </div>
        <div id="certCanvasArea" style="position:relative;display:inline-block;width:100%;text-align:center;background:#eef2f6;border-radius:6px;overflow:hidden;min-height:300px">
            <img id="modalCertImg" src="" style="max-width:100%;height:auto;display:block;margin:0 auto">
            <div id="previewNameTag" style="position:absolute;font-weight:bold;color:#1e293b;font-size:18px;white-space:nowrap;pointer-events:none;transform:translate(-50%,-50%);border:1px dashed #1d4ed8;padding:2px 6px;background:rgba(255,255,255,0.7)">
                นายทดสอบ ระบบฝึกอบรม
            </div>
            <div id="previewDateTag" style="position:absolute;font-weight:bold;color:#1e293b;font-size:14px;white-space:nowrap;pointer-events:none;transform:translate(-50%,-50%);border:1px dashed #15803d;padding:2px 6px;background:rgba(255,255,255,0.7)">
                <?= date('d/m/Y') ?>
            </div>
        </div>
        <p class="adm-muted" style="margin-top:10px;text-align:center">กรอบเส้นประสีน้ำเงินคือตำแหน่งพิกัดชื่อ กรอบสีเขียวคือตำแหน่งพิกัดวันที่ตามที่คุณระบุ</p>
    </div>
</div>

<!-- Modal สำหรับเพิ่ม/แก้ไข หัวข้อหลัก -->
<div id="topicModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;width:550px;border-radius:10px;padding:20px;box-shadow:0 4px 12px rgba(0,0,0,0.15)">
        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:10px;margin-bottom:14px">
            <h3 id="topicModalTitle" style="margin:0">เพิ่มหัวข้อหลัก</h3>
            <button type="button" onclick="closeTopicModal()" style="border:0;background:none;font-size:18px;cursor:pointer">&times;</button>
        </div>
        <div class="adm-field" style="margin-bottom:12px">
            <label class="adm-label">ชื่อหัวข้อหลัก *</label>
            <input class="adm-input" id="modalTopicTitle" placeholder="เช่น บทที่ 1: พื้นฐานการใช้งาน">
        </div>
        <div class="adm-field" style="margin-bottom:16px">
            <label class="adm-label">ข้อความสรุปหัวข้อ</label>
            <textarea class="adm-input" id="modalTopicSummary" rows="3" placeholder="คำอธิบายย่อประจำหัวข้อ..."></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px">
            <button type="button" class="adm-btn is-light" onclick="closeTopicModal()">ยกเลิก</button>
            <button type="button" class="adm-btn is-primary" onclick="saveTopicModal()">บันทึกหัวข้อ</button>
        </div>
    </div>
</div>

<!-- Modal สำหรับบทเรียน (Lesson Modal) -->
<div id="lessonModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;width:600px;border-radius:10px;padding:20px;box-shadow:0 4px 12px rgba(0,0,0,0.15)">
        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:10px;margin-bottom:14px">
            <h3 id="lessonModalTitle" style="margin:0">เพิ่มบทเรียน</h3>
            <button type="button" onclick="closeLessonModal()" style="border:0;background:none;font-size:18px;cursor:pointer">&times;</button>
        </div>
        <div class="adm-field" style="margin-bottom:12px">
            <label class="adm-label">หัวเรื่องบทเรียน *</label>
            <input class="adm-input" id="modalLessonTitle" placeholder="เช่น แนะนำการใช้งานโปรแกรม">
        </div>
        <div class="adm-field" style="margin-bottom:16px">
            <label class="adm-label">รายละเอียดเนื้อหา</label>
            <textarea class="adm-input" id="modalLessonDesc" rows="5" placeholder="ระบุรายละเอียดเนื้อหาบทเรียน..."></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px">
            <button type="button" class="adm-btn is-light" onclick="closeLessonModal()">ยกเลิก</button>
            <button type="button" class="adm-btn is-primary" onclick="saveLessonModal()">บันทึก</button>
        </div>
    </div>
</div>

<!-- Modal สำหรับแบบทดสอบ (Quiz Modal พร้อม 3 Tabs) -->
<div id="quizModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;width:800px;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,0.2);max-height:92vh;display:flex;flex-direction:column">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e2e8f0">
            <h3 id="quizModalTitle" style="margin:0">เพิ่มแบบทดสอบ</h3>
            <button type="button" onclick="closeQuizModal()" style="border:0;background:none;font-size:20px;cursor:pointer">&times;</button>
        </div>
        <div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
            <div style="display:flex;border-bottom:1px solid #e2e8f0;background:#f8fafc;padding:0 20px">
                <button type="button" class="quiz-tab-btn is-active" onclick="switchQuizTab(0)" style="padding:12px 16px;border:0;background:none;cursor:pointer;font-weight:600;border-bottom:2px solid #1d4ed8;color:#1d4ed8">📋 ข้อมูลแบบทดสอบ</button>
                <button type="button" class="quiz-tab-btn" onclick="switchQuizTab(1)" style="padding:12px 16px;border:0;background:none;cursor:pointer;color:#64748b">❓ จำนวนข้อแบบทดสอบ</button>
                <button type="button" class="quiz-tab-btn" onclick="switchQuizTab(2)" style="padding:12px 16px;border:0;background:none;cursor:pointer;color:#64748b">⚙️ การตั้งค่า</button>
            </div>
            <div style="padding:20px;overflow-y:auto;flex:1">
                <!-- Tab 0: ข้อมูลแบบทดสอบ -->
                <div class="quiz-tab-pane" style="display:block">
                    <div class="adm-field" style="margin-bottom:12px">
                        <label class="adm-label">ชื่อแบบทดสอบ *</label>
                        <input class="adm-input" id="modalQuizTitle" placeholder="แบบทดสอบหลังอบรม">
                    </div>
                    <div class="adm-field">
                        <label class="adm-label">คำอธิบาย</label>
                        <textarea class="adm-input" id="modalQuizDesc" rows="4" placeholder="รายละเอียดและคำชี้แจงในการทำแบบทดสอบ..."></textarea>
                    </div>
                </div>

                <!-- Tab 1: จำนวนข้อแบบทดสอบ -->
                <div class="quiz-tab-pane" style="display:none">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                        <strong>รายการคำถามทั้งหมดในแบบทดสอบนี้</strong>
                        <button type="button" class="adm-btn is-primary is-sm" onclick="openQuestionModalForAdd()">+ เพิ่มคำถาม</button>
                    </div>
                    <div id="questions-list-container">
                        <div class="adm-empty" style="padding:20px;border:1px dashed #cbd5e1;border-radius:6px">ยังไม่มีข้อคำถาม กรุณากดปุ่ม "+ เพิ่มคำถาม"</div>
                    </div>
                </div>

                <!-- Tab 2: การตั้งค่า -->
                <div class="quiz-tab-pane" style="display:none">
                    <div class="adm-grid-2" style="margin-bottom:14px">
                        <label class="adm-field">
                            <span class="adm-label">จำกัดเวลา (นาที)</span>
                            <input class="adm-input" id="qTime" type="number" value="0" min="0">
                            <small class="adm-muted" style="font-style:italic">กำหนดเวลาสำหรับทำแบบทดสอบ ตั้งค่าเป็น 0 หมายถึง ไม่จำกัดเวลา</small>
                        </label>
                        <label class="adm-field">
                            <span class="adm-label">ผ่านเกณฑ์ (%)</span>
                            <input class="adm-input" id="qPassGrade" type="number" value="70" min="0" max="100">
                            <small class="adm-muted" style="font-style:italic">กำหนดเปอร์เซ็นต์สำหรับการผ่านแบบทดสอบนี้</small>
                        </label>
                    </div>

                    <div class="adm-field" style="margin-bottom:14px">
                        <span class="adm-label">จำนวนสิทธิที่ทำแบบทดสอบซ้ำได้ (ครั้ง)</span>
                        <input class="adm-input" id="qAttempts" type="number" value="0" min="0">
                        <small class="adm-muted" style="font-style:italic">กำหนดจำนวนครั้งที่ผู้เข้าอบรมจะสามารถทำแบบทดสอบนี้ซ้ำได้ ตั้งค่าเป็น 0 หมายถึง ยังไม่เปิดให้ทำแบบทดสอบ</small>
                    </div>

                    <div class="adm-field" style="margin-bottom:14px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                            <input type="checkbox" id="qAutoStart" style="width:18px;height:18px"> <strong>เริ่มการทดสอบอัตโนมัติ</strong>
                        </label>
                        <small class="adm-muted" style="margin-left:28px;font-style:italic">หากเลือกเปิดใช้งานตัวเลือกนี้ คำถามจะเริ่มโดยอัตโนมัติหลังจากโหลดหน้าคำถาม หากไม่เลือกเปิด จะมีปุ่มให้กดเรื่มทำแบบทดสอบ</small>
                    </div>

                    <div class="adm-grid-2" style="margin-bottom:14px">
                        <label class="adm-field">
                            <span class="adm-label">รูปแบบคำถาม</span>
                            <select class="adm-input" id="qLayout">
                                <option value="คำถามเดียว" selected>คำถามเดียว</option>
                                <option value="การแบ่งหน้าคำถาม">การแบ่งหน้าคำถาม</option>
                                <option value="แสดงทุกคำถาม">แสดงทุกคำถาม</option>
                            </select>
                        </label>
                        <label class="adm-field">
                            <span class="adm-label">การเรียงคำถาม</span>
                            <select class="adm-input" id="qSort">
                                <option value="ตามที่กำหนด" selected>ตามที่กำหนด</option>
                                <option value="สุ่ม">สุ่ม</option>
                                <option value="น้อยไปมาก">น้อยไปมาก</option>
                                <option value="มากไปน้อย">มากไปน้อย</option>
                            </select>
                        </label>
                    </div>

                    <div class="adm-field">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                            <input type="checkbox" id="qShowNum" checked style="width:18px;height:18px"> <strong>หมายเลขข้อคำถาม</strong>
                        </label>
                        <small class="adm-muted" style="margin-left:28px;font-style:italic">แสดง/ซ่อน หมายเลขข้อคำถามระหว่างการทำแบบทดสอบ</small>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;border-top:1px solid #e2e8f0;background:#f8fafc">
            <button type="button" class="adm-btn is-light" onclick="closeQuizModal()">ยกเลิก</button>
            <button type="button" class="adm-btn is-primary" onclick="saveQuizModal()">บันทึก</button>
        </div>
    </div>
</div>

<!-- Modal สำหรับเพิ่ม/แก้ไข ข้อคำถามเดี่ยว -->
<div id="questionModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
    <div style="background:#fff;width:720px;border-radius:10px;padding:20px;box-shadow:0 6px 20px rgba(0,0,0,0.2);max-height:90vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:10px;margin-bottom:14px">
            <h3 id="questionModalTitle" style="margin:0">เพิ่มข้อคำถาม</h3>
            <button type="button" onclick="closeQuestionModal()" style="border:0;background:none;font-size:18px;cursor:pointer">&times;</button>
        </div>

        <div class="adm-field" style="margin-bottom:12px">
            <label class="adm-label">คำถาม (รองรับ HTML / รูปภาพ) *</label>
            <textarea class="adm-input" id="qText" rows="3" placeholder="พิมพ์คำถาม หรือแท็ก HTML..."></textarea>
        </div>

        <div class="adm-field" style="margin-bottom:14px">
            <label class="adm-label">ประเภทคำถาม *</label>
            <select class="adm-input" id="qType" onchange="renderAnswerInputs()">
                <option value="ถูก/ผิด">ถูก/ผิด</option>
                <option value="ตัวเลือกเดี่ยว">ตัวเลือกเดี่ยว</option>
                <option value="หลายตัวเลือก">หลายตัวเลือก</option>
                <option value="คำถามเปิด/เรียความ">คำถามเปิด/เรียความ</option>
                <option value="เติมคำในช่องว่าง">เติมคำในช่องว่าง</option>
                <option value="การจับคู่">การจับคู่ (ข้อความ หรือ รูปภาพ)</option>
                <option value="การเรียงลำดับ">การเรียงลำดับ</option>
            </select>
        </div>

        <div id="answer-inputs-area" style="background:#f8fafc;border:1px solid #e2e8f0;padding:14px;border-radius:8px;margin-bottom:16px">
            <!-- Dynamic generated by JS -->
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px">
            <button type="button" class="adm-btn is-light" onclick="closeQuestionModal()">ยกเลิก</button>
            <button type="button" class="adm-btn is-primary" onclick="saveQuestionItem()">บันทึกคำถาม</button>
        </div>
    </div>
</div>

<script>
var currentEditingTopicBox = null;
var activeLessonRow = null;
var draggedRow = null;
var currentQuestionsList = [];
var activeQuestionIndex = -1;
var defaultPaymentDays = <?= $defaultPaymentDays ?>;

function parseIntOrDefault(val, fallback) {
    var parsed = parseInt(String(val ?? '').trim(), 10);
    return Number.isNaN(parsed) ? fallback : Math.max(0, parsed);
}

// --- ฟังก์ชันพรีวิวใบเซอร์จำลองพิกัด X/Y ---
function openCertPreviewModal() {
    var certImg = document.getElementById('certPreview');
    var modalImg = document.getElementById('modalCertImg');
    if (!certImg.src || certImg.src === '#' || certImg.style.display === 'none') {
        alert('กรุณาอัปโหลดรูปภาพใบเซอร์ก่อนดูตัวอย่าง');
        return;
    }
    modalImg.src = certImg.src;

    modalImg.onload = function() {
        var naturalW = modalImg.naturalWidth;
        var displayedW = modalImg.clientWidth;
        var scale = displayedW / naturalW;

        var nx = parseInt(document.getElementById('cert_name_x').value) || 0;
        var ny = parseInt(document.getElementById('cert_name_y').value) || 0;
        var dx = parseInt(document.getElementById('cert_date_x').value) || 0;
        var dy = parseInt(document.getElementById('cert_date_y').value) || 0;

        var nameTag = document.getElementById('previewNameTag');
        var dateTag = document.getElementById('previewDateTag');

        nameTag.style.left = (nx * scale) + 'px';
        nameTag.style.top = (ny * scale) + 'px';

        dateTag.style.left = (dx * scale) + 'px';
        dateTag.style.top = (dy * scale) + 'px';
    };

    document.getElementById('certPreviewModal').style.display = 'flex';
}

function closeCertPreviewModal() {
    document.getElementById('certPreviewModal').style.display = 'none';
}

// --- พรีวิวรูปภาพ Sidebar ---
function previewBrochure(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var prev = document.getElementById('brochurePreview');
            prev.src = e.target.result;
            prev.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewCertImg(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var prev = document.getElementById('certPreview');
            prev.src = e.target.result;
            prev.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewGallery(input) {
    var container = document.getElementById('galleryPreviewContainer');
    if (input.files) {
        for (var i = 0; i < input.files.length; i++) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var div = document.createElement('div');
                div.style.cssText = 'text-align:center;border:1px solid #cbd5e1;padding:4px;border-radius:4px;background:#fff';
                div.innerHTML = '<img src="' + e.target.result + '" style="width:90px;height:90px;object-fit:cover;border-radius:3px;display:block">' +
                                '<small class="adm-muted" style="font-size:10px">ใหม่</small>';
                container.appendChild(div);
            }
            reader.readAsDataURL(input.files[i]);
        }
    }
}

// --- ควบคุมการแสดงผลเรตราคาตาม 4 รูปแบบที่สั่ง ---
document.getElementById('price_type_sel').addEventListener('change', updateRateRows);
document.getElementById('pay_type_sel').addEventListener('change', updateRateRows);

function updateRateRows() {
    var pType = document.getElementById('price_type_sel').value;
    var payType = document.getElementById('pay_type_sel').value;

    var r1 = document.querySelector('.row-rate-1');
    var r2 = document.querySelector('.row-rate-2');
    var r3 = document.querySelector('.row-rate-3');
    var r4 = document.querySelector('.row-rate-4');

    var l1 = document.getElementById('label-rate-1');
    var l2 = document.getElementById('label-rate-2');
    var l3 = document.getElementById('label-rate-3');
    var l4 = document.getElementById('label-rate-4');

    if (pType === 'single' && payType === 'single') {
        if(r1) r1.style.display = '';
        if(r2) r2.style.display = 'none';
        if(r3) r3.style.display = 'none';
        if(r4) r4.style.display = 'none';
        if(l1) l1.innerText = 'ราคาค่าอบรม';
    } 
    else if (pType === 'single' && payType === 'days_before') {
        if(r1) r1.style.display = '';
        if(r2) r2.style.display = 'none';
        if(r3) r3.style.display = '';
        if(r4) r4.style.display = 'none';
        if(l1) l1.innerText = 'ราคาค่าอบรม';
        if(l3) l3.innerText = 'ชำระก่อนถึงวันอบรมวันแรก ' + defaultPaymentDays + ' วัน';
    } 
    else if (pType === 'member_split' && payType === 'single') {
        if(r1) r1.style.display = '';
        if(r2) r2.style.display = '';
        if(r3) r3.style.display = 'none';
        if(r4) r4.style.display = 'none';
        if(l1) l1.innerText = 'ราคาบุคคลทั่วไป';
        if(l2) l2.innerText = 'ราคาสำหรับสมาชิก';
    } 
    else if (pType === 'member_split' && payType === 'days_before') {
        if(r1) r1.style.display = '';
        if(r2) r2.style.display = '';
        if(r3) r3.style.display = '';
        if(r4) r4.style.display = '';
        if(l1) l1.innerText = 'ราคาบุคคลทั่วไป';
        if(l2) l2.innerText = 'ราคาสำหรับสมาชิก';
        if(l3) l3.innerText = 'ราคาบุคคลทั่วไปชำระก่อนถึงวันอบรมวันแรก ' + defaultPaymentDays + ' วัน';
        if(l4) l4.innerText = 'ราคาสำหรับสมาชิกชำระก่อนถึงวันอบรมวันแรก ' + defaultPaymentDays + ' วัน';
    }
}
document.addEventListener('DOMContentLoaded', updateRateRows);

// --- จัดการหัวข้อหลัก (Topics) ---
function openTopicModalForAdd() {
    currentEditingTopicBox = null;
    document.getElementById('topicModalTitle').innerText = 'เพิ่มหัวข้อหลัก';
    document.getElementById('modalTopicTitle').value = '';
    document.getElementById('modalTopicSummary').value = '';
    document.getElementById('topicModal').style.display = 'flex';
}

function openTopicModalForEdit(btn) {
    currentEditingTopicBox = btn.closest('.topic-box');
    var titleVal = currentEditingTopicBox.querySelector('input[name="topic_title[]"]').value;
    var sumVal = currentEditingTopicBox.querySelector('textarea[name="topic_summary[]"]').value;
    
    document.getElementById('topicModalTitle').innerText = 'แก้ไขหัวข้อหลัก';
    document.getElementById('modalTopicTitle').value = titleVal;
    document.getElementById('modalTopicSummary').value = sumVal;
    document.getElementById('topicModal').style.display = 'flex';
}

function closeTopicModal() { document.getElementById('topicModal').style.display = 'none'; }

function saveTopicModal() {
    var tTitle = document.getElementById('modalTopicTitle').value.trim();
    var tSum = document.getElementById('modalTopicSummary').value.trim();
    if (!tTitle) { alert('กรุณากรอกชื่อหัวข้อหลัก'); return; }

    var container = document.getElementById('topics-container');
    var idx = container.children.length;

    if (currentEditingTopicBox) {
        currentEditingTopicBox.querySelector('input[name="topic_title[]"]').value = tTitle;
        currentEditingTopicBox.querySelector('textarea[name="topic_summary[]"]').value = tSum;
        currentEditingTopicBox.querySelector('.topic-title-text').innerText = tTitle;
        currentEditingTopicBox.querySelector('.topic-sum-text').innerHTML = '<em>สรุปหัวข้อ:</em> ' + (tSum || '-');
    } else {
        var html = '<div class="topic-box" style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;margin-bottom:12px;overflow:hidden">' +
            '<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:#f1f5f9;border-bottom:1px solid #e2e8f0">' +
            '<div style="display:flex;align-items:center;gap:10px;cursor:pointer;flex:1" onclick="toggleTopicAccordion(this)">' +
            '<span class="accordion-icon" style="font-weight:bold">&#9658;</span>' +
            '<strong class="topic-title-text">' + tTitle + '</strong>' +
            '<span class="adm-muted" style="font-size:13px">(0 รายการ)</span></div>' +
            '<div style="display:flex;gap:6px">' +
            '<input type="hidden" name="topic_title[]" value="' + tTitle + '">' +
            '<textarea style="display:none" name="topic_summary[]">' + tSum + '</textarea>' +
            '<button type="button" class="adm-btn is-light is-sm" onclick="openTopicModalForEdit(this)">แก้ไข</button>' +
            '<button type="button" class="adm-btn is-danger-ghost is-sm" onclick="this.closest(\'.topic-box\').remove()">ลบ</button></div></div>' +
            '<div class="topic-content-body" style="display:none;padding:16px">' +
            '<div class="adm-muted topic-sum-text" style="margin-bottom:10px"><em>สรุปหัวข้อ:</em> ' + (tSum || '-') + '</div>' +
            '<div class="lessons-box" style="padding:12px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;border-left:4px solid #2563eb">' +
            '<div style="margin-bottom:8px"><strong>รายการบทเรียน / แบบทดสอบย่อย (จัดลำดับได้):</strong></div>' +
            '<div class="lessons-list" ondragover="allowDrop(event)"></div>' +
            '<div style="display:flex;gap:8px;margin-top:10px">' +
            '<button type="button" class="adm-btn is-light is-sm" onclick="openAddModal(this, ' + idx + ', \'lesson\')">+ เพิ่มบทเรียน</button>' +
            '<button type="button" class="adm-btn is-light is-sm" onclick="openAddModal(this, ' + idx + ', \'quiz\')">+ เพิ่มแบบทดสอบ</button>' +
            '</div></div></div></div>';
        container.insertAdjacentHTML('beforeend', html);
    }
    closeTopicModal();
}

function toggleTopicAccordion(el) {
    var box = el.closest('.topic-box');
    var body = box.querySelector('.topic-content-body');
    var icon = box.querySelector('.accordion-icon');
    if (body.style.display === 'none') {
        body.style.display = 'block';
        icon.innerHTML = '&#9660;';
    } else {
        body.style.display = 'none';
        icon.innerHTML = '&#9658;';
    }
}

// --- จัดการบทเรียนและแบบทดสอบ ---
function openAddModal(btn, idx, type) {
    activeLessonRow = { type: type, rowElement: null, topicIdx: idx };
    if (type === 'lesson') {
        document.getElementById('lessonModalTitle').innerText = 'เพิ่มบทเรียน';
        document.getElementById('modalLessonTitle').value = '';
        document.getElementById('modalLessonDesc').value = '';
        document.getElementById('lessonModal').style.display = 'flex';
    } else {
        document.getElementById('quizModalTitle').innerText = 'เพิ่มแบบทดสอบ';
        document.getElementById('modalQuizTitle').value = '';
        document.getElementById('modalQuizDesc').value = '';
        document.getElementById('qTime').value = 0;
        document.getElementById('qPassGrade').value = 70;
        document.getElementById('qAttempts').value = 0;
        document.getElementById('qAutoStart').checked = false;
        document.getElementById('qLayout').value = 'คำถามเดียว';
        document.getElementById('qSort').value = 'ตามที่กำหนด';
        document.getElementById('qShowNum').checked = true;
        currentQuestionsList = [];
        renderQuestionsListUI();
        switchQuizTab(0);
        document.getElementById('quizModal').style.display = 'flex';
    }
}

function openConfigModalFromRow(btn) {
    var row = btn.closest('.lesson-row');
    activeLessonRow = { type: row.querySelector('input[name*="lesson_type"]').value, rowElement: row, topicIdx: 0 };
    
    var titleVal = row.querySelector('input[name*="lesson_title"]').value;
    var descVal = row.querySelector('input[name*="lesson_desc"]').value;

    if (activeLessonRow.type === 'lesson') {
        document.getElementById('lessonModalTitle').innerText = 'แก้ไขบทเรียน';
        document.getElementById('modalLessonTitle').value = titleVal;
        document.getElementById('modalLessonDesc').value = descVal;
        document.getElementById('lessonModal').style.display = 'flex';
    } else {
        document.getElementById('quizModalTitle').innerText = 'แก้ไขแบบทดสอบ';
        document.getElementById('modalQuizTitle').value = titleVal;
        
        try {
            var parsed = JSON.parse(descVal);
            document.getElementById('modalQuizDesc').value = parsed.desc || '';
            document.getElementById('qTime').value = parsed.time !== undefined && parsed.time !== null && parsed.time !== '' ? parseIntOrDefault(parsed.time, 0) : 0;
            document.getElementById('qPassGrade').value = parsed.pass_grade !== undefined && parsed.pass_grade !== null && parsed.pass_grade !== '' ? parseIntOrDefault(parsed.pass_grade, 70) : 70;
            document.getElementById('qAttempts').value = parsed.attempts !== undefined && parsed.attempts !== null && parsed.attempts !== '' ? parseIntOrDefault(parsed.attempts, 0) : 0;
            document.getElementById('qAutoStart').checked = !!parsed.auto_start;
            document.getElementById('qLayout').value = parsed.q_layout || 'คำถามเดียว';
            document.getElementById('qSort').value = parsed.q_sort || 'ตามที่กำหนด';
            document.getElementById('qShowNum').checked = parsed.show_q_num !== undefined ? !!parsed.show_q_num : true;
            currentQuestionsList = Array.isArray(parsed.questions) ? parsed.questions : [];
        } catch (e) {
            document.getElementById('modalQuizDesc').value = descVal;
            document.getElementById('qTime').value = 0;
            document.getElementById('qPassGrade').value = 70;
            document.getElementById('qAttempts').value = 0;
            document.getElementById('qAutoStart').checked = false;
            document.getElementById('qLayout').value = 'คำถามเดียว';
            document.getElementById('qSort').value = 'ตามที่กำหนด';
            document.getElementById('qShowNum').checked = true;
            currentQuestionsList = [];
        }

        renderQuestionsListUI();
        switchQuizTab(0);
        document.getElementById('quizModal').style.display = 'flex';
    }
}

function closeLessonModal() { document.getElementById('lessonModal').style.display = 'none'; }
function closeQuizModal() { document.getElementById('quizModal').style.display = 'none'; }

function saveLessonModal() {
    var title = document.getElementById('modalLessonTitle').value.trim();
    var desc = document.getElementById('modalLessonDesc').value.trim();
    if (!title) { alert('กรุณากรอกหัวเรื่องบทเรียน'); return; }

    if (activeLessonRow.rowElement) {
        activeLessonRow.rowElement.querySelector('input[name*="lesson_title"]').value = title;
        activeLessonRow.rowElement.querySelector('input[name*="lesson_desc"]').value = desc;
    } else {
        var topicBox = document.querySelectorAll('.topic-box')[activeLessonRow.topicIdx];
        if (!topicBox) topicBox = document.querySelector('.topic-box:last-of-type');
        var listContainer = topicBox.querySelector('.lessons-list');
        
        var html = '<div style="display:flex;gap:8px;margin-top:8px;align-items:center;background:#fff;padding:6px;border:1px solid #e2e8f0;border-radius:6px" class="lesson-row" draggable="true" ondragstart="drag(event)" ondragover="allowDrop(event)" ondrop="drop(event)">' +
            '<span style="cursor:grab;color:#94a3b8;font-weight:bold;padding:0 4px" title="ลากเพื่อสลับตำแหน่ง">☰</span>' +
            '<span class="adm-badge is-active" style="min-width:80px;text-align:center">บทเรียน</span>' +
            '<input type="hidden" name="lesson_type[' + activeLessonRow.topicIdx + '][]" value="lesson">' +
            '<input class="adm-input" name="lesson_title[' + activeLessonRow.topicIdx + '][]" value="' + title + '" readonly style="background:#f8fafc;cursor:pointer" onclick="openConfigModalFromRow(this)" title="คลิกเพื่อแก้ไข">' +
            '<input type="hidden" name="lesson_desc[' + activeLessonRow.topicIdx + '][]" value="' + desc + '">' +
            '<div style="display:flex;flex-direction:column;gap:2px">' +
            '<button type="button" class="adm-btn is-light is-sm" style="padding:0 4px;font-size:10px;line-height:1" onclick="moveItem(this, \'up\')">▲</button>' +
            '<button type="button" class="adm-btn is-light is-sm" style="padding:0 4px;font-size:10px;line-height:1" onclick="moveItem(this, \'down\')">▼</button></div>' +
            '<button type="button" class="adm-btn is-light is-sm" onclick="openConfigModalFromRow(this)">แก้ไข</button>' +
            '<button type="button" class="adm-btn is-danger-ghost is-sm" onclick="this.closest(\'.lesson-row\').remove()">ลบ</button></div>';
        listContainer.insertAdjacentHTML('beforeend', html);
    }
    closeLessonModal();
}

function saveQuizModal() {
    var title = document.getElementById('modalQuizTitle').value.trim();
    var desc = document.getElementById('modalQuizDesc').value.trim();
    if (!title) { alert('กรุณากรอกชื่อแบบทดสอบ'); return; }

    var quizSettings = {
        desc: desc,
        time: parseIntOrDefault(document.getElementById('qTime').value, 0),
        pass_grade: parseIntOrDefault(document.getElementById('qPassGrade').value, 70),
        attempts: parseIntOrDefault(document.getElementById('qAttempts').value, 0),
        auto_start: document.getElementById('qAutoStart').checked ? 1 : 0,
        q_layout: document.getElementById('qLayout').value,
        q_sort: document.getElementById('qSort').value,
        show_q_num: document.getElementById('qShowNum').checked ? 1 : 0,
        questions: currentQuestionsList
    };
    var jsonPayload = JSON.stringify(quizSettings);

    if (activeLessonRow.rowElement) {
        activeLessonRow.rowElement.querySelector('input[name*="lesson_title"]').value = title;
        activeLessonRow.rowElement.querySelector('input[name*="lesson_desc"]').value = jsonPayload;
    } else {
        var topicBox = document.querySelectorAll('.topic-box')[activeLessonRow.topicIdx];
        if (!topicBox) topicBox = document.querySelector('.topic-box:last-of-type');
        var listContainer = topicBox.querySelector('.lessons-list');
        
        var html = '<div style="display:flex;gap:8px;margin-top:8px;align-items:center;background:#fff;padding:6px;border:1px solid #e2e8f0;border-radius:6px" class="lesson-row" draggable="true" ondragstart="drag(event)" ondragover="allowDrop(event)" ondrop="drop(event)">' +
            '<span style="cursor:grab;color:#94a3b8;font-weight:bold;padding:0 4px" title="ลากเพื่อสลับตำแหน่ง">☰</span>' +
            '<span class="adm-badge is-draft" style="min-width:80px;text-align:center">แบบทดสอบ</span>' +
            '<input type="hidden" name="lesson_type[' + activeLessonRow.topicIdx + '][]" value="quiz">' +
            '<input class="adm-input" name="lesson_title[' + activeLessonRow.topicIdx + '][]" value="' + title + '" readonly style="background:#f8fafc;cursor:pointer" onclick="openConfigModalFromRow(this)" title="คลิกเพื่อแก้ไข">' +
            '<input type="hidden" name="lesson_desc[' + activeLessonRow.topicIdx + '][]" value=\'' + jsonPayload.replace(/'/g, "&#39;") + '\'>' +
            '<div style="display:flex;flex-direction:column;gap:2px">' +
            '<button type="button" class="adm-btn is-light is-sm" style="padding:0 4px;font-size:10px;line-height:1" onclick="moveItem(this, \'up\')">▲</button>' +
            '<button type="button" class="adm-btn is-light is-sm" style="padding:0 4px;font-size:10px;line-height:1" onclick="moveItem(this, \'down\')">▼</button></div>' +
            '<button type="button" class="adm-btn is-light is-sm" onclick="openConfigModalFromRow(this)">แก้ไข</button>' +
            '<button type="button" class="adm-btn is-danger-ghost is-sm" onclick="this.closest(\'.lesson-row\').remove()">ลบ</button></div>';
        listContainer.insertAdjacentHTML('beforeend', html);
    }
    closeQuizModal();
}

// --- จัดการข้อสอบ ---
function openQuestionModalForAdd() {
    activeQuestionIndex = -1;
    document.getElementById('questionModalTitle').innerText = 'เพิ่มข้อคำถาม';
    document.getElementById('qText').value = '';
    document.getElementById('qType').value = 'ถูก/ผิด';
    renderAnswerInputs();
    document.getElementById('questionModal').style.display = 'flex';
}

function openQuestionModalForEdit(index) {
    activeQuestionIndex = index;
    var q = currentQuestionsList[index];
    document.getElementById('questionModalTitle').innerText = 'แก้ไขข้อคำถาม';
    document.getElementById('qText').value = q.text || '';
    document.getElementById('qType').value = q.type || 'ถูก/ผิด';
    renderAnswerInputs(q.answers || null);
    document.getElementById('questionModal').style.display = 'flex';
}

function closeQuestionModal() { document.getElementById('questionModal').style.display = 'none'; }

function renderAnswerInputs(existingAnswers = null) {
    var type = document.getElementById('qType').value;
    var area = document.getElementById('answer-inputs-area');
    var html = '';

    if (type === 'ถูก/ผิด') {
        var val = existingAnswers ? existingAnswers[0] : 'ถูก';
        html = '<label class="adm-label">เลือกคำตอบที่ถูกต้อง</label>' +
               '<select class="adm-input" id="ansTrueFalse">' +
               '<option value="ถูก"' + (val==='ถูก'?' selected':'') + '>ถูก</option>' +
               '<option value="ผิด"' + (val==='ผิด'?' selected':'') + '>ผิด</option>' +
               '</select>';
    } else if (type === 'ตัวเลือกเดี่ยว' || type === 'หลายตัวเลือก') {
        var list = Array.isArray(existingAnswers) ? existingAnswers : [{text:'', image:'', correct:false}, {text:'', image:'', correct:false}];
        html = '<label class="adm-label">รายการตัวเลือก (ใส่ข้อความ หรือแนบรูปภาพ อย่างใดอย่างหนึ่งหรือทั้งสองอย่าง)</label><div id="choices-builder"></div>' +
               '<button type="button" class="adm-btn is-light is-sm" style="margin-top:8px" onclick="addChoiceRow(\'\', \'\', false, \'' + type + '\')">+ เพิ่มตัวเลือก</button>';
        area.innerHTML = html;
        list.forEach(item => addChoiceRow(item.text, item.image || '', item.correct, type));
        return;
    } else if (type === 'คำถามเปิด/เรียความ') {
        html = '<p class="adm-muted" style="margin:0">ผู้เรียนพิมพ์คำตอบด้วยตนเอง ไม่มีตัวเลือกคำตอบล่วงหน้า</p>';
    } else if (type === 'เติมคำในช่องว่าง') {
        var fillVal = existingAnswers ? existingAnswers[0] : '';
        html = '<label class="adm-label">คำตอบที่ถูกต้อง (สำหรับตรวจคำตอบ)</label>' +
               '<input class="adm-input" id="ansFillBlank" value="' + (fillVal||'') + '" placeholder="พิมพ์คำตอบที่ถูกต้อง...">';
    } else if (type === 'การจับคู่') {
        var pairs = Array.isArray(existingAnswers) ? existingAnswers : [{leftText:'', leftImg:'', rightText:'', rightImg:''}];
        html = '<label class="adm-label">กำหนดคู่ที่ถูกต้อง (ใส่ข้อความ หรือแนบรูปภาพ อย่างใดอย่างหนึ่งหรือทั้งสองอย่างในแต่ละฝั่ง)</label><div id="pairs-builder"></div>' +
               '<button type="button" class="adm-btn is-light is-sm" style="margin-top:8px" onclick="addPairRow(\'\', \'\', \'\', \'\')">+ เพิ่มคู่จับคู่</button>';
        area.innerHTML = html;
        pairs.forEach(p => addPairRow(p.leftText || '', p.leftImg || '', p.rightText || '', p.rightImg || ''));
        return;
    } else if (type === 'การเรียงลำดับ') {
        var list = Array.isArray(existingAnswers) ? existingAnswers : [{text:'', image:''}, {text:'', image:''}];
        html = '<label class="adm-label">กำหนดข้อความหรือรูปเรียงลงมาตามลำดับที่ถูกต้อง (ระบบจะสุ่มแสดงผลให้ผู้เรียนเรียงใหม่)</label><div id="sort-builder"></div>' +
               '<button type="button" class="adm-btn is-light is-sm" style="margin-top:8px" onclick="addSortRow(\'\', \'\')">+ เพิ่มรายการเรียงลำดับ</button>';
        area.innerHTML = html;
        list.forEach(item => addSortRow(typeof item === 'object' ? (item.text || '') : item, typeof item === 'object' ? (item.image || '') : ''));
        return;
    }
    area.innerHTML = html;
}

function handleImagePreview(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var prev = document.getElementById(previewId);
            prev.src = e.target.result;
            prev.style.display = 'block';
            input.setAttribute('data-base64', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function addChoiceRow(text, image, correct, type) {
    var cb = document.getElementById('choices-builder');
    if (!cb) return;
    var inputType = (type === 'หลายตัวเลือก') ? 'checkbox' : 'radio';
    var uniqueId = 'img_choice_' + Math.random().toString(36).substring(2, 9);
    var row = document.createElement('div');
    row.style.cssText = 'display:flex;gap:8px;margin-top:8px;align-items:center;background:#fff;padding:8px;border:1px solid #cbd5e1;border-radius:6px';
    row.className = 'choice-row';
    row.innerHTML = '<input type="' + inputType + '" name="ansCorrect" ' + (correct?'checked':'') + ' title="เลือกข้อที่ถูกต้อง"> ' +
                    '<input class="adm-input choice-text" value="' + text + '" placeholder="พิมพ์ตัวเลือก..." style="flex:1"> ' +
                    '<div style="display:flex;align-items:center;gap:6px">' +
                    '<img id="' + uniqueId + '" src="' + (image || '#') + '" style="height:100px;width:auto;object-fit:contain;border-radius:4px;display:' + (image?'block':'none') + '"> ' +
                    '<input type="file" accept="image/*" style="width:180px;font-size:12px" onchange="handleImagePreview(this, \'' + uniqueId + '\')" data-base64="' + (image||'') + '">' +
                    '</div> ' +
                    '<button type="button" class="adm-btn is-danger-ghost is-sm" onclick="this.closest(\'.choice-row\').remove()">&times;</button>';
    cb.appendChild(row);
}

function addPairRow(leftText, leftImg, rightText, rightImg) {
    var pb = document.getElementById('pairs-builder');
    if (!pb) return;
    var idL = 'img_pl_' + Math.random().toString(36).substring(2, 9);
    var idR = 'img_pr_' + Math.random().toString(36).substring(2, 9);
    var row = document.createElement('div');
    row.style.cssText = 'display:flex;gap:8px;margin-top:8px;align-items:center;background:#fff;padding:8px;border:1px solid #cbd5e1;border-radius:6px';
    row.className = 'pair-row';
    row.innerHTML = '<div style="flex:1;display:flex;flex-direction:column;gap:6px">' +
                    '<input class="adm-input pair-left-text" value="' + leftText + '" placeholder="ฝั่งซ้าย (ข้อความ)">' +
                    '<div style="display:flex;align-items:center;gap:6px"><img id="' + idL + '" src="' + (leftImg||'#') + '" style="height:100px;width:auto;object-fit:contain;border-radius:4px;display:' + (leftImg?'block':'none') + '"><input type="file" accept="image/*" style="font-size:11px" onchange="handleImagePreview(this, \'' + idL + '\')" data-base64="' + (leftImg||'') + '"></div>' +
                    '</div> <span>=</span> ' +
                    '<div style="flex:1;display:flex;flex-direction:column;gap:6px">' +
                    '<input class="adm-input pair-right-text" value="' + rightText + '" placeholder="ฝั่งขวา (ข้อความ)">' +
                    '<div style="display:flex;align-items:center;gap:6px"><img id="' + idR + '" src="' + (rightImg||'#') + '" style="height:100px;width:auto;object-fit:contain;border-radius:4px;display:' + (rightImg?'block':'none') + '"><input type="file" accept="image/*" style="font-size:11px" onchange="handleImagePreview(this, \'' + idR + '\')" data-base64="' + (rightImg||'') + '"></div>' +
                    '</div> ' +
                    '<button type="button" class="adm-btn is-danger-ghost is-sm" onclick="this.closest(\'.pair-row\').remove()">&times;</button>';
    pb.appendChild(row);
}

function addSortRow(text, image) {
    var sb = document.getElementById('sort-builder');
    if (!sb) return;
    var idS = 'img_s_' + Math.random().toString(36).substring(2, 9);
    var row = document.createElement('div');
    row.style.cssText = 'display:flex;gap:8px;margin-top:8px;align-items:center;background:#fff;padding:8px;border:1px solid #cbd5e1;border-radius:6px';
    row.className = 'sort-row';
    row.innerHTML = '<span style="cursor:grab;color:#94a3b8;font-weight:bold">☰</span> ' +
                    '<input class="adm-input sort-text" value="' + text + '" placeholder="พิมพ์ข้อความ..." style="flex:1"> ' +
                    '<div style="display:flex;align-items:center;gap:6px">' +
                    '<img id="' + idS + '" src="' + (image||'#') + '" style="height:100px;width:auto;object-fit:contain;border-radius:4px;display:' + (image?'block':'none') + '"> ' +
                    '<input type="file" accept="image/*" style="width:160px;font-size:11px" onchange="handleImagePreview(this, \'' + idS + '\')" data-base64="' + (image||'') + '">' +
                    '</div> ' +
                    '<div style="display:flex;flex-direction:column;gap:2px">' +
                    '<button type="button" class="adm-btn is-light is-sm" style="padding:0 4px;font-size:10px" onclick="moveSortRow(this, \'up\')">▲</button>' +
                    '<button type="button" class="adm-btn is-light is-sm" style="padding:0 4px;font-size:10px" onclick="moveSortRow(this, \'down\')">▼</button></div> ' +
                    '<button type="button" class="adm-btn is-danger-ghost is-sm" onclick="this.closest(\'.sort-row\').remove()">&times;</button>';
    sb.appendChild(row);
}

function moveSortRow(btn, direction) {
    var row = btn.closest('.sort-row');
    var container = row.parentNode;
    if (direction === 'up') {
        var prev = row.previousElementSibling;
        if (prev) { container.insertBefore(row, prev); }
    } else {
        var next = row.nextElementSibling;
        if (next) { container.insertBefore(next, row); }
    }
}

function saveQuestionItem() {
    var text = document.getElementById('qText').value.trim();
    var type = document.getElementById('qType').value;
    if (!text) { alert('กรุณากรอกคำถาม'); return; }

    var answers = [];
    if (type === 'ถูก/ผิด') {
        answers = [document.getElementById('ansTrueFalse').value];
    } else if (type === 'ตัวเลือกเดี่ยว' || type === 'หลายตัวเลือก') {
        var rows = document.querySelectorAll('.choice-row');
        var valid = true;
        rows.forEach(row => {
            var tVal = row.querySelector('.choice-text').value.trim();
            var imgVal = row.querySelector('input[type="file"]').getAttribute('data-base64') || '';
            if (!tVal && !imgVal) { valid = false; }
            answers.push({ text: tVal, image: imgVal, correct: row.querySelector('input[type="radio"], input[type="checkbox"]').checked });
        });
        if (!valid) { alert('กรุณาใส่ข้อความหรือแนบรูปภาพอย่างน้อย 1 อย่างในแต่ละตัวเลือก'); return; }
    } else if (type === 'เติมคำในช่องว่าง') {
        var fVal = document.getElementById('ansFillBlank').value.trim();
        if (!fVal) { alert('กรุณากรอกคำตอบที่ถูกต้อง'); return; }
        answers = [fVal];
    } else if (type === 'การจับคู่') {
        var rows = document.querySelectorAll('.pair-row');
        var valid = true;
        rows.forEach(row => {
            var lText = row.querySelector('.pair-left-text').value.trim();
            var fL = row.querySelector('.pair-left-text').nextElementSibling.querySelector('input[type="file"]');
            var lImg = fL ? (fL.getAttribute('data-base64') || '') : '';

            var rText = row.querySelector('.pair-right-text').value.trim();
            var fR = row.querySelector('.pair-right-text').nextElementSibling.querySelector('input[type="file"]');
            var rImg = fR ? (fR.getAttribute('data-base64') || '') : '';

            if ((!lText && !lImg) || (!rText && !rImg)) { valid = false; }
            answers.push({ leftText: lText, leftImg: lImg, rightText: rText, rightImg: rImg });
        });
        if (!valid) { alert('กรุณาใส่ข้อความหรือแนบรูปภาพให้ครบทั้งฝั่งซ้ายและฝั่งขวาในแต่ละคู่'); return; }
    } else if (type === 'การเรียงลำดับ') {
        var rows = document.querySelectorAll('.sort-row');
        var valid = true;
        rows.forEach(row => {
            var sText = row.querySelector('.sort-text').value.trim();
            var fS = row.querySelector('input[type="file"]');
            var sImg = fS ? (fS.getAttribute('data-base64') || '') : '';
            if (!sText && !sImg) { valid = false; }
            answers.push({ text: sText, image: sImg });
        });
        if (!valid) { alert('กรุณาใส่ข้อความหรือแนบรูปภาพอย่างน้อย 1 อย่างในแต่ละแถวเรียงลำดับ'); return; }
    }

    var qObj = { text: text, type: type, answers: answers };

    if (activeQuestionIndex >= 0) {
        currentQuestionsList[activeQuestionIndex] = qObj;
    } else {
        currentQuestionsList.push(qObj);
    }

    renderQuestionsListUI();
    closeQuestionModal();
}

function renderQuestionsListUI() {
    var container = document.getElementById('questions-list-container');
    if (!currentQuestionsList.length) {
        container.innerHTML = '<div class="adm-empty" style="padding:20px;border:1px dashed #cbd5e1;border-radius:6px">ยังไม่มีข้อคำถาม กรุณากดปุ่ม "+ เพิ่มคำถาม"</div>';
        return;
    }
    var html = '';
    currentQuestionsList.forEach((q, idx) => {
        html += '<div style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:6px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center">' +
                '<div><strong>ข้อ ' + (idx + 1) + ':</strong> ' + q.text + ' <span class="adm-badge is-active" style="margin-left:8px">' + q.type + '</span></div>' +
                '<div style="display:flex;gap:6px">' +
                '<button type="button" class="adm-btn is-light is-sm" onclick="openQuestionModalForEdit(' + idx + ')">แก้ไข</button>' +
                '<button type="button" class="adm-btn is-danger-ghost is-sm" onclick="deleteQuestionItem(' + idx + ')">ลบ</button></div></div>';
    });
    container.innerHTML = html;
}

function deleteQuestionItem(index) {
    if (confirm('ต้องการลบคำถามนี้?')) {
        currentQuestionsList.splice(index, 1);
        renderQuestionsListUI();
    }
}

function moveItem(btn, direction) {
    var row = btn.closest('.lesson-row');
    var container = row.parentNode;
    if (direction === 'up') {
        var prev = row.previousElementSibling;
        if (prev) { container.insertBefore(row, prev); }
    } else {
        var next = row.nextElementSibling;
        if (next) { container.insertBefore(next, row); }
    }
}

function allowDrop(ev) { ev.preventDefault(); }
function drag(ev) { draggedRow = ev.target.closest('.lesson-row'); }
function drop(ev) {
    ev.preventDefault();
    var targetRow = ev.target.closest('.lesson-row');
    if (targetRow && draggedRow && targetRow !== draggedRow) {
        var container = targetRow.parentNode;
        var allRows = Array.from(container.children);
        var draggedIdx = allRows.indexOf(draggedRow);
        var targetIdx = allRows.indexOf(targetRow);
        if (draggedIdx < targetIdx) {
            container.insertBefore(draggedRow, targetRow.nextSibling);
        } else {
            container.insertBefore(draggedRow, targetRow);
        }
    }
}

function switchQuizTab(tabIdx) {
    var panes = document.querySelectorAll('.quiz-tab-pane');
    var buttons = document.querySelectorAll('.quiz-tab-btn');
    panes.forEach((p, idx) => { p.style.display = (idx === tabIdx) ? 'block' : 'none'; });
    buttons.forEach((b, idx) => {
        if (idx === tabIdx) {
            b.style.borderBottom = '2px solid #1d4ed8';
            b.style.color = '#1d4ed8';
            b.style.fontWeight = '600';
        } else {
            b.style.borderBottom = '0';
            b.style.color = '#64748b';
            b.style.fontWeight = 'normal';
        }
    });
}
</script>