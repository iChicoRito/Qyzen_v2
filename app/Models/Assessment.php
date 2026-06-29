<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $table = 'tbl_assessments';

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
