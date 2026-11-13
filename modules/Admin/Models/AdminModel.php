<?php
declare(strict_types=1);

namespace Modules\Admin\Models;

use App\Core\Model;

final class AdminModel extends Model
{
	protected static string $table = 'admin_logs';
	
	public function test(): void
	{
		return 'TaRote ทดสอบ...';
	}
}