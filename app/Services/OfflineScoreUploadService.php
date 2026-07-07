<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Score;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OfflineScoreUploadService
{
    public function import(User $educator, Assessment $assessment, array $rows): int
    {
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => ['The upload has no score rows.']]);
        }

        $assessment->loadCount('quizzes');

        $errors = [];
        $clean = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $validator = Validator::make($row, [
                'student_id' => ['required', 'string'],
                'score' => ['required', 'integer', 'min:0'],
                'total_questions' => ['required', 'integer', 'min:1'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$line}: ".$validator->errors()->first();

                continue;
            }

            $data = $validator->validated();
            $student = User::where('user_id', $data['student_id'])->where('user_type', 'student')->first();

            if (! $student) {
                $errors[] = "Row {$line}: student_id was not found.";

                continue;
            }
            if ((int) $data['score'] > (int) $data['total_questions']) {
                $errors[] = "Row {$line}: score cannot exceed total_questions.";

                continue;
            }
            if ($assessment->quizzes_count > 0 && (int) $data['total_questions'] !== (int) $assessment->quizzes_count) {
                $errors[] = "Row {$line}: total_questions does not match the assessment quiz count.";

                continue;
            }

            $key = $assessment->id.'|'.$student->id;
            if (isset($seen[$key])) {
                $errors[] = "Row {$line}: duplicate score for this student and assessment.";

                continue;
            }
            $seen[$key] = true;

            $enrolled = Enrolled::where('educator_id', $educator->id)
                ->where('student_id', $student->id)
                ->where('subject_id', $assessment->subject_id)
                ->where('is_active', true)
                ->exists();
            if (! $enrolled) {
                $errors[] = "Row {$line}: student is not actively enrolled under this educator for the assessment subject.";

                continue;
            }

            $existing = Score::where('assessment_id', $assessment->id)
                ->where('student_id', $student->id)
                ->whereNotNull('submitted_at')
                ->exists();
            if ($existing) {
                $errors[] = "Row {$line}: a submitted score already exists for this student and assessment.";

                continue;
            }

            $passed = (((int) $data['score'] / (int) $data['total_questions']) * 100) >= 75;
            $now = Carbon::now();

            $clean[] = [
                'student_id' => $student->id,
                'educator_id' => $educator->id,
                'assessment_id' => $assessment->id,
                'subject_id' => $assessment->subject_id,
                'section_id' => $assessment->section_id,
                'score' => (int) $data['score'],
                'total_questions' => (int) $data['total_questions'],
                'student_answer' => [],
                'warning_attempts' => 0,
                'status' => $passed ? 'passed' : 'failed',
                'is_passed' => $passed,
                'taken_at' => $now,
                'submitted_at' => $now,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        return DB::transaction(function () use ($clean) {
            foreach ($clean as $row) {
                Score::create($row);
            }

            return count($clean);
        });
    }
}
