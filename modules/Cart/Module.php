<?php
/**
 * ============================================================================
 *   Project Name : SIAM Online Training (SOT LMS)
 *   File Name    : Module.php
 *   Module       : Cart Module Registration
 *   Description  : ลงทะเบียน Route และ Service ของระบบตะกร้าสินค้า การสั่งซื้อ และชำระเงิน
 * ============================================================================
 *   Author       : TaRote SiRi
 *   Created Date : 2026-11-10
 *   Version      : 1.0.1
 *   License      : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/

declare(strict_types=1);

namespace Modules\Cart;

use App\Core\App;
use App\Core\ModuleInterface;
use Modules\Cart\Controllers\CartController;
use Modules\Cart\Controllers\CheckoutController;
use Modules\Cart\Controllers\PaymentController;

final class Module implements ModuleInterface
{
    public function register(App $app): void
    {
        $router = $app->router;

        // --------------------------------------------------------------------
        // 1. Cart Routes (ระบบตะกร้าสินค้า & AJAX Sync)
        // --------------------------------------------------------------------
        $router->get('/cart', [CartController::class, 'index'], 'cart.index');
        $router->post('/cart/add', [CartController::class, 'add'], 'cart.add');
        $router->post('/cart/update', [CartController::class, 'update'], 'cart.update');
        $router->get('/cart/remove', [CartController::class, 'remove'], 'cart.remove');
        $router->get('/cart/clear', [CartController::class, 'clear'], 'cart.clear');
        
        // เพิ่ม Route นี้เพื่อให้ AJAX สามารถวิ่งเข้ามาซิงค์ข้อมูลรายชื่อผู้เข้าอบรมได้ถูกต้อง 100%
        $router->post('/cart/sync-ajax', [CartController::class, 'syncAjax'], 'cart.syncAjax');
        $router->get('/cart/my-profile', [CartController::class, 'getMyProfileAjax'], 'cart.getMyProfileAjax');

        // --------------------------------------------------------------------
        // 2. Checkout Routes (ระบบลงทะเบียนผู้เข้าอบรม & ออกใบเสร็จ)
        // --------------------------------------------------------------------
        $router->get('/checkout', [CheckoutController::class, 'index'], 'checkout.index');
        $router->post('/checkout/place-order', [CheckoutController::class, 'placeOrder'], 'checkout.placeOrder');
        $router->get('/checkout/success', [CheckoutController::class, 'success'], 'checkout.success');

        // --------------------------------------------------------------------
        // 3. Payment Routes (ระบบอัปโหลดสลิปชำระเงิน)
        // --------------------------------------------------------------------
        $router->post('/payment/upload-slip', [PaymentController::class, 'uploadSlip'], 'payment.uploadSlip');
    }
}