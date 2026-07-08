<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Quiz extends Model
{
    protected $table = 'tbl_quizzes';

    // D2: educator ownership / student enrollment (educator+subject). No admin policy
    // in source (questions are never browsed by admin); admins still excluded here.
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('educator')) {
            return $query->where('educator_id', $user->id);
        }

        if ($user->hasRole('student')) {
            return $query->whereExists(fn ($q) => $q->selectRaw('1')
                ->from('tbl_enrolled')
                ->whereColumn('tbl_enrolled.educator_id', 'tbl_quizzes.educator_id')
                ->whereColumn('tbl_enrolled.subject_id', 'tbl_quizzes.subject_id')
                ->where('tbl_enrolled.student_id', $user->id)
                ->where('tbl_enrolled.is_active', true));
        }

        return $query->whereRaw('1 = 0'); // admin/other: no quiz visibility
    }

    protected $fillable = [
        'subject_id', 'educator_id', 'question', 'quiz_type', 'choices', 'correct_answer', 'batch_label',
    ];

    protected $casts = ['choices' => 'array'];

    // Security invariant: correct_answer must never be serialized to a student.
    // Hiding it from array/JSON output is the model-layer guard (Stage B7 / D3.5 / H6).
    protected $hidden = ['correct_answer'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    // Task 51: which assessments have this bank question in their eligible pool.
    public function eligibleAssessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'tbl_assessment_question_pool', 'quiz_id', 'assessment_id');
    }
}
