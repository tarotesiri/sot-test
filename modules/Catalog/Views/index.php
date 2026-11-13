<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name : index.php
 * Module : Catalog - Course List Index
 * Description : Displays the list of training courses, search filters, and batch availability status based on strict 3-step rules.
 * Author : Software Engineering Team
 * Last Updated : 2026-11-10
 * ====================================================================
**/

declare(strict_types=1);

$this->partial('page-header', ['title' => 'หลักสูตรฝึกอบรม & สัมมนา (Training Courses)', 'subtitle' => 'อบรมแบบ Live Streaming และ Onsite พร้อมวุฒิบัตรรับรองตามกฎหมายกระทรวงแรงงาน', 'breadcrumbs' => ['หน้าแรก' => '/', 'หลักสูตรทั้งหมด' => '']]);
?>
<section class="section" style="padding-top:1.25rem;">
    <div class="container">
        <!-- กล่องค้นหาและตัวกรองด้านบน -->
        <form method="get" action="" class="card mb-3" style="padding:1rem 1.25rem;border-radius:12px;">
            <div class="grid" style="grid-template-columns: 2fr 1fr 1fr 1fr; gap:1rem; align-items:center;">
                <div>
                    <label style="display:block;font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:.25rem;">ค้นหาหลักสูตร / รุ่น</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.85rem;pointer-events:none;"><?= icon('search') ?></span>
                        <input class="form-control" type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="พิมพ์ชื่อหลักสูตร หรือรุ่น..." style="padding-left:2.25rem;font-size:.85rem;width:100%;padding-top:.35rem;padding-bottom:.35rem;border-radius:6px;border:1px solid var(--line);outline:none;box-shadow:none;">
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:.25rem;">ประเภทหลักสูตร</label>
                    <select class="form-control" name="type" style="font-size:.85rem;width:100%;padding-top:.35rem;padding-bottom:.35rem;border-radius:6px;border:1px solid var(--line);outline:none;box-shadow:none;" onchange="this.form.submit()">
                        <option value="">ทั้งหมด (ทุกประเภท)</option>
                        <option value="live_online"<?= ($filters['type'] ?? '') === 'live_online' ? ' selected' : '' ?>>อบรม Live สดผ่าน Zoom</option>
                        <option value="clip_vdo"<?= ($filters['type'] ?? '') === 'clip_vdo' ? ' selected' : '' ?>>หลักสูตรออนไลน์</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:.25rem;">สถานะ</label>
                    <select class="form-control" name="status" style="font-size:.85rem;width:100%;padding-top:.35rem;padding-bottom:.35rem;border-radius:6px;border:1px solid var(--line);outline:none;box-shadow:none;" onchange="this.form.submit()">
                        <option value="">ทั้งหมด</option>
                        <option value="open"<?= ($filters['status'] ?? '') === 'open' ? ' selected' : '' ?>>เปิดรับสมัคร</option>
                        <option value="closed"<?= ($filters['status'] ?? '') === 'closed' ? ' selected' : '' ?>>ปิดรับสมัครแล้ว</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:.25rem;">การจัดเรียง</label>
                    <select class="form-control" name="sort" style="font-size:.85rem;width:100%;padding-top:.35rem;padding-bottom:.35rem;border-radius:6px;border:1px solid var(--line);outline:none;box-shadow:none;" onchange="this.form.submit()">
                        <option value="furthest"<?= ($filters['sort'] ?? '') === 'furthest' ? ' selected' : '' ?>>รุ่นล่าสุด</option>
                        <option value="nearest"<?= ($filters['sort'] ?? '') === 'nearest' ? ' selected' : '' ?>>รุ่นแรกสุด</option>
                        <option value="title"<?= ($filters['sort'] ?? '') === 'title' ? ' selected' : '' ?>>ชื่อหลักสูตร</option>
                    </select>
                </div>
            </div>

            <hr style="border:0;border-top:1px solid var(--line);margin:.85rem 0;">

            <div class="flex justify-between items-center" style="font-size:.85rem;">
                <div style="color:var(--muted);">
                    พบรายการรุ่นทั้งหมด <strong style="color:var(--ink);"><?= $pager['total'] ?? 0 ?></strong> รายการ 
                    (หน้า <?= $pager['current_page'] ?? 1 ?> / <?= $pager['last_page'] ?? 1 ?>)
                </div>
                <div class="flex items-center gap-1">
                    <span style="color:var(--muted);">แสดงต่อหน้า:</span>
                    <?php 
                        $currentPerPage = isset($_GET['per_page']) && $_GET['per_page'] !== '' ? (int)$_GET['per_page'] : 6;
                        $perPageOptions = [6, 12, 24, 48];
                        if (!in_array($currentPerPage, $perPageOptions, true)) {
                            $perPageOptions[] = $currentPerPage;
                            sort($perPageOptions, SORT_NUMERIC);
                        }

                        foreach ($perPageOptions as $num) {
                            $queryParams = $_GET;
                            $queryParams['per_page'] = $num;
                            unset($queryParams['page']);
                            $optionUrl = '?' . http_build_query($queryParams);
                            $isSelected = ($currentPerPage === $num);
                            
                            $bgStyle = $isSelected ? 'background:var(--brand-600);color:#fff;' : 'background:var(--surface-alt);color:var(--ink);';
                            echo '<a href="' . e($optionUrl) . '" class="badge ' . ($isSelected ? 'badge--solid' : '') . '" style="' . $bgStyle . 'padding:.2rem .5rem;text-decoration:none;border-radius:4px;">' . $num . '</a>';
                        }
                    ?>
                </div>
            </div>
        </form>

        <?php if (empty($courses)): ?>
        <div class="empty-state">ไม่พบรายการรุ่นที่ค้นหา</div>
        <?php else: ?>
        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1.5rem;">
            <?php foreach ($courses as $c): 
                $courseStatus = $c['course_status'] ?? ($c['status'] ?? 'open');
                $batchStatus = $c['status'] ?? 'open';
                $isCourseClosed = ($courseStatus === 'closed');
                $isBatchClosed = ($batchStatus === 'closed' || $batchStatus === 'full');

                $priceType = trim((string)($c['price_type'] ?? 'single'));
                $paymentType = trim((string)($c['payment_type'] ?? 'single'));

                $p1 = (float)($c['price_1'] ?? 0); 
                $p2 = (float)($c['price_2'] ?? 0); 
                $p3 = (float)($c['price_3'] ?? 0); 
                $p4 = (float)($c['price_4'] ?? 0); 

                $status1 = trim((string)($c['status_1'] ?? 'open'));
                $status2 = trim((string)($c['status_2'] ?? 'open'));
                $status3 = trim((string)($c['status_3'] ?? 'open'));
                $status4 = trim((string)($c['status_4'] ?? 'open'));

                $isStatus1Open = ($status1 === 'open' || $status1 === 'เปิด' || $status1 === '1');
                $isStatus2Open = ($status2 === 'open' || $status2 === 'เปิด' || $status2 === '1');
                $isStatus3Open = ($status3 === 'open' || $status3 === 'เปิด' || $status3 === '1');
                $isStatus4Open = ($status4 === 'open' || $status4 === 'เปิด' || $status4 === '1');

                $limit1 = (int)($c['limit_enabled_1'] ?? 0);
                $limit2 = (int)($c['limit_enabled_2'] ?? 0);
                $limit3 = (int)($c['limit_enabled_3'] ?? 0);
                $limit4 = (int)($c['limit_enabled_4'] ?? 0);

                $cap1 = (int)($c['capacity_1'] ?? 0);
                $cap2 = (int)($c['capacity_2'] ?? 0);
                $cap3 = (int)($c['capacity_3'] ?? 0);
                $cap4 = (int)($c['capacity_4'] ?? 0);

                // --- ขั้นที่ 1 & 2: คัดกรองเรทตาม price_type, payment_type และ status ---
                $activeTiers = [];
                $availablePrices = [];

                if ($priceType === 'single' && $paymentType === 'single') {
                    if ($isStatus1Open) {
                        $activeTiers[] = ['limit' => $limit1, 'cap' => $cap1];
                        $availablePrices[] = $p1;
                    }
                } elseif ($priceType === 'member_split' && $paymentType === 'single') {
                    if ($isStatus1Open) { $activeTiers[] = ['limit' => $limit1, 'cap' => $cap1]; $availablePrices[] = $p1; }
                    if ($isStatus2Open) { $activeTiers[] = ['limit' => $limit2, 'cap' => $cap2]; $availablePrices[] = $p2; }
                } elseif ($priceType === 'single' && $paymentType === 'days_before') {
                    if ($isStatus1Open) { $activeTiers[] = ['limit' => $limit1, 'cap' => $cap1]; $availablePrices[] = $p1; }
                    if ($isStatus3Open) { $activeTiers[] = ['limit' => $limit3, 'cap' => $cap3]; $availablePrices[] = $p3; }
                } elseif ($priceType === 'member_split' && $paymentType === 'days_before') {
                    if ($isStatus1Open) { $activeTiers[] = ['limit' => $limit1, 'cap' => $cap1]; $availablePrices[] = $p1; }
                    if ($isStatus2Open) { $activeTiers[] = ['limit' => $limit2, 'cap' => $cap2]; $availablePrices[] = $p2; }
                    if ($isStatus3Open) { $activeTiers[] = ['limit' => $limit3, 'cap' => $cap3]; $availablePrices[] = $p3; }
                    if ($isStatus4Open) { $activeTiers[] = ['limit' => $limit4, 'cap' => $cap4]; $availablePrices[] = $p4; }
                } else {
                    if ($isStatus1Open) { $activeTiers[] = ['limit' => $limit1, 'cap' => $cap1]; $availablePrices[] = $p1; }
                }

                // ตรวจสอบสถานะ Fully Booked
                $isFullyBooked = false;
                if (!empty($activeTiers)) {
                    $hasUnlimited = false;
                    $allLimitedFull = true;
                    $hasLimited = false;

                    foreach ($activeTiers as $t) {
                        if ($t['limit'] === 0) {
                            $hasUnlimited = true;
                        } else {
                            $hasLimited = true;
                            if ($t['cap'] > 0) {
                                $allLimitedFull = false;
                            }
                        }
                    }
                    if (!$hasUnlimited && $hasLimited && $allLimitedFull) {
                        $isFullyBooked = true;
                    }
                } else {
                    $isFullyBooked = true;
                }

                $isClosed = ($isCourseClosed || $isBatchClosed || $isFullyBooked);
                $isOpen = !$isClosed;
                
                $priceLabel = 'ราคาค่าอบรม / ท่าน';
                $sessionDateText = $c['date_text'] ?? 'ระบุเร็วๆ นี้';
                $sessionTimeText = $c['time_text'] ?? '09:00 - 16:00 น.';
                $batchNo = $c['batch_no'] ?? 'รุ่นที่ 1';
                $batchId = $c['batch_id'] ?? 0;

                $rawImage = trim($c['brochure_image'] ?? '');
                $brochureImage = '';
                if ($rawImage !== '') {
                    $brochureImage = (strpos($rawImage, 'uploads/') !== 0) ? 'uploads/' . ltrim($rawImage, '/') : $rawImage;
                }

                $detailUrl = url('/courses/' . $c['slug'] . '?batch=' . $batchId);
            ?>
            <div class="card course-card" style="display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--radius); overflow: hidden; font-size: .9rem; position: relative; width: 100%; max-width: 360px; flex: 1 1 340px;">
                <div>
                    <div style="position:relative;width:100%;aspect-ratio:16/9;background:#e2e8f0;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                        <?php if ($brochureImage !== ''): ?>
                            <a href="<?= e($detailUrl) ?>" style="display:block;width:100%;height:100%;">
                                <img src="<?= e(url($brochureImage)) ?>" alt="" style="width:100%;height:100%;object-fit:cover;transition:transform .4s ease;<?= $isClosed ? 'opacity:0.45;' : '' ?>">
                            </a>
                        <?php else: ?>
                            <span style="color:#64748b;font-weight:600;font-size:0.95rem;user-select:none;">No picture</span>
                        <?php endif; ?>
                        
                        <div style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.75);color:#fff;padding:0.2rem 0.6rem;border-radius:4px;font-size:0.75rem;font-weight:600;">
                            <?= e($batchNo) ?>
                        </div>

                        <?php if ($isClosed): ?>
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;">
                            <span style="font-size:1.1rem;font-weight:700;color:#ffffff;letter-spacing:1px;user-select:none;text-shadow:0 2px 4px rgba(0,0,0,0.8);background:rgba(0,0,0,0.35);padding:0.2rem 0.75rem;border-radius:4px;">
                                <?= $isFullyBooked ? 'Fully Booked' : 'Closed' ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <style>
                        .course-card:hover img { transform: scale(1.05); }
                        .form-control:focus { outline: none !important; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important; border-color: var(--brand-600) !important; }
                    </style>

                    <div class="card__body" style="padding:1rem 1rem .5rem 1rem;">
                        <h3 style="font-size:0.98rem;font-weight:600;line-height:1.4;margin-bottom:.4rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;min-height:4.2em;">
                            <a href="<?= e($detailUrl) ?>" style="color:var(--ink);"><?= e($c['title']) ?></a>
                        </h3>
                        <?php if (!empty($c['summary'])): ?>
                        <p style="color:var(--muted);font-size:.82rem;line-height:1.4;margin-bottom:.75rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            <?= e($c['summary']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div style="padding:0 1rem;">
                        <div class="flex justify-between items-center text-muted" style="font-size:.8rem;margin-bottom:.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.3rem;">
                                <?= icon('calendar') ?> <strong style="color: #2563eb;"><?= e($sessionDateText) ?></strong>
                            </div>
                            <div style="color:var(--brand-700);font-weight:500;">
                                <?= icon('clock') ?> <?= e($sessionTimeText) ?>
                            </div>
                        </div>

                        <hr style="border:0;border-top:1px solid var(--line);margin:.5rem 0;">

                        <div style="font-size:.75rem;color:var(--brand-700);margin-bottom:.1rem;font-weight:600;"><?= $priceLabel ?></div>
                        
                        <div class="flex justify-between items-center mb-1">
                            <div>
                                <div style="font-size:.8rem;color:var(--muted);">
                                    <?php 
                                        if (empty($availablePrices)) {
                                            $availablePrices = [0.0];
                                        }

                                        $actualMinP = min($availablePrices);
                                        $actualMaxP = max($availablePrices);
                                    ?>

                                    <?php if ($actualMaxP <= 0): ?>
                                        <strong style="font-size:0.95rem;color:#16a34a;">อบรมฟรี (ไม่มีค่าใช้จ่าย)</strong>
                                    <?php elseif ($actualMinP === $actualMaxP): ?>
                                        <strong style="font-size:0.95rem;color:var(--brand-700);">
                                            <?= ($actualMinP == 0) ? '0.00 บาท (ฟรี)' : baht($actualMinP) ?>
                                        </strong>
                                    <?php else: ?>
                                        <strong style="font-size:0.95rem;color:var(--brand-700);">
                                            <?= baht($actualMinP) . ' - ' . baht($actualMaxP) ?>
                                        </strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($isOpen): ?>
                                <a href="<?= e($detailUrl) ?>" class="btn btn--primary btn--sm" style="font-size:.8rem;padding:.3rem .9rem;background:var(--brand-600);color:#fff;border-radius:6px;">
                                    รายละเอียด
                                </a>
                                <?php else: ?>
                                <button type="button" class="btn btn--sm" style="font-size:.8rem;padding:.3rem .9rem;background:#e2e8f0;color:#64748b;cursor:not-allowed;border:none;border-radius:6px;" title="ปิดรับสมัครแล้ว" disabled>
                                    รายละเอียด
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div style="background:var(--surface-alt);padding:.4rem 1rem;font-size:.75rem;color:var(--muted);display:flex;justify-content:space-between;border-top:1px solid var(--line);margin-top:.75rem;">
                        <span><?= icon('award') ?> มีวุฒิบัตร</span>
                        <span><?= e($batchNo) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php 
            $totalPages = $pager['last_page'] ?? 1;
            $currentPage = $pager['current_page'] ?? 1;
        ?>
        <?php if ($totalPages > 1): ?>
        <div style="text-align:center;margin-top:2.5rem;margin-bottom:1.5rem;">
            <div style="display:inline-flex;align-items:center;gap:0.35rem;flex-wrap:wrap;justify-content:center;">
                <?php if ($currentPage > 1): ?>
                    <?php $prevParams = $_GET; $prevParams['page'] = $currentPage - 1; ?>
                    <a href="?<?= e(http_build_query($prevParams)) ?>" class="btn btn--outline btn--sm" style="color:var(--ink);border-color:var(--line);border-radius:6px;text-decoration:none;">&larr; ก่อนหน้า</a>
                <?php else: ?>
                    <button type="button" class="btn btn--outline btn--sm" style="color:var(--muted);border-color:var(--line);border-radius:6px;opacity:0.5;cursor:not-allowed;" disabled>&larr; ก่อนหน้า</button>
                <?php endif; ?>

                <?php
                    $range = 3;
                    if ($currentPage - $range > 1) {
                        $p1Params = $_GET; $p1Params['page'] = 1;
                        echo '<a href="?' . e(http_build_query($p1Params)) . '" class="btn btn--outline btn--sm" style="color:var(--ink);border-color:var(--line);border-radius:6px;min-width:38px;text-decoration:none;">1</a>';
                        if ($currentPage - $range > 2) {
                            echo '<span style="padding:0 .25rem;color:var(--muted);">...</span>';
                        }
                    }

                    for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
                        $isCurrent = ($i === $currentPage);
                        $pageParams = $_GET;
                        $pageParams['page'] = $i;
                        if ($isCurrent) {
                            echo '<button type="button" class="btn btn--sm" style="background:var(--brand-600);color:#fff;border:none;min-width:38px;border-radius:6px;">' . $i . '</button>';
                        } else {
                            echo '<a href="?' . e(http_build_query($pageParams)) . '" class="btn btn--outline btn--sm" style="color:var(--ink);border-color:var(--line);min-width:38px;text-decoration:none;border-radius:6px;">' . $i . '</a>';
                        }
                    }

                    if ($currentPage + $range < $totalPages) {
                        if ($currentPage + $range < $totalPages - 1) {
                            echo '<span style="padding:0 .25rem;color:var(--muted);">...</span>';
                        }
                        $pLastParams = $_GET; $pLastParams['page'] = $totalPages;
                        echo '<a href="?' . e(http_build_query($pLastParams)) . '" class="btn btn--outline btn--sm" style="color:var(--ink);border-color:var(--line);border-radius:6px;min-width:38px;text-decoration:none;">' . $totalPages . '</a>';
                    }
                ?>

                <?php if ($currentPage < $totalPages): ?>
                    <?php $nextParams = $_GET; $nextParams['page'] = $currentPage + 1; ?>
                    <a href="?<?= e(http_build_query($nextParams)) ?>" class="btn btn--outline btn--sm" style="color:var(--ink);border-color:var(--line);border-radius:6px;text-decoration:none;">ถัดไป &rarr;</a>
                <?php else: ?>
                    <button type="button" class="btn btn--outline btn--sm" style="color:var(--muted);border-color:var(--line);border-radius:6px;opacity:0.5;cursor:not-allowed;" disabled>ถัดไป &rarr;</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>