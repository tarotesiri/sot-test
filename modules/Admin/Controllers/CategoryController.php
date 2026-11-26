<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : CategoryController.php
 * Description : Category Controller
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Modules\Admin\Models\CategoryAdminModel;
use Modules\Admin\Support\AdminHelper as H;

final class CategoryController extends AdminBaseController
{
    private function admModel(): CategoryAdminModel { return new CategoryAdminModel($this->db()); }

    public function index(): string
    {
        $this->admGuard();
        $form = H::pullForm();
        return $this->admPage('categories/index', ['categories' => $this->admModel()->all('active'), 'old' => $form['old'], 'errors' => $form['errors']], 'หมวดหมู่ (Tags)', 'categories');
    }

    public function store(): void
    {
        $this->admGuard();
        $name = H::in('name', 150);
        if ($name === '') { H::flash('error', 'กรุณากรอกชื่อหมวดหมู่'); H::redirect('admin/categories'); }
        $this->admModel()->create(['name' => $name, 'slug' => $this->admModel()->uniqueSlug(H::slugify($name))]);
        H::flash('success', 'เพิ่มหมวดหมู่แล้ว'); H::redirect('admin/categories');
    }

    public function edit(string $id): string
    {
        $this->admGuard();
        $cat = ctype_digit($id) ? $this->admModel()->find((int)$id) : null;
        if (!$cat) { H::flash('error', 'ไม่พบหมวดหมู่'); H::redirect('admin/categories'); }
        return $this->admPage('categories/edit', ['category' => $cat], 'แก้ไขหมวดหมู่', 'categories');
    }

    public function update(string $id): void
    {
        $this->admGuard();
        $name = H::in('name', 150);
        if (ctype_digit($id) && $name !== '') {
            $this->admModel()->update((int)$id, ['name' => $name, 'slug' => $this->admModel()->uniqueSlug(H::slugify($name), (int)$id)]);
        }
        H::flash('success', 'บันทึกแล้ว'); H::redirect('admin/categories');
    }

    public function trash(string $id): void
    {
        $this->admGuard();
        if (ctype_digit($id)) { $this->admModel()->trash((int)$id); }
        H::flash('success', 'ย้ายไปถังขยะแล้ว'); H::redirect('admin/categories');
    }

    public function restore(string $id): void
    {
        $this->admGuard();
        if (ctype_digit($id)) { $this->admModel()->restore((int)$id); }
        H::flash('success', 'กู้คืนแล้ว'); H::redirect('admin/trash');
    }

    public function destroy(string $id): void
    {
        $this->admGuard();
        if (H::in('confirm') === '1' && ctype_digit($id)) { $this->admModel()->forceDelete((int)$id); }
        H::flash('success', 'ลบถาวรแล้ว'); H::redirect('admin/trash');
    }
}