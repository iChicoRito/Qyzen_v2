<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $table = 'tbl_scores';

    // Task 19: opaque route key — URLs bind by the random uuid, never the sequential id.
    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->uuid ??= (string) \Illuminate\Support\Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // D2: admin all / educator ownership / student own scores only.
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

    protected $fillable = [
        'student_id', 'educator_id', 'assessment_id', 'subject_id', 'section_id',
        'score', 'total_questions', 'student_answer', 'warning_attempts',
        'status', 'is_passed', 'taken_at', 'submitted_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_questions' => 'integer',
        'student_answer' => 'array',
        'warning_attempts' => 'integer',
        'is_passed' => 'boolean',
        'taken_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
}
