<?php
/**
 * View Page : Checkout (ระบุรายชื่อผู้เข้าอบรม และใบกำกับภาษี)
**/
$this->partial('page-header', ['title' => 'ยืนยันการสั่งซื้อ & ระบุรายชื่อผู้เข้าอบรม', 'subtitle' => 'กรอกข้อมูลผู้เข้าเรียนเพื่อออกวุฒิบัตร e-Certificate และออกเอกสารใบกำกับภาษี', 'breadcrumbs' => $breadcrumbs]);
?>
<section class="section" style="padding-top:1.25rem;">
    <div class="container">
        <form method="post" action="/checkout/place-order">
	<?= csrf_field() ?>
            <div class="grid" style="grid-template-columns: 1.8fr 1.2fr; gap: 2rem; align-items: start;">
                <div>
                    <!-- 1. ข้อมูลผู้ทำรายการ/สั่งซื้อ -->
                    <div class="card mb-3" style="border-radius: var(--radius);">
                        <div class="card__body">
                            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">
                                👤 ข้อมูลผู้ทำรายการสั่งซื้อ
                            </h3>
                            <div class="grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem;">
                                <div>
                                    <label style="display: block; font-size: 0.78rem; font-weight: 600; color: var(--muted); margin-bottom: 0.25rem;">ชื่อ-นามสกุล ผู้สั่งซื้อ *</label>
                                    <input type="text" name="buyer_name" value="<?= e($currentUser['name'] ?? '') ?>" required class="form-control" style="width: 100%; font-size: 0.85rem; padding: 0.4rem; border: 1px solid var(--line); border-radius: 4px;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.78rem; font-weight: 600; color: var(--muted); margin-bottom: 0.25rem;">อีเมล *</label>
                                    <input type="email" name="buyer_email" value="<?= e($currentUser['email'] ?? '') ?>" required class="form-control" style="width: 100%; font-size: 0.85rem; padding: 0.4rem; border: 1px solid var(--line); border-radius: 4px;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.78rem; font-weight: 600; color: var(--muted); margin-bottom: 0.25rem;">เบอร์โทรศัพท์ *</label>
                                    <input type="text" name="buyer_phone" value="<?= e($currentUser['phone'] ?? '') ?>" required class="form-control" style="width: 100%; font-size: 0.85rem; padding: 0.4rem; border: 1px solid var(--line); border-radius: 4px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. ข้อมูลผู้เข้าอบรมแต่ละท่าน -->
                    <div class="card mb-3" style="border-radius: var(--radius);">
                        <div class="card__body">
                            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">
                                🎓 รายชื่อผู้เข้าอบรม (สำหรับออก e-Certificate & ส่งลิงก์เรียน)
                            </h3>
                            
                            <?php foreach ($items as $itemKey => $item): ?>
                                <div style="background: var(--surface-alt); border: 1px solid var(--line); border-radius: 6px; padding: 1rem; margin-bottom: 1rem;">
                                    <div style="font-weight: 600; color: var(--ink); font-size: 0.92rem;">
                                        <?= e($item['course_title']) ?> (รุ่น <?= e($item['batch_no']) ?>)
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--muted); margin-top: 0.15rem;">
                                        เรทราคา: <?= e($item['tier_name']) ?> | จำนวนผู้เข้าเรียน: <strong><?= $item['quantity'] ?> ท่าน</strong>
                                    </div>

                                    <?php for ($i = 0; $i < $item['quantity']; $i++): ?>
                                        <div style="border-top: 1px dashed var(--line); margin-top: 0.75rem; padding-top: 0.75rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                                <span style="font-size: 0.82rem; font-weight: 600; color: var(--brand-600);">ผู้เข้าอบรมท่านที่ <?= ($i + 1) ?>:</span>
                                                <?php if ($i === 0 && !empty($currentUser)): ?>
                                                    <label style="font-size: 0.78rem; color: var(--muted); cursor: pointer;">
                                                        <input type="checkbox" onchange="autoFillSelf(this, '<?= $itemKey ?>', <?= $i ?>, <?= json_encode($currentUser) ?>)"> ใช้ข้อมูลผู้สั่งซื้อ
                                                    </label>
                                                <?php endif; ?>
                                            </div>

                                            <div class="grid" style="grid-template-columns: 0.8fr 1.5fr 1.5fr 2fr 1.5fr; gap: 0.5rem;">
                                                <div>
                                                    <select name="attendees[<?= $itemKey ?>][<?= $i ?>][prefix]" class="form-control" style="width: 100%; font-size: 0.82rem; padding: 0.3rem; border: 1px solid var(--line); border-radius: 4px;">
                                                        <option value="นาย">นาย</option>
                                                        <option value="นาง">นาง</option>
                                                        <option value="นางสาว">นางสาว</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <input type="text" id="att_first_<?= $itemKey ?>_<?= $i ?>" name="attendees[<?= $itemKey ?>][<?= $i ?>][first_name]" placeholder="ชื่อจริง *" required class="form-control" style="width: 100%; font-size: 0.82rem; padding: 0.3rem; border: 1px solid var(--line); border-radius: 4px;">
                                                </div>
                                                <div>
                                                    <input type="text" id="att_last_<?= $itemKey ?>_<?= $i ?>" name="attendees[<?= $itemKey ?>][<?= $i ?>][last_name]" placeholder="นามสกุล *" required class="form-control" style="width: 100%; font-size: 0.82rem; padding: 0.3rem; border: 1px solid var(--line); border-radius: 4px;">
                                                </div>
                                                <div>
                                                    <input type="email" id="att_email_<?= $itemKey ?>_<?= $i ?>" name="attendees[<?= $itemKey ?>][<?= $i ?>][email]" placeholder="อีเมลสำหรับรับลิงก์ *" required class="form-control" style="width: 100%; font-size: 0.82rem; padding: 0.3rem; border: 1px solid var(--line); border-radius: 4px;">
                                                </div>
                                                <div>
                                                    <input type="text" id="att_phone_<?= $itemKey ?>_<?= $i ?>" name="attendees[<?= $itemKey ?>][<?= $i ?>][phone]" placeholder="เบอร์โทรศัพท์" class="form-control" style="width: 100%; font-size: 0.82rem; padding: 0.3rem; border: 1px solid var(--line); border-radius: 4px;">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 3. ข้อมูลใบเสร็จ/ใบกำกับภาษี -->
                    <div class="card" style="border-radius: var(--radius);">
                        <div class="card__body">
                            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">
                                📄 ข้อมูลการออกใบเสร็จ / ใบกำกับภาษี
                            </h3>
                            <div style="display: flex; gap: 1.5rem; margin-bottom: 1rem; font-size: 0.88rem;">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="invoice[tax_type]" value="personal" checked onclick="toggleTaxType('personal')"> บุคคลธรรมดา
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="invoice[tax_type]" value="corporate" onclick="toggleTaxType('corporate')"> นิติบุคคล / บริษัท
                                </label>
                            </div>

                            <div id="corporate-fields" style="display: none; margin-bottom: 0.75rem;">
                                <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                                    <div>
                                        <label style="display: block; font-size: 0.78rem; color: var(--muted); margin-bottom: 0.2rem;">ชื่อบริษัท / หน่วยงาน</label>
                                        <input type="text" name="invoice[company_name]" class="form-control" placeholder="บริษัท สยาม ออนไลน์ เทรนนิ่ง จำกัด" style="width: 100%; font-size: 0.85rem; padding: 0.4rem; border: 1px solid var(--line); border-radius: 4px;">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 0.78rem; color: var(--muted); margin-bottom: 0.2rem;">สาขา</label>
                                        <input type="text" name="invoice[branch]" class="form-control" placeholder="สำนักงานใหญ่" style="width: 100%; font-size: 0.85rem; padding: 0.4rem; border: 1px solid var(--line); border-radius: 4px;">
                                    </div>
                                </div>
                            </div>

                            <div style="margin-bottom: 0.75rem;">
                                <label style="display: block; font-size: 0.78rem; color: var(--muted); margin-bottom: 0.2rem;">เลขประจำตัวผู้เสียภาษี / เลขบัตรประชาชน *</label>
                                <input type="text" name="invoice[tax_id]" required class="form-control" placeholder="X-XXXX-XXXXX-XX-X" style="width: 100%; font-size: 0.85rem; padding: 0.4rem; border: 1px solid var(--line); border-radius: 4px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 0.78rem; color: var(--muted); margin-bottom: 0.2rem;">ที่อยู่สำหรับออกเอกสารใบกำกับภาษี *</label>
                                <textarea name="invoice[address]" required class="form-control" rows="3" placeholder="ระบุที่อยู่ครบถ้วน..." style="width: 100%; font-size: 0.85rem; padding: 0.4rem; border: 1px solid var(--line); border-radius: 4px;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ฝั่งขวา: สรุปราคาสั่งซื้อ -->
                <div>
                    <div class="card" style="border-radius: var(--radius); border: 2px solid var(--brand-600); position: sticky; top: 5rem;">
                        <div class="card__body">
                            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--ink);">สรุปยอดคำสั่งซื้อ</h3>

                            <div class="flex justify-between items-center" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                <span style="color: var(--muted);">ค่าอบรมรวม:</span>
                                <strong><?= number_format($totals['subtotal'], 2) ?> บาท</strong>
                            </div>

                            <div class="flex justify-between items-center" style="font-size: 0.85rem; color: #166534; margin-bottom: 0.4rem;">
                                <span>VAT (7%):</span>
                                <span>+<?= number_format($totals['vat'], 2) ?> บาท</span>
                            </div>

                            <div class="flex justify-between items-center" style="font-size: 0.85rem; color: #166534; margin-bottom: 0.8rem;">
                                <span>WHT (3%):</span>
                                <span>-<?= number_format($totals['wht'], 2) ?> บาท</span>
                            </div>

                            <div class="flex justify-between items-center" style="border-top: 2px solid #16a34a; padding-top: 0.75rem; margin-bottom: 1.25rem;">
                                <span style="font-weight: 700; color: var(--ink);">ยอดสุทธิรวม:</span>
                                <strong style="font-size: 1.3rem; color: #15803d;"><?= number_format($totals['grand_total'], 2) ?> บาท</strong>
                            </div>

                            <button type="submit" class="btn btn--primary btn--block" style="width: 100%; background: #16a34a; color: #fff; padding: 0.75rem; border-radius: 6px; border: none; font-weight: 600; font-size: 1rem; cursor: pointer;">
                                ✅ ยืนยันสั่งซื้อและลงทะเบียน
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
function toggleTaxType(type) {
    let corpGroup = document.getElementById('corporate-fields');
    if (type === 'corporate') {
        corpGroup.style.display = 'block';
    } else {
        corpGroup.style.display = 'none';
    }
}

function autoFillSelf(chk, key, idx, user) {
    if (chk.checked && user) {
        let nameParts = user.name ? user.name.split(' ') : ['', ''];
        document.getElementById(`att_first_${key}_${idx}`).value = nameParts[0] || '';
        document.getElementById(`att_last_${key}_${idx}`).value = nameParts.slice(1).join(' ') || '';
        document.getElementById(`att_email_${key}_${idx}`).value = user.email || '';
        document.getElementById(`att_phone_${key}_${idx}`).value = user.phone || '';
    }
}
</script>