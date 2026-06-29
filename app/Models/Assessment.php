<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $table = 'tbl_assessments';

    // D2: admin all / educator ownership / student enrollment (active tbl_enrolled
    // matching educator+subject). NOTE: no admin RLS policy on assessments in source,
    // but admin retains full app-layer access per the admin Policy.
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
            ->whereColumn('tbl_enrolled.educator_id', 'tbl_assessments.educator_id')
            ->whereColumn('tbl_enrolled.subject_id', 'tbl_assessments.subject_id')
            ->where('tbl_enrolled.student_id', $user->id)
            ->where('tbl_enrolled.is_active', true));
    }

    protected $fillable = [
        'educator_id', 'subject_id', 'section_id', 'assessment_code', 'time_limit',
        'cheating_attempts', 'is_shuffle', 'is_active', 'start_date', 'end_date',
        'start_time', 'end_time', 'term', 'allow_review', 'allow_hint', 'hint_count',
        'allow_retake', 'retake_count',
    ];

    protected $casts = [
        'cheating_attempts' => 'integer',
        'is_shuffle' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'allow_review' => 'boolean',
        'allow_hint' => 'boolean',
        'hint_count' => 'integer',
        'allow_retake' => 'boolean',
        'retake_count' => 'integer',
    ];

    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'assessment_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class, 'assessment_id');
    }
}
