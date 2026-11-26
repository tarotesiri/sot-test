#
# Structure for table "sot_companies"
#

CREATE TABLE `sot_companies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tax_type` enum('personal','corporate') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'corporate',
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อบริษัท/องค์กร',
  `branch` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'สำนักงานใหญ่',
  `tax_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'เลขประจำตัวผู้เสียภาษี',
  `address` text COLLATE utf8mb4_unicode_ci COMMENT 'ที่อยู่',
  `province` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amphoe` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zipcode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'เบอร์โทรศัพท์บริษัท',
  `billing_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'อีเมลสำหรับออกบิล',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางข้อมูลบริษัทและองค์กร';

#
# Structure for table "sot_course_categories"
#

CREATE TABLE `sot_course_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','trashed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `trashed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_courses"
#

CREATE TABLE `sot_courses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned DEFAULT NULL,
  `title` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('live_online','clip_vdo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'live_online',
  `summary` text COLLATE utf8mb4_unicode_ci,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brochure_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','open','closed','cancelled','trashed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `price_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'multi' COMMENT 'รูปแบบราคา: free, single, multi',
  `single_price` decimal(10,2) DEFAULT '0.00' COMMENT 'ราคาเดียวหรือราคาตั้งต้น',
  `trashed_at` datetime DEFAULT NULL,
  `trashed_prev_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trashed_by` int unsigned DEFAULT NULL,
  `category_ids` text COLLATE utf8mb4_unicode_ci,
  `published_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `code` (`code`),
  KEY `fk_course_category` (`category_id`),
  CONSTRAINT `fk_course_category` FOREIGN KEY (`category_id`) REFERENCES `sot_course_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_course_sessions"
#

CREATE TABLE `sot_course_sessions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int unsigned NOT NULL,
  `session_no` int unsigned NOT NULL DEFAULT '1',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `date_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_date` date NOT NULL,
  `time_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int unsigned NOT NULL DEFAULT '0',
  `status` enum('open','full','closed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `capacity_member` int unsigned DEFAULT '0' COMMENT 'จำนวนที่รับสำหรับสมาชิก',
  `capacity_standard` int unsigned DEFAULT '0' COMMENT 'จำนวนที่รับสำหรับบุคคลทั่วไป',
  PRIMARY KEY (`id`),
  KEY `fk_session_course` (`course_id`),
  CONSTRAINT `fk_session_course` FOREIGN KEY (`course_id`) REFERENCES `sot_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_course_documents"
#

CREATE TABLE `sot_course_documents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_doc_course` (`course_id`),
  CONSTRAINT `fk_doc_course` FOREIGN KEY (`course_id`) REFERENCES `sot_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_course_category_maps"
#

CREATE TABLE `sot_course_category_maps` (
  `course_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  PRIMARY KEY (`course_id`,`category_id`),
  KEY `fk_ccm_cat` (`category_id`),
  CONSTRAINT `fk_ccm_cat` FOREIGN KEY (`category_id`) REFERENCES `sot_course_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ccm_course` FOREIGN KEY (`course_id`) REFERENCES `sot_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_course_batches"
#

CREATE TABLE `sot_course_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int unsigned NOT NULL,
  `batch_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'รุ่นที่ 1',
  `date_start` date DEFAULT NULL COMMENT 'วันที่เริ่มอบรมวันแรก',
  `date_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_text` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price_type` enum('single','member_split') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single',
  `payment_type` enum('single','days_before') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single',
  `status` enum('draft','open','full','closed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `topics_json` longtext COLLATE utf8mb4_unicode_ci,
  `evaluation_id` int unsigned DEFAULT NULL,
  `survey_id` int unsigned DEFAULT NULL,
  `cert_template` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cert_settings` text COLLATE utf8mb4_unicode_ci,
  `time_text` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brochure_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gallery_images` text COLLATE utf8mb4_unicode_ci,
  `document_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_1` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_2` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_3` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_4` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status_1` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `status_2` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `status_3` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `status_4` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `limit_enabled_1` tinyint(1) NOT NULL DEFAULT '0',
  `limit_enabled_2` tinyint(1) NOT NULL DEFAULT '0',
  `limit_enabled_3` tinyint(1) NOT NULL DEFAULT '0',
  `limit_enabled_4` tinyint(1) NOT NULL DEFAULT '0',
  `capacity_1` int NOT NULL DEFAULT '0',
  `capacity_2` int NOT NULL DEFAULT '0',
  `capacity_3` int NOT NULL DEFAULT '0',
  `capacity_4` int NOT NULL DEFAULT '0',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `published_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_course_batches` (`course_id`),
  CONSTRAINT `fk_batches_course` FOREIGN KEY (`course_id`) REFERENCES `sot_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_doc_sequences"
#

CREATE TABLE `sot_doc_sequences` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `doc_type` enum('order','invoice','quotation') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'order',
  `prefix` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'เช่น # หรือ IVE-',
  `year_yy` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ปี ค.ศ. หรือ พ.ศ. 2 หลัก เช่น 26',
  `last_number` int unsigned NOT NULL DEFAULT '0' COMMENT 'เลขรันล่าสุดของปีนั้น',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_doc_type_year` (`doc_type`,`year_yy`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตัวรันเลขที่คำสั่งซื้อและใบแจ้งหนี้รายปี';

#
# Structure for table "sot_enrollments"
#

CREATE TABLE `sot_enrollments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `course_id` int unsigned NOT NULL,
  `batch_id` int unsigned NOT NULL,
  `participant_id` int unsigned NOT NULL,
  `learner_user_id` int unsigned DEFAULT NULL COMMENT 'บัญชีผู้เรียน sot_users',
  `payer_user_id` int unsigned DEFAULT NULL COMMENT 'ผู้ประสานงานที่สมัครให้',
  `payment_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, paid',
  `exam_score` decimal(5,2) DEFAULT '0.00' COMMENT 'คะแนนสอบ',
  `certificate_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'รหัสใบเซอร์',
  `attendance_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'enrolled' COMMENT 'enrolled, passed, failed',
  `participation_status` enum('pending','joined','not_joined') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'เข้าร่วม / ไม่เข้าร่วม (ใช้งานเมื่อคำสั่งซื้อเป็น paid=อนุมัติ)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_enrollments_order_id` (`order_id`),
  KEY `idx_enrollments_learner_user` (`learner_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางลงทะเบียนอบรม';

#
# Structure for table "sot_order_payments"
#

CREATE TABLE `sot_order_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payer_user_id` bigint unsigned DEFAULT NULL,
  `payer_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payer_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_no_last4` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `transfer_date` date DEFAULT NULL,
  `transfer_time` time DEFAULT NULL,
  `transfer_datetime_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slip_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slip_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slip_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slip_size` bigint unsigned DEFAULT '0',
  `note` text COLLATE utf8mb4_unicode_ci,
  `review_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reject_reason` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_payments_order_id` (`order_id`),
  KEY `idx_order_payments_review_status` (`review_status`),
  KEY `idx_order_payments_created_at` (`created_at`),
  KEY `idx_order_payments_reference_no` (`reference_no`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_order_status_logs"
#

CREATE TABLE `sot_order_status_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `enrollment_id` bigint unsigned DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `action_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'status_change',
  `from_status` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` longtext COLLATE utf8mb4_unicode_ci,
  `new_value` longtext COLLATE utf8mb4_unicode_ci,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_role` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_status_logs_order_id` (`order_id`),
  KEY `idx_order_status_logs_enrollment_id` (`enrollment_id`),
  KEY `idx_order_status_logs_action_type` (`action_type`),
  KEY `idx_order_status_logs_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_orders"
#

CREATE TABLE `sot_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'รูปแบบ IVE-YYAuto4',
  `quotation_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'รูปแบบ QUO-YYAuto4 (เลขเดียวกับ IVE-)',
  `user_id` bigint unsigned DEFAULT NULL,
  `company_id` int unsigned DEFAULT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_attendee_direct_email` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = ส่งเมลให้ผู้เข้าอบรมโดยตรงเมื่ออนุมัติ',
  `total_subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_vat` decimal(10,2) NOT NULL DEFAULT '0.00',
  `wht_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_wht` decimal(10,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `special_requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payment_status` enum('pending','awaiting_verification','pending_approval','paid','cancelled','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending=รอการชำระ, awaiting_verification=กำลังตรวจสอบ, pending_approval=รออนุมัติ, paid=อนุมัติ, cancelled=ยกเลิก, refunded=คืนเงิน',
  `slip_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_orders_order_no` (`order_no`),
  UNIQUE KEY `uk_orders_invoice_no` (`invoice_no`),
  UNIQUE KEY `uk_orders_quotation_no` (`quotation_no`),
  KEY `idx_orders_user_id` (`user_id`),
  KEY `idx_orders_payment_status` (`payment_status`),
  KEY `idx_orders_company_id` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_order_items"
#

CREATE TABLE `sot_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned NOT NULL,
  `rate_tier` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `batch_approval_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `batch_approved_at` datetime DEFAULT NULL,
  `batch_approved_by` int DEFAULT NULL,
  `batch_mail_sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order_id` (`order_id`),
  KEY `idx_order_items_batch_id` (`batch_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `sot_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_order_attendees"
#

CREATE TABLE `sot_order_attendees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint unsigned NOT NULL,
  `participant_id` int unsigned DEFAULT NULL COMMENT 'อ้างอิงผู้เข้าอบรมหลักใน sot_participants',
  `user_id` bigint unsigned DEFAULT NULL,
  `prefix` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ว่างได้กรณีไม่มีอีเมล (ใช้ pin_code ใน sot_participants)',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'ความต้องการอื่นๆ รายบุคคล',
  `is_self_enrollment` tinyint(1) NOT NULL DEFAULT '0',
  `attendance_status` enum('pending','joined','not_joined') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending=รอเข้าร่วม, joined=เข้าร่วม, not_joined=ไม่เข้าร่วม',
  `certificate_status` enum('pending','issued') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attendees_item_id` (`order_item_id`),
  KEY `idx_attendees_email` (`email`),
  KEY `idx_attendees_participant_id` (`participant_id`),
  CONSTRAINT `fk_attendees_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `sot_order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_order_invoices"
#

CREATE TABLE `sot_order_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `tax_type` enum('personal','corporate') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'personal',
  `tax_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `po_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `province` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amphoe` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zipcode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoices_order_id` (`order_id`),
  CONSTRAINT `fk_invoices_order` FOREIGN KEY (`order_id`) REFERENCES `sot_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_participants"
#

CREATE TABLE `sot_participants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int unsigned DEFAULT NULL COMMENT 'รหัสบริษัท (ถ้ามี)',
  `user_id` int unsigned DEFAULT NULL COMMENT 'รหัสผู้ประสานงาน sot_users (ถ้ามี)',
  `learner_user_id` int unsigned DEFAULT NULL COMMENT 'บัญชีผู้เรียน sot_users ที่ผูกแล้ว',
  `prefix_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'คำนำหน้า',
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อจริงผู้เข้าอบรม',
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'นามสกุลผู้เข้าอบรม',
  `id_card` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'เลขบัตรประชาชน',
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'อีเมล (รองรับกรณีไม่มี)',
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'เบอร์โทร',
  `position` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ตำแหน่ง',
  `department` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'แผนก',
  `pin_code` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'PIN 7 หลักสำหรับคนไม่มีอีเมล',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pin_code` (`pin_code`),
  KEY `idx_participants_learner_user` (`learner_user_id`),
  KEY `idx_participants_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางผู้เข้าอบรมจริง';

#
# Structure for table "sot_settings"
#

CREATE TABLE `sot_settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `value_type` enum('string','int','float','bool','json') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `setting_group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `label` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`),
  KEY `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Structure for table "sot_users"
#

CREATE TABLE `sot_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `prefix` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'คำนำหน้า',
  `firstname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อผู้เข้ารับอบรม',
  `lastname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'นามสกุลผู้เข้ารับอบรม',
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'อีเมลสำหรับเข้าสู่ระบบ (เว้นว่างได้หากเข้าผ่าน PIN)',
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `id_card` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'เลขบัตรประชาชน 13 หลัก',
  `position` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ตำแหน่งงาน',
  `other_requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'ความต้องการอื่นๆ',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'รหัสผ่านที่เข้ารหัส Hash แล้ว',
  `password_locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = เจ้าตัวตั้งรหัสผ่านเองหรือมีบัญชีอยู่แล้ว ห้ามระบบสุ่มทับ',
  `created_by_order` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = สร้างอัตโนมัติจากคำสั่งซื้อ',
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'user' COMMENT 'ระดับผู้ใช้งาน เช่น user, admin',
  `reset_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Token สำหรับรีเซ็ตรหัสผ่าน',
  `reset_expires` datetime DEFAULT NULL COMMENT 'วันเวลาหมดอายุของ Token รีเซ็ตรหัสผ่าน',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้างบัญชี',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่อัปเดตข้อมูลล่าสุด',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บข้อมูลสมาชิกเว็บไซต์';

#
# Structure for table "sot_company_users"
#

CREATE TABLE `sot_company_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_user` (`company_id`,`user_id`),
  KEY `idx_company_users_user_id` (`user_id`),
  CONSTRAINT `fk_company_users_company` FOREIGN KEY (`company_id`) REFERENCES `sot_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_company_users_user` FOREIGN KEY (`user_id`) REFERENCES `sot_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเชื่อมผู้ประสานงานกับบริษัท';
