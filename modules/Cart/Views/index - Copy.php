<?php
/**
* ====================================================================
* Project Name : SIAM Online Training (SOT LMS)
* File Name    : index.php
* Module       : Cart Index View
* Description  : หน้าตะกร้าสินค้า ซิงค์จำนวนและรายชื่อระหว่าง LocalStorage และเซิร์ฟเวอร์อย่างแม่นยำ
* Author       : Software Engineering Team
* Last Updated : 2026-11-12
* ====================================================================
**/

declare(strict_types=1);

$this->partial('page-header', ['title' => 'ตะกร้าสินค้า / รายการอบรมที่เลือก', 'subtitle' => 'ตรวจสอบรายการหลักสูตร จำนวนผู้เข้าเรียน และประมาณการค่าใช้จ่าย', 'breadcrumbs' => $breadcrumbs]);

$items = is_array($items ?? null) ? $items : [];
$totalCourses = count($items);
$defaultPaymentDays = (int)($defaultPaymentDays ?? 20);
?>
<section class="section" style="padding-top:1.25rem;">
	<div class="container">
		<?php
		if ($totalCourses === 0) : ?>
		<div class="card" style="padding: 3rem; text-align: center; border-radius: var(--radius);">
			<div style="font-size: 3rem; margin-bottom: 0.5rem; color: var(--muted);"><?= icon('shopping-cart') ?></div>
			<h3 style="color: var(--ink); margin-bottom: 0.5rem;">ไม่มีรายการอบรมในตะกร้าสินค้า</h3>
			<p style="color: var(--muted); font-size: 0.95rem; margin-bottom: 1.5rem;">ท่านยังไม่ได้เลือกหลักสูตรหรือรุ่นการอบรม สามารถเลือกดูหลักสูตรที่เปิดรับสมัครได้ที่นี่</p>
			<a href="<?= e(url('/courses')) ?>" class="btn btn--primary" style="background: var(--brand-600); color: #fff; padding: 0.6rem 1.5rem; text-decoration: none; border-radius: 6px;">
			<?= icon('search') ?> เลือกดูหลักสูตรอบรม
			</a>
		</div>
		<?php else : ?>

		<div class="card" style="border-radius: 12px; border: 1px solid var(--line); background: #fff; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
			<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
				<div>
					<h2 style="font-size: 1.35rem; font-weight: 700; color: var(--ink); margin: 0;">
						ขั้นตอนที่ 1 ระบุรายชื่อผู้ที่จะเข้ารับการอบรม
					</h2>
					<div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.3rem; flex-wrap: wrap;">
						<span style="font-size: 0.75rem; font-weight: 700; color: #16a34a; letter-spacing: 0.5px;">
							รายชื่อสำหรับออกประกาศณียบัตร กรุณาตรวจสอบให้ถูกต้องทั้งหมด <?= $totalCourses ?> หลักสูตร
						</span>
						<span style="background: #e2e8f0; color: #475569; padding: 0.1rem 0.6rem; border-radius: 12px; font-size: 0.72rem; font-weight: 600;" id="header-total-attendees-badge">
						รวม 0 ท่าน
						</span>
					</div>
				</div>

				<?php if (!empty($_SESSION['user_id'])): ?>
					
				<?php else: ?>
					<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #eff6ff; padding: 1rem; border-radius: 10px; border: 1px solid #bfdbfe; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);">
					    <div style="color: #1e3a8a; font-size: 0.8rem; font-weight: 500; line-height: 1.6;">
					        ระบบจำเป็นต้องให้ท่าน <a href="#" onclick="openLoginModal(); return false;" style="color: #2563eb; font-weight: 700; text-decoration: underline; white-space: nowrap;">เข้าสู่ระบบ</a> ในการยืนยันการสั่งซื้อ<br/>
					        หากท่านยังไม่มีรหัส กรุณา<a href="#" onclick="openRegisterModal(); return false;" style="color: #2563eb; font-weight: 700; text-decoration: underline; white-space: nowrap;">สมัครสมาชิกลงทะเบียนใหม่</a> เพื่อรับรหัสเช้าใช้งานค่ะ
					    </div>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<form id="cart-checkout-form" method="post" action="<?= e(url('/checkout')) ?>">
			<?= csrf_field() ?>

			<div class="grid" style="grid-template-columns: 2.2fr 0.80fr; gap: 2rem; align-items: start;">
				<div>
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
						<div style="font-size: 1.05rem; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 0.5rem;">
							<span><?= icon('users') ?></span> รายการหลักสูตรและรายชื่อผู้เข้าอบรม
						</div>
						<a href="<?= e(url('/courses')) ?>" style="font-size: 0.85rem; color: var(--brand-600); text-decoration: none; font-weight: 600;">
						+ เพิ่มหลักสูตรใหม่อื่น
						</a>
					</div>

					<div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;" id="courses-wrapper">
						<?php $idx = 1;
						foreach ($items as $itemKey => $item) :
							$itemKeyStr = (string)($item['key'] ?? $itemKey);
							$cType = trim((string)($item['course_type'] ?? 'live_online'));
							$isClip = ($cType === 'clip_vdo' || $cType === 'clip' || $cType === 'vdo');
							$initialQty = max(1, (int)($item['quantity'] ?? 1));
						?>
						<div class="card course-item-container"
                             data-course-key="<?= e($itemKeyStr) ?>"
                             data-initial-qty="<?= $initialQty ?>"
                             style="border-radius: 12px; border: 1px solid var(--line); overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.02);"
                             id="course-card-<?= e($itemKeyStr) ?>">

							<div onclick="toggleCourseAccordion('<?= e($itemKeyStr) ?>')" style="padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; background: #fff; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
								<div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
									<span style="background: #dcfce7; color: #166534; font-weight: 700; font-size: 0.9rem; width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; border: 1px solid #bbf7d0; flex-shrink: 0;">
									<?= $idx ?>
									</span>
									<div style="flex: 1;">
										<div style="font-weight: 600; font-size: 1rem; color: var(--ink); margin-bottom: 0.3rem;">
											<?= e($item['course_title'] ?? '') ?>
										</div>
										<div style="display: flex; align-items: center; gap: 0.4rem;">
											<span style="display: inline-flex; align-items: center; color: <?= $isClip ? '#7c3aed' : '#dc2626' ?>;" title="<?= $isClip ? 'VDO On-Demand' : 'Live Interactive' ?>">
											<?= $isClip ? icon('video') : '' ?>
											</span>
											<span style="background: #e0f2fe; color: #0284c7; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.78rem; font-weight: 600; display: inline-block;">
											<?= e($item['date_text'] ?? 'ระบุเร็วๆ นี้') ?>
											</span>
										</div>
									</div>
								</div>

								<div style="display: flex; align-items: center; gap: 1.5rem;" onclick="event.stopPropagation()">
									<div style="text-align: right; cursor: pointer;" onclick="toggleCourseAccordion('<?= e($itemKeyStr) ?>')" title="คลิกเพื่อเปิด/ปิดจัดการรายชื่อผู้เข้าอบรม">
										<div style="font-size: 0.8rem; color: var(--muted); margin-bottom: 0.15rem;">
											<?php
											$rateTier = (string)($item['rate_tier'] ?? 'p1');
											$tierName = (string)($item['tier_name'] ?? '');
											$priceType = trim((string)($item['price_type'] ?? ''));
											$paymentType = trim((string)($item['payment_type'] ?? ''));

											$hasMemberSplit = ($priceType === 'member_split' || isset($item['price_2']) || isset($item['price_4']) || (float)($item['price_2'] ?? 0) > 0 || (float)($item['price_4'] ?? 0) > 0 || strpos($tierName, 'สมาชิก') !== false || $rateTier === 'p2' || $rateTier === 'p4');
											$hasPaymentDays = ($paymentType === 'days_before' || $paymentType === 'payment_days' || isset($item['price_3']) || isset($item['price_4']) || (float)($item['price_3'] ?? 0) > 0 || (float)($item['price_4'] ?? 0) > 0 || isset($item['payment_days']) || strpos($tierName, 'ก่อน') !== false || strpos($tierName, 'วัน') !== false || $rateTier === 'p3' || $rateTier === 'p4');

											$batchPaymentDays = isset($item['payment_days']) ? (int)$item['payment_days'] : $defaultPaymentDays;
											if (preg_match('/(\d+)\s*วัน/', $tierName, $matches)) {
												$batchPaymentDays = (int)$matches[1];
											}

											$isMemberSelected = ($rateTier === 'p2' || $rateTier === 'p4' || (strpos($tierName, 'สมาชิก') !== false && strpos($tierName, 'ทั่วไป') === false));
											$isPaymentSelected = ($rateTier === 'p3' || $rateTier === 'p4' || strpos($tierName, 'ก่อน') !== false || strpos($tierName, 'วัน') !== false);

											$tags = [];

											if ($hasMemberSplit && $hasPaymentDays) {
												$tags[] = $isMemberSelected ? 'ใช้สิทธิ์สมาชิก' : 'ไม่เป็นสมาชิก';
												$tags[] = $isPaymentSelected ? ('ชำระก่อนวันอบรม ' . $batchPaymentDays . ' วัน') : 'ชำระก่อนวันอบรม';
											} elseif ($hasMemberSplit && !$hasPaymentDays) {
												$tags[] = $isMemberSelected ? 'ใช้สิทธิ์สมาชิก' : 'ไม่เป็นสมาชิก';
											} elseif (!$hasMemberSplit && $hasPaymentDays) {
												$tags[] = $isPaymentSelected ? ('ชำระก่อนวันอบรม ' . $batchPaymentDays . ' วัน') : 'ชำระก่อนวันอบรม';
											}

											if (!empty($tags)) {
												echo e(implode(' / ', $tags));
											}
											?>
										</div>
										<div style="font-size: 0.85rem; color: var(--muted);">ราคาต่อท่าน:
											<strong style="color: var(--ink);">฿<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></strong>
										</div>
										<div style="font-size: 0.85rem; color: #166534; font-weight: 600;" id="learner-count-badge-<?= e($itemKeyStr) ?>">ผู้เรียน: 0 ท่าน (฿0.00)</div>
										<div style="font-size: 0.75rem; margin-top: 0.15rem; font-weight: 500;" id="learner-status-warning-<?= e($itemKeyStr) ?>"></div>
									</div>
									<div style="display: flex; align-items: center; gap: 0.5rem;">
										<a href="<?= e(url('/cart/remove?key=' . urlencode($itemKeyStr))) ?>" onclick="if(confirm('ยืนยันลบรายการนี้ใช่หรือไม่?')) { localStorage.removeItem('sot_cart_attendees_<?= e($itemKeyStr) ?>'); localStorage.removeItem('sot_cart_server_qty_<?= e($itemKeyStr) ?>'); localStorage.removeItem('sot_cart_last_qty_<?= e($itemKeyStr) ?>'); if(typeof updateGlobalCartBadge==='function') updateGlobalCartBadge(); return true; } return false;" style="color: var(--danger); padding: 0.4rem;" title="ลบรายการ">
										<?= icon('trash') ?>
										</a>
										<button type="button" id="arrow-btn-<?= e($itemKeyStr) ?>" style="background: none; border: none; font-size: 1.1rem; color: var(--muted); cursor: pointer; transition: transform 0.2s;" onclick="toggleCourseAccordion('<?= e($itemKeyStr) ?>')">
											▼
										</button>
									</div>
								</div>
							</div>

							<div id="body-<?= e($itemKeyStr) ?>" style="display: none; background: #f8fafc; padding: 1.25rem; border-top: 1px solid var(--line);">
								<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
									<div style="font-size: 0.85rem; color: var(--ink); font-weight: 600;">
										รายชื่อผู้เข้าอบรมในหลักสูตรนี้
									</div>
									<div style="display: flex; gap: 0.5rem;">
										<button type="button" onclick="openBulkModal('<?= e($itemKeyStr) ?>')" class="btn" style="background: #fff; border: 1px solid var(--line); color: var(--ink); font-size: 0.8rem; padding: 0.35rem 0.75rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.3rem; cursor: pointer;">
											📄 เพิ่มรายชื่อผู้เข้าอบรมหลายท่านอย่างง่าย
										</button>
										<button type="button" onclick="addAttendeeRow('<?= e($itemKeyStr) ?>')" class="btn" style="background: #16a34a; color: #fff; font-size: 0.8rem; padding: 0.35rem 0.75rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.3rem; border: none; cursor: pointer;">
											👤 + เพิ่มผู้เข้าอบรม
										</button>
									</div>
								</div>

								<div id="attendees-list-<?= e($itemKeyStr) ?>">
									<div id="empty-state-<?= e($itemKeyStr) ?>" style="border: 2px dashed var(--line); border-radius: 8px; padding: 2.5rem 1rem; text-align: center; background: #fff;">
										<div style="font-size: 2.5rem; color: var(--muted); margin-bottom: 0.5rem;">👥</div>
										<div style="font-size: 0.95rem; color: var(--ink); font-weight: 600; margin-bottom: 0.25rem;">ยังไม่มีรายชื่อผู้เข้าอบรมในหลักสูตรนี้</div>
										<div style="font-size: 0.8rem; color: var(--muted); margin-bottom: 1.25rem;">สามารถกดเพิ่มรายชื่อหรือเพิ่มหลายท่านอย่างง่ายตามจำนวนที่สั่งซื้อ</div>
										<div style="display: flex; justify-content: center; gap: 0.75rem;">
											<button type="button" onclick="addAttendeeRow('<?= e($itemKeyStr) ?>')" class="btn" style="background: #16a34a; color: #fff; font-size: 0.85rem; padding: 0.5rem 1.25rem; border-radius: 6px; font-weight: 600; border: none; cursor: pointer;">+ เพิ่มผู้เข้าอบรมท่านแรก</button>
											<button type="button" onclick="openBulkModal('<?= e($itemKeyStr) ?>')" class="btn" style="background: #fff; border: 1px solid var(--line); color: var(--ink); font-size: 0.85rem; padding: 0.5rem 1.25rem; border-radius: 6px; cursor: pointer;">เพิ่มรายชื่อผู้เข้าอบรมหลายท่านอย่างง่าย</button>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php $idx++;
					endforeach; ?>
					</div>
				</div>

				<!-- คอลัมน์ขวา: ใช้ระบบ JavaScript สไลด์ตามหน้าจอแบบสมบูรณ์ -->
				<div id="sticky-summary-column" style="position: relative;">
					<div id="sticky-summary-card" class="card" style="border-radius: 12px; border: 2px solid var(--brand-600); background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); will-change: transform; transition: transform 0.08s ease-out;">
						<div class="card__body" style="padding: 1.25rem;">
							<h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--ink);">
								สรุปยอดคำสั่งซื้อ
							</h3>

							<div class="flex justify-between items-center" style="font-size: 0.9rem; margin-bottom: 0.5rem; white-space: nowrap;">
								<span id="summary-subtotal-label" style="color: var(--muted);">ค่าอบรมรวม <span style="background: #dcfce7; color: #166534; padding: 0.1rem 0.6rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">0</span> ท่าน :</span>
								<strong id="summary-subtotal" style="color: var(--ink);">0.00 บาท</strong>
							</div>

							<div class="flex justify-between items-center" style="font-size: 0.85rem; color: #166534; margin-bottom: 0.4rem; white-space: nowrap;">
								<span>ภาษีมูลค่าเพิ่ม VAT (7%):</span>
								<span id="summary-vat">+0.00 บาท</span>
							</div>

							<div class="flex justify-between items-center" style="font-size: 0.85rem; color: #166534; margin-bottom: 0.2rem; white-space: nowrap;">
								<span>หัก ณ ที่จ่าย WHT (3%):</span>
								<span id="summary-wht">-0.00 บาท</span>
							</div>
							<div style="font-size: 0.72rem; color: #dc2626; font-style: italic; margin-bottom: 0.8rem; text-align: right; line-height: 1.2;">
								* WHT เปลี่ยนได้ตามเรทจริงของบริษัทในขั้นตอนต่อไป
							</div>

							<div class="flex justify-between items-center" style="border-top: 2px solid #16a34a; padding-top: 0.75rem; margin-bottom: 1.25rem; white-space: nowrap;">
								<span style="font-weight: 700; color: var(--ink);">ยอดเงินรวมสุทธิ :</span>
								<strong style="font-size: 1.2rem; color: #15803d;" id="summary-grand-total">0.00 บาท</strong>
							</div>

							<button type="submit" class="btn btn--primary btn--block" style="display: block; width: 100%; text-align: center; background: #16a34a; color: #fff; padding: 0.75rem; border-radius: 6px; border: none; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
								ยืนยันรายชื่อ & ไปหน้าข้อมูลบริษัท &rarr;
							</button>
						</div>
					</div>
				</div>
			</div>
		</form>
		<?php
	endif; ?>
	</div>
</section>

<div id="bulk-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center; padding: 1rem;">
	<div style="background: #fff; width: 100%; max-width: 680px; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
		<div style="padding: 1rem 1.25rem; background: var(--surface-alt); border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
			<strong style="font-size: 0.95rem; color: var(--ink);">📄 เพิ่มรายชื่อผู้เข้าอบรมหลายท่านอย่างง่าย</strong>
			<button type="button" onclick="closeBulkModal()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--ink);">&times;</button>
		</div>
		<div style="padding: 1.25rem;">
			<div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 6px; padding: 0.75rem; font-size: 0.78rem; color: var(--muted); margin-bottom: 1rem;">
				รูปแบบที่รองรับ (ใช้เครื่องหมายจุลภาค
				<strong style="color: #dc2626;">,</strong> คั่นเท่านั้น 1 บรรทัดต่อ 1 ท่าน):<br>
				<code style="color: #16a34a; font-weight: bold;">[คำนำหน้า], [ชื่อ], [นามสกุล], [อีเมล], [เบอร์โทร], [เลขบัตรประชาชน], [ตำแหน่ง], [ความต้องการอื่นๆ]</code><br>
				ตัวอย่าง: นาย, สมชาย, มั่นคง, somchai@company.com, 0812345678, 1234567890123, วิศวกรอาวุโส, อาหารอิสลาม
			</div>
			<textarea id="bulk-textarea-input" rows="6" class="form-control" placeholder="วางข้อมูลที่นี่ (คั่นแต่ละช่องด้วยเครื่องหมาย , เท่านั้น)..." style="width: 100%; font-size: 0.85rem; padding: 0.5rem; border: 1px solid var(--line); border-radius: 4px; margin-bottom: 1rem;"></textarea>
			<div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
				<button type="button" onclick="closeBulkModal()" class="btn" style="background: #e2e8f0; border: 1px solid #cbd5e1; color: #1e293b; font-weight: 600; padding: 0.45rem 1.1rem; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">ยกเลิก</button>
				<button type="button" onclick="processBulkPaste()" class="btn" style="background: #16a34a; color: #fff; font-weight: 600; padding: 0.45rem 1.1rem; border-radius: 6px; font-size: 0.85rem; border: none; cursor: pointer;">นำเข้ารายชื่อเข้าสู่หลักสูตร</button>
			</div>
		</div>
	</div>
</div>

<script>
	const courseUnitPrices = {};
	<?php
	if (!empty($items)) :
		?>
		<?php foreach ($items as $ik => $it): ?>
		courseUnitPrices["<?= addslashes((string)($it['key'] ?? $ik)) ?>"] = <?= (float)($it['unit_price'] ?? 0) ?>;
		<?php endforeach; ?>
		<?php endif; ?>

		let activeBulkCourseKey = null;

		function saveCartStateToLocalStorage(key)
		{
		let listContainer = document.getElementById('attendees-list-' + key);
		if (!listContainer)
			return;

		let rows = listContainer.querySelectorAll('.attendee-row-card');
		let attendeesData = [];

		rows.forEach(row => {
			attendeesData.push({
				prefix:             row.querySelector('[data-field="prefix"]')?.value || '',
				custom_prefix:      row.querySelector('[data-field="custom_prefix"]')?.value || '',
				first_name:         row.querySelector('[data-field="first_name"]')?.value || '',
				last_name:          row.querySelector('[data-field="last_name"]')?.value || '',
				email:              row.querySelector('[data-field="email"]')?.value || '',
				phone:              row.querySelector('[data-field="phone"]')?.value || '',
				id_card:            row.querySelector('[data-field="id_card"]')?.value || '',
				position:           row.querySelector('[data-field="position"]')?.value || '',
				other_requirements: row.querySelector('[data-field="other_requirements"]')?.value || ''
			});
		});

		localStorage.setItem('sot_cart_attendees_' + key, JSON.stringify(attendeesData));
		localStorage.setItem('sot_cart_user_qty_' + key, attendeesData.length.toString());

		if (typeof updateGlobalCartBadge === 'function') {
			updateGlobalCartBadge();
		}
	}

	function toggleCourseAccordion(key)
	{
	let body = document.getElementById('body-' + key);
	let arrow = document.getElementById('arrow-btn-' + key);
	if (!body)
		return;

	if (body.style.display === 'none' || body.style.display === '') {
		body.style.display = 'block';
		if (arrow)
			arrow.style.transform = 'rotate(180deg)';
	} else {
		body.style.display = 'none';
		if (arrow)
			arrow.style.transform = 'rotate(0deg)';
	}
	setTimeout(updateStickySummaryPosition, 50);
	}

function onPrefixChange(selectEl, rowId, key)
{
let customInput = document.getElementById('custom-prefix-container-' + rowId);
if (customInput) {
	if (selectEl.value === 'อื่นๆ') {
		customInput.style.display = 'block';
	} else {
		customInput.style.display = 'none';
		let inputField = customInput.querySelector('input');
		if (inputField)
			inputField.value = '';
	}
}
saveCartStateToLocalStorage(key);
	}

function escapeHtml(str)
{
return String(str)
.replace(/&/g, '&amp;')
.replace(/"/g, '&quot;')
.replace(/'/g, '&#039;')
.replace(/</g, '&lt;')
.replace(/>/g, '&gt;');
	}

function addAttendeeRow(key, data = {})
{
let listContainer = document.getElementById('attendees-list-' + key);
if (!listContainer)
	return;

let emptyState = document.getElementById('empty-state-' + key);
if (emptyState)
	emptyState.remove();

let index = listContainer.querySelectorAll('.attendee-row-card').length;
let rowId = 'row_' + key.replace(/[^a-zA-Z0-9]/g, '_') + '_' + Date.now() + '_' + index;

let rawPrefix = (data.prefix !== undefined) ? data.prefix : '';
let standardPrefixes = ['', 'นาย', 'นาง', 'นางสาว'];
let isStandardPrefix = standardPrefixes.includes(rawPrefix);
let selectValue = isStandardPrefix ? rawPrefix : 'อื่นๆ';
let customPrefixVal = isStandardPrefix ? '' : rawPrefix;

let firstName = escapeHtml(data.first_name || '');
let lastName  = escapeHtml(data.last_name || '');
let email     = escapeHtml(data.email || '');
let phone     = escapeHtml(data.phone || '');
let idCard    = escapeHtml(data.id_card || '');
let position  = escapeHtml(data.position || '');
let otherReq  = escapeHtml(data.other_requirements || '');

let html = `
        <div class="attendee-row-card card mb-2" id="${rowId}" style="border: 1px solid var(--line); border-radius: 8px; padding: 1rem; background: #fff; margin-bottom: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span style="font-size: 0.85rem; font-weight: 700; color: #16a34a;">ผู้เข้าอบรมท่านที่ <span class="attendee-number">${index + 1}</span></span>
                <button type="button" onclick="removeAttendeeRow('${key}', '${rowId}')" style="background: none; border: none; color: var(--danger); font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.2rem;">
                    🗑️ ลบ
                </button>
            </div>
            <div class="grid" style="grid-template-columns: 1.2fr 1.5fr 1.5fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                <div>
                    <label style="font-size:0.72rem; color:var(--muted);">คำนำหน้า</label>
                    <select name="attendees[${key}][${index}][prefix]" data-field="prefix" id="prefix-select-${rowId}" onchange="onPrefixChange(this, '${rowId}', '${key}')" class="form-control" style="width:100%; font-size:0.8rem; padding:0.3rem;">
                        <option value="" ${selectValue===''?'selected':''}>(เลือก)</option>
                        <option value="นาย" ${selectValue==='นาย'?'selected':''}>นาย</option>
                        <option value="นาง" ${selectValue==='นาง'?'selected':''}>นาง</option>
                        <option value="นางสาว" ${selectValue==='นางสาว'?'selected':''}>นางสาว</option>
                        <option value="อื่นๆ" ${selectValue==='อื่นๆ'?'selected':''}>อื่นๆ (ระบุ)</option>
                    </select>
                    <div id="custom-prefix-container-${rowId}" style="margin-top: 0.3rem; display: ${selectValue==='อื่นๆ'?'block':'none'};">
                        <input type="text" name="attendees[${key}][${index}][custom_prefix]" data-field="custom_prefix" oninput="saveCartStateToLocalStorage('${key}')" value="${escapeHtml(customPrefixVal)}" placeholder="ระบุคำนำหน้า" class="form-control" style="width:100%; font-size:0.8rem; padding:0.25rem;">
                    </div>
                </div>
                <div>
                    <label style="font-size:0.72rem; color:var(--muted);">ชื่อ (ภาษาไทย) *</label>
                    <input type="text" name="attendees[${key}][${index}][first_name]" data-field="first_name" oninput="saveCartStateToLocalStorage('${key}')" value="${firstName}" placeholder="เช่น สมชาย" required class="form-control" style="width:100%; font-size:0.8rem; padding:0.3rem;">
                </div>
                <div>
                    <label style="font-size:0.72rem; color:var(--muted);">นามสกุล (ภาษาไทย) *</label>
                    <input type="text" name="attendees[${key}][${index}][last_name]" data-field="last_name" oninput="saveCartStateToLocalStorage('${key}')" value="${lastName}" placeholder="เช่น มั่นคง" required class="form-control" style="width:100%; font-size:0.8rem; padding:0.3rem;">
                </div>
            </div>
            <div class="grid" style="grid-template-columns: 1.5fr 1fr 1.5fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                <div>
                    <label style="font-size:0.72rem; color:var(--muted);">อีเมลผู้เรียน</label>
                    <input type="email" name="attendees[${key}][${index}][email]" data-field="email" oninput="saveCartStateToLocalStorage('${key}')" value="${email}" placeholder="somchai@company.com" class="form-control" style="width:100%; font-size:0.8rem; padding:0.3rem;">
                </div>
                <div>
                    <label style="font-size:0.72rem; color:var(--muted);">เบอร์โทรศัพท์</label>
                    <input type="text" name="attendees[${key}][${index}][phone]" data-field="phone" oninput="saveCartStateToLocalStorage('${key}')" value="${phone}" placeholder="081-234-5678" class="form-control" style="width:100%; font-size:0.8rem; padding:0.3rem;">
                </div>
                <div>
                    <label style="font-size:0.72rem; color:var(--muted);">เลขบัตรประชาชน (13 หลัก)</label>
                    <input type="text" name="attendees[${key}][${index}][id_card]" data-field="id_card" oninput="saveCartStateToLocalStorage('${key}')" value="${idCard}" placeholder="1-2345-67890-12-3" class="form-control" style="width:100%; font-size:0.8rem; padding:0.3rem;">
                </div>
            </div>
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                <div>
                    <label style="font-size:0.72rem; color:var(--muted);">ตำแหน่งงาน</label>
                    <input type="text" name="attendees[${key}][${index}][position]" data-field="position" oninput="saveCartStateToLocalStorage('${key}')" value="${position}" placeholder="เช่น วิศวกร" class="form-control" style="width:100%; font-size:0.8rem; padding:0.3rem;">
                </div>
                <div>
                    <label style="font-size:0.72rem; color:var(--muted);">ความต้องการอื่นๆ (ถ้ามี)</label>
                    <input type="text" name="attendees[${key}][${index}][other_requirements]" data-field="other_requirements" oninput="saveCartStateToLocalStorage('${key}')" value="${otherReq}" placeholder="เช่น อาหารมังสวิรัติ, อิสลาม" class="form-control" style="width:100%; font-size:0.8rem; padding:0.3rem;">
                </div>
            </div>
        </div>`;

listContainer.insertAdjacentHTML('beforeend', html);
recalculateTotals();
saveCartStateToLocalStorage(key);
setTimeout(updateStickySummaryPosition, 50);
	}

function removeAttendeeRow(key, rowId)
{
let row = document.getElementById(rowId);
if (row)
	row.remove();

let listContainer = document.getElementById('attendees-list-' + key);
if (!listContainer)
	return;

let rows = listContainer.querySelectorAll('.attendee-row-card');
rows.forEach((r, idx) => {
	let numSpan = r.querySelector('.attendee-number');
	if (numSpan)
		numSpan.innerText = idx + 1;
	r.querySelectorAll('input, select').forEach(input => {
		input.name = input.name.replace(/\[\d+\]/, '[' + idx + ']');
	});
	});

if (rows.length === 0) {
	listContainer.innerHTML = `
            <div id="empty-state-${key}" style="border: 2px dashed var(--line); border-radius: 8px; padding: 2.5rem 1rem; text-align: center; background: #fff;">
                <div style="font-size: 2.5rem; color: var(--muted); margin-bottom: 0.5rem;">👥</div>
                <div style="font-size: 0.95rem; color: var(--ink); font-weight: 600; margin-bottom: 0.25rem;">ยังไม่มีรายชื่อผู้เข้าอบรมในหลักสูตรนี้</div>
                <div style="font-size: 0.8rem; color: var(--muted); margin-bottom: 1.25rem;">สามารถกดเพิ่มรายชื่อหรือเพิ่มหลายท่านอย่างง่ายตามจำนวนที่สั่งซื้อ</div>
                <div style="display: flex; justify-content: center; gap: 0.75rem;">
                    <button type="button" onclick="addAttendeeRow('${key}')" class="btn" style="background: #16a34a; color: #fff; font-size: 0.85rem; padding: 0.5rem 1.25rem; border-radius: 6px; font-weight: 600; border: none; cursor: pointer;">+ เพิ่มผู้เข้าอบรมท่านแรก</button>
                    <button type="button" onclick="openBulkModal('${key}')" class="btn" style="background: #fff; border: 1px solid var(--line); color: var(--ink); font-size: 0.85rem; padding: 0.5rem 1.25rem; border-radius: 6px; cursor: pointer;">เพิ่มรายชื่อผู้เข้าอบรมหลายท่านอย่างง่าย</button>
                </div>
            </div>`;
}

recalculateTotals();
saveCartStateToLocalStorage(key);
setTimeout(updateStickySummaryPosition, 50);
	}

function openBulkModal(key)
{
activeBulkCourseKey = key;
document.getElementById('bulk-textarea-input').value = '';
document.getElementById('bulk-modal').style.display = 'flex';
	}

function closeBulkModal()
{
document.getElementById('bulk-modal').style.display = 'none';
activeBulkCourseKey = null;
	}

function processBulkPaste()
{
if (!activeBulkCourseKey)
	return;
let text = document.getElementById('bulk-textarea-input').value.trim();
if (!text) {
	alert('กรุณาวางข้อมูลรายชื่อก่อน');
	return;
}

let lines = text.split('\n');
let addedCount = 0;

lines.forEach(line => {
	line = line.trim();
	if (!line)
		return;

	let parts = line.split(',').map(p => p.trim().replace(/^['"]|['"]$/g, ''));

	if (parts.length >= 2) {
		addAttendeeRow(activeBulkCourseKey, {
			prefix:             parts[0] || '',
			first_name:         parts[1] || '',
			last_name:          parts[2] || '',
			email:              parts[3] || '',
			phone:              parts[4] || '',
			id_card:            parts[5] || '',
			position:           parts[6] || '',
			other_requirements: parts[7] || ''
		});
		addedCount++;
	}
	});

if (addedCount === 0) {
	alert('ไม่พบข้อมูลที่ถูกต้อง กรุณาตรวจสอบว่าใช้เครื่องหมายจุลภาค (,) คั่นระหว่างข้อมูลในแต่ละช่อง');
	return;
}

closeBulkModal();
setTimeout(updateStickySummaryPosition, 50);
	}

function recalculateTotals()
{
let grandSubtotal = 0;
let totalAttendeesCount = 0;

Object.keys(courseUnitPrices).forEach(k => {
	let listContainer = document.getElementById('attendees-list-' + k);
	let count = listContainer ? listContainer.querySelectorAll('.attendee-row-card').length : 0;
	totalAttendeesCount += count;

	let unitPrice = courseUnitPrices[k] || 0;
	let subtotal = count * unitPrice;

	grandSubtotal += subtotal;

	let badge = document.getElementById('learner-count-badge-' + k);
	if (badge) {
		badge.innerText = `ผู้เรียน: ${count} ท่าน (฿${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})})`;
	}

	let warningEl = document.getElementById('learner-status-warning-' + k);
	if (warningEl) {
		if (count === 0) {
			warningEl.style.color = '#dc2626';
			warningEl.innerText = '⚠️ ยังไม่ได้ใส่ชื่อผู้เรียน';
		} else {
			let rows = listContainer ? listContainer.querySelectorAll('.attendee-row-card') : [];
			let incomplete = false;
			rows.forEach(r => {
				let fName = r.querySelector('[data-field="first_name"]')?.value.trim() || '';
				let lName = r.querySelector('[data-field="last_name"]')?.value.trim() || '';
				if (!fName || !lName) {
					incomplete = true;
				}
			});

			if (incomplete) {
				warningEl.style.color = '#d97706';
				warningEl.innerText = '⚠️ กรอกข้อมูลรายชื่อยังไม่ครบถ้วน';
			} else {
				warningEl.style.color = '#16a34a';
				warningEl.innerText = '✓ ใส่รายชื่อผู้เรียนครบถ้วนแล้ว';
			}
		}
	}
	});

let headerBadge = document.getElementById('header-total-attendees-badge');
if (headerBadge)
	headerBadge.innerText = `รวม ${totalAttendeesCount} ท่าน`;

let vat = grandSubtotal * 0.07;
let wht = grandSubtotal * 0.03;
let grandTotal = grandSubtotal + vat - wht;

let subtotalLabelEl = document.getElementById('summary-subtotal-label');
let subtotalEl      = document.getElementById('summary-subtotal');
let vatEl           = document.getElementById('summary-vat');
let whtEl           = document.getElementById('summary-wht');
let grandTotalEl    = document.getElementById('summary-grand-total');

// ใช้ innerHTML เพื่ออัปเดตตัวเลขจำนวนท่านภายในแท็ก <span> ได้อย่างถูกต้อง
if (subtotalLabelEl)
	subtotalLabelEl.innerHTML = `ค่าอบรมรวม <span style="background: #dcfce7; color: #166534; padding: 0.1rem 0.6rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">${totalAttendeesCount}</span> ท่าน :`;
if (subtotalEl)
	subtotalEl.innerText   = grandSubtotal.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' บาท';
if (vatEl)
	vatEl.innerText        = '+' + vat.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' บาท';
if (whtEl)
	whtEl.innerText        = '-' + wht.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' บาท';
if (grandTotalEl)
	grandTotalEl.innerText = grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' บาท';

if (typeof updateGlobalCartBadge === 'function') {
	updateGlobalCartBadge();
}
}

function submitCartToStep2()
{
let form = document.getElementById('cart-checkout-form');
if (form) {
	if (form.checkValidity()) {
		form.submit();
	} else {
		form.reportValidity();
	}
}
}

// ระบบคำนวณการเลื่อนตามหน้าจอ (Smart Floating Summary Box) ป้องกันการชน Footer และหลุดกรอบ
function updateStickySummaryPosition() {
	const col = document.getElementById('sticky-summary-column');
	const card = document.getElementById('sticky-summary-card');
	if (!col || !card) return;

	if (window.innerWidth <= 992) {
		card.style.transform = 'none';
		return;
	}

	const topMargin = 95; // ระยะห่างจากขอบบนเพื่อหลบแถบเมนู
	const colRect = col.getBoundingClientRect();
	const cardHeight = card.offsetHeight;
	
	// หาฟอร์มหรือคอนเทนเนอร์หลักเพื่อดูขอบเขตสิ้นสุด
	const formEl = document.getElementById('cart-checkout-form');
	const formRect = formEl ? formEl.getBoundingClientRect() : colRect;

	// ระยะที่เลื่อนผ่านไปแล้วจากจุดเริ่มต้นของคอลัมน์
	let scrollOffset = topMargin - colRect.top;

	// ขอบเขตสูงสุดที่กล่องจะเลื่อนลงไปได้ (ไม่ให้ทะลุความสูงของคอลัมน์ซ้าย)
	const maxScroll = formRect.height - cardHeight - 20;

	if (scrollOffset > 0) {
		let finalOffset = Math.min(scrollOffset, maxScroll);
		card.style.transform = `translateY(${finalOffset}px)`;
	} else {
		card.style.transform = 'translateY(0px)';
	}
}

window.addEventListener('scroll', updateStickySummaryPosition, { passive: true });
window.addEventListener('resize', updateStickySummaryPosition);

document.addEventListener("DOMContentLoaded", function() {
let courseContainers = document.querySelectorAll('.course-item-container');

if (courseContainers.length === 0) {
	for (let i = localStorage.length - 1; i >= 0; i--) {
		let key = localStorage.key(i);
		if (key && (key.startsWith('sot_cart_attendees_') || key.startsWith('sot_cart_user_qty_') || key.startsWith('sot_cart_server_qty_'))) {
			localStorage.removeItem(key);
		}
	}
	if (typeof updateGlobalCartBadge === 'function') {
		updateGlobalCartBadge();
	}
	return;
}

let activeKeys = Array.from(courseContainers).map(c => c.getAttribute('data-course-key'));
for (let i = localStorage.length - 1; i >= 0; i--) {
	let key = localStorage.key(i);
	if (key && (key.startsWith('sot_cart_attendees_') || key.startsWith('sot_cart_user_qty_') || key.startsWith('sot_cart_server_qty_'))) {
		let courseKey = key.replace('sot_cart_attendees_', '').replace('sot_cart_user_qty_', '').replace('sot_cart_server_qty_', '');
		if (!activeKeys.includes(courseKey)) {
			localStorage.removeItem(key);
		}
	}
}

courseContainers.forEach(container => {
	let key = container.getAttribute('data-course-key');
	let initialQty = parseInt(container.getAttribute('data-initial-qty')) || 1;

	let savedData = null;
	try {
		let raw = localStorage.getItem('sot_cart_attendees_' + key);
		if (raw !== null) {
			savedData = JSON.parse(raw);
		}
	} catch (e) {
		savedData = null;
	}

	let userQty = localStorage.getItem('sot_cart_user_qty_' + key);
	let hasUserInteracted = userQty !== null;
	let targetQty = hasUserInteracted ? parseInt(userQty) : initialQty;

	let storedServerQty = localStorage.getItem('sot_cart_server_qty_' + key);
	storedServerQty = storedServerQty !== null ? parseInt(storedServerQty) : null;

	let attendeesToLoad = [];

	if (!hasUserInteracted) {
		for (let i = 0; i < initialQty; i++) {
			attendeesToLoad.push({});
		}
	} else if (storedServerQty !== null && initialQty > storedServerQty) {
		attendeesToLoad = Array.isArray(savedData) ? savedData : [];
		let addedDiff = initialQty - storedServerQty;
		for (let i = 0; i < addedDiff; i++) {
			attendeesToLoad.push({});
		}
	} else {
		attendeesToLoad = Array.isArray(savedData) ? savedData.slice(0, targetQty) : [];
	}

	attendeesToLoad.forEach(att => addAttendeeRow(key, att));

	localStorage.setItem('sot_cart_server_qty_' + key, initialQty.toString());
	saveCartStateToLocalStorage(key);
	});

recalculateTotals();

if (courseContainers.length === 1) {
	let singleKey = courseContainers[0].getAttribute('data-course-key');
	toggleCourseAccordion(singleKey);
}

setTimeout(updateStickySummaryPosition, 100);
});
</script>