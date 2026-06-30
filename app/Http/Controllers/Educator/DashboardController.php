<?php

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Score;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// G1: read-only educator dashboard. Every query carries visibleTo($educator) — ownership gate.
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $stats = [
            'sections'    => Section::visibleTo($user)->count(),
            'subjects'    => Subject::visibleTo($user)->count(),
            'assessments' => Assessment::visibleTo($user)->count(),
            'assessments_active' => Assessment::visibleTo($user)->where('is_active', true)->count(),
        ];

        // Top students across this educator's scored assessments.
        $topStudents = Score::visibleTo($user)
            ->selectRaw('student_id, AVG(score) as avg_score, COUNT(*) as attempts')
            ->groupBy('student_id')
            ->orderByDesc('avg_score')
            ->with('student:id,given_name,surname,user_id')
            ->limit(5)
            ->get();

        return view('educator.dashboard', compact('stats', 'topStudents'));
    }
}
