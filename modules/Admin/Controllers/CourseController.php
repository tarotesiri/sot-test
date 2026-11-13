<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : CourseController.php
 * Description : Course Controller
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.23.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Modules\Admin\Models\CategoryAdminModel;
use Modules\Admin\Models\CourseAdminModel;
use Modules\Admin\Models\BatchAdminModel;
use Modules\Admin\Models\SettingAdminModel;
use Modules\Admin\Support\AdminHelper as H;
use Modules\Admin\Support\Uploader;
use RuntimeException;

final class CourseController extends AdminBaseController
{
    private const PER_PAGE = 20;

    private function admCourses(): CourseAdminModel { return new CourseAdminModel($this->db()); }
    private function admCategories(): CategoryAdminModel { return new CategoryAdminModel($this->db()); }
    private function admBatches(): BatchAdminModel { return new BatchAdminModel($this->db()); }
    private function admSettings(): SettingAdminModel { return new SettingAdminModel($this->db()); }

    private function admEditable(string $id): array
    {
        $course = ctype_digit($id) ? $this->admCourses()->find((int) $id) : null;
        if ($course === null || ($course['status'] ?? '') === 'trashed') {
            H::flash('error', 'ไม่พบหลักสูตร');
            H::redirect('admin/courses');
        }
        return $course;
    }

    public function index(): string
    {
        $this->admGuard();
        $filters = ['q' => H::query('q'), 'status' => H::query('status')];
        $m = $this->admCourses();
        return $this->admPage('courses/index', [
            'result' => $m->paginate($filters, max(1, H::inInt('page', 1)), self::PER_PAGE),
            'filters' => $filters,
            'counts' => $m->statusCounts(),
        ], 'จัดการหลักสูตร', 'courses');
    }

    public function create(): string
    {
        $this->admGuard();
        $form = H::pullForm();
        return $this->admPage('courses/form', [
            'course' => [
                'id' => 0, 
                'title' => '', 
                'slug' => '', 
                'type' => 'live_online', 
                'summary' => '', 
                'status' => 'draft', 
                'cover_image' => '',
                'category_ids' => '[]',
                'published_at' => date('Y-m-d H:i:s')
            ],
            'isNew' => true, 'old' => $form['old'], 'errors' => $form['errors'], 'categories' => $this->admCategories()->options(),
        ], 'เพิ่มหลักสูตร', 'course_new');
    }

    public function store(): void
    {
        $this->admGuard();
        [$data, $errors] = $this->admCollect(0, null);
        if ($errors !== []) {
            H::keepForm(H::post(), $errors);
            H::flash('error', 'กรุณาตรวจสอบข้อมูล');
            H::redirect('admin/courses/create');
        }
        $id = $this->admCourses()->create($data, []);
        H::flash('success', 'บันทึกสำเร็จ: เพิ่มหลักสูตรใหม่เรียบร้อยแล้ว');
        H::redirect('admin/courses/' . $id . '/edit');
    }

    public function edit(string $id): string
    {
        $this->admGuard();
        $course = $this->admEditable($id);
        $form = H::pullForm();
        return $this->admPage('courses/form', [
            'course' => $course, 'isNew' => false, 'old' => $form['old'], 'errors' => $form['errors'],
            'categories' => $this->admCategories()->options(), 'batches' => $this->admBatches()->items((int)$course['id']),
        ], 'แก้ไขหลักสูตร', 'courses');
    }

    public function update(string $id): void
    {
        $this->admGuard();
        $course = $this->admEditable($id);
        $cid = (int) $course['id'];
        [$data, $errors] = $this->admCollect($cid, $course);
        if ($errors !== []) {
            H::keepForm(H::post(), $errors);
            H::flash('error', 'กรุณาตรวจสอบข้อมูล');
            H::redirect('admin/courses/' . $cid . '/edit');
        }
        $this->admCourses()->update($cid, $data, []);
        H::flash('success', 'บันทึกสำเร็จ: อัปเดตข้อมูลหลักสูตรเรียบร้อยแล้ว');
        H::redirect('admin/courses/' . $cid . '/edit');
    }

    public function trash(string $id): void
    {
        $this->admGuard();
        if (ctype_digit($id)) { $this->admCourses()->trash((int)$id); }
        H::flash('success', 'ย้ายไปถังขยะแล้ว');
        H::redirect('admin/courses');
    }

    public function restore(string $id): void
    {
        $this->admGuard();
        if (ctype_digit($id)) { $this->admCourses()->restore((int)$id); }
        H::flash('success', 'กู้คืนแล้ว');
        H::redirect('admin/trash');
    }

    public function destroy(string $id): void
    {
        $this->admGuard();
        if (H::in('confirm') === '1' && ctype_digit($id)) { $this->admCourses()->forceDelete((int)$id); }
        H::flash('success', 'ลบถาวรแล้ว');
        H::redirect('admin/trash');
    }

    /* ======================== จัดการรุ่น (Batches v.5.23) ======================== */
    public function createBatch(string $id): string
    {
        $this->admGuard();
        $course = $this->admEditable($id);
        $batch = $this->defaultBatch();
        $settings = $this->admSettings();
        
        $defaultPaymentDays = (int) $settings->get('default_payment_days', '20');
        $certSet = [
            'name_x' => 100, 'name_y' => 200, 'date_x' => 100, 'date_y' => 300,
        ];
        $batch['cert_settings'] = json_encode($certSet);
        $batch['published_at'] = date('Y-m-d H:i:s');

        return $this->admPage('courses/batch_form', [
            'course' => $course, 'batch' => $batch, 'isNew' => true,
            'defaultPaymentDays' => $defaultPaymentDays,
        ], 'เพิ่มรุ่นอบรม', 'courses');
    }

    public function editBatch(string $id, string $bid): string
    {
        $this->admGuard();
        $course = $this->admEditable($id);
        $batch = ctype_digit($bid) ? $this->admBatches()->find((int)$bid, (int)$course['id']) : null;
        if (!$batch) { H::flash('error', 'ไม่พบรุ่นอบรม'); H::redirect('admin/courses/' . $course['id'] . '/edit'); }
        
        $settings = $this->admSettings();
        $defaultPaymentDays = (int) $settings->get('default_payment_days', '20');

        return $this->admPage('courses/batch_form', [
            'course' => $course, 'batch' => $batch, 'isNew' => false,
            'defaultPaymentDays' => $defaultPaymentDays,
        ], 'แก้ไขรุ่นอบรม', 'courses');
    }

    public function storeBatch(string $id): void
    {
        $this->admGuard();
        $cid = (int) $this->admEditable($id)['id'];
        [$data, $errors] = $this->admCollectBatch(null);
        if ($errors !== []) {
            foreach ($errors as $e) { H::flash('error', $e); }
            H::redirect('admin/courses/' . $cid . '/batches/create');
        }
        $data['course_id'] = $cid;
        $this->admBatches()->create($data);
        H::flash('success', 'บันทึกสำเร็จ: เพิ่มรุ่นอบรมเรียบร้อยแล้ว');
        H::redirect('admin/courses/' . $cid . '/edit');
    }

    public function updateBatch(string $id, string $bid): void
    {
        $this->admGuard();
        $cid = (int) $this->admEditable($id)['id'];
        $bId = ctype_digit($bid) ? (int)$bid : 0;
        $oldBatch = $this->admBatches()->find($bId, $cid);
        if (!$oldBatch) { H::flash('error', 'ไม่พบรุ่นอบรม'); H::redirect('admin/courses/' . $cid . '/edit'); }

        [$data, $errors] = $this->admCollectBatch($oldBatch);
        if ($errors !== []) {
            foreach ($errors as $e) { H::flash('error', $e); }
            H::redirect('admin/courses/' . $cid . '/batches/' . $bId . '/edit');
        }
        $this->admBatches()->update($bId, $data);
        H::flash('success', 'บันทึกสำเร็จ: อัปเดตข้อมูลรุ่นอบรมเรียบร้อยแล้ว');
        H::redirect('admin/courses/' . $cid . '/edit');
    }

    public function copyBatch(string $id, string $bid): void
    {
        $this->admGuard();
        $cid = (int) $this->admEditable($id)['id'];
        $bId = ctype_digit($bid) ? (int)$bid : 0;
        $oldBatch = $this->admBatches()->find($bId, $cid);
        if (!$oldBatch) { 
            H::flash('error', 'ไม่พบรุ่นอบรมที่ต้องการคัดลอก'); 
            H::redirect('admin/courses/' . $cid . '/edit'); 
        }

        $newData = $oldBatch;
        unset($newData['id'], $newData['created_at'], $newData['updated_at']);
        $newData['batch_no'] = $oldBatch['batch_no'] . ' (คัดลอก)';
        $newData['brochure_image'] = null;
        $newData['gallery_images'] = null;
        $newData['document_file'] = null;
        $newData['document_title'] = null;
        $newData['cert_template'] = null;
        $newData['published_at'] = date('Y-m-d H:i:s');

        $this->admBatches()->create($newData);
        H::flash('success', 'บันทึกสำเร็จ: คัดลอกรุ่นอบรมเรียบร้อยแล้ว');
        H::redirect('admin/courses/' . $cid . '/edit');
    }

    public function deleteBatch(string $id, string $bid): void
    {
        $this->admGuard();
        $cid = (int) $this->admEditable($id)['id'];
        if (ctype_digit($bid)) {
            $oldBatch = $this->admBatches()->find((int)$bid, $cid);
            if ($oldBatch) {
                Uploader::delete($oldBatch['brochure_image'] ?? null);
                Uploader::delete($oldBatch['cert_template'] ?? null);
                Uploader::delete($oldBatch['document_file'] ?? null);
            }
            $this->admBatches()->delete((int)$bid);
        }
        H::flash('success', 'ลบรุ่นอบรมแล้ว');
        H::redirect('admin/courses/' . $cid . '/edit');
    }

    private function admCollect(int $exceptId, mixed $oldCourse): array
    {
        $m = $this->admCourses();
        $errors = [];
        $title = H::in('title', 250);
        $slugIn = H::in('slug', 250);
        $type = H::in('type');
        $status = H::in('status');
        $categoryIds = H::inArray('category_ids');
        $publishedAt = H::in('published_at');

        if ($title === '') { $errors['title'] = 'กรุณากรอกชื่อหลักสูตร'; }

        if ($slugIn !== '') {
            $slug = H::slugify($slugIn, 240);
        } elseif (is_array($oldCourse) && !empty($oldCourse['slug'])) {
            $slug = $oldCourse['slug'];
        } else {
            $slug = $m->uniqueSlug(H::slugify($title, 200) ?: 'course', $exceptId);
        }

        $cover = is_array($oldCourse) ? ($oldCourse['cover_image'] ?? '') : '';
        if (H::in('remove_cover') === '1') {
            Uploader::delete($cover);
            $cover = '';
        }

        try {
            if (isset($_FILES['cover_img']) && $_FILES['cover_img']['error'] === UPLOAD_ERR_OK) {
                if ($cover) { Uploader::delete($cover); }
                $cover = Uploader::upload($_FILES['cover_img'], 'courses/covers', 5242880, ['jpg', 'png', 'webp']);
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'category_id' => !empty($categoryIds) ? (int)$categoryIds[0] : null,
            'category_ids' => json_encode($categoryIds),
            'type' => (is_array($oldCourse) && !empty($oldCourse['type'])) ? $oldCourse['type'] : ($type ?: 'live_online'),
            'summary' => H::in('summary'),
            'description' => H::in('description'),
            'cover_image' => $cover !== '' ? $cover : null,
            'status' => $status ?: 'draft',
            'published_at' => $publishedAt !== '' ? date('Y-m-d H:i:s', strtotime($publishedAt)) : date('Y-m-d H:i:s'),
        ];
        return [$data, $errors];
    }

    private function defaultBatch(): array
    {
        return [
            'id' => 0, 'batch_no' => 'รุ่นที่ 1', 'date_text' => '', 'time_text' => '', 'duration_text' => '',
            'location_text' => '', 'description' => '', 'topics_json' => '', 'brochure_image' => '', 'gallery_images' => '',
            'document_file' => '', 'document_title' => '', 'evaluation_id' => 0, 'survey_id' => 0,
            'cert_template' => '', 'cert_settings' => '', 'price_type' => 'member_split', 'payment_type' => 'single',
            'price_1' => 0, 'price_2' => 0, 'price_3' => 0, 'price_4' => 0,
            'status_1' => 'open', 'status_2' => 'open', 'status_3' => 'open', 'status_4' => 'open',
            'limit_enabled_1' => 0, 'limit_enabled_2' => 0, 'limit_enabled_3' => 0, 'limit_enabled_4' => 0,
            'capacity_1' => 0, 'capacity_2' => 0, 'capacity_3' => 0, 'capacity_4' => 0, 'remark' => '', 'status' => 'open',
        ];
    }

    private function admCollectBatch(?array $old): array
    {
        $errors = [];
        $batchNo = H::in('batch_no', 100);
        if ($batchNo === '') { $errors[] = 'กรุณาระบุชื่อรุ่น'; }

        $priceType = H::in('price_type');
        $paymentType = H::in('payment_type');

        $p1 = H::inFloat('price_1');
        $p2 = H::inFloat('price_2');
        $p3 = H::inFloat('price_3');
        $p4 = H::inFloat('price_4');
        $publishedAt = H::in('published_at');

        $brochure = $old['brochure_image'] ?? '';
        if (H::in('remove_brochure') === '1') { Uploader::delete($brochure); $brochure = ''; }

        $docFile = $old['document_file'] ?? '';
        if (H::in('remove_doc') === '1') { Uploader::delete($docFile); $docFile = ''; }

        $certTemp = $old['cert_template'] ?? '';
        if (H::in('remove_cert') === '1') { Uploader::delete($certTemp); $certTemp = ''; }

        try {
            if (isset($_FILES['brochure_img']) && $_FILES['brochure_img']['error'] === UPLOAD_ERR_OK) {
                if ($brochure) { Uploader::delete($brochure); }
                $brochure = Uploader::upload($_FILES['brochure_img'], 'courses/brochures', 5242880, ['jpg', 'png', 'webp', 'pdf']);
            }
            if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
                if ($docFile) { Uploader::delete($docFile); }
                $docFile = Uploader::upload($_FILES['doc_file'], 'courses/docs', 20971520, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip']);
            }
            if (isset($_FILES['cert_file']) && $_FILES['cert_file']['error'] === UPLOAD_ERR_OK) {
                if ($certTemp) { Uploader::delete($certTemp); }
                $certTemp = Uploader::upload($_FILES['cert_file'], 'courses/certs', 10485760, ['jpg', 'png', 'webp', 'pdf']);
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }

        $existingGalleries = [];
        if ($old && !empty($old['gallery_images'])) {
            $existingGalleries = H::decodeJsonSafe($old['gallery_images']);
        }
        $removeGalleryImages = H::inArray('remove_gallery');
        $galleries = [];
        foreach ($existingGalleries as $gImg) {
            if (in_array($gImg, $removeGalleryImages, true)) {
                Uploader::delete($gImg);
            } else {
                $galleries[] = $gImg;
            }
        }

        if (isset($_FILES['gallery_imgs'])) {
            $files = $_FILES['gallery_imgs'];
            for ($i = 0; $i < count($files['name'] ?? []); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    try {
                        $singleFile = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                        $galleries[] = Uploader::upload($singleFile, 'courses/gallery', 5242880, ['jpg', 'png', 'webp']);
                    } catch (RuntimeException) {}
                }
            }
        }

        $topicTitles = H::inArray('topic_title');
        $topicSummaries = H::inArray('topic_summary');
        $topicsData = [];
        for ($i = 0; $i < count($topicTitles); $i++) {
            $tTitle = trim((string)($topicTitles[$i] ?? ''));
            if ($tTitle !== '') {
                $tSum = trim((string)($topicSummaries[$i] ?? ''));
                $lNames = $_POST['lesson_title'][$i] ?? [];
                $lTypes = $_POST['lesson_type'][$i] ?? [];
                $lDescs = $_POST['lesson_desc'][$i] ?? [];
                $lessons = [];
                for ($j = 0; $j < count($lNames); $j++) {
                    $lName = trim((string)($lNames[$j] ?? ''));
                    if ($lName !== '') {
                        $lessons[] = [
                            'title' => $lName, 
                            'type' => (string)($lTypes[$j] ?? 'lesson'),
                            'description' => (string)($lDescs[$j] ?? '')
                        ];
                    }
                }
                $topicsData[] = ['title' => $tTitle, 'summary' => $tSum, 'lessons' => $lessons];
            }
        }

        $certSettings = [
            'name_x' => H::inInt('cert_name_x'),
            'name_y' => H::inInt('cert_name_y'),
            'date_x' => H::inInt('cert_date_x'),
            'date_y' => H::inInt('cert_date_y'),
        ];

        $data = [
            'batch_no' => $batchNo,
            'date_text' => H::in('date_text', 255) ?: null,
            'time_text' => H::in('time_text', 150) ?: null,
            'duration_text' => H::in('duration_text', 150) ?: null,
            'location_text' => H::in('location_text', 255) ?: null,
            'description' => H::in('description'),
            'topics_json' => json_encode($topicsData, JSON_UNESCAPED_UNICODE),
            'brochure_image' => $brochure !== '' ? $brochure : null,
            'gallery_images' => json_encode($galleries),
            'document_file' => $docFile !== '' ? $docFile : null,
            'document_title' => H::in('document_title', 255) ?: null,
            'evaluation_id' => H::inInt('evaluation_id'),
            'survey_id' => H::inInt('survey_id'),
            'cert_template' => $certTemp !== '' ? $certTemp : null,
            'cert_settings' => json_encode($certSettings),
            'price_type' => $priceType,
            'payment_type' => $paymentType,
            'price_1' => $p1,
            'price_2' => $priceType === 'member_split' ? $p2 : 0.00,
            'price_3' => $paymentType === 'days_before' ? $p3 : 0.00,
            'price_4' => ($priceType === 'member_split' && $paymentType === 'days_before') ? $p4 : 0.00,
            'status_1' => H::in('status_1') ?: 'open',
            'status_2' => H::in('status_2') ?: 'open',
            'status_3' => H::in('status_3') ?: 'open',
            'status_4' => H::in('status_4') ?: 'open',
            'limit_enabled_1' => H::inInt('limit_enabled_1'),
            'limit_enabled_2' => H::inInt('limit_enabled_2'),
            'limit_enabled_3' => H::inInt('limit_enabled_3'),
            'limit_enabled_4' => H::inInt('limit_enabled_4'),
            'capacity_1' => H::inInt('capacity_1'),
            'capacity_2' => H::inInt('capacity_2'),
            'capacity_3' => H::inInt('capacity_3'),
            'capacity_4' => H::inInt('capacity_4'),
            'remark' => H::in('remark') ?: null,
            'status' => H::in('status') ?: 'open',
            'published_at' => $publishedAt !== '' ? date('Y-m-d H:i:s', strtotime($publishedAt)) : date('Y-m-d H:i:s'),
        ];
        return [$data, $errors];
    }
}