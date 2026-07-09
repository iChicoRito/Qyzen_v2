<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentImport extends Model
{
    protected $table = 'tbl_enrollment_imports';

    protected $fillable = [
        'initiated_by_user_id',
        'original_filename',
        'upload_path',
        'status',
        'error_message',
        'created_count',
        'failed_rows',
        'failed_report_path',
    ];

    protected function casts(): array
    {
        return ['failed_rows' => 'array'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function scopeOwnedBy($query, User $user)
    {
        return $query->where('initiated_by_user_id', $user->id);
    }
}
