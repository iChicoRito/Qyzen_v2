<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Score;
use App\Models\User;
use App\Services\Export\ScoreRowBuilder;
use App\Services\Export\WorkbookBuilder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

// G8/Task 27: single-assessment export, a lightweight pre-export preview, and the bulk
// zip (grouped TERM/SUBJECT/SECTION.xlsx, one workbook per group, one sheet per assessment).
// Bulk fetches enrollments + scores ONCE for every assessment in scope and groups them in
// memory — no per-assessment queries.
class ScoreExportService
{
    public function preview(Assessment $assessment): array
    {
        $roster = Enrolled::where('subject_id', $assessment->subject_id)->where('is_active', true)->count();
        $withSubmission = Score::where('assessment_id', $assessment->id)
            ->whereNotNull('submitted_at')->distinct('student_id')->count('student_id');

        return [
            'subject' => trim(($assessment->subject?->subject_code ?? '').' — '.($assessment->subject?->subject_name ?? ''), ' —'),
            'section' => $assessment->section?->section_name,
            'assessmentCode' => $assessment->assessment_code,
            'academicTerm' => $assessment->academicTerm?->term_name,
            'enrolled' => $roster,
            'withSubmission' => $withSubmission,
            'withoutSubmission' => $roster - $withSubmission,
        ];
    }

    public function single(Assessment $assessment): Spreadsheet
    {
        $roster = Enrolled::where('subject_id', $assessment->subject_id)->where('is_active', true)
            ->with('student:id,given_name,surname,user_id')->get();
        $scores = Score::where('assessment_id', $assessment->id)->whereNotNull('submitted_at')->get();

        $context = $this->context($assessment);
        $built = ScoreRowBuilder::build($roster, $scores, $assessment->quizzes()->count(), $context);

        return WorkbookBuilder::singleSheetWorkbook(
            ['title' => "Scores — {$assessment->assessment_code}", ...$context, 'summary' => $built['summary']],
            $built['rows'],
        );
    }

    public function bulk(User $educator, array $filter): StreamedResponse
    {
        $query = Assessment::visibleTo($educator)->withCount('quizzes')->with([
            'subject:id,subject_code,subject_name',
            'section:id,section_name',
            'academicTerm:id,term_name,semester,academic_year_id',
            'academicTerm.year:id,year',
        ]);

        if ($filter['type'] === 'term') {
            $query->where('term', $filter['termId']);
        }
        if ($filter['type'] === 'semester') {
            $query->whereHas('academicTerm', fn ($t) => $t->where('semester', $filter['semester'])
                ->whereHas('year', fn ($y) => $y->where('year', $filter['academicYear'])));
        }

        $assessments = $query->get();

        // Exactly 3 queries total for the whole bulk export — fetch once, group in memory.
        $enrollmentsBySubject = Enrolled::whereIn('subject_id', $assessments->pluck('subject_id')->unique())
            ->where('is_active', true)->with('student:id,given_name,surname,user_id')->get()->groupBy('subject_id');
        $scoresByAssessment = Score::whereIn('assessment_id', $assessments->pluck('id'))
            ->whereNotNull('submitted_at')->get()->groupBy('assessment_id');

        $groups = $assessments->groupBy(fn (Assessment $a) => "{$a->subject_id}:{$a->section_id}:{$a->term}");

        $tmpDir = storage_path('app/tmp');
        File::ensureDirectoryExists($tmpDir);
        $zipPath = $tmpDir.'/score-export-'.$educator->id.'-'.now()->timestamp.'.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $usedPaths = [];
        foreach ($groups as $groupAssessments) {
            $book = WorkbookBuilder::newWorkbook();
            foreach ($groupAssessments as $a) {
                $context = $this->context($a);
                $built = ScoreRowBuilder::build(
                    $enrollmentsBySubject->get($a->subject_id, collect()),
                    $scoresByAssessment->get($a->id, collect()),
                    $a->quizzes_count,
                    $context,
                );
                WorkbookBuilder::addSheet(
                    $book,
                    ['title' => $a->assessment_code, ...$context, 'summary' => $built['summary']],
                    $built['rows'],
                );
            }

            $first = $groupAssessments->first();
            $path = $this->sanitizePathSegment($first->academicTerm?->term_name ?? 'no term')
                .'/'.$this->sanitizePathSegment(($first->subject?->subject_code ?? 'no subject').' '.($first->subject?->subject_name ?? ''))
                .'/'.$this->sanitizePathSegment($first->section?->section_name ?? 'no section').'.xlsx';

            // De-dupe in the (rare) case two groups sanitize to the same path.
            $unique = $path;
            $suffix = 2;
            while (in_array($unique, $usedPaths, true)) {
                $unique = preg_replace('/\.xlsx$/', "-{$suffix}.xlsx", $path);
                $suffix++;
            }
            $usedPaths[] = $unique;

            $zip->addFromString($unique, WorkbookBuilder::toBytes($book));
        }

        $zip->close();

        $filename = Str::slug($educator->name).'-'.$filter['type'].'-grades-'.now()->format('Y-m-d').'.zip';

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            @unlink($zipPath);
        }, $filename, ['Content-Type' => 'application/zip']);
    }

    private function context(Assessment $assessment): array
    {
        return [
            'subject' => $assessment->subject?->subject_code,
            'section' => $assessment->section?->section_name,
            'assessmentCode' => $assessment->assessment_code,
            'academicTerm' => $assessment->academicTerm?->term_name,
        ];
    }

    private function sanitizePathSegment(string $name): string
    {
        $upper = strtoupper(trim($name));
        $clean = trim(preg_replace('/[^A-Z0-9]+/', '-', $upper), '-');

        return $clean !== '' ? $clean : 'UNKNOWN';
    }
}
