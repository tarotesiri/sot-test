<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : SettingController.php
 * Description : Setting Controller
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.24.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Modules\Admin\Models\SettingAdminModel;
use Modules\Admin\Support\AdminHelper as H;
use Modules\Admin\Support\Uploader;
use RuntimeException;

final class SettingController extends AdminBaseController
{
    public function index(): string
    {
        $this->admGuard();
        $s = new SettingAdminModel($this->db());
        $data = [
            'days'             => (int) $s->get('default_payment_days', '20'),
            'name_x'           => (int) $s->get('default_cert_name_x', '100'),
            'name_y'           => (int) $s->get('default_cert_name_y', '200'),
            'date_x'           => (int) $s->get('default_cert_date_x', '100'),
            'date_y'           => (int) $s->get('default_cert_date_y', '300'),
            'site_name'        => $s->get('site_name', 'SIAM Online Training'),
            'site_tagline'     => $s->get('site_tagline', ''),
            'footer_about'     => $s->get('footer_about', ''),
            'logo_path'        => $s->get('logo_path', ''),
            'contact_phone'    => $s->get('contact_phone', ''),
            'contact_email'    => $s->get('contact_email', ''),
            'contact_line'     => $s->get('contact_line', ''),
            'contact_address'  => $s->get('contact_address', ''),
            'office_hours'     => $s->get('office_hours', ''),
            'vat_rate'         => $s->get('vat_rate', '7'),
            'wht_rate'         => $s->get('wht_rate', '3'),
            'clip_access_days' => (int) $s->get('clip_access_days', '30'),
        ];
        return $this->admPage('settings/index', $data, 'ตั้งค่ามาตรฐานระบบ', 'settings');
    }

    public function update(): void
    {
        $this->admGuard();
        $s = new SettingAdminModel($this->db());

        $s->set('default_payment_days', (string) H::inInt('default_payment_days', 20), 'int', 'general', 'จำนวนวันก่อนวันอบรมวันแรก สำหรับราคาชำระก่อน (Live Online)');
        $s->set('default_cert_name_x', (string) H::inInt('default_cert_name_x', 100), 'int', 'general', 'พิกัด X ชื่อ-นามสกุล บนใบเซอร์');
        $s->set('default_cert_name_y', (string) H::inInt('default_cert_name_y', 200), 'int', 'general', 'พิกัด Y ชื่อ-นามสกุล บนใบเซอร์');
        $s->set('default_cert_date_x', (string) H::inInt('default_cert_date_x', 100), 'int', 'general', 'พิกัด X วันที่ออกใบเซอร์');
        $s->set('default_cert_date_y', (string) H::inInt('default_cert_date_y', 300), 'int', 'general', 'พิกัด Y วันที่ออกใบเซอร์');

        $s->set('site_name', H::in('site_name', 250), 'string', 'site', 'ชื่อเว็บไซต์');
        $s->set('site_tagline', H::in('site_tagline', 250), 'string', 'site', 'คำโปรย');
        $s->set('footer_about', H::in('footer_about'), 'string', 'site', 'ข้อความท้ายเว็บ');

        $logoPath = trim($s->get('logo_path', ''));
        if (H::in('remove_logo') === '1') {
            if ($logoPath !== '') {
                Uploader::delete($logoPath);
            }
            $logoPath = '';
        }
        try {
            if (isset($_FILES['logo_file']) && (int)($_FILES['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                if ($logoPath !== '') {
                    Uploader::delete($logoPath);
                }
                $logoPath = Uploader::upload($_FILES['logo_file'], 'site/logo', 5242880, ['jpg', 'jpeg', 'png', 'webp', 'svg']);
            }
        } catch (RuntimeException $e) {
            H::flash('error', $e->getMessage());
            H::redirect('admin/settings');
        }
        $s->set('logo_path', $logoPath !== '' ? $logoPath : '', 'string', 'site', 'ไฟล์โลโก้ (ว่าง = ใช้โลโก้เริ่มต้น)');

        $s->set('contact_phone', H::in('contact_phone', 150), 'string', 'contact', 'เบอร์โทร');
        $s->set('contact_email', H::in('contact_email', 150), 'string', 'contact', 'อิเมล');
        $s->set('contact_line', H::in('contact_line', 150), 'string', 'contact', 'LINE ID');
        $s->set('contact_address', H::in('contact_address'), 'string', 'contact', 'ที่อยู่');
        $s->set('office_hours', H::in('office_hours', 250), 'string', 'contact', 'เวลาทำการ');

        $vatRate = max(0.0, H::inFloat('vat_rate', 7.0));
        $whtRate = max(0.0, H::inFloat('wht_rate', 3.0));
        $s->set('vat_rate', rtrim(rtrim(number_format($vatRate, 2, '.', ''), '0'), '.') ?: '0', 'float', 'finance', 'อัตรา VAT (%)');
        $s->set('wht_rate', rtrim(rtrim(number_format($whtRate, 2, '.', ''), '0'), '.') ?: '0', 'float', 'finance', 'อัตราหัก ณ ที่จ่ายเริ่มต้น (%)');

        $clipDays = max(1, H::inInt('clip_access_days', 30));
        $s->set('clip_access_days', (string) $clipDays, 'int', 'learning', 'จำนวนวันเข้าเรียน/พิมพ์ใบเซอร์ หลักสูตร Clip นับจากวันอนุมัติ');

        H::flash('success', 'บันทึกสำเร็จ: อัปเดตการตั้งค่ามาตรฐานระบบเรียบร้อยแล้ว');
        H::redirect('admin/settings');
    }
}