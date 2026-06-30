<?php

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Score;
use App\Models\StudentPresence;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// G11: realtime monitoring, request/response (live updates deferred to Stage I). View-only:
// per assessment, count enrolled/online/answering/finished. Manual refresh = page reload.
class MonitoringController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Assessment::class);

        $user = Auth::user();
        $onlineThreshold = now()->subSeconds(60); // 60s-stale = offline (matches source heuristic)

        $assessments = Assessment::visibleTo($user)
            ->where('is_active', true)
            ->with(['subject:id,subject_code', 'section:id,section_name', 'academicTerm:id,term_name'])
            ->get()
            ->map(function (Assessment $a) use ($onlineThreshold) {
                $studentIds = Enrolled::where('educator_id', $a->educator_id)
                    ->where('subject_id', $a->subject_id)
                    ->where('is_active', true)
                    ->pluck('student_id');

                $scores = Score::where('assessment_id', $a->id)
                    ->whereIn('student_id', $studentIds)
                    ->get()->keyBy('student_id');

                $online = StudentPresence::whereIn('student_id', $studentIds)
                    ->where('last_seen_at', '>=', $onlineThreshold)
                    ->count();

                return [
                    'assessment' => $a,
                    'enrolled' => $studentIds->count(),
                    'online' => $online,
                    'answering' => $scores->where('status', 'in_progress')->count(),
                    'finished' => $scores->whereIn('status', ['submitted', 'passed', 'failed'])->count(),
                ];
            });

        return view('educator.monitoring.index', compact('assessments'));
    }
}
