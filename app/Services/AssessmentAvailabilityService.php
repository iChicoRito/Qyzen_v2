<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Score;
use App\Models\StudentAssessmentExemption;
use App\Models\StudentAssessmentRetake;
use Carbon\Carbon;

// H2/H3: availability badge + can-take logic (ports assessment-availability.ts).
// Pure computation over schedule + attempt history — no correct_answer involved.
class AssessmentAvailabilityService
{
    /**
     * @return array{badge:string, can_take:bool, remaining:int, submitted_attempts:int, effective_retakes:int, window_open:bool}
     */
    public function summarize(Assessment $assessment, int $studentId, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        // Inactive/unpublished assessments must never be startable, even inside their date
        // window — this was previously unchecked here, so take()/submit() (which trust this
        // summary) let a deactivated assessment through as long as "now" was between the dates.
        if (! $assessment->is_active) {
            return [
                'badge' => 'Inactive', 'can_take' => false, 'remaining' => 0,
                'submitted_attempts' => 0, 'effective_retakes' => 0, 'window_open' => false,
            ];
        }

        // Exemption overrides everything else — an educator-exempted student (e.g. absent) never
        // sees this as takeable, regardless of schedule/retake state.
        $exempt = StudentAssessmentExemption::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();
        if ($exempt) {
            return [
                'badge' => 'Exempted', 'can_take' => false, 'remaining' => 0,
                'submitted_attempts' => 0, 'effective_retakes' => 0, 'window_open' => false,
            ];
        }

        $start = $this->combine($assessment->start_date, $assessment->start_time);
        $end = $this->combine($assessment->end_date, $assessment->end_time);

        $scheduleValid = $start !== null && $end !== null && $start->lte($end);
        $windowOpen = $scheduleValid && $now->gte($start) && $now->lt($end);

        // submitted (terminal) attempts for this student.
        $submitted = Score::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'passed', 'failed'])
            ->count();

        $granted = (int) StudentAssessmentRetake::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->value('extra_retake_count');

        $baseRetakes = $assessment->allow_retake ? (int) $assessment->retake_count : 0;
        $effective = $baseRetakes + $granted;        // total allowed attempts beyond the first
        $allowedAttempts = 1 + $effective;            // first attempt + retakes
        $remaining = max(0, $allowedAttempts - $submitted);

        $firstAttempt = $submitted === 0;
        $canTake = $windowOpen && ($firstAttempt || ($effective > 0 && $remaining > 0));

        return [
            'badge' => $this->badge($scheduleValid, $now, $start, $end, $submitted, $remaining),
            'can_take' => $canTake,
            'remaining' => $remaining,
            'submitted_attempts' => $submitted,
            'effective_retakes' => $effective,
            'window_open' => $windowOpen,
        ];
    }

    private function badge(bool $valid, Carbon $now, ?Carbon $start, ?Carbon $end, int $submitted, int $remaining): string
    {
        if (! $valid) {
            return 'Schedule issue';
        }
        if ($now->lt($start)) {
            return 'Upcoming';
        }
        if ($now->gte($end)) {
            return 'Expired';
        }

        // within window
        return $submitted > 0 && $remaining > 0 ? 'Reopened' : 'Available';
    }

    private function combine($date, $time): ?Carbon
    {
        if (! $date || ! $time) {
            return null;
        }
        try {
            // Educators type start/end date+time as their own local wall-clock (no tz info is
            // captured client-side) — parse it as school-local time, not the app's default UTC,
            // or an assessment starting "now" compares as hours in the future/past. Carbon's
            // instant comparisons (lt/gt/betweenIncluded) work correctly across tz representations.
            return Carbon::parse(Carbon::parse($date)->toDateString().' '.$time, config('app.school_timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}
