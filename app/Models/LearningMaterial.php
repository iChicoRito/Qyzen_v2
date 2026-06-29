<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningMaterial extends Model
{
    protected $table = 'tbl_learning_materials';

    protected $fillable = [
        'educator_id', 'subject_id', 'section_id', 'storage_bucket', 'storage_path',
        'file_name', 'file_extension', 'mime_type', 'file_size', 'is_active',
    ];

    protected $casts = ['file_size' => 'integer', 'is_active' => 'boolean'];

    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
