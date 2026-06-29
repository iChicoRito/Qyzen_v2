<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quiz extends Model
{
    protected $table = 'tbl_quizzes';

    protected $fillable = [
        'assessment_id', 'subject_id', 'section_id', 'educator_id',
        'question', 'quiz_type', 'choices', 'correct_answer',
    ];

    protected $casts = ['choices' => 'array'];

    // Security invariant: correct_answer must never be serialized to a student.
    // Hiding it from array/JSON output is the model-layer guard (Stage B7 / D3.5 / H6).
    protected $hidden = ['correct_answer'];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }
}
