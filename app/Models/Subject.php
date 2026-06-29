<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $table = 'tbl_subjects';

    // D2: admin all / educator ownership (perm 'subjects:view' gated at Policy) /
    // student enrollment in this subject.
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('educator')) {
            return $query->where('educator_id', $user->id);
        }

        return $query->whereExists(fn ($q) => $q->selectRaw('1')
            ->from('tbl_enrolled')
            ->whereColumn('tbl_enrolled.educator_id', 'tbl_subjects.educator_id')
            ->whereColumn('tbl_enrolled.subject_id', 'tbl_subjects.id')
            ->where('tbl_enrolled.student_id', $user->id)
            ->where('tbl_enrolled.is_active', true));
    }

    protected $fillable = ['educator_id', 'sections_id', 'subject_code', 'subject_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'sections_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrolled::class, 'subject_id');
    }
}
