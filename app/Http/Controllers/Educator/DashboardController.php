<?php

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Notification;
use App\Models\Score;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

// G1: educator dashboard — ownership-scoped (educator_id) quiz analytics + assessment calendar.
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $today = Carbon::today();

        // --- Metric cards ---
        $sectionCount = Section::visibleTo($user)->count();
        $subjectCount = Subject::visibleTo($user)->count();
        $studentCount = Enrolled::visibleTo($user)->where('is_active', true)
            ->distinct('student_id')->count('student_id');
        $pendingCount = Assessment::visibleTo($user)->where('is_active', true)
            ->whereDate('end_date', '>=', $today)->count();

        // --- Grouped bar: avg score % per section, one series per subject ---
        $sections = Section::visibleTo($user)->orderBy('section_name')->get(['id', 'section_name']);
        $subjects = Subject::visibleTo($user)->orderBy('subject_name')->get(['id', 'subject_name']);
        $avgRows = Score::visibleTo($user)->where('total_questions', '>', 0)
            ->selectRaw('section_id, subject_id, AVG(score * 100.0 / total_questions) AS avg_pct')
            ->groupBy('section_id', 'subject_id')->get();

        $avgMap = [];
        foreach ($avgRows as $r) {
            $avgMap[$r->section_id][$r->subject_id] = round((float) $r->avg_pct, 1);
        }
        $perfCategories = $sections->pluck('section_name')->values();
        $perfSeries = $subjects->map(fn ($subj) => [
            'name' => $subj->subject_name,
            'data' => $sections->map(fn ($sec) => $avgMap[$sec->id][$subj->id] ?? 0)->values(),
        ])->values();

        // --- Area: quiz-activity trend (submitted scores per ISO week) ---
        $trend = Score::weeklyTrend(Score::visibleTo($user));
        $trendLabels = $trend['labels'];
        $trendData = $trend['data'];

        // --- Table: sections → subjects / enrollment / avg score ---
        // Qualify columns explicitly: the tbl_subjects join makes educator_id/is_active ambiguous,
        // so the unqualified visibleTo() predicate can't be reused here. Same ownership filter.
        $enrollBySection = Enrolled::query()
            ->where('tbl_enrolled.educator_id', $user->id)
            ->where('tbl_enrolled.is_active', true)
            ->join('tbl_subjects', 'tbl_subjects.id', '=', 'tbl_enrolled.subject_id')
            ->selectRaw('tbl_subjects.sections_id AS sid, COUNT(DISTINCT tbl_enrolled.student_id) AS c')
            ->groupBy('tbl_subjects.sections_id')->pluck('c', 'sid');
        $avgBySection = Score::visibleTo($user)->where('total_questions', '>', 0)
            ->selectRaw('section_id, AVG(score * 100.0 / total_questions) AS avg_pct')
            ->groupBy('section_id')->pluck('avg_pct', 'section_id');
        $sectionTable = Section::visibleTo($user)->with('subjects:id,sections_id,subject_name')
            ->orderBy('section_name')->get()->map(fn ($s) => [
                'name' => $s->section_name,
                'subjects' => $s->subjects->pluck('subject_name')->join(', ') ?: '—',
                'enrolled' => (int) ($enrollBySection[$s->id] ?? 0),
                'avg' => isset($avgBySection[$s->id]) ? round((float) $avgBySection[$s->id], 1) : null,
            ]);

        // --- This-week assessments (cards) + calendar + next-up ---
        $weekAssessments = Assessment::visibleTo($user)->with('subject:id,subject_name')
            ->whereDate('start_date', '<=', $today->copy()->endOfWeek())
            ->whereDate('end_date', '>=', $today->copy()->startOfWeek())
            ->orderBy('start_date')->get();

        $calendarAssessments = Assessment::visibleTo($user)->with('subject:id,subject_name')
            ->whereNotNull('start_date')->whereNotNull('end_date')->get();
        $calendarEvents = $calendarAssessments->map->calendarEvent()->values();

        $nextAssessment = Assessment::visibleTo($user)->with('subject:id,subject_name')
            ->whereDate('start_date', '>=', $today)->orderBy('start_date')->first();

        // --- Notifications ---
        $unreadCount = Notification::forRecipient($user->id)->where('is_read', false)->count();
        $recentNotifications = Notification::recentForBell($user->id, 5);

        return view('educator.dashboard', compact(
            'sectionCount', 'subjectCount', 'studentCount', 'pendingCount',
            'perfCategories', 'perfSeries', 'trendLabels', 'trendData',
            'sectionTable', 'weekAssessments', 'calendarEvents', 'nextAssessment',
            'unreadCount', 'recentNotifications',
        ));
    }
}
