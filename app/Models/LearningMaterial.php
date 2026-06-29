<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningMaterial extends Model
{
    protected $table = 'tbl_learning_materials';

    // D2: educator ownership / student enrollment (active material only). No admin
    // policy in source. Admins excluded.
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('educator')) {
            return $query->where('educator_id', $user->id);
        }

        if ($user->hasRole('student')) {
            return $query->where('is_active', true)
                ->whereExists(fn ($q) => $q->selectRaw('1')
                    ->from('tbl_enrolled')
                    ->whereColumn('tbl_enrolled.educator_id', 'tbl_learning_materials.educator_id')
                    ->whereColumn('tbl_enrolled.subject_id', 'tbl_learning_materials.subject_id')
                    ->where('tbl_enrolled.student_id', $user->id)
                    ->where('tbl_enrolled.is_active', true));
        }

        return $query->whereRaw('1 = 0');
    }

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
