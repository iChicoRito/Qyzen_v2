<?php

namespace App\Services;

use App\Models\Assessment;

// Task 51: performs a random draw of N eligible bank questions for an assessment. Pure — no
// persistence. The caller (QuizGradingService::saveDraft) decides when to pin the result.
class QuestionPoolDrawService
{
    /** @return array<int,int> quiz ids */
    public function drawFor(Assessment $assessment): array
    {
        return $assessment->eligibleQuizzes()
            ->pluck('tbl_quizzes.id')
            ->shuffle()
            ->take($assessment->pool_size)
            ->values()
            ->all();
    }
}
