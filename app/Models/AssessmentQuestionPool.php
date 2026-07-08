<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Task 51: pivot for the eligible-question pool. Always accessed through the owning
// Assessment (Assessment::eligibleQuizzes()) — no visibleTo scope of its own.
class AssessmentQuestionPool extends Model
{
    protected $table = 'tbl_assessment_question_pool';

    protected $fillable = ['assessment_id', 'quiz_id'];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }
}
