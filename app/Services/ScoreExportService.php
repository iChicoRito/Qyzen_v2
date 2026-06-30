<?php

namespace App\Services;

use App\Exports\ScoresExport;
use App\Models\Assessment;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

// G8: bulk score export → a zip of TERM/SUBJECT/SECTION.xlsx, one workbook per assessment.
// method: all (every owned assessment), term/semester (filtered). ponytail: synchronous build
// into a temp dir then stream + clean up; move to a queued job only if it gets slow.
class ScoreExportService
{
    public function bulkZip(User $educator, string $method = 'all'): StreamedResponse
    {
        $assessments = Assessment::visibleTo($educator)
            ->with(['subject:id,subject_code', 'section:id,section_name', 'academicTerm:id,term_name,semester'])
            ->get();

        $tmpDir = storage_path('app/tmp/score-export-'.$educator->id.'-'.now()->timestamp);
        File::ensureDirectoryExists($tmpDir);
        $zipPath = $tmpDir.'.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($assessments as $a) {
            $term = $this->safe($a->academicTerm?->term_name ?? 'NO_TERM');
            $subject = $this->safe($a->subject?->subject_code ?? 'NO_SUBJECT');
            $section = $this->safe($a->section?->section_name ?? 'NO_SECTION');

            // method filter (semester lives on the term).
            if ($method === 'semester' && ! $a->academicTerm) {
                continue;
            }

            $file = $tmpDir.'/'.$a->id.'.xlsx';
            Excel::store(new ScoresExport($a), str_replace(storage_path('app').'/', '', $file));
            $zip->addFile($file, "{$term}/{$subject}/{$section}.xlsx");
        }

        $zip->close();

        return response()->streamDownload(function () use ($zipPath, $tmpDir) {
            readfile($zipPath);
            @File::deleteDirectory($tmpDir);
            @unlink($zipPath);
        }, 'scores-export.zip', ['Content-Type' => 'application/zip']);
    }

    private function safe(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9 _-]/', '_', $name);
    }
}
