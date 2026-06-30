<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Score;
use App\Models\User;
use Illuminate\View\View;

// F1: read-only admin dashboard. Live-refresh deferred to Stage I.
class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        // ponytail: plain aggregate queries, no caching — add only if the page measurably slows.
        $stats = [
            'users_total'   => User::count(),
            'users_active'  => User::where('is_active', true)->count(),
            'educators'     => User::where('user_type', 'educator')->count(),
            'students'      => User::where('user_type', 'student')->count(),
            'assessments'   => Assessment::count(),
            'assessments_active' => Assessment::where('is_active', true)->count(),
        ];

        // Top students by average passed-score (admin sees all — no visibleTo needed).
        $topStudents = Score::query()
            ->selectRaw('student_id, AVG(score) as avg_score, COUNT(*) as attempts')
            ->groupBy('student_id')
            ->orderByDesc('avg_score')
            ->with('student:id,given_name,surname,user_id')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'topStudents'));
    }
}
