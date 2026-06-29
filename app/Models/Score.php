<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $table = 'tbl_scores';

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
}
