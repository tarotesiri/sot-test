<?php
/**
 * ============================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name    : profile.php
 * Module       : Auth View (Edit Profile & Company Info)
 * Description  : หน้า "แก้ไขข้อมูลส่วนตัว" (ซิงก์ข้อมูลผู้เข้าเรียนของตัวเอง)
 *                และแท็บ "ข้อมูลบริษัท" พร้อมรายการบริษัท ปุ่มตั้งเป็นบริษัทเริ่มต้น
 *                ปุ่มนำออกจากการผูก Popup รายชื่อผู้เรียนของบริษัท ที่กดปุ่ม "แก้ไข"
 *                รายคนและกด "บันทึก" ทีละคน (แก้ชื่อ-นามสกุล-ตำแหน่งได้เฉพาะคน
 *                ที่ไม่มี user เข้าระบบ และเช็กชื่อ-นามสกุลซ้ำ)
 * ============================================================================
 */

declare(strict_types=1);

$user = is_array($user ?? null) ? $user : [];
unset($user['password'], $user['password_hash']);

$company = is_array($company ?? null) ? $company : [];
$companies = is_array($companies ?? null) ? $companies : (!empty($company) ? [$company] : []);
$companyParticipantsById = is_array($companyParticipantsById ?? null) ? $companyParticipantsById : [];
$companyParticipants = is_array($companyParticipants ?? null) ? $companyParticipants : [];
$isNewCompany = (bool)($isNewCompany ?? false);
$isCoordinator = (bool)($isCoordinator ?? false);
$activeTab = (string)($activeTab ?? 'personal');
$isCompanyTab = ($activeTab === 'company');

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$standardPrefixes = ['นาย', 'นาง', 'นางสาว'];
$currentPrefix = trim((string)($user['prefix'] ?? ''));
$isCustomPrefix = ($currentPrefix !== '' && !in_array($currentPrefix, $standardPrefixes, true));
$selectPrefixVal = $isCustomPrefix ? '__other__' : $currentPrefix;
$customPrefixVal = $isCustomPrefix ? $currentPrefix : '';

$firstname = (string)($user['firstname'] ?? '');
$lastname = (string)($user['lastname'] ?? '');
$email = (string)($user['email'] ?? '');
$phone = (string)($user['phone'] ?? '');
$idCard = (string)($user['id_card'] ?? '');
$position = (string)($user['position'] ?? '');
$otherRequirements = (string)($user['other_requirements'] ?? '');

$companyId = (int)($company['id'] ?? 0);
$companyDefault = (int)($company['is_default'] ?? 0);
$taxType = (string)($company['tax_type'] ?? 'corporate');
$companyName = (string)($company['company_name'] ?? '');
$branch = (string)($company['branch'] ?? 'สำนักงานใหญ่');
$taxId = (string)($company['tax_id'] ?? '');
$address = (string)($company['address'] ?? '');
$province = (string)($company['province'] ?? '');
$amphoe = (string)($company['amphoe'] ?? '');
$district = (string)($company['district'] ?? '');
$zipcode = (string)($company['zipcode'] ?? '');
$companyPhone = (string)($company['phone'] ?? '');
$billingEmail = (string)($company['billing_email'] ?? '');

$currentUserId = (int)($_SESSION['user_id'] ?? ($user['id'] ?? 0));
$currentUserEmail = strtolower(trim($email));

// รวมข้อมูลผู้เรียนรายบริษัท (ห้ามมีรหัสผ่าน)
$modalCompaniesData = [];
foreach ($companies as $compItem) {
    $cid = (int)($compItem['id'] ?? 0);
    if ($cid <= 0) {
        continue;
    }
    $list = $companyParticipantsById[$cid] ?? ($cid === $companyId ? $companyParticipants : []);
    $cleanList = [];
    if (is_array($list)) {
        foreach ($list as $pRow) {
            if (!is_array($pRow)) {
                continue;
            }
            $learnerUid = (int)($pRow['learner_user_id'] ?? 0);
            $partEmail  = strtolower(trim((string)($pRow['email'] ?? '')));
            $isSelf     = array_key_exists('is_self', $pRow)
                ? (bool)$pRow['is_self']
                : (($learnerUid === $currentUserId) || ($learnerUid === 0 && $currentUserEmail !== '' && $partEmail !== '' && $partEmail === $currentUserEmail));
            $hasLearnerAccount = array_key_exists('has_learner_account', $pRow)
                ? (bool)$pRow['has_learner_account']
                : ($learnerUid > 0);
            $canEditName = array_key_exists('can_edit_name', $pRow)
                ? (bool)$pRow['can_edit_name']
                : (!$isSelf && !$hasLearnerAccount);

            $cleanList[] = [
                'id'                  => (int)($pRow['id'] ?? 0),
                'prefix_name'         => (string)($pRow['prefix_name'] ?? ''),
                'first_name'          => (string)($pRow['first_name'] ?? ''),
                'last_name'           => (string)($pRow['last_name'] ?? ''),
                'email'               => (string)($pRow['email'] ?? ''),
                'pin_code'            => (string)($pRow['pin_code'] ?? ''),
                'department'          => (string)($pRow['department'] ?? ''),
                'position'            => (string)($pRow['position'] ?? ''),
                'is_self'             => $isSelf,
                'has_learner_account' => $hasLearnerAccount,
                'can_edit_name'       => $canEditName,
            ];
        }
    }
    $modalCompaniesData[$cid] = [
        'id'           => $cid,
        'company_name' => (string)($compItem['company_name'] ?? ''),
        'participants' => $cleanList,
    ];
}

$openModalCompanyId = (int)($_GET['modal_company_id'] ?? 0);
$csrfHtml = function_exists('csrf_field') ? (string)(csrf_field() ?? '') : '';
?>

<div style="max-width: 1200px; margin: 30px auto; padding: 0 15px;">
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">

        <!-- เมนูด้านซ้าย (Sidebar) -->
        <div style="flex: 1; min-width: 250px; max-width: 280px;">
            <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
                <a href="<?= e(url('dashboard')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none;">ภาพรวมระบบ (Overview)</a>
                <a href="<?= e(url('my-orders')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none; border-top: 1px solid #dee2e6;">คำสั่งซื้อของฉัน</a>
                <a href="<?= e(url('my-courses')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none; border-top: 1px solid #dee2e6;">หลักสูตรของฉัน</a>
                <a href="<?= e(url('profile')) ?>" style="display: block; padding: 12px 20px; background: #0d6efd; color: #fff; text-decoration: none; font-weight: 500; border-top: 1px solid #dee2e6;">แก้ไขข้อมูลส่วนตัว</a>
                <a href="<?= e(url('auth/logout')) ?>" style="display: block; padding: 12px 20px; color: #dc3545; text-decoration: none; border-top: 1px solid #dee2e6;">ออกจากระบบ</a>
            </div>
        </div>

        <!-- พื้นที่แสดงข้อมูลฝั่งขวา (Main Content) -->
        <div style="flex: 3; min-width: 300px;">
            <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 25px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
                <div style="margin-bottom: 14px;">
                    <h3 style="margin: 0; font-weight: 700; color: #212529;">แก้ไขข้อมูลส่วนตัว</h3>
                    <div style="font-size: 0.85rem; color: #6c757d; margin-top: 4px;">
                        <?= $isCompanyTab ? 'จัดการรายการบริษัท/องค์กร ตั้งบริษัทเริ่มต้น แก้ไขข้อมูลบริษัท และจัดการชื่อ-ตำแหน่งผู้เข้าอบรมผ่าน Popup' : 'จัดการข้อมูลบัญชีผู้ใช้งาน ข้อมูลผู้เข้าเรียนของตนเอง และรหัสผ่าน' ?>
                    </div>
                </div>

                <!-- Tabs สลับหน้า: ข้อมูลส่วนตัว / ข้อมูลบริษัท -->
                <?php if ($isCoordinator): ?>
                    <div style="display: flex; gap: 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 12px; margin-bottom: 18px; flex-wrap: wrap;">
                        <a href="<?= e(url('profile?tab=personal')) ?>"
                           style="display: inline-block; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.88rem; font-weight: 600; border: 1px solid <?= !$isCompanyTab ? '#0d6efd' : '#ced4da' ?>; background: <?= !$isCompanyTab ? '#0d6efd' : '#f8f9fa' ?>; color: <?= !$isCompanyTab ? '#fff' : '#212529' ?>;">
                            ข้อมูลส่วนตัว
                        </a>
                        <a href="<?= e(url('profile?tab=company')) ?>"
                           style="display: inline-block; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.88rem; font-weight: 600; border: 1px solid <?= $isCompanyTab ? '#0d6efd' : '#ced4da' ?>; background: <?= $isCompanyTab ? '#0d6efd' : '#f8f9fa' ?>; color: <?= $isCompanyTab ? '#fff' : '#212529' ?>;">
                            ข้อมูลบริษัท (<?= count($companies) ?>)
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($flashSuccess)): ?>
                    <div style="background: #dcfce7; color: #166534; border: 1px solid #86efac; padding: 10px 14px; border-radius: 6px; font-size: 0.9rem; margin-bottom: 16px;">
                        <?= e((string)$flashSuccess) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($flashError)): ?>
                    <div style="background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 10px 14px; border-radius: 6px; font-size: 0.9rem; margin-bottom: 16px;">
                        <?= e((string)$flashError) ?>
                    </div>
                <?php endif; ?>

                <?php if ($isCoordinator && $isCompanyTab): ?>
                    <!-- ส่วนแสดงรายการบริษัททั้งหมดใน sot_company_users -->
                    <div style="margin-bottom: 22px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                            <div style="font-size: 0.92rem; font-weight: 700; color: #212529;">
                                รายการบริษัทในความดูแล (<?= count($companies) ?> แห่ง)
                            </div>
                            <a href="<?= e(url('profile?tab=company&new_company=1')) ?>"
                               style="display: inline-block; padding: 6px 12px; border-radius: 5px; border: 1px solid #0d6efd; background: #eff6ff; color: #0d6efd; text-decoration: none; font-size: 0.82rem; font-weight: 600;">
                                + เพิ่มบริษัทใหม่
                            </a>
                        </div>

                        <?php if (!empty($companies)): ?>
                            <div style="overflow-x: auto; border: 1px solid #dee2e6; border-radius: 6px;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 0.86rem;">
                                    <thead>
                                        <tr style="background: #f8f9fa; border-bottom: 1px solid #dee2e6; text-align: left;">
                                            <th style="padding: 9px 12px;">ชื่อบริษัท / องค์กร</th>
                                            <th style="padding: 9px 12px;">สาขา</th>
                                            <th style="padding: 9px 12px;">เลขผู้เสียภาษี</th>
                                            <th style="padding: 9px 12px; text-align: center;">สถานะ</th>
                                            <th style="padding: 9px 12px; text-align: right;">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($companies as $compItem): ?>
                                            <?php
                                            $cid = (int)($compItem['id'] ?? 0);
                                            $isDef = ((int)($compItem['is_default'] ?? 0) === 1);
                                            $isEditing = (!$isNewCompany && $cid === $companyId);
                                            $partCount = isset($modalCompaniesData[$cid]) ? count($modalCompaniesData[$cid]['participants']) : 0;
                                            ?>
                                            <tr style="border-bottom: 1px solid #eef2f6; background: <?= $isEditing ? '#f0f9ff' : '#fff' ?>;">
                                                <td style="padding: 9px 12px; font-weight: 600; color: #212529;">
                                                    <?= e((string)($compItem['company_name'] ?? '')) ?>
                                                </td>
                                                <td style="padding: 9px 12px; color: #495057;">
                                                    <?= e((string)($compItem['branch'] ?? 'สำนักงานใหญ่')) ?>
                                                </td>
                                                <td style="padding: 9px 12px; color: #495057; font-family: monospace;">
                                                    <?= e((string)($compItem['tax_id'] ?? '-')) ?>
                                                </td>
                                                <td style="padding: 9px 12px; text-align: center;">
                                                    <?php if ($isDef): ?>
                                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 0.76rem; font-weight: 600;">
                                                            บริษัทเริ่มต้น
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 999px; background: #f1f5f9; color: #64748b; font-size: 0.76rem;">
                                                            ทั่วไป
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 9px 12px; text-align: right; white-space: nowrap;">
                                                    <button type="button"
                                                            onclick="openParticipantsModal(<?= $cid ?>)"
                                                            style="padding: 4px 10px; border-radius: 4px; border: 1px solid #93c5fd; background: #eff6ff; color: #1d4ed8; font-size: 0.8rem; font-weight: 500; cursor: pointer; margin-right: 4px;">
                                                        รายชื่อผู้เรียน (<?= $partCount ?>)
                                                    </button>
                                                    <a href="<?= e(url('profile?tab=company&company_id=' . $cid)) ?>"
                                                       style="display: inline-block; padding: 4px 10px; border-radius: 4px; border: 1px solid #ced4da; background: #fff; color: #212529; text-decoration: none; font-size: 0.8rem; margin-right: 4px;">
                                                        แก้ไข
                                                    </a>
                                                    <?php if (!$isDef): ?>
                                                        <form action="<?= e(url('profile/update')) ?>" method="POST" style="display: inline-block; margin: 0 4px 0 0;">
                                                            <?= $csrfHtml ?>
                                                            <input type="hidden" name="section" value="set_default_company">
                                                            <input type="hidden" name="company_id" value="<?= $cid ?>">
                                                            <button type="submit"
                                                                    style="padding: 4px 10px; border-radius: 4px; border: 1px solid #16a34a; background: #16a34a; color: #fff; font-size: 0.8rem; font-weight: 500; cursor: pointer;">
                                                                ตั้งเป็นบริษัทเริ่มต้น
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form action="<?= e(url('profile/update')) ?>" method="POST"
                                                          style="display: inline-block; margin: 0;"
                                                          onsubmit="return confirm('ยืนยันนำบริษัทนี้ออกจากการผูกบัญชีผู้ประสานงานใช่หรือไม่? (ระบบจะลบข้อมูลบริษัทออกจากฐานข้อมูลเฉพาะกรณีที่ไม่มีผู้ประสานงานและผู้เรียนผูกอยู่)');">
                                                        <?= $csrfHtml ?>
                                                        <input type="hidden" name="section" value="unlink_company">
                                                        <input type="hidden" name="company_id" value="<?= $cid ?>">
                                                        <button type="submit"
                                                                style="padding: 4px 10px; border-radius: 4px; border: 1px solid #fca5a5; background: #fef2f2; color: #b91c1c; font-size: 0.8rem; font-weight: 500; cursor: pointer;">
                                                            นำออกจากการผูก
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr style="border: 0; border-top: 1px solid #e9ecef; margin: 18px 0;">

                    <div style="font-size: 0.92rem; font-weight: 700; color: #212529; margin-bottom: 12px;">
                        <?= $companyId > 0 && !$isNewCompany ? ('แก้ไขข้อมูลบริษัท #' . $companyId) : 'เพิ่มข้อมูลบริษัทใหม่' ?>
                    </div>

                    <!-- ฟอร์มเพิ่ม/แก้ไขข้อมูลบริษัท -->
                    <form action="<?= e(url('profile/update')) ?>" method="POST" id="companyForm">
                        <?= $csrfHtml ?>
                        <input type="hidden" name="section" value="company">
                        <input type="hidden" name="company_id" value="<?= $isNewCompany ? 0 : (int)$companyId ?>">

                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px;">
                            <div style="flex: 0.9; min-width: 160px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">ประเภทผู้เสียภาษี</label>
                                <select name="tax_type" style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px; background: #fff;">
                                    <option value="corporate" <?= $taxType === 'corporate' ? 'selected' : '' ?>>นิติบุคคล / บริษัท (corporate)</option>
                                    <option value="personal" <?= $taxType === 'personal' ? 'selected' : '' ?>>บุคคลธรรมดา (personal)</option>
                                </select>
                            </div>
                            <div style="flex: 2.1; min-width: 240px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">ชื่อบริษัท / องค์กร *</label>
                                <input type="text" name="company_name" value="<?= e($companyName) ?>" required placeholder="เช่น บริษัท สยาม ออนไลน์ เทรนนิ่ง จำกัด"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                        </div>

                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px;">
                            <div style="flex: 1; min-width: 180px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">สาขา</label>
                                <input type="text" name="branch" value="<?= e($branch) ?>" placeholder="สำนักงานใหญ่"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                            <div style="flex: 1.2; min-width: 180px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">เลขประจำตัวผู้เสียภาษี</label>
                                <input type="text" name="tax_id" value="<?= e($taxId) ?>" placeholder="01055XXXXXXXXX"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                            <div style="flex: 1; min-width: 160px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">เบอร์โทรศัพท์บริษัท</label>
                                <input type="text" name="phone" value="<?= e($companyPhone) ?>" placeholder="02-xxx-xxxx"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">ที่อยู่ (เลขที่, อาคาร, หมู่, ซอย, ถนน) *</label>
                            <input type="text" name="address" value="<?= e($address) ?>" placeholder="เลขที่ อาคาร หมู่ ซอย ถนน..."
                                   style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                        </div>

                        <!-- แถวที่ 1 ของที่อยู่: จังหวัด / อำเภอ / เขต -->
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px;">
                            <div style="flex: 1; min-width: 220px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">จังหวัด *</label>
                                <select name="province" id="addr-province"
                                        style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px; background: #fff;">
                                    <option value="">-- กำลังโหลดข้อมูลจังหวัด --</option>
                                </select>
                            </div>
                            <div style="flex: 1; min-width: 220px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">อำเภอ / เขต *</label>
                                <select name="amphoe" id="addr-amphoe" disabled
                                        style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px; background: #f8fafc;">
                                    <option value="">-- เลือกจังหวัดก่อน --</option>
                                </select>
                            </div>
                        </div>

                        <!-- แถวที่ 2 ของที่อยู่: ตำบล / แขวง / รหัสไปรษณีย์ -->
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px;">
                            <div style="flex: 1; min-width: 220px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">ตำบล / แขวง *</label>
                                <select name="district" id="addr-district" disabled
                                        style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px; background: #f8fafc;">
                                    <option value="">-- เลือกอำเภอ/เขตก่อน --</option>
                                </select>
                            </div>
                            <div style="flex: 1; min-width: 220px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">รหัสไปรษณีย์ *</label>
                                <input type="text" name="zipcode" id="addr-zipcode" maxlength="5" value="<?= e($zipcode) ?>" placeholder="กรอกรหัส 5 หลัก หรือเลือกตำบล"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">อีเมลสำหรับออกบิล</label>
                            <input type="email" name="billing_email" value="<?= e($billingEmail) ?>" placeholder="billing@company.com"
                                   style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                        </div>

                        <?php if ($isNewCompany || $companyDefault !== 1): ?>
                            <div style="margin-bottom: 20px;">
                                <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; color: #374151; cursor: pointer;">
                                    <input type="checkbox" name="set_as_default" value="1" <?= ($isNewCompany && empty($companies)) ? 'checked' : '' ?>>
                                    ตั้งเป็นบริษัทเริ่มต้น (is_default)
                                </label>
                            </div>
                        <?php else: ?>
                            <div style="margin-bottom: 20px; font-size: 0.84rem; color: #166534;">
                                บริษัทนี้ถูกตั้งเป็นบริษัทเริ่มต้น (is_default) อยู่แล้ว
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <a href="<?= e(url('dashboard')) ?>" style="display: inline-block; padding: 9px 16px; border-radius: 6px; border: 1px solid #ced4da; background: #f8f9fa; color: #212529; text-decoration: none; font-size: 0.9rem; font-weight: 500;">ยกเลิก</a>
                            <button type="submit" style="padding: 9px 20px; border-radius: 6px; border: none; background: #16a34a; color: #fff; font-size: 0.9rem; font-weight: 600; cursor: pointer;">บันทึกข้อมูลบริษัท</button>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- ฟอร์มแก้ไขข้อมูลส่วนตัว (และอัปเดตข้อมูลผู้เข้าเรียนที่เป็นของตนเองด้วย) -->
                    <form action="<?= e(url('profile/update')) ?>" method="POST">
                        <?= $csrfHtml ?>
                        <input type="hidden" name="section" value="personal">

                        <div style="margin-bottom: 14px; font-size: 0.82rem; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 6px;">
                            การบันทึกข้อมูลส่วนตัวจะอัปเดตข้อมูลผู้เข้าเรียนที่เป็นของบัญชีคุณในระบบด้วยอัตโนมัติ
                        </div>

                        <!-- แถวที่ 1: คำนำหน้า, ชื่อ, นามสกุล -->
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; align-items: flex-start;">
                            <div style="flex: 0.9; min-width: 150px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">คำนำหน้า</label>
                                <select name="prefix" id="prefixSelect"
                                        style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px; background: #fff;">
                                    <option value="" <?= $selectPrefixVal === '' ? 'selected' : '' ?>>(เลือก)</option>
                                    <?php foreach ($standardPrefixes as $optPrefix): ?>
                                        <option value="<?= e($optPrefix) ?>" <?= $selectPrefixVal === $optPrefix ? 'selected' : '' ?>><?= e($optPrefix) ?></option>
                                    <?php endforeach; ?>
                                    <option value="__other__" <?= $selectPrefixVal === '__other__' ? 'selected' : '' ?>>อื่นๆ (ระบุ)</option>
                                </select>
                                <input type="text" name="prefix_custom" id="prefixCustomInput"
                                       value="<?= e($customPrefixVal) ?>"
                                       placeholder="ระบุคำนำหน้า"
                                       style="width: 100%; font-size: 0.88rem; padding: 7px 10px; border: 1px solid #ced4da; border-radius: 5px; margin-top: 6px; display: <?= $isCustomPrefix ? 'block' : 'none' ?>;">
                            </div>
                            <div style="flex: 1.2; min-width: 180px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">ชื่อ (ภาษาไทย) *</label>
                                <input type="text" name="firstname" value="<?= e($firstname) ?>" required placeholder="เช่น สมชาย"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                            <div style="flex: 1.2; min-width: 180px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">นามสกุล (ภาษาไทย) *</label>
                                <input type="text" name="lastname" value="<?= e($lastname) ?>" required placeholder="เช่น มั่นคง"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                        </div>

                        <!-- แถวที่ 2: อีเมล, เบอร์โทร, เลขบัตรประชาชน -->
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px;">
                            <div style="flex: 1.3; min-width: 200px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">อีเมลผู้เรียน *</label>
                                <input type="email" name="email" value="<?= e($email) ?>" required placeholder="somchai@company.com"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                            <div style="flex: 1; min-width: 160px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">เบอร์โทรศัพท์</label>
                                <input type="text" name="phone" value="<?= e($phone) ?>" placeholder="081-234-5678"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                            <div style="flex: 1.2; min-width: 180px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">เลขบัตรประชาชน (13 หลัก)</label>
                                <input type="text" name="id_card" value="<?= e($idCard) ?>" placeholder="1-2345-67890-12-3"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                        </div>

                        <!-- แถวที่ 3: ตำแหน่งงาน, ความต้องการอื่นๆ -->
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 18px;">
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">ตำแหน่งงาน</label>
                                <input type="text" name="position" value="<?= e($position) ?>" placeholder="เช่น วิศวกร"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">ความต้องการอื่นๆ (ถ้ามี)</label>
                                <input type="text" name="other_requirements" value="<?= e($otherRequirements) ?>" placeholder="เช่น อาหารมังสวิรัติ, อิสลาม"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                        </div>

                        <hr style="border: 0; border-top: 1px solid #e9ecef; margin: 18px 0;">

                        <div style="font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 10px;">
                            เปลี่ยนรหัสผ่านใหม่ <span style="font-weight: 400; color: #6c757d;">(เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน)</span>
                        </div>

                        <!-- แถวที่ 4: รหัสผ่านใหม่ / ยืนยันรหัสผ่านใหม่ -->
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">รหัสผ่านใหม่</label>
                                <input type="password" name="password" placeholder="กรอกรหัสผ่านใหม่"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-size: 0.82rem; color: #495057; display: block; margin-bottom: 4px;">ยืนยันรหัสผ่านใหม่อีกครั้ง</label>
                                <input type="password" name="password_confirmation" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง"
                                       style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <a href="<?= e(url('dashboard')) ?>" style="display: inline-block; padding: 9px 16px; border-radius: 6px; border: 1px solid #ced4da; background: #f8f9fa; color: #212529; text-decoration: none; font-size: 0.9rem; font-weight: 500;">ยกเลิก</a>
                            <button type="submit" style="padding: 9px 20px; border-radius: 6px; border: none; background: #16a34a; color: #fff; font-size: 0.9rem; font-weight: 600; cursor: pointer;">บันทึกข้อมูลส่วนตัว</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Popup รายชื่อผู้เรียนของบริษัท (กดปุ่ม "แก้ไข" รายคน แล้วกด "บันทึก" ทีละคน เฉพาะคนที่ไม่มี user เข้าระบบ) -->
<div id="participantsModalBackdrop"
     onclick="if (event.target === this) closeParticipantsModal();"
     style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
    <div style="width: 100%; max-width: 980px; max-height: 88vh; background: #fff; border-radius: 10px; box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25); display: flex; flex-direction: column; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
            <div>
                <h4 id="participantsModalTitle" style="margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;">รายชื่อผู้เรียนของบริษัท</h4>
                <div id="participantsModalSubtitle" style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">แก้ไขชื่อ-นามสกุล และตำแหน่งได้เฉพาะผู้เข้าอบรมคนอื่นที่ยังไม่มีบัญชีเข้าระบบ (กด "แก้ไข" รายคน แล้วกด "บันทึก" ทีละคน)</div>
            </div>
            <button type="button"
                    onclick="closeParticipantsModal()"
                    style="border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 6px; padding: 4px 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                ปิด ✕
            </button>
        </div>

        <div id="participantsModalBody" style="padding: 18px 20px; overflow-y: auto; flex: 1;">
            <!-- เติมตารางผู้เรียนแบบไดนามิกด้วย JavaScript -->
        </div>
    </div>
</div>

<script>
(function () {
    const sel = document.getElementById('prefixSelect');
    const inp = document.getElementById('prefixCustomInput');
    if (sel && inp) {
        function syncPrefixCustom() {
            const isOther = sel.value === '__other__';
            inp.style.display = isOther ? 'block' : 'none';
            inp.required = isOther;
            if (!isOther) {
                inp.value = '';
            }
        }
        sel.addEventListener('change', syncPrefixCustom);
        syncPrefixCustom();
    }

    const modalCompaniesData = <?= json_encode($modalCompaniesData, JSON_UNESCAPED_UNICODE) ?>;
    const profileUpdateUrl = <?= json_encode(url('profile/update')) ?>;
    const csrfTokenHtml = <?= json_encode($csrfHtml) ?>;

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeNameKey(firstName, lastName) {
        return (String(firstName || '').trim() + ' ' + String(lastName || '').trim()).replace(/\s+/g, ' ').toLowerCase();
    }

    window.toggleParticipantRowEdit = function (pid, enableEdit) {
        const nameViewBox = document.getElementById('part-name-view-' + pid);
        const nameEditBox = document.getElementById('part-name-edit-' + pid);
        const posViewBox  = document.getElementById('part-pos-view-' + pid);
        const posEditBox  = document.getElementById('part-pos-edit-' + pid);
        const btnEdit     = document.getElementById('part-btn-edit-' + pid);
        const btnSave     = document.getElementById('part-btn-save-' + pid);
        const btnCancel   = document.getElementById('part-btn-cancel-' + pid);

        if (nameViewBox) nameViewBox.style.display = enableEdit ? 'none' : 'inline';
        if (nameEditBox) nameEditBox.style.display = enableEdit ? 'flex' : 'none';
        if (posViewBox)  posViewBox.style.display  = enableEdit ? 'none' : 'inline';
        if (posEditBox)  posEditBox.style.display  = enableEdit ? 'block' : 'none';
        if (btnEdit)     btnEdit.style.display     = enableEdit ? 'none' : 'inline-block';
        if (btnSave)     btnSave.style.display     = enableEdit ? 'inline-block' : 'none';
        if (btnCancel)   btnCancel.style.display   = enableEdit ? 'inline-block' : 'none';
    };

    window.submitParticipantRow = function (companyId, pid) {
        const rowEl = document.getElementById('part-row-' + pid);
        if (!rowEl) return;

        const pfxInput = rowEl.querySelector('input[data-field="prefix_name"]');
        const fnInput  = rowEl.querySelector('input[data-field="first_name"]');
        const lnInput  = rowEl.querySelector('input[data-field="last_name"]');
        const posInput = rowEl.querySelector('input[data-field="position"]');

        const pfxVal = String(pfxInput ? pfxInput.value : '').trim();
        const fnVal  = String(fnInput ? fnInput.value : '').trim();
        const lnVal  = String(lnInput ? lnInput.value : '').trim();
        const posVal = String(posInput ? posInput.value : '').trim();

        if (!fnVal || !lnVal) {
            alert('กรุณาระบุชื่อและนามสกุลของผู้เข้าอบรมให้ครบถ้วน');
            return;
        }

        const compData = modalCompaniesData[String(companyId)] || modalCompaniesData[companyId] || null;
        const list = (compData && Array.isArray(compData.participants)) ? compData.participants : [];
        const targetKey = normalizeNameKey(fnVal, lnVal);

        const duplicate = list.find(function (item) {
            return Number(item.id || 0) !== Number(pid) && normalizeNameKey(item.first_name, item.last_name) === targetKey;
        });

        if (duplicate) {
            const dupName = [duplicate.prefix_name || '', duplicate.first_name || '', duplicate.last_name || '']
                .map(s => String(s).trim())
                .filter(Boolean)
                .join(' ');
            alert('ชื่อ-นามสกุล "' + fnVal + ' ' + lnVal + '" ซ้ำกับผู้เข้าอบรมรายอื่นในบริษัท (' + (dupName || 'รายการอื่น') + ')');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = profileUpdateUrl;
        form.style.display = 'none';

        // แทรก CSRF input ถ้ามี
        if (csrfTokenHtml) {
            const tmp = document.createElement('div');
            tmp.innerHTML = csrfTokenHtml;
            tmp.querySelectorAll('input').forEach(function (inp) {
                const clone = document.createElement('input');
                clone.type = inp.type || 'hidden';
                clone.name = inp.name;
                clone.value = inp.value;
                form.appendChild(clone);
            });
        }

        const fields = {
            section: 'update_participant_name',
            participant_id: String(pid),
            company_id: String(companyId),
            prefix_name: pfxVal,
            first_name: fnVal,
            last_name: lnVal,
            position: posVal
        };

        Object.keys(fields).forEach(function (k) {
            const h = document.createElement('input');
            h.type = 'hidden';
            h.name = k;
            h.value = fields[k];
            form.appendChild(h);
        });

        document.body.appendChild(form);
        form.submit();
    };

    window.openParticipantsModal = function (companyId) {
        const backdrop = document.getElementById('participantsModalBackdrop');
        const titleEl = document.getElementById('participantsModalTitle');
        const subtitleEl = document.getElementById('participantsModalSubtitle');
        const bodyEl = document.getElementById('participantsModalBody');
        if (!backdrop || !titleEl || !subtitleEl || !bodyEl) return;

        const compData = modalCompaniesData[String(companyId)] || modalCompaniesData[companyId] || null;
        const compName = compData ? (compData.company_name || ('บริษัท #' + companyId)) : ('บริษัท #' + companyId);
        const list = (compData && Array.isArray(compData.participants)) ? compData.participants : [];

        titleEl.textContent = 'รายชื่อผู้เรียนของบริษัท "' + compName + '"';
        subtitleEl.textContent = 'ทั้งหมด ' + list.length + ' คน — กด "แก้ไข" รายคน แล้วกด "บันทึก" ทีละคน (แก้ชื่อ-นามสกุล-ตำแหน่งได้เฉพาะคนที่ไม่มี user เข้าระบบ และเช็กชื่อ-นามสกุลซ้ำ)';

        if (!list.length) {
            bodyEl.innerHTML = '<div style="padding: 18px; border: 1px dashed #cbd5e1; border-radius: 8px; text-align: center; color: #64748b; font-size: 0.88rem; background: #f8fafc;">ยังไม่มีรายชื่อผู้เรียนที่ผูกกับบริษัทนี้</div>';
        } else {
            let rowsHtml = '';
            list.forEach(function (p, idx) {
                const pid = Number(p.id || 0);
                const fullName = [p.prefix_name || '', p.first_name || '', p.last_name || '']
                    .map(s => String(s).trim())
                    .filter(Boolean)
                    .join(' ');
                const posText = String(p.position || '').trim();
                const deptText = String(p.department || '').trim();
                const posDisplay = [posText, deptText].filter(Boolean).join(' / ');
                const contactInfo = String(p.email || '').trim() !== '' ? p.email : ('PIN: ' + (p.pin_code || '-'));

                let actionHtml = '';
                if (p.can_edit_name) {
                    actionHtml = `
                        <button type="button" id="part-btn-edit-${pid}"
                                onclick="toggleParticipantRowEdit(${pid}, true)"
                                style="padding: 4px 10px; border-radius: 4px; border: 1px solid #93c5fd; background: #eff6ff; color: #1d4ed8; font-size: 0.78rem; font-weight: 600; cursor: pointer;">
                            แก้ไข
                        </button>
                        <button type="button" id="part-btn-save-${pid}"
                                onclick="submitParticipantRow(${escapeHtml(companyId)}, ${pid})"
                                style="display: none; padding: 4px 10px; border-radius: 4px; border: none; background: #16a34a; color: #fff; font-size: 0.78rem; font-weight: 600; cursor: pointer; margin-right: 4px;">
                            บันทึก
                        </button>
                        <button type="button" id="part-btn-cancel-${pid}"
                                onclick="toggleParticipantRowEdit(${pid}, false)"
                                style="display: none; padding: 4px 8px; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff; color: #475569; font-size: 0.78rem; cursor: pointer;">
                            ยกเลิก
                        </button>
                    `;
                } else if (p.is_self) {
                    actionHtml = `<span style="display: inline-block; padding: 2px 8px; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: 0.75rem;">ชื่อตัวเอง (แก้ที่ข้อมูลส่วนตัว)</span>`;
                } else {
                    actionHtml = `<span style="display: inline-block; padding: 2px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 0.75rem;">มี user เข้าระบบแล้ว</span>`;
                }

                const nameCellHtml = p.can_edit_name
                    ? `
                        <span id="part-name-view-${pid}" style="font-weight: 600; color: #0f172a;">${escapeHtml(fullName || '-')}</span>
                        <div id="part-name-edit-${pid}" style="display: none; gap: 6px; flex-wrap: wrap; align-items: center;">
                            <input type="text" data-field="prefix_name" value="${escapeHtml(p.prefix_name)}" placeholder="คำนำหน้า"
                                   style="width: 74px; font-size: 0.8rem; padding: 4px 6px; border: 1px solid #93c5fd; border-radius: 4px;">
                            <input type="text" data-field="first_name" value="${escapeHtml(p.first_name)}" placeholder="ชื่อจริง"
                                   style="width: 110px; font-size: 0.8rem; padding: 4px 6px; border: 1px solid #93c5fd; border-radius: 4px;">
                            <input type="text" data-field="last_name" value="${escapeHtml(p.last_name)}" placeholder="นามสกุล"
                                   style="width: 110px; font-size: 0.8rem; padding: 4px 6px; border: 1px solid #93c5fd; border-radius: 4px;">
                        </div>
                    `
                    : `<span style="font-weight: 600; color: #0f172a;">${escapeHtml(fullName || '-')}</span>`;

                const posCellHtml = p.can_edit_name
                    ? `
                        <span id="part-pos-view-${pid}" style="color: #475569;">${escapeHtml(posDisplay || '-')}</span>
                        <div id="part-pos-edit-${pid}" style="display: none;">
                            <input type="text" data-field="position" value="${escapeHtml(p.position)}" placeholder="ตำแหน่ง"
                                   style="width: 145px; font-size: 0.8rem; padding: 4px 6px; border: 1px solid #93c5fd; border-radius: 4px;">
                        </div>
                    `
                    : `<span style="color: #64748b;">${escapeHtml(posDisplay || '-')}</span>`;

                rowsHtml += `
                    <tr id="part-row-${pid}" style="border-bottom: 1px solid #eef2f6;">
                        <td style="padding: 9px 10px; color: #64748b; width: 42px;">${idx + 1}</td>
                        <td style="padding: 9px 10px;">${nameCellHtml}</td>
                        <td style="padding: 9px 10px; color: #334155;">${escapeHtml(contactInfo)}</td>
                        <td style="padding: 9px 10px;">${posCellHtml}</td>
                        <td style="padding: 9px 10px; text-align: right; white-space: nowrap;">${actionHtml}</td>
                    </tr>
                `;
            });

            bodyEl.innerHTML = `
                <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="background: #f1f5f9; border-bottom: 1px solid #e2e8f0; text-align: left; color: #1e293b;">
                                <th style="padding: 9px 10px; width: 42px;">#</th>
                                <th style="padding: 9px 10px;">ชื่อ - นามสกุลผู้เรียน</th>
                                <th style="padding: 9px 10px;">อีเมล / PIN</th>
                                <th style="padding: 9px 10px;">ตำแหน่ง</th>
                                <th style="padding: 9px 10px; text-align: right;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                </div>
            `;
        }

        backdrop.style.display = 'flex';
    };

    window.closeParticipantsModal = function () {
        const backdrop = document.getElementById('participantsModalBackdrop');
        if (backdrop) {
            backdrop.style.display = 'none';
        }
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.closeParticipantsModal();
        }
    });

    const autoOpenCompanyId = <?= (int)$openModalCompanyId ?>;
    if (autoOpenCompanyId > 0) {
        window.openParticipantsModal(autoOpenCompanyId);
    }

    let thaiAddressDB = [];
    const targetProvince = <?= json_encode($province, JSON_UNESCAPED_UNICODE) ?>;
    const targetAmphoe = <?= json_encode($amphoe, JSON_UNESCAPED_UNICODE) ?>;
    const targetDistrict = <?= json_encode($district, JSON_UNESCAPED_UNICODE) ?>;
    const targetZipcode = <?= json_encode($zipcode, JSON_UNESCAPED_UNICODE) ?>;

    async function initThaiAddress() {
        const provinceSelect = document.getElementById('addr-province');
        const amphoeSelect = document.getElementById('addr-amphoe');
        const districtSelect = document.getElementById('addr-district');
        const zipcodeInput = document.getElementById('addr-zipcode');

        if (!provinceSelect || !amphoeSelect || !districtSelect || !zipcodeInput) return;

        try {
            const res = await fetch('https://raw.githubusercontent.com/earthchie/jquery.Thailand.js/master/jquery.Thailand.js/database/raw_database/raw_database.json');
            if (!res.ok) throw new Error('Network error');
            thaiAddressDB = await res.json();

            const provinces = [...new Set(thaiAddressDB.map(item => item.province))].sort((a, b) => a.localeCompare(b, 'th'));
            provinceSelect.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
            provinces.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                provinceSelect.appendChild(opt);
            });

            function populateAmphoes(selectedProv, defaultAmphoe = '') {
                amphoeSelect.innerHTML = '<option value="">-- เลือกอำเภอ / เขต --</option>';
                districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ/เขตก่อน --</option>';
                districtSelect.disabled = true;
                districtSelect.style.background = '#f8fafc';

                if (!selectedProv) {
                    amphoeSelect.disabled = true;
                    amphoeSelect.style.background = '#f8fafc';
                    return;
                }

                const amphoes = [...new Set(thaiAddressDB.filter(item => item.province === selectedProv).map(item => item.amphoe))].sort((a, b) => a.localeCompare(b, 'th'));
                amphoes.forEach(a => {
                    const opt = document.createElement('option');
                    opt.value = a;
                    opt.textContent = a;
                    if (a === defaultAmphoe) opt.selected = true;
                    amphoeSelect.appendChild(opt);
                });

                amphoeSelect.disabled = false;
                amphoeSelect.style.background = '#fff';
            }

            function populateDistricts(selectedProv, selectedAmphoe, defaultDistrict = '') {
                districtSelect.innerHTML = '<option value="">-- เลือกตำบล / แขวง --</option>';

                if (!selectedProv || !selectedAmphoe) {
                    districtSelect.disabled = true;
                    districtSelect.style.background = '#f8fafc';
                    return;
                }

                const districts = thaiAddressDB
                    .filter(item => item.province === selectedProv && item.amphoe === selectedAmphoe)
                    .sort((a, b) => a.district.localeCompare(b.district, 'th'));

                const seen = new Set();
                districts.forEach(d => {
                    const key = d.district + '_' + d.zipcode;
                    if (seen.has(key)) return;
                    seen.add(key);

                    const opt = document.createElement('option');
                    opt.value = d.district;
                    opt.textContent = d.district;
                    opt.dataset.zipcode = d.zipcode;
                    if (d.district === defaultDistrict) opt.selected = true;
                    districtSelect.appendChild(opt);
                });

                districtSelect.disabled = false;
                districtSelect.style.background = '#fff';
            }

            provinceSelect.addEventListener('change', function () {
                populateAmphoes(this.value);
                zipcodeInput.value = '';
            });

            amphoeSelect.addEventListener('change', function () {
                populateDistricts(provinceSelect.value, this.value);
                zipcodeInput.value = '';
            });

            districtSelect.addEventListener('change', function () {
                const selectedOpt = this.options[this.selectedIndex];
                if (selectedOpt && selectedOpt.dataset.zipcode) {
                    zipcodeInput.value = selectedOpt.dataset.zipcode;
                }
            });

            zipcodeInput.addEventListener('input', function () {
                const zip = this.value.trim();
                if (zip.length === 5) {
                    const matches = thaiAddressDB.filter(item => String(item.zipcode) === zip);
                    if (matches.length > 0) {
                        const first = matches[0];
                        provinceSelect.value = first.province;
                        populateAmphoes(first.province, first.amphoe);
                        populateDistricts(first.province, first.amphoe, matches.length === 1 ? first.district : '');
                    }
                }
            });

            if (targetProvince) {
                provinceSelect.value = targetProvince;
                populateAmphoes(targetProvince, targetAmphoe);
                if (targetAmphoe) {
                    populateDistricts(targetProvince, targetAmphoe, targetDistrict);
                }
                if (targetZipcode) {
                    zipcodeInput.value = targetZipcode;
                }
            }
        } catch (err) {
            console.error('ไม่สามารถโหลดข้อมูลจังหวัด/อำเภอ/ตำบลได้:', err);
            provinceSelect.outerHTML = `<input type="text" name="province" id="addr-province" value="${targetProvince}" placeholder="ระบุจังหวัด" style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">`;
            amphoeSelect.outerHTML = `<input type="text" name="amphoe" id="addr-amphoe" value="${targetAmphoe}" placeholder="ระบุอำเภอ / เขต" style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">`;
            districtSelect.outerHTML = `<input type="text" name="district" id="addr-district" value="${targetDistrict}" placeholder="ระบุตำบล / แขวง" style="width: 100%; font-size: 0.9rem; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px;">`;
        }
    }

    initThaiAddress();
})();
</script>