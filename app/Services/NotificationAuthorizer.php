<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

// D5: ports the tbl_notifications INSERT RLS (educator + student emit rules).
// The actor is always the current user; recipient/subject/assessment come from the payload.
class NotificationAuthorizer
{
    /** Event types an educator may emit (everything except quiz_submitted). */
    public const EDUCATOR_EVENTS = [
        'assessment_created', 'assessment_updated', 'assessment_deleted',
        'assessment_exempted', 'assessment_access_granted',
        'learning_material_uploaded', 'learning_material_deleted',
        'quiz_created', 'quiz_uploaded', 'quiz_updated', 'quiz_deleted',
        'enrollment_created', 'enrollment_updated', 'enrollment_deleted', 'retake_updated', 'announcement_created',
    ];

    /**
     * Can $actor emit a notification with this event_type to $recipientId?
     *
     * @param  array{subject_id?:int|null, assessment_id?:int|null}  $context
     */
    public function canEmit(User $actor, string $eventType, int $recipientId, array $context = []): bool
    {
        if ($actor->hasRole('educator')) {
            return $this->educatorCanEmit($actor, $eventType, $recipientId, $context);
        }

        if ($actor->hasRole('student')) {
            return $this->studentCanEmit($actor, $eventType, $recipientId, $context['assessment_id'] ?? null);
        }

        return false;
    }

    private function educatorCanEmit(User $actor, string $eventType, int $recipientId, array $context): bool
    {
        if (! in_array($eventType, self::EDUCATOR_EVENTS, true)) {
            return false;
        }

        // recipient must be a student (not soft-deleted)
        $recipientIsStudent = User::whereKey($recipientId)->where('user_type', 'student')->exists();
        if (! $recipientIsStudent) {
            return false;
        }

        $subjectId = $context['subject_id'] ?? null;

        // enrollment_deleted: the enrolled row may already be gone, so verify by subject ownership.
        if ($eventType === 'enrollment_deleted') {
            return DB::table('tbl_subjects')
                ->where('id', $subjectId)
                ->where('educator_id', $actor->id)
                ->exists();
        }

        if ($eventType === 'announcement_created') {
            if ($subjectId) {
                return DB::table('tbl_subjects')
                    ->where('id', $subjectId)
                    ->where('educator_id', $actor->id)
                    ->exists()
                    && DB::table('tbl_enrolled')
                        ->where('educator_id', $actor->id)
                        ->where('student_id', $recipientId)
                        ->where('subject_id', $subjectId)
                        ->where('is_active', true)
                        ->exists();
            }

            return DB::table('tbl_enrolled')
                ->where('educator_id', $actor->id)
                ->where('student_id', $recipientId)
                ->where('is_active', true)
                ->exists();
        }

        // all other events: recipient must be actively enrolled with this educator in this subject.
        return DB::table('tbl_enrolled')
            ->where('educator_id', $actor->id)
            ->where('student_id', $recipientId)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    private function studentCanEmit(User $actor, string $eventType, int $recipientId, ?int $assessmentId): bool
    {
        if ($eventType !== 'quiz_submitted') {
            return false;
        }

        // recipient must be the assessment's educator, and the actor actively enrolled in it.
        return DB::table('tbl_assessments as a')
            ->join('tbl_enrolled as e', function ($join) use ($actor) {
                $join->on('e.educator_id', '=', 'a.educator_id')
                    ->on('e.subject_id', '=', 'a.subject_id')
                    ->where('e.student_id', '=', $actor->id)
                    ->where('e.is_active', '=', true);
            })
            ->where('a.id', $assessmentId)
            ->where('a.educator_id', $recipientId)
            ->exists();
    }
}
