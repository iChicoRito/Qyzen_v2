<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserImport extends Model
{
    protected $table = 'tbl_user_imports';

    protected $fillable = [
        'initiated_by_user_id',
        'original_filename',
        'upload_path',
        'failed_report_path',
        'status',
        'error_message',
        'total_rows',
        'total_chunks',
        'processed_chunks',
        'created_count',
        'failed_count',
        'failed_rows',
    ];

    protected function casts(): array
    {
        return [
            'failed_rows' => 'array',
        ];
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
