<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Score;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// H1: read-only student dashboard. All queries scoped to the student's own data via visibleTo /
// enrollment. No correct_answer touched.
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        // Enrolled assessments visible to this student.
        $totalAssessments = Assessment::visibleTo($user)->count();

        $ownScores = Score::where('student_id', $user->id);
        $completed = (clone $ownScores)->whereIn('status', ['passed', 'failed', 'submitted'])->count();
        $avg = (clone $ownScores)->whereNotNull('score')->avg('score');

        $recent = Score::where('student_id', $user->id)
            ->with('assessment:id,assessment_code')
            ->orderByDesc('submitted_at')->limit(5)->get();

        $stats = [
            'assessments' => $totalAssessments,
            'completed' => $completed,
            'pending' => max(0, $totalAssessments - $completed),
            'avg_score' => $avg ? round($avg, 1) : 0,
        ];

        return view('student.dashboard', compact('stats', 'recent'));
    }
}
