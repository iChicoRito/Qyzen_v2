<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrolled extends Model
{
    protected $table = 'tbl_enrolled';

    // D2: admin all / educator ownership / student own enrollments.
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('educator')) {
            return $query->where('educator_id', $user->id);
        }

        return $query->where('student_id', $user->id);
    }

    protected $fillable = ['student_id', 'educator_id', 'subject_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
