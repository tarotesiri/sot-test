<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : online.php
 *    Description : Widget แสดงหลักสูตร Live Online ยอดนิยมในหน้าแรก
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
?>
<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <h2 class="section__title">หลักสูตร <span class="hl">Live Online</span> ยอดนิยม</h2>
                <p class="section__subtitle">อบรมสดผ่านระบบออนไลน์ พร้อมวิทยากรผู้เชี่ยวชาญ</p>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= e(url('/courses')) ?>">ดูทั้งหมด <?= icon('arrow-right') ?></a>
        </div>

        <?php if (empty($courses)): ?>
        <div class="empty-state">ยังไม่มีหลักสูตรเปิดสอนในขณะนี้</div>
        <?php else: ?>
        <div class="grid" style="--min:320px">
            <?php foreach ($courses as $c): ?>
            <div class="card course-card">
                <div class="course-card__cover cover--<?= ($c['id'] % 4) + 1 ?>">
                    <div class="course-card__badges">
                        <span class="badge badge--solid">Live Online</span>
                        <?php if (!empty($c['category_name'])): ?>
                        <span class="badge" style="background:rgba(255,255,255,.9);color:var(--brand-900)"><?= e($c['category_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?= icon('monitor') ?>
                </div>
                <div class="course-card__body">
                    <div class="course-card__cat"><?= e($c['code'] ?? 'ONLINE') ?></div>
                    <h3 class="course-card__title"><a href="<?= e(url('/courses/' . $c['slug'])) ?>"><?= e($c['title']) ?></a></h3>
                    <p style="color:var(--muted);font-size:.92rem;margin-bottom:0"><?= e(str_limit((string) $c['summary'], 85)) ?></p>
                    <div class="course-card__foot">
                        <div class="price">
                            <?= $c['min_price'] !== null ? baht($c['min_price'], true) : 'สอบถามราคา' ?>
                            <small>เริ่มต้น</small>
                        </div>
                        <a class="btn btn--sm btn--outline" href="<?= e(url('/courses/' . $c['slug'])) ?>">รายละเอียด <?= icon('arrow-right') ?></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>