<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name    : CartService.php
 * Module       : Cart Service Layer
 * Description  : จัดการ Cart Session State, รายชื่อผู้เข้าอบรม, การคำนวณราคารวม, VAT 7% และ WHT 3%
 * Author       : Software Engineering Team
 * Last Updated : 2026-11-12
 * ====================================================================
**/

declare(strict_types=1);

namespace Modules\Cart\Services;

class CartService
{
    private string $sessionKey = 'sot_shopping_cart';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }

    public function getItems(): array
    {
        return $_SESSION[$this->sessionKey] ?? [];
    }

    public function addItem(array $itemData): void
    {
        $key = $itemData['batch_id'] . '_' . $itemData['rate_tier'];
        $addQty = max(1, (int)($itemData['quantity'] ?? 1));
        
        if (isset($_SESSION[$this->sessionKey][$key])) {
            $_SESSION[$this->sessionKey][$key]['quantity'] += $addQty;
            $currentAttendees = $_SESSION[$this->sessionKey][$key]['attendees'] ?? [];
            for ($i = 0; $i < $addQty; $i++) {
                $currentAttendees[] = $this->createEmptyAttendee();
            }
            $_SESSION[$this->sessionKey][$key]['attendees'] = $currentAttendees;
        } else {
            $initialAttendees = [];
            for ($i = 0; $i < $addQty; $i++) {
                $initialAttendees[] = $this->createEmptyAttendee();
            }

            $_SESSION[$this->sessionKey][$key] = [
                'key'          => $key,
                'course_id'    => (int)$itemData['course_id'],
                'course_title' => (string)$itemData['course_title'],
                'course_type'  => (string)($itemData['course_type'] ?? 'live_online'),
                'batch_id'     => (int)$itemData['batch_id'],
                'batch_no'     => (string)$itemData['batch_no'],
                'date_text'    => (string)($itemData['date_text'] ?? ''),
                'rate_tier'    => (string)$itemData['rate_tier'],
                'tier_name'    => (string)$itemData['tier_name'],
                'unit_price'   => (float)$itemData['unit_price'],
                'quantity'     => $addQty,
                'attendees'    => $initialAttendees,
            ];
        }
    }

    public function syncFromFormPost(array $postAttendees): void
    {
        foreach ($postAttendees as $key => $attendeesList) {
            if (!isset($_SESSION[$this->sessionKey][$key]) || !is_array($attendeesList)) {
                continue;
            }

            $cleanAttendees = [];
            foreach ($attendeesList as $att) {
                $prefix = trim((string)($att['prefix'] ?? ''));
                if ($prefix === 'อื่นๆ') {
                    $prefix = trim((string)($att['custom_prefix'] ?? ''));
                }

                $cleanAttendees[] = [
                    'prefix'             => $prefix,
                    'first_name'         => trim((string)($att['first_name'] ?? '')),
                    'last_name'          => trim((string)($att['last_name'] ?? '')),
                    'email'              => trim((string)($att['email'] ?? '')),
                    'phone'              => trim((string)($att['phone'] ?? '')),
                    'id_card'            => trim((string)($att['id_card'] ?? '')),
                    'position'           => trim((string)($att['position'] ?? '')),
                    'other_requirements' => trim((string)($att['other_requirements'] ?? '')),
                ];
            }

            $_SESSION[$this->sessionKey][$key]['attendees'] = $cleanAttendees;
            $_SESSION[$this->sessionKey][$key]['quantity']  = max(1, count($cleanAttendees));
        }
    }

    // เมธอดรองรับการซิงค์ผ่าน AJAX ทันทีที่มีการเปลี่ยนแปลง
    public function syncAttendeesAjax(string $key, array $attendeesList): bool
    {
        if (!isset($_SESSION[$this->sessionKey][$key])) {
            return false;
        }

        $cleanAttendees = [];
        foreach ($attendeesList as $att) {
            $prefix = trim((string)($att['prefix'] ?? ''));
            if ($prefix === 'อื่นๆ') {
                $prefix = trim((string)($att['custom_prefix'] ?? ''));
            }

            $cleanAttendees[] = [
                'prefix'             => $prefix,
                'first_name'         => trim((string)($att['first_name'] ?? '')),
                'last_name'          => trim((string)($att['last_name'] ?? '')),
                'email'              => trim((string)($att['email'] ?? '')),
                'phone'              => trim((string)($att['phone'] ?? '')),
                'id_card'            => trim((string)($att['id_card'] ?? '')),
                'position'           => trim((string)($att['position'] ?? '')),
                'other_requirements' => trim((string)($att['other_requirements'] ?? '')),
            ];
        }

        $_SESSION[$this->sessionKey][$key]['attendees'] = $cleanAttendees;
        $_SESSION[$this->sessionKey][$key]['quantity']  = max(1, count($cleanAttendees));

        return true;
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($key);
            return;
        }
        if (isset($_SESSION[$this->sessionKey][$key])) {
            $_SESSION[$this->sessionKey][$key]['quantity'] = $quantity;
        }
    }

    public function removeItem(string $key): void
    {
        unset($_SESSION[$this->sessionKey][$key]);
    }

    public function clear(): void
    {
        $_SESSION[$this->sessionKey] = [];
    }

    public function getTotals(): array
    {
        $items = $this->getItems();
        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += ($item['unit_price'] * $item['quantity']);
        }

        $vat = $subtotal * 0.07;
        $wht = $subtotal * 0.03;
        $grandTotal = $subtotal + $vat - $wht;

        return [
            'count'       => count($items),
            'total_qty'   => array_sum(array_column($items, 'quantity')),
            'subtotal'    => $subtotal,
            'vat'         => $vat,
            'wht'         => $wht,
            'grand_total' => $grandTotal
        ];
    }

    private function createEmptyAttendee(): array
    {
        return [
            'prefix'             => '',
            'first_name'         => '',
            'last_name'          => '',
            'email'              => '',
            'phone'              => '',
            'id_card'            => '',
            'position'           => '',
            'other_requirements' => '',
        ];
    }
}