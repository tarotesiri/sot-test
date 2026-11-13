<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : SettingController.php
 * Description : Setting Controller
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Modules\Admin\Models\SettingAdminModel;
use Modules\Admin\Support\AdminHelper as H;

final class SettingController extends AdminBaseController
{
    public function index(): string
    {
        $this->admGuard();
        $s = new SettingAdminModel($this->db());
        $data = [
            'days' => (int) $s->get('default_payment_days', '20'),
            'name_x' => (int) $s->get('default_cert_name_x', '100'),
            'name_y' => (int) $s->get('default_cert_name_y', '200'),
            'date_x' => (int) $s->get('default_cert_date_x', '100'),
            'date_y' => (int) $s->get('default_cert_date_y', '300'),
        ];
        return $this->admPage('settings/index', $data, 'ตั้งค่ามาตรฐานระบบ', 'settings');
    }

    public function update(): void
    {
        $this->admGuard();
        $s = new SettingAdminModel($this->db());
        $s->set('default_payment_days', (string) H::inInt('default_payment_days', 20));
        $s->set('default_cert_name_x', (string) H::inInt('default_cert_name_x', 100));
        $s->set('default_cert_name_y', (string) H::inInt('default_cert_name_y', 200));
        $s->set('default_cert_date_x', (string) H::inInt('default_cert_date_x', 100));
        $s->set('default_cert_date_y', (string) H::inInt('default_cert_date_y', 300));

        H::flash('success', 'บันทึกสำเร็จ: อัปเดตการตั้งค่ามาตรฐานระบบเรียบร้อยแล้ว');
        H::redirect('admin/settings');
    }
}