<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : RoadmapController.php
 *    Description : Controller แสดงข้อมูล Roadmap 14 Modules พร้อมรายละเอียดตามเอกสารต้นฉบับ
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 3.1.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

namespace Modules\Roadmap\Controllers;

use App\Core\Controller;

final class RoadmapController extends Controller
{
    private array $modules = [
        'm0' => [
            'id' => 'm0', 'num' => 'Module 0', 'title' => 'Core Foundation & Theme Engine',
            'desc' => 'โครงสร้างพื้นฐานระบบ, Database PDO, Router, View Engine, Setup Wizard และ Theme ขาว-เขียว',
            'status' => 'completed', 'status_label' => 'ติดตั้งแล้ว (Completed)',
            'features' => [
                '<strong>ทำอะไรได้:</strong> โครงสร้างโฟลเดอร์, Autoload, ไฟล์ <code>.env</code>, คลาส Database (PDO), Router + Front Controller, Base Controller/Model/View, ฟังก์ชันกัน XSS (<code>e()</code>), CSRF Token, Flash Message, หน้า 404/500',
                '<strong>Template:</strong> ส่วนหัว (Logo Banner), แถบเมนู, ส่วนเนื้อหา, ส่วนท้าย โทนสีขาว-เขียว ข้อความที่ต้องการเน้นเป็นสีน้ำเงิน (<code>.text-highlight</code>) โดยกำหนดสีทั้งหมดผ่าน CSS Variables',
                '<strong>หน้าแรก:</strong> มี 3 Widget Slot คือ Slide, Online, Clip (ตอนนี้ยังเป็น Placeholder)',
                '<strong>ตารางหลัก:</strong> <code>settings</code>, <code>migrations</code>',
                '<strong>ข้อมูลทดสอบ:</strong> ชื่อเว็บ/โลโก้ใน <code>settings</code> เป็น Seed (ข้อมูลจริงจะมาจาก M14) ส่วนสไลด์และคลิปเป็น Static Placeholder (ข้อมูลจริงจาก M14)'
            ]
        ],
        'm1' => [
            'id' => 'm1', 'num' => 'Module 1', 'title' => 'Course Catalog & Details',
            'desc' => 'หน้าแสดงรายการหลักสูตร, ค้นหา, กรองตามหมวดหมู่และประเภท Live Online / Clip VDO',
            'status' => 'completed', 'status_label' => 'ติดตั้งแล้ว (Completed)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; <strong>หน้ารายการหลักสูตร:</strong> ค้นหา, กรองตามหมวด/รูปแบบ (Online/Onsite)/สถานะ, แบ่งหน้า<br>' .
                '&bull; <strong>หน้ารายละเอียด:</strong> ภาพโบรชัวร์, รายละเอียดหลักสูตร, ตารางรุ่นที่/วันที่อบรม/จำนวนที่นั่ง, ตารางราคา, ดาวน์โหลดเอกสาร, ป้ายสถานะ (เปิดรับ/เต็ม/ปิด/ยกเลิก)<br>' .
                '&bull; รองรับราคา 3 แบบ ได้แก่ ฟรี, 2 ราคา (สมาชิก/ไม่ใช่สมาชิก) และ 4 ราคา (สมาชิก × ชำระก่อน 20 วัน/หลัง 20 วัน)<br>' .
                '&bull; เชื่อม Widget "Online" ในหน้าแรกเข้ากับหลักสูตร<br>' .
                '&bull; ปุ่ม "เพิ่มลงตะกร้า" แสดงไว้ก่อน แต่จะเริ่มใช้งานได้ใน M4',
                '<strong>ตารางหลัก:</strong> <code>course_categories</code>, <code>courses</code>, <code>course_sessions</code>, <code>course_prices</code>, <code>course_documents</code>',
                '<strong>ข้อมูลทดสอบ:</strong> Seed ประมาณ 6 หลักสูตรให้ครอบคลุมราคาทุกแบบและทุกสถานะ (ข้อมูลจริงมาจาก M3 ระบบหลังบ้าน) ส่วนจำนวนที่นั่งคงเหลือจะคำนวณจาก Enrollment ใน M5 (ตอนนี้ถือว่ายังไม่มีผู้สมัคร)'
            ]
        ],
        'm2' => [
            'id' => 'm2', 'num' => 'Module 2', 'title' => 'Authentication & User Profile',
            'desc' => 'ระบบสมาชิก เข้าสู่ระบบ, สมัครสมาชิก, ยืนยันตัวตน และจัดการข้อมูลส่วนตัว',
            'status' => 'processing', 'status_label' => 'ดำเนินการบางส่วน (Processing)',
            'features' => [
                '<strong>ทำอะไรได้:</strong> สมัครสมาชิก, Login/Logout, ลืมรหัสผ่าน, แก้ไขโปรไฟล์, ชื่อ-นามสกุลสำหรับพิมพ์ใบเซอร์ (TH/EN), จำกัดจำนวนครั้งที่ Login ผิด (Login Throttle), Password Hash',
                '<strong>Role:</strong> admin, staff, buyer (ผู้สั่งซื้อ/ผู้ประสานงาน), learner (ผู้เรียน) โดย 1 คนมีได้หลาย Role',
                '<strong>สถานะสมาชิก:</strong> เลขสมาชิกและวันหมดอายุ ใช้เป็นเงื่อนไขในการคำนวณราคา',
                '<strong>ตารางหลัก:</strong> <code>users</code>, <code>user_profiles</code>, <code>user_roles</code>, <code>memberships</code>, <code>password_resets</code>',
                '<strong>ข้อมูลทดสอบ:</strong> Seed บัญชี admin 1 บัญชี และผู้ใช้ 3 บัญชี (เป็นสมาชิกและไม่เป็นสมาชิก) โดยการอนุมัติสถานะสมาชิกตัวจริงจะทำใน M3'
            ]
        ],
        'm3' => [
            'id' => 'm3', 'num' => 'Module 3', 'title' => 'Admin Panel Core & Course/Member Management',
            'desc' => 'ระบบบริหารจัดการหลังบ้าน, สิทธิ์ตาม Role (RBAC) และจัดการหลักสูตร/สมาชิก',
            'status' => 'processing', 'status_label' => 'ดำเนินการบางส่วน (Processing)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; Layout หลังบ้าน และสิทธิ์ตาม Role (RBAC)<br>' .
                '&bull; จัดการหมวดหมู่, หลักสูตร, รุ่น, ราคา, เอกสาร และอัปโหลดโบรชัวร์ (ตรวจชนิดและขนาดไฟล์)<br>' .
                '&bull; จัดการผู้ใช้และอนุมัติสถานะสมาชิก',
                '<strong>ตารางหลัก:</strong> <code>permissions</code>, <code>role_permissions</code>, <code>admin_activity_logs</code>',
                '<strong>ข้อมูลทดสอบ:</strong> ใช้ตารางจาก M1/M2 และนับจาก Module นี้ไป ข้อมูลหลักสูตรจริงจะมาจากหลังบ้าน (ลบ Seed ของ M1 ได้)'
            ]
        ],
        'm4' => [
            'id' => 'm4', 'num' => 'Module 4', 'title' => 'Pricing Engine & Cart',
            'desc' => 'ระบบตะกร้าสินค้า Multi-Course & Multi-Attendee คำนวณราคาอัตโนมัติ',
            'status' => 'pending', 'status_label' => 'กำลังดำเนินการ (Processing)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; <code>PriceCalculator</code> Service ที่คำนวณราคาของผู้เข้าอบรมแต่ละคน จากสถานะสมาชิกและช่วงเวลาชำระ (เกณฑ์ 20 วันตั้งค่าได้ในแต่ละหลักสูตร)<br>' .
                '&bull; 1 ตะกร้ามีได้หลายหลักสูตร/หลายรุ่น และแต่ละรายการระบุผู้เข้าอบรมได้หลายคน โดยเลือกจากบัญชีที่มีอยู่แล้ว หรือกรอกคนใหม่ด้วยอีเมลและชื่อ<br>' .
                '&bull; ตรวจชื่อซ้ำในรุ่นเดียวกัน และตรวจที่นั่งคงเหลือ',
                '<strong>ตารางหลัก:</strong> <code>carts</code>, <code>cart_items</code>, <code>cart_attendees</code>',
                '<strong>ข้อมูลทดสอบ:</strong> หลักสูตร/ราคามาจาก M1/M3 ส่วนผู้ซื้อและผู้เข้าอบรมมาจาก M2'
            ]
        ],
        'm5' => [
            'id' => 'm5', 'num' => 'Module 5', 'title' => 'Checkout, Orders & Attendee Provisioning',
            'desc' => 'แปลงตะกร้าเป็นคำสั่งซื้อ, ออกเลขคำสั่งซื้อ และสร้างสิทธิ์ผู้เข้าอบรม',
            'status' => 'pending', 'status_label' => 'รอพัฒนา (Pending)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; แปลงตะกร้าเป็นคำสั่งซื้อ พร้อมออกเลขคำสั่งซื้อ<br>' .
                '&bull; Snapshot ราคา ณ เวลาสั่งซื้อ เพื่อไม่ให้ราคาเปลี่ยนตามการแก้ไขในหลังบ้าน<br>' .
                '&bull; กรอกข้อมูลออกเอกสาร (บุคคล/นิติบุคคล, เลขผู้เสียภาษี)<br>' .
                '&bull; สร้างบัญชี learner ให้ผู้เข้าอบรมคนใหม่อัตโนมัติ<br>' .
                '&bull; สร้าง Enrollment ให้ผู้เข้าอบรม 1 คนต่อ 1 รุ่น<br>' .
                '&bull; หน้า "คำสั่งซื้อของฉัน"',
                '<strong>ตารางหลัก:</strong> <code>orders</code>, <code>order_items</code>, <code>order_attendees</code>, <code>enrollments</code>, <code>billing_profiles</code>',
                '<strong>ข้อมูลทดสอบ:</strong> ตะกร้าจาก M4 และการส่งอีเมลรหัสผ่านจะทำจริงใน M12 (ระหว่างนี้แสดงบนหน้าจอหรือเขียนลง Log แทน)'
            ]
        ],
        'm6' => [
            'id' => 'm6', 'num' => 'Module 6', 'title' => 'Quotation & Invoice PDF',
            'desc' => 'ระบบออกเอกสารใบเสนอราคา, ใบแจ้งหนี้ และใบเสร็จรับเงินในรูปแบบ PDF',
            'status' => 'pending', 'status_label' => 'รอพัฒนา (Pending)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; ออกใบเสนอราคาได้ตั้งแต่ขั้นตะกร้าหรือคำสั่งซื้อ และออกใบแจ้งหนี้จากคำสั่งซื้อ<br>' .
                '&bull; เลขที่เอกสารรันอัตโนมัติรายปี (QT-2026-0001 / INV-2026-0001)<br>' .
                '&bull; จำนวนเงินเป็นตัวอักษรภาษาไทย, VAT และหัก ณ ที่จ่าย (ตั้งค่าได้), วันครบกำหนดชำระ<br>' .
                '&bull; Preview, ดาวน์โหลด และพิมพ์ PDF ด้วย TCPDF ฟอนต์ไทย',
                '<strong>ตารางหลัก:</strong> <code>billing_documents</code>, <code>billing_document_items</code>, <code>document_sequences</code>',
                '<strong>ข้อมูลทดสอบ:</strong> คำสั่งซื้อจาก M5 ส่วนข้อมูลบริษัทผู้ออกเอกสารใช้ Seed ใน <code>settings</code> (ข้อมูลจริงจาก M14)'
            ]
        ],
        'm7' => [
            'id' => 'm7', 'num' => 'Module 7', 'title' => 'Payment & Approval Workflow',
            'desc' => 'ระบบแจ้งชำระเงินแนบสลิป และขั้นตอนการตรวจสอบอนุมัติการเงิน',
            'status' => 'pending', 'status_label' => 'รอพัฒนา (Pending)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; แจ้งชำระเงินพร้อมอัปโหลดสลิป<br>' .
                '&bull; Admin ตรวจสอบ อนุมัติ หรือปฏิเสธ ทีละรายการหรือหลายรายการพร้อมกัน<br>' .
                '&bull; State Machine ของ Enrollment: รอชำระเงิน / รออนุมัติ (กรณีหลักสูตรฟรี) / อนุมัติ / ยกเลิก / เรียนจบ<br>' .
                '&bull; ตรวจว่าวันที่ชำระจริงเลยเกณฑ์ 20 วันหรือไม่ ถ้าเลยให้แจ้งเตือนส่วนต่าง<br>' .
                '&bull; บันทึกประวัติการเปลี่ยนสถานะทุกครั้ง<br>' .
                '&bull; สิทธิ์ในการเข้าเรียน, ทำข้อสอบ และออกใบเซอร์ ผูกกับสถานะที่ได้จาก Module นี้',
                '<strong>ตารางหลัก:</strong> <code>payments</code>, <code>payment_slips</code>, <code>enrollment_status_logs</code>',
                '<strong>ข้อมูลทดสอบ:</strong> คำสั่งซื้อจาก M5 และใบแจ้งหนี้จาก M6'
            ]
        ],
        'm8' => [
            'id' => 'm8', 'num' => 'Module 8', 'title' => 'Learning Center (LMS เข้าเรียน)',
            'desc' => 'ศูนย์การเรียนรู้ออนไลน์ บทเรียนวิดีโอ PDF และลิงก์ Live',
            'status' => 'pending', 'status_label' => 'รอพัฒนา (Pending)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; Dashboard "หลักสูตรของฉัน" สำหรับผู้เรียน<br>' .
                '&bull; บทเรียนแบบวิดีโอ (YouTube/Vimeo/MP4), PDF และลิงก์ Live (Zoom/Teams)<br>' .
                '&bull; ติดตามความคืบหน้าในการเรียน<br>' .
                '&bull; เข้าเรียนได้เฉพาะ Enrollment ที่ได้รับอนุมัติแล้ว<br>' .
                '&bull; Admin จัดการบทเรียน และเชื่อม Widget "Clip" ในหน้าแรก',
                '<strong>ตารางหลัก:</strong> <code>lessons</code>, <code>lesson_materials</code>, <code>lesson_progress</code>',
                '<strong>ข้อมูลทดสอบ:</strong> Enrollment ที่อนุมัติแล้วจาก M7'
            ]
        ],
        'm9' => [
            'id' => 'm9', 'num' => 'Module 9', 'title' => 'Exam Module (Pre-test / Post-test)',
            'desc' => 'ระบบแบบทดสอบก่อนและหลังเรียน พร้อมสุ่มข้อสอบและตรวจผลอัตโนมัติ',
            'status' => 'pending', 'status_label' => 'รอพัฒนา (Pending)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; คลังข้อสอบแบบปรนัยและถูก/ผิด<br>' .
                '&bull; ชุดข้อสอบแยก Pre-test และ Post-test ตั้งค่าได้ว่าจะใช้ชุดเดียวกันหรือคนละชุด<br>' .
                '&bull; สุ่มลำดับข้อและลำดับตัวเลือก, สุ่มจำนวนข้อจากคลัง<br>' .
                '&bull; จับเวลา, กำหนดจำนวนครั้งที่สอบได้, เกณฑ์ผ่าน (%)<br>' .
                '&bull; กำหนดเงื่อนไข เช่น ต้องเรียนครบก่อนจึงจะทำ Post-test ได้<br>' .
                '&bull; เปรียบเทียบคะแนนก่อนเรียนและหลังเรียน',
                '<strong>ตารางหลัก:</strong> <code>question_banks</code>, <code>questions</code>, <code>question_choices</code>, <code>exams</code>, <code>exam_attempts</code>, <code>attempt_answers</code>',
                '<strong>ข้อมูลทดสอบ:</strong> Enrollment จาก M7 และความคืบหน้าการเรียนจาก M8'
            ]
        ],
        'm10' => [
            'id' => 'm10', 'num' => 'Module 10', 'title' => 'Evaluation & Survey',
            'desc' => 'ระบบประเมินความพึงพอใจหลักสูตรและวิทยากร',
            'status' => 'pending', 'status_label' => 'รอพัฒนา (Pending)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; สร้างแบบประเมินแบบ Likert 1–5 และคำถามปลายเปิด ผูกได้รายหลักสูตรหรือรายรุ่น<br>' .
                '&bull; บังคับให้ทำแบบประเมินก่อนปลดล็อกใบเซอร์<br>' .
                '&bull; สรุปผลคะแนนเฉลี่ยและความคิดเห็นรายรุ่น',
                '<strong>ตารางหลัก:</strong> <code>surveys</code>, <code>survey_questions</code>, <code>survey_responses</code>, <code>survey_answers</code>',
                '<strong>ข้อมูลทดสอบ:</strong> ผู้เรียนที่ผ่าน Post-test แล้วจาก M9'
            ]
        ],
        'm11' => [
            'id' => 'm11', 'num' => 'Module 11', 'title' => 'Certificate Designer & Generator',
            'desc' => 'ระบบออกแบบและออกใบรับรองอิเล็กทรอนิกส์ PDF พร้อม QR Code ตรวจสอบ',
            'status' => 'pending', 'status_label' => 'รอพัฒนา (Pending)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; Admin อัปโหลดภาพพื้นหลังใบเซอร์<br>' .
                '&bull; ลากวางตำแหน่ง Field ด้วย JavaScript เช่น ชื่อ-นามสกุล, ชื่อหลักสูตร, วันที่อบรม, เลขที่ใบเซอร์, QR Code และกำหนดฟอนต์/ขนาด/สี/การจัดแนว<br>' .
                '&bull; สร้าง PDF รายบุคคลด้วย TCPDF โดยใช้ชื่อของแต่ละคน<br>' .
                '&bull; หน้าสาธารณะสำหรับตรวจสอบใบเซอร์ (<code>/verify/{code}</code>)<br>' .
                '&bull; เงื่อนไขปลดล็อก: อนุมัติแล้ว + ผ่าน Post-test (ถ้ากำหนดไว้) + ทำแบบประเมินแล้ว + Admin อนุมัติออกใบเซอร์ (ถ้ากำหนดไว้)',
                '<strong>ตารางหลัก:</strong> <code>certificate_templates</code>, <code>certificate_fields</code>, <code>certificates</code>',
                '<strong>ข้อมูลทดสอบ:</strong> ผลสอบจาก M9, ผลประเมินจาก M10 และชื่อสำหรับใบเซอร์จาก M2'
            ]
        ],
        'm12' => [
            'id' => 'm12', 'num' => 'Module 12', 'title' => 'Notification & Email',
            'desc' => 'ระบบแจ้งเตือนผ่านอีเมล (SMTP Queue & Cron) และกล่องข้อความในระบบ',
            'status' => 'pending', 'status_label' => 'รอพัฒนา (Pending)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; ส่งอีเมลด้วย PHPMailer ผ่าน Queue และ Cron<br>' .
                '&bull; Admin แก้ไข Template อีเมลได้<br>' .
                '&bull; เหตุการณ์ที่แจ้งเตือน: สมัครสมาชิก, ส่งรหัสผ่านผู้เข้าอบรม, คำสั่งซื้อ/ใบแจ้งหนี้, ผลอนุมัติการชำระเงิน, เตือนก่อนวันอบรม, ใบเซอร์พร้อมดาวน์โหลด<br>' .
                '&bull; กล่องแจ้งเตือนในเว็บ',
                '<strong>ตารางหลัก:</strong> <code>email_templates</code>, <code>email_queue</code>, <code>notifications</code>',
                '<strong>ข้อมูลทดสอบ:</strong> Event ที่เกิดขึ้นจริงใน M2, M5, M6, M7, M11'
            ]
        ],
        'm13' => [
            'id' => 'm13', 'num' => 'Module 13', 'title' => 'Dashboard & Reports',
            'desc' => 'ระบบรายงานผู้บริหาร กราฟวิเคราะห์ และรายงานรายรุ่น',
            'status' => 'pending', 'status_label' => 'รอพัฒนา (Pending)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; กราฟด้วย Chart.js: รายได้, จำนวนผู้สมัครรายหลักสูตร, อัตราการสอบผ่าน, คะแนนความพึงพอใจ, ยอดค้างชำระ<br>' .
                '&bull; Export CSV/Excel<br>' .
                '&bull; รายชื่อผู้เข้าอบรมรายรุ่น (ใช้เช็กชื่อได้)',
                '<strong>ตารางหลัก:</strong> ไม่มีตารางใหม่ ใช้ SQL View และ Query จาก Module ก่อนหน้า',
                '<strong>ข้อมูลทดสอบ:</strong> ข้อมูลจริงที่สะสมจาก M5–M11 (มี Seed สำหรับทำ Demo กราฟให้ด้วย)'
            ]
        ],
        'm14' => [
            'id' => 'm14', 'num' => 'Module 14', 'title' => 'CMS, Settings & Production Hardening',
            'desc' => 'ระบบจัดการเนื้อหาเว็บไซต์, สไลด์แบนเนอร์, เมนู และความปลอดภัยขั้นสูง',
            'status' => 'pending', 'status_label' => 'รอพัฒนา (Pending)',
            'features' => [
                '<strong>ทำอะไรได้:</strong><br>' .
                '&bull; จัดการสไลด์หน้าแรก, คลิป, ข่าว/ประกาศ และเมนู<br>' .
                '&bull; ตั้งค่าเว็บ: โลโก้, สีธีม, ข้อมูลบริษัทสำหรับใบเสนอราคา/ใบแจ้งหนี้<br>' .
                '&bull; Security Hardening: CSP, Rate Limit, Audit Log<br>' .
                '&bull; Backup และคู่มือ Deploy',
                '<strong>ตารางหลัก:</strong> <code>slides</code>, <code>video_clips</code>, <code>news</code>, <code>menus</code>',
                '<strong>ข้อมูลทดสอบ:</strong> ข้อมูลจากหลังบ้าน Module นี้จะเข้าไปแทนที่ Placeholder และ Seed ของ M0 และ M6'
            ]
        ],
    ];

    private array $seedSources = [
        ['module' => 'M0 Core & Theme', 'dep' => '-', 'seed' => 'settings, slide/clip placeholder', 'real' => 'M14'],
        ['module' => 'M1 Catalog', 'dep' => 'M0', 'seed' => 'หลักสูตร/รุ่น/ราคา/เอกสาร', 'real' => 'M3'],
        ['module' => 'M2 Auth & Profile', 'dep' => 'M0', 'seed' => 'admin + ผู้ใช้ทดสอบ', 'real' => 'M2 (สมัครจริง), M3 (อนุมัติสมาชิก)'],
        ['module' => 'M3 Admin & Course Mgmt', 'dep' => 'M1, M2', 'seed' => '-', 'real' => 'M3'],
        ['module' => 'M4 Pricing & Cart', 'dep' => 'M1–M3', 'seed' => '-', 'real' => 'M1/M3, M2'],
        ['module' => 'M5 Checkout & Orders', 'dep' => 'M4', 'seed' => '-', 'real' => 'M4'],
        ['module' => 'M6 Quotation/Invoice', 'dep' => 'M5', 'seed' => 'ข้อมูลบริษัท', 'real' => 'M14'],
        ['module' => 'M7 Payment & Approval', 'dep' => 'M5, M6', 'seed' => '-', 'real' => 'M5, M6'],
        ['module' => 'M8 LMS', 'dep' => 'M7', 'seed' => '-', 'real' => 'M7'],
        ['module' => 'M9 Exam', 'dep' => 'M7, M8', 'seed' => '-', 'real' => 'M7, M8'],
        ['module' => 'M10 Survey', 'dep' => 'M9', 'seed' => '-', 'real' => 'M9'],
        ['module' => 'M11 Certificate', 'dep' => 'M2, M9, M10', 'seed' => '-', 'real' => 'M9, M10'],
        ['module' => 'M12 Notification', 'dep' => 'M2, M5–M7, M11', 'seed' => '-', 'real' => 'Event จริง'],
        ['module' => 'M13 Dashboard', 'dep' => 'M5–M11', 'seed' => 'ข้อมูล Demo กราฟ', 'real' => 'M5–M11'],
        ['module' => 'M14 CMS & Hardening', 'dep' => 'ทั้งหมด', 'seed' => '-', 'real' => 'M14'],
    ];

    public function index(): string
    {
        return $this->view('Roadmap::index', [
            'title' => 'แผนงานและสถานะการพัฒนา (Roadmap 14 Modules)',
            'subtitle' => 'สรุปภาพรวมโครงสร้าง 14 โมดูล และตารางสรุปแหล่งที่มาของข้อมูลทดสอบ',
            'modules' => $this->modules,
            'seedSources' => $this->seedSources,
            'breadcrumbs' => ['หน้าแรก' => '/', 'Roadmap' => '']
        ]);
    }

    public function detail(string $id): string
    {
        $mod = $this->modules[$id] ?? null;
        if (! $mod) {
            abort(404, 'ไม่พบข้อมูลโมดูลที่คุณต้องการ');
        }

        return $this->view('Roadmap::detail', [
            'title' => $mod['num'] . ' : ' . $mod['title'],
            'subtitle' => $mod['desc'],
            'mod' => $mod,
            'breadcrumbs' => ['หน้าแรก' => '/', 'Roadmap' => '/roadmap', $mod['num'] => '']
        ]);
    }
}