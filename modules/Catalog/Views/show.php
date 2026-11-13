<?php
/**
* ====================================================================
* Project Name : SIAM Online Training (SOT LMS)
* File Name : show.php
* Module : Catalog - Course Detail View
* Description : Displays course details with connected Add-to-Cart Action based on strict 3-step rules.
* Author : Software Engineering Team
* Last Updated : 2026-11-12
* ====================================================================
**/

declare(strict_types=1);


// ตั้งค่า true เพื่อปิดการใช้งาน (disable) checkbox สิทธิ์สมาชิกและการชำระเงินล่วงหน้า (เปลี่ยนเป็น false เพื่อเปิดใช้งาน)
$disableSpecialRates = false;

// ตั้งค่า true เพื่อคำนวณและแสดงรายการภาษี (VAT/WHT) ในหน้านี้ | ตั้งค่า false เพื่อซ่อนและยังไม่รวมยอดภาษี (ไปรวมทีเดียวในหน้ายืนยัน)
$includeTaxInShow = false;

$defaultPaymentDays = (int)($defaultPaymentDays ?? 20);


$this->partial('page-header', ['title' => $course['title'], 'subtitle' => $course['summary'], 'breadcrumbs' => $breadcrumbs]);
?>
<section class="section" style="padding-top:1.25rem;">
	<div class="container">
		<style>
			.course-top-full {
				width: 100%;
				margin-bottom: 1.5rem;
			}
			.course-main-grid {
				display: grid;
				grid-template-columns: 1.8fr 1.2fr;
				gap: 2rem;
				align-items: start;
				margin-bottom: 1.5rem;
			}
			.course-price-card {
				position: sticky;
				top: 5rem;
				z-index: 10;
			}
			.brochure-view-wrapper {
				position: relative;
				width: 100%;
				aspect-ratio: 1920 / 1080;
				background: #111;
				border-radius: 6px;
				overflow: hidden;
				cursor: zoom-in;
			}
			.brochure-main-img {
				width: 100%;
				height: 100%;
				object-fit: cover;
				transition: transform 0.1s ease-out;
				transform-origin: center center;
			}
			.zoom-btn-overlay {
				position: absolute;
				bottom: 12px;
				right: 12px;
				background: rgba(0, 0, 0, 0.6);
				color: #fff;
				border: none;
				border-radius: 50%;
				width: 40px;
				height: 40px;
				display: grid;
				place-items: center;
				cursor: pointer;
				transition: background 0.2s ease;
				z-index: 5;
			}
			.zoom-btn-overlay:hover {
				background: rgba(0, 0, 0, 0.8);
			}
			.brochure-thumb-container {
				display: flex;
				gap: 0.75rem;
				overflow-x: auto;
				padding-top: 0.75rem;
				margin-top: 0.75rem;
				border-top: 1px solid var(--line);
			}
			.brochure-thumb-item {
				width: 90px;
				aspect-ratio: 720 / 1040;
				flex-shrink: 0;
				border-radius: 4px;
				overflow: hidden;
				border: 2px solid var(--line);
				cursor: pointer;
				transition: border-color 0.2s ease;
			}
			.brochure-thumb-item:hover {
				border-color: var(--brand-600);
			}
			.brochure-thumb-item img {
				width: 100%;
				height: 100%;
				object-fit: cover;
			}
			#image-lightbox {
				display: none;
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background: rgba(0, 0, 0, 0.85);
				z-index: 9999;
				justify-content: center;
				align-items: center;
				padding: 2rem;
			}
			#image-lightbox.active {
				display: flex;
			}
			#image-lightbox img {
				max-width: 90%;
				max-height: 90vh;
				object-fit: contain;
				border-radius: 4px;
				box-shadow: 0 10px 25px rgba(0,0,0,0.5);
			}
			.lightbox-close {
				position: absolute;
				top: 20px;
				right: 30px;
				color: #fff;
				font-size: 2.5rem;
				cursor: pointer;
				background: none;
				border: none;
			}
			.course-tabs {
				display: flex;
				gap: 1.5rem;
				border-bottom: 2px solid var(--line);
				margin-bottom: 1.25rem;
				list-style: none;
				padding: 0;
			}
			.course-tab-btn {
				background: none;
				border: none;
				padding: 0.75rem 0.25rem;
				font-size: 0.95rem;
				font-weight: 600;
				color: var(--muted);
				cursor: pointer;
				position: relative;
				transition: color 0.2s ease;
			}
			.course-tab-btn.active {
				color: var(--brand-600);
			}
			.course-tab-btn.active::after {
				content: '';
				position: absolute;
				bottom: -2px;
				left: 0;
				width: 100%;
				height: 2px;
				background-color: var(--brand-600);
			}
			.tab-pane {
				display: none;
			}
			.tab-pane.active {
				display: block;
			}
			.price-card-box {
				background: var(--surface-alt);
				padding: 0.75rem;
				border-radius: var(--radius-sm);
				border: 1px solid var(--line);
				transition: all 0.2s ease;
			}
			.price-card-box.active {
				background: #f0fdf4;
				border: 2px solid #16a34a;
			}
			@media (max-width: 992px) {
				.course-main-grid {
					grid-template-columns: 1fr !important;
				}.course-price-card {
					position: static !important;
				}
			}
		</style>

		<?php
		$requestedBatchId = isset($_GET['batch']) ? (int)$_GET['batch'] : 0;
		$activeBatch = null;
		if ($requestedBatchId > 0 && !empty($batches)) {
			foreach ($batches as $b) {
				if ((int)$b['id'] === $requestedBatchId) {
					$activeBatch = $b;
					break;
				}
			}
		}
		if (!$activeBatch) {
			$activeBatch = $batches[0] ?? null;
		}
		?>

		<!-- 1. ส่วนบนเต็มหน้า: ข้อมูลหลักสูตร -->
		<div class="course-top-full">
			<div class="card">
				<div class="card__body">
					<h2 style="font-size:1.6rem; margin-bottom:.5rem; color:var(--ink); line-height:1.3;"><?= e($course['title']) ?></h2>
					<p style="color:var(--muted); font-size:.95rem; margin-bottom:1.25rem;"><?= e($course['summary'] ?? '') ?></p>

					<div class="grid" style="grid-template-columns: 1.5fr 1fr 1fr 0.8fr 0.6fr; gap:.75rem;">
						<div style="background:var(--surface-alt); padding:.75rem; border-radius:var(--radius); display:flex; align-items:center; gap:.75rem;">
							<div style="color:var(--brand-600); font-size:1.2rem;"><?= icon('calendar') ?></div>
							<div>
								<div style="font-size:.7rem; color:var(--muted);">วันที่อบรม (รุ่น <?= e($activeBatch['batch_no'] ?? '1') ?>)</div>
								<div style="font-weight:600; font-size:.85rem; color:var(--ink);"><?= $activeBatch ? e($activeBatch['date_text'] ?? 'ระบุเร็วๆ นี้') : 'ระบุเร็วๆ นี้' ?></div>
							</div>
						</div>
						<div style="background:var(--surface-alt); padding:.75rem; border-radius:var(--radius); display:flex; align-items:center; gap:.75rem;">
							<div style="color:var(--brand-600); font-size:1.2rem;"><?= icon('clock') ?></div>
							<div>
								<div style="font-size:.7rem; color:var(--muted);">เวลา</div>
								<div style="font-weight:600; font-size:.85rem; color:var(--ink);"><?= e($activeBatch['time_text'] ?? '09:00 - 16:00 น.') ?></div>
							</div>
						</div>
						<div style="background:var(--surface-alt); padding:.75rem; border-radius:var(--radius); display:flex; align-items:center; gap:.75rem;">
							<div style="color:var(--brand-600); font-size:1.2rem;"><?= icon('video') ?></div>
							<div>
								<div style="font-size:.7rem; color:var(--muted);">รูปแบบการอบรม</div>
								<div style="font-weight:600; font-size:.85rem; color:var(--ink);">
									<?= (($course['type'] ?? '') === 'clip_vdo') ? 'หลักสูตรออนไลน์' : 'อบรม Live สดผ่าน Zoom' ?>
								</div>
							</div>
						</div>
						<div style="background:var(--surface-alt); padding:.75rem; border-radius:var(--radius); display:flex; align-items:center; gap:.75rem;">
							<div style="color:var(--brand-600); font-size:1.2rem;"><?= icon('award') ?></div>
							<div>
								<div style="font-size:.7rem; color:var(--muted);">ประกาศนียบัตร</div>
								<div style="font-weight:600; font-size:.85rem; color:var(--brand-700);">ได้รับ e-Certificate</div>
							</div>
						</div>
						<div style="background:var(--surface-alt); padding:.75rem; border-radius:var(--radius); display:flex; align-items:center; gap:.75rem;">
							<div style="color:var(--danger); font-size:1.2rem;"><?= icon('file-text') ?></div>
							<div style="overflow:hidden;">
								<div style="font-size:.7rem; color:var(--muted);">เอกสารหลักสูตร</div>
								<?php
								if (!empty($activeBatch['document_file'])) :
									$docPath = trim($activeBatch['document_file']);
									if (strpos($docPath, 'uploads/') !== 0) {
										$docPath = 'uploads/' . ltrim($docPath, '/');
									}
								?>
								<a href="<?= e(url($docPath)) ?>" target="_blank" style="font-weight:600; font-size:.85rem; color:var(--brand-650); text-decoration:underline; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
								<?= e($activeBatch['document_title'] ?? 'ดาวน์โหลด') ?>
								</a>
								<?php else : ?>
								<span style="font-weight:600; font-size:.85rem; color:var(--muted); display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
								ไม่มีไฟล์แนบ
								</span>
								<?php
							endif; ?>
							</div>
						</div>
					</div>

					<?php
					if (count($batches) > 1) : ?>
					<div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--line); display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
						<span style="font-size: 0.85rem; font-weight: 600; color: var(--muted);">เลือกรุ่นอบรม:</span>
						<?php
						foreach ($batches as $b) :
							$isSel = ((int)$b['id'] === (int)$activeBatch['id']);
						?>
						<a href="<?= e(url('/courses/' . $course['slug'] . '?batch=' . $b['id'])) ?>" class="badge <?= $isSel ? 'badge--solid' : '' ?>" style="padding: 0.3rem 0.7rem; border-radius: 4px; text-decoration: none; font-size: 0.8rem; <?= $isSel ? 'background: var(--brand-600); color: #fff;' : 'background: var(--surface-alt); color: var(--ink); border: 1px solid var(--line);' ?>">
						<?= e($b['batch_no']) ?> (<?= e($b['date_text'] ?? 'ระบุเร็วๆ นี้') ?>)
						</a>
						<?php
					endforeach; ?>
					</div>
					<?php
				endif; ?>
				</div>
			</div>
		</div>

		<!-- 2. ส่วนเนื้อหาหลัก (ซ้ายรูปโบรชัวร์ | ขวากรอบราคา + ฟอร์มเข้า Cart) -->
		<div class="course-main-grid">
			<div>
				<div class="card">
					<div class="card__body">
						<?php
						$rawMainImage = trim($activeBatch['brochure_image'] ?? ($course['cover_image'] ?? ''));
						$mainBrochurePath = '';
						if ($rawMainImage !== '') {
							$mainBrochurePath = (strpos($rawMainImage, 'uploads/') !== 0) ? 'uploads/' . ltrim($rawMainImage, '/') : $rawMainImage;
						}
						?>
						<div class="brochure-view-wrapper" id="zoom-container" onmousemove="zoomIn(event)" onmouseleave="zoomOut()">
							<?php
							if ($mainBrochurePath !== '') : ?>
							<img id="main-brochure-view" src="<?= e(url($mainBrochurePath)) ?>" alt="Brochure Main" class="brochure-main-img">
							<?php else : ?>
							<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#fff;font-weight:600;">No picture</div>
							<?php
						endif; ?>

							<button type="button" class="zoom-btn-overlay" onclick="openLightbox()" title="คลิกเพื่อดูรูปเต็มจอ">
								<?= icon('search') ?>
							</button>
						</div>

						<?php
						$galleryList = !empty($activeBatch['gallery_images']) ? json_decode($activeBatch['gallery_images'], true) : [];
						?>
						<?php
						if (!empty($galleryList)) : ?>
						<div class="brochure-thumb-container">
							<?php
							foreach ($galleryList as $gImg) :
								$gPath = (strpos($gImg, 'uploads/') !== 0) ? 'uploads/' . ltrim($gImg, '/') : $gImg;
							?>
							<div class="brochure-thumb-item" onclick="changeBrochureImage('<?= e(url($gPath)) ?>')">
								<img src="<?= e(url($gPath)) ?>" alt="Gallery Image">
							</div>
							<?php
						endforeach; ?>
						</div>
						<?php
					endif; ?>
					</div>
				</div>
			</div>

			<!-- ฝั่งขวา: กรอบราคา + ฟอร์มส่งเข้า Cart -->
			<div>
				<form id="frm-add-to-cart" method="post" action="<?= e(url('/cart/add')) ?>" onsubmit="saveUserToCartLocalStorage(event)">
					<?= csrf_field() ?>
					<input type="hidden" name="course_id" value="<?= e($course['id']) ?>">
					<input type="hidden" name="course_title" value="<?= e($course['title']) ?>">
					<input type="hidden" name="course_type" value="<?= e($course['type'] ?? 'live_online') ?>">
					<input type="hidden" name="batch_id" value="<?= e($activeBatch['id']) ?>">
					<input type="hidden" name="batch_no" value="<?= e($activeBatch['batch_no'] ?? '1') ?>">
					<input type="hidden" name="date_text" value="<?= e($activeBatch['date_text'] ?? '') ?>">
					<input type="hidden" id="hid-rate-tier" name="rate_tier" value="p1">
					<input type="hidden" id="hid-tier-name" name="tier_name" value="ราคาค่าอบรมทั่วไป">
					<input type="hidden" id="hid-unit-price" name="unit_price" value="<?= e($activeBatch['price_1'] ?? 0) ?>">

					<div class="card course-price-card" style="border:2px solid var(--brand-600);">
						<div class="card__body">
							<h3 style="font-size:1.2rem; margin-bottom:1.0rem;">ราคาค่าอบรม / ท่าน</h3>

							<?php
							$priceType = trim((string)($activeBatch['price_type'] ?? 'single'));
							$paymentType = trim((string)($activeBatch['payment_type'] ?? 'single'));

							$p1 = (float)($activeBatch['price_1'] ?? 0);
							$p2 = (float)($activeBatch['price_2'] ?? 0);
							$p3 = (float)($activeBatch['price_3'] ?? 0);
							$p4 = (float)($activeBatch['price_4'] ?? 0);

							$status1 = trim((string)($activeBatch['status_1'] ?? 'open'));
							$status2 = trim((string)($activeBatch['status_2'] ?? 'open'));
							$status3 = trim((string)($activeBatch['status_3'] ?? 'open'));
							$status4 = trim((string)($activeBatch['status_4'] ?? 'open'));

							$isStatus1Open = ($status1 === 'open' || $status1 === '1' || $status1 === 'เปิด');
							$isStatus2Open = ($status2 === 'open' || $status2 === '1' || $status2 === 'เปิด');
							$isStatus3Open = ($status3 === 'open' || $status3 === '1' || $status3 === 'เปิด');
							$isStatus4Open = ($status4 === 'open' || $status4 === '1' || $status4 === 'เปิด');

							$limit1 = (int)($activeBatch['limit_enabled_1'] ?? 0);
							$limit2 = (int)($activeBatch['limit_enabled_2'] ?? 0);
							$limit3 = (int)($activeBatch['limit_enabled_3'] ?? 0);
							$limit4 = (int)($activeBatch['limit_enabled_4'] ?? 0);

							$cap1 = (int)($activeBatch['capacity_1'] ?? 0);
							$cap2 = (int)($activeBatch['capacity_2'] ?? 0);
							$cap3 = (int)($activeBatch['capacity_3'] ?? 0);
							$cap4 = (int)($activeBatch['capacity_4'] ?? 0);

							$showP1 = false; $showP2 = false; $showP3 = false; $showP4 = false;

							if ($priceType === 'single' && $paymentType === 'single') {
								$showP1 = $isStatus1Open;
							} elseif ($priceType === 'member_split' && $paymentType === 'single') {
								$showP1 = $isStatus1Open;
								$showP2 = $isStatus2Open;
							} elseif ($priceType === 'single' && $paymentType === 'days_before') {
								$showP1 = $isStatus1Open;
								$showP3 = $isStatus3Open;
							} elseif ($priceType === 'member_split' && $paymentType === 'days_before') {
								$showP1 = $isStatus1Open;
								$showP2 = $isStatus2Open;
								$showP3 = $isStatus3Open;
								$showP4 = $isStatus4Open;
							} else {
								$showP1 = $isStatus1Open;
								$showP2 = $isStatus2Open;
								$showP3 = $isStatus3Open;
								$showP4 = $isStatus4Open;
							}

							$activeDisplayedPrices = [];
							if ($showP1)
								$activeDisplayedPrices[] = $p1;
							if ($showP2)
								$activeDisplayedPrices[] = $p2;
							if ($showP3)
								$activeDisplayedPrices[] = $p3;
							if ($showP4)
								$activeDisplayedPrices[] = $p4;

							$isAllDisplayedFree = !empty($activeDisplayedPrices) && (max($activeDisplayedPrices) <= 0);

							$courseStatus = $course['status'] ?? 'open';
							$batchStatus = $activeBatch['status'] ?? 'open';
							$isCourseClosed = ($courseStatus === 'closed');
							$isBatchClosed = ($batchStatus === 'closed' || $batchStatus === 'full');

							$hasAnyLimitEnabled = (($limit1 === 1 && $showP1) || ($limit2 === 1 && $showP2) || ($limit3 === 1 && $showP3) || ($limit4 === 1 && $showP4));

							$isFullyBooked = false;
							$openTiersCount = 0;
							$hasUnlimitedTier = false;
							$allLimitedAreFull = true;

							$tiersToCheck = [
								[$showP1, $limit1, $cap1],
								[$showP2, $limit2, $cap2],
								[$showP3, $limit3, $cap3],
								[$showP4, $limit4, $cap4]
							];

							foreach ($tiersToCheck as $tier) {
								if ($tier[0]) {
									$openTiersCount++;
									if ($tier[1] === 0) {
										$hasUnlimitedTier = true;
									} else {
										if ($tier[2] > 0) {
											$allLimitedAreFull = false;
										}
									}
								}
							}

							if ($openTiersCount > 0) {
								if ($hasUnlimitedTier) {
									$isFullyBooked = false;
								} else {
									$isFullyBooked = $allLimitedAreFull;
								}
							} else {
								$isFullyBooked = true;
							}

							$isOverallClosed = ($isCourseClosed || $isBatchClosed || $isFullyBooked);
							?>

							<?php
							if ($hasAnyLimitEnabled) : ?>
							<div class="grid" style="grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-bottom: 0.75rem;">
								<?php
								if ($showP2 || $showP4) : ?>
								<div style="background: var(--surface-alt); padding: 0.5rem; border-radius: 4px; border: 1px solid var(--line); font-size: 0.78rem; text-align: center;">
									👤 สมาชิก: คงเหลือ
									<strong><?= ($limit2 === 1 || $limit4 === 1) ? (($cap2 > 0 ? $cap2 : $cap4) . ' ท่าน') : 'ไม่จำกัด' ?></strong>
								</div>
								<?php
							endif; ?>
								<?php
								if ($showP1 || $showP3) : ?>
								<div style="background: var(--surface-alt); padding: 0.5rem; border-radius: 4px; border: 1px solid var(--line); font-size: 0.78rem; text-align: center;">
									🌐 ทั่วไป: คงเหลือ
									<strong><?= ($limit1 === 1 || $limit3 === 1) ? (($cap1 > 0 ? $cap1 : $cap3) . ' ท่าน') : 'ไม่จำกัด' ?></strong>
								</div>
								<?php
							endif; ?>
							</div>
							<?php
						endif; ?>

							<?php
							if ($isAllDisplayedFree) : ?>
							<div style="background:#dcfce7; padding:1rem; border-radius:var(--radius-sm); border:2px solid #16a34a; text-align:center; margin-bottom:1.25rem;">
								<div style="font-size:1.1rem; color:#166534; font-weight:700;">เข้าร่วมอบรมฟรี ไม่มีค่าใช้จ่าย</div>
							</div>
							<?php else : ?>

							<div class="grid" style="grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-bottom: 1rem;">
								<?php
								if ($showP4) : ?>
								<div id="box-p4" class="price-card-box">
									<div style="font-size: 0.72rem; color: var(--muted); margin-bottom: 0.15rem;">สมาชิกชำระก่อนอบรม <?= $defaultPaymentDays ?> วัน</div>
									<div style="font-weight: 700; color: #166534; font-size: 0.95rem;"><?= ($p4 == 0) ? '0.00 บาท (ฟรี)' : number_format($p4, 2) . ' บาท' ?></div>
								</div>
								<?php
							endif; ?>

								<?php
								if ($showP2) : ?>
								<div id="box-p2" class="price-card-box">
									<div style="font-size: 0.72rem; color: var(--muted); margin-bottom: 0.15rem;">ราคาสำหรับสมาชิก</div>
									<div style="font-weight: 700; color: #166534; font-size: 0.95rem;"><?= ($p2 == 0) ? '0.00 บาท (ฟรี)' : number_format($p2, 2) . ' บาท' ?></div>
								</div>
								<?php
							endif; ?>

								<?php
								if ($showP3) : ?>
								<div id="box-p3" class="price-card-box">
									<div style="font-size: 0.72rem; color: var(--muted); margin-bottom: 0.15rem;">บุคคลทั่วไปชำระก่อนอบรม <?= $defaultPaymentDays ?> วัน</div>
									<div style="font-weight: 700; color: #166534; font-size: 0.95rem;"><?= ($p3 == 0) ? '0.00 บาท (ฟรี)' : number_format($p3, 2) . ' บาท' ?></div>
								</div>
								<?php
							endif; ?>

								<?php
								if ($showP1) : ?>
								<div id="box-p1" class="price-card-box active">
									<div style="font-size: 0.72rem; color: var(--muted); margin-bottom: 0.15rem;">ราคาค่าอบรมทั่วไป</div>
									<div style="font-weight: 700; color: #166534; font-size: 0.95rem;"><?= ($p1 == 0) ? '0.00 บาท (ฟรี)' : number_format($p1, 2) . ' บาท' ?></div>
								</div>
								<?php
							endif; ?>
							</div>

							<?php
						endif; ?>

							<?php
							if ($showP2 || $showP4 || $showP3) : ?>
							<div style="background: var(--surface-alt); padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--line); margin-bottom: 1.25rem; font-size: 0.85rem; display: grid; gap: 0.6rem; <?= $disableSpecialRates ? 'opacity: 0.65;' : '' ?>">
								<?php
								if ($showP2 || $showP4) : ?>
								<div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
									<label class="flex items-center gap-1 <?= $disableSpecialRates ? '' : 'cursor-pointer' ?>" style="margin-bottom: 0; <?= $disableSpecialRates ? 'cursor: not-allowed;' : '' ?>">
										<input type="checkbox" id="chk-member" onchange="calcPrice()" <?= $disableSpecialRates ? 'disabled' : '' ?>> ต้องการใช้สิทธิ์เป็นสมาชิก
									</label>
									<div id="member-email-container" style="display: none; flex: 1; min-width: 160px;">
										<input type="email" id="txt-member-email" class="form-control" placeholder="email@sot.com" style="font-size:0.78rem; width:100%; padding:0.2rem 0.4rem; border-radius:4px; border:1px solid var(--line); height: 28px;" <?= $disableSpecialRates ? 'disabled' : '' ?>>
									</div>
								</div>
								<?php
							endif; ?>

								<?php
								if ($paymentType === 'days_before' && ($showP3 || $showP4)) : ?>
								<label class="flex items-center gap-1 <?= $disableSpecialRates ? '' : 'cursor-pointer' ?>" style="margin-bottom: 0; <?= $disableSpecialRates ? 'cursor: not-allowed;' : '' ?>">
									<input type="checkbox" id="chk-early" onchange="calcPrice()" <?= $disableSpecialRates ? 'disabled' : '' ?>> ต้องการชำระเงินล่วงหน้าก่อนถึงวันจัดอบรม <?= $defaultPaymentDays ?> วัน
								</label>
								<?php
							endif; ?>

								<div style="font-size: 0.75rem; color: #dc2626; font-style: italic; margin-top: 0.2rem;">
									* เลือกเงื่อนไขราคาได้ในขั้นตอนยืนยัน
								</div>
							</div>
							<?php
						endif; ?>

							<div style="background:#dcfce7; color:#14532d; border:2px solid #16a34a; padding:1rem; border-radius:var(--radius); font-size:.9rem;">
								<div class="flex justify-between items-center mb-1" style="font-size:.88rem; font-weight:600;">
									<div class="flex items-center gap-1 flex-wrap">
										<span>รวมค่าอบรม</span>
										<input type="number" name="quantity" id="txt-quantity" value="1" min="1" max="100" class="form-control" style="width: 55px; text-align: center; font-size: .85rem; padding: .15rem; border-radius: 4px; border: 1px solid #16a34a; background: #fff;" oninput="calcPrice()">
										<span>ท่าน:</span>
									</div>
									<span id="price-subtotal" style="font-size: 1rem; font-weight: 700;">0.00 บาท</span>
								</div>

								<?php
								if ($includeTaxInShow) : ?>
								<div id="tax-calculation-section">
									<div class="flex justify-between items-center mb-1" style="font-size:.85rem; color:#166534;">
										<span>ภาษีมูลค่าเพิ่ม (VAT 7%):</span>
										<span id="price-vat">+0.00 บาท</span>
									</div>
									<div class="flex justify-between items-center mb-1" style="font-size:.85rem; color:#166534;">
										<span>หักภาษี ณ ที่จ่าย (WHT 3%):</span>
										<span id="price-wht">-0.00 บาท</span>
									</div>
									<div style="font-size: 0.72rem; color: #dc2626; font-style: italic; margin-bottom: 0.4rem;">
										* กำหนด WHT ได้ตามเรทจริงของบริษัทในขั้นตอนยืนยัน
									</div>
								</div>
								<?php
							endif; ?>

								<div class="flex justify-between items-center" style="border-top:1px solid rgba(22, 163, 74, 0.3); padding-top:.5rem;">
									<span style="font-weight:700;">ยอดเงินรวมสุทธิ : </span>
									<strong style="font-size:1.3rem; color:#15803d;" id="price-net">0.00 บาท</strong>
								</div>
							</div>

							<div class="mt-2" id="submit-button-container"></div>
						</div>
					</div>
				</form>
			</div>
		</div>

		<!-- 3. ส่วน Tab รายละเอียดด้านล่างสุด -->
		<div class="card" style="width: 100%;">
			<div class="card__body">
				<ul class="course-tabs">
					<li>
						<button type="button" class="course-tab-btn" onclick="switchTab(event, 'tab-detail')">รายละเอียดหลักสูตร</button></li>
					<li>
						<button type="button" class="course-tab-btn active" onclick="switchTab(event, 'tab-objective')">รายละเอียดในการจัดอบรม</button></li>
				</ul>

				<div id="tab-detail" class="tab-pane" style="font-size: 0.9rem; line-height: 1.6; color: var(--ink);">
					<div style="margin-bottom: 1rem;">
						<strong><?= e($course['summary'] ?? '') ?></strong>
					</div>
					<div>
						<?= $course['description'] ?>
					</div>
				</div>
				<div id="tab-objective" class="tab-pane active" style="font-size: 0.9rem; line-height: 1.6; color: var(--ink);">
					<p><?= e($activeBatch['description'] ?? '') ?></p>
				</div>
			</div>
		</div>

	</div>
</section>

<!-- Lightbox -->
<div id="image-lightbox" onclick="closeLightbox()">
	<button class="lightbox-close" onclick="closeLightbox()">&times;</button>
	<img id="lightbox-img-view" src="" alt="Full size brochure">
</div>

<!-- JavaScript คำนวณราคาและตั้งค่า Hidden Fields ให้ Cart พร้อมดึงข้อมูลผู้ใช้เข้ามาเป็นคนที่ 1 อัตโนมัติ -->
<script>
	const includeTaxInShow = <?= $includeTaxInShow ? 'true' : 'false' ?>;

	const batchData = {
	priceType: '<?= $priceType ?>',
	paymentType: '<?= $paymentType ?>',
	prices: { p1: <?= $p1 ?>, p2: <?= $p2 ?>, p3: <?= $p3 ?>, p4: <?= $p4 ?> },
	limits: { p1: <?= $limit1 ?>, p2: <?= $limit2 ?>, p3: <?= $limit3 ?>, p4: <?= $limit4 ?> },
	capacities: { p1: <?= $cap1 ?>, p2: <?= $cap2 ?>, p3: <?= $cap3 ?>, p4: <?= $cap4 ?> },
	statuses: { p1: <?= $showP1 ? 'true' : 'false' ?>, p2: <?= $showP2 ? 'true' : 'false' ?>, p3: <?= $showP3 ? 'true' : 'false' ?>, p4: <?= $showP4 ? 'true' : 'false' ?> },
	isAllFree: <?= $isAllDisplayedFree ? 'true' : 'false' ?>,
	isOverallClosed: <?= ($isCourseClosed || $isBatchClosed) ? 'true' : 'false' ?>
	};

const tierNames = {
p1: 'ราคาค่าอบรมทั่วไป',
p2: 'ราคาสำหรับสมาชิก',
p3: 'บุคคลทั่วไปชำระก่อนอบรม <?= $defaultPaymentDays ?> วัน',
p4: 'สมาชิกชำระก่อนอบรม <?= $defaultPaymentDays ?> วัน'
	};

// ข้อมูลผู้ใช้ปัจจุบันที่ Login อยู่ (ถ้ามี)
const loggedInUser = <?= $loggedInUser ? json_encode([
    'prefix' => $loggedInUser['prefix'] ?? '',
    'firstname' => $loggedInUser['firstname'] ?? '',
    'lastname' => $loggedInUser['lastname'] ?? '',
    'email' => $loggedInUser['email'] ?? '',
    'phone' => $loggedInUser['phone'] ?? '',
    'id_card' => $loggedInUser['id_card'] ?? '',
    'position' => $loggedInUser['position'] ?? '',
    'other_requirements' => $loggedInUser['other_requirements'] ?? ''
]) : 'null' ?>;

function changeBrochureImage(imgSrc)
{
document.getElementById('main-brochure-view').src = imgSrc;
	}

function zoomIn(event)
{
let container = document.getElementById('zoom-container');
let img = document.getElementById('main-brochure-view');
if (!img)
	return;
let rect = container.getBoundingClientRect();
let x = ((event.clientX - rect.left) / container.offsetWidth) * 100;
let y = ((event.clientY - rect.top) / container.offsetHeight) * 100;
img.style.transformOrigin = `${x}% ${y}%`;
img.style.transform = "scale(2.2)";
	}

function zoomOut()
{
let img = document.getElementById('main-brochure-view');
if (!img)
	return;
img.style.transformOrigin = "center center";
img.style.transform = "scale(1)";
	}

function openLightbox()
{
let img = document.getElementById('main-brochure-view');
if (!img)
	return;
let currentSrc = img.src;
let lightbox = document.getElementById('image-lightbox');
let lightboxImg = document.getElementById('lightbox-img-view');
lightboxImg.src = currentSrc;
lightbox.classList.add('active');
	}

function closeLightbox()
{
document.getElementById('image-lightbox').classList.remove('active');
	}

function switchTab(evt, tabId)
{
let panes = document.getElementsByClassName('tab-pane');
let btns = document.getElementsByClassName('course-tab-btn');
for (let p of panes)
	p.classList.remove('active');
for (let b of btns)
	b.classList.remove('active');
document.getElementById(tabId).classList.add('active');
evt.currentTarget.classList.add('active');
	}

function calcPrice()
{
let chkMember = document.getElementById('chk-member');
let chkEarly = document.getElementById('chk-early');

let isMember = (chkMember && !chkMember.disabled) ? chkMember.checked : false;
let isEarly = (chkEarly && !chkEarly.disabled) ? chkEarly.checked : false;
let qty = parseInt(document.getElementById('txt-quantity').value) || 1;
if (qty < 1)
	qty = 1;

let emailContainer = document.getElementById('member-email-container');
if (emailContainer) {
	emailContainer.style.display = isMember ? 'flex' : 'none';
}

let activeKey = 'p1';
if (isMember && isEarly && batchData.statuses.p4 && document.getElementById('box-p4')) {
	activeKey = 'p4';
} else if (isMember && batchData.statuses.p2 && document.getElementById('box-p2')) {
	activeKey = 'p2';
} else if (isEarly && batchData.statuses.p3 && document.getElementById('box-p3')) {
	activeKey = 'p3';
} else if (batchData.statuses.p1 && document.getElementById('box-p1')) {
	activeKey = 'p1';
} else {
	let openKeys = ['p1', 'p2', 'p3', 'p4'].filter(k => batchData.statuses[k] && document.getElementById('box-' + k));
	if (openKeys.length > 0)
		activeKey = openKeys[0];
}

let targetBox = document.getElementById('box-' + activeKey);
if (!targetBox) {
	let openKeys = ['p1', 'p2', 'p3', 'p4'].filter(k => batchData.statuses[k] && document.getElementById('box-' + k));
	if (openKeys.length > 0)
		activeKey = openKeys[0];
}

	['p1', 'p2', 'p3', 'p4'].forEach(k => {
	let box = document.getElementById('box-' + k);
	if (box)
		box.classList.remove('active');
	});
let activeBox = document.getElementById('box-' + activeKey);
if (activeBox)
	activeBox.classList.add('active');

let unitPrice = batchData.prices[activeKey] ?? 0;
let subtotal = unitPrice * qty;

document.getElementById('hid-rate-tier').value = activeKey;
document.getElementById('hid-tier-name').value = tierNames[activeKey] || 'ราคาค่าอบรมทั่วไป';
document.getElementById('hid-unit-price').value = unitPrice;

let isLimitActive = (batchData.limits[activeKey] === 1);
let currentCapacity = batchData.capacities[activeKey];
let isRateCapacityFull = (isLimitActive && currentCapacity <= 0);
let isRateDisabled = batchData.isOverallClosed || isRateCapacityFull;

let submitContainer = document.getElementById('submit-button-container');
if (submitContainer) {
	if (isRateDisabled) {
		submitContainer.innerHTML = `<button type="button" class="btn btn--block" style="background: #9ca3af; color: #fff; cursor: not-allowed; padding: 0.75rem; width:100%; border:none; border-radius:6px;" disabled>🔒 ที่นั่งเต็มแล้ว (Full)</button>`;
	} else {
		let btnText = (unitPrice === 0) ? 'ลงทะเบียนอบรมฟรี' : '🛒 หยิบลงตะกร้า / สมัครอบรม';
		submitContainer.innerHTML = `<button type="submit" class="btn btn--primary btn--block" style="background: #16a34a; color: #fff; padding: 0.75rem; width:100%; border:none; border-radius:6px; font-weight:600; cursor:pointer;">${btnText}</button>`;
	}
}

let subtotalText = (unitPrice === 0) ? "0.00 บาท (ฟรี)" : subtotal.toLocaleString('en-US', {minimumFractionDigits: 2}) + " บาท";
document.getElementById('price-subtotal').innerText = subtotalText;

let taxSection = document.getElementById('tax-calculation-section');
if (unitPrice === 0) {
	if (taxSection)
		taxSection.style.display = 'none';
	document.getElementById('price-net').innerText = "0.00 บาท (ฟรี)";
} else if (!includeTaxInShow) {
	if (taxSection)
		taxSection.style.display = 'none';
	document.getElementById('price-net').innerText = subtotal.toLocaleString('en-US', {minimumFractionDigits: 2}) + " บาท";
} else {
	if (taxSection)
		taxSection.style.display = 'block';
	let vat = subtotal * 0.07;
	let wht = subtotal * 0.03;
	let net = subtotal + vat - wht;

	document.getElementById('price-vat').innerText = "+" + vat.toLocaleString('en-US', {minimumFractionDigits: 2}) + " บาท";
	document.getElementById('price-wht').innerText = "-" + wht.toLocaleString('en-US', {minimumFractionDigits: 2}) + " บาท";
	document.getElementById('price-net').innerText = net.toLocaleString('en-US', {minimumFractionDigits: 2}) + " บาท";
}
	}

// ฟังก์ชันบันทึกข้อมูลผู้ใช้ปัจจุบันลงใน LocalStorage ให้เป็นรายชื่อคนที่ 1 อัตโนมัติเมื่อกดเพิ่มลงตะกร้า
function saveUserToCartLocalStorage(event) {
    if (loggedInUser) {
        let courseId = "<?= e($course['id']) ?>";
        let batchId = "<?= e($activeBatch['id']) ?>";
        let cartKey = courseId + "_" + batchId;

        let attendeesData = [{
            prefix: loggedInUser.prefix || '',
            custom_prefix: '',
            first_name: loggedInUser.firstname || '',
            last_name: loggedInUser.lastname || '',
            email: loggedInUser.email || '',
            phone: loggedInUser.phone || '',
            id_card: loggedInUser.id_card || '',
            position: loggedInUser.position || '',
            other_requirements: loggedInUser.other_requirements || ''
        }];

        localStorage.setItem('sot_cart_attendees_' + cartKey, JSON.stringify(attendeesData));
        localStorage.setItem('sot_cart_user_qty_' + cartKey, '1');
    }
}

document.addEventListener("DOMContentLoaded", function() {
calcPrice();
	});
</script>