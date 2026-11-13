<?php
/**
 * View Page : Order Success & Payment Slip Upload
**/
$this->partial('page-header', ['title' => 'ลงทะเบียนสั่งซื้อสำเร็จ', 'subtitle' => 'กรุณาโอนเงินชำระค่าลงทะเบียนและอัปโหลดหลักฐานการโอนเงิน', 'breadcrumbs' => $breadcrumbs]);
?>
<section class="section">
    <div class="container" style="max-width: 650px;">
        <div class="card" style="border: 2px solid #16a34a; border-radius: var(--radius); overflow: hidden;">
            <div class="card__body" style="padding: 2rem; text-align: center;">
                <div style="font-size: 3.5rem; margin-bottom: 0.5rem; color: #16a34a;"><?= icon('check-circle') ?></div>
                <h2 style="color: #15803d; margin-bottom: 0.5rem; font-size: 1.5rem;">บันทึกการลงทะเบียนเรียบร้อยแล้ว!</h2>
                <p style="color: var(--muted); font-size: 0.95rem;">
                    เลขที่คำสั่งซื้อของคุณคือ: <strong style="color: var(--ink); font-size: 1.15rem; font-family: monospace; background: var(--surface-alt); padding: 0.2rem 0.5rem; border-radius: 4px;"><?= e($orderNo) ?></strong>
                </p>

                <hr style="border: 0; border-top: 1px solid var(--line); margin: 1.5rem 0;">

                <!-- บัญชีธนาคาร -->
                <div style="background: var(--surface-alt); border: 1px solid var(--line); border-radius: 8px; padding: 1rem; text-align: left; font-size: 0.9rem; margin-bottom: 1.5rem;">
                    <strong style="color: var(--ink); display: block; margin-bottom: 0.5rem;">💳 ช่องทางการชำระเงินค่าอบรม:</strong>
                    <div style="color: var(--ink); line-height: 1.5;">
                        <strong>ธนาคารกสิกรไทย (KBANK)</strong><br>
                        เลขที่บัญชี: <strong style="color: #2563eb; font-size: 1.05rem;">123-4-56789-0</strong><br>
                        ชื่อบัญชี: <strong>บริษัท สยาม ออนไลน์ เทรนนิ่ง จำกัด</strong>
                    </div>
                </div>

                <!-- ฟอร์มอัปโหลดสลิป -->
                <form method="post" action="/payment/upload-slip" enctype="multipart/form-data" style="text-align: left;">
	       <?= csrf_field() ?>
                    <input type="hidden" name="order_no" value="<?= e($orderNo) ?>">
                    <label style="font-weight: 600; font-size: 0.88rem; display: block; margin-bottom: 0.35rem; color: var(--ink);">
                        📤 อัปโหลดหลักฐานการโอนเงิน (สลิป):
                    </label>
                    <input type="file" name="slip_file" accept="image/*,.pdf" required class="form-control" style="width: 100%; padding: 0.4rem; border: 1px solid var(--line); border-radius: 4px; margin-bottom: 1rem; font-size: 0.85rem;">
                    
                    <button type="submit" class="btn btn--primary btn--block" style="width: 100%; background: #2563eb; color: #fff; padding: 0.75rem; border: none; border-radius: 6px; font-weight: 600; font-size: 0.95rem; cursor: pointer;">
                        แนบสลิปชำระเงิน
                    </button>
                </form>

                <div style="margin-top: 1.5rem;">
                    <a href="/courses" style="color: var(--muted); font-size: 0.88rem; text-decoration: underline;">กลับไปหน้าหลักสูตรทั้งหมด</a>
                </div>
            </div>
        </div>
    </div>
</section>