<?php

namespace App\Imports;

use App\Models\Assessment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

// G6: bulk question upload parser/validator for one assessment.
// Columns: question, quiz_type, choice_a, choice_b, choice_c, choice_d, correct_answer.
// For identification, leave choice_* blank and put the answer in correct_answer.
//
// This does NOT write to the DB. It validates every row and collects errors; the controller
// aborts the whole upload if any row (in any file) is invalid, then inserts the clean rows.
class QuizzesImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array<string, mixed>> validated quiz attribute rows ready for insert */
    private array $rows = [];

    /** @var array<int, string> human-readable errors, one per bad row */
    private array $errors = [];

    public function __construct(private Assessment $assessment, private string $fileLabel = 'file') {}

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->errors[] = "{$this->fileLabel}: the file is empty — no header row or data. Use the provided template.";

            return;
        }

        // Right file type, wrong contents: verify the template's required headers are present.
        // WithHeadingRow turns the header row into array keys; a non-template sheet won't have them.
        $required = ['question', 'quiz_type', 'correct_answer'];
        $headers = array_keys($rows->first()->toArray());
        $missing = array_diff($required, $headers);
        if ($missing !== []) {
            $this->errors[] = "{$this->fileLabel}: wrong format inside the file — missing required column(s): "
                .implode(', ', $missing).'. Download and use the template.';

            return;
        }

        // +1 for the header row that WithHeadingRow consumed, +1 to make it 1-based for humans.
        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $question = trim((string) ($row['question'] ?? ''));
            $type = trim((string) ($row['quiz_type'] ?? ''));
            $correct = trim((string) ($row['correct_answer'] ?? ''));

            // A fully blank row is an error too (no silent skipping).
            if ($question === '' && $type === '' && $correct === '') {
                $this->errors[] = "{$this->fileLabel} row {$line}: the row is blank.";
                continue;
            }
            if ($question === '') {
                $this->errors[] = "{$this->fileLabel} row {$line}: question is missing.";
                continue;
            }
            if (! in_array($type, ['multiple_choice', 'identification'], true)) {
                $this->errors[] = "{$this->fileLabel} row {$line}: quiz_type must be 'multiple_choice' or 'identification'.";
                continue;
            }
            if ($correct === '') {
                $this->errors[] = "{$this->fileLabel} row {$line}: correct_answer is missing.";
                continue;
            }

            $choices = null;
            if ($type === 'multiple_choice') {
                $choices = array_filter([
                    'A' => $row['choice_a'] ?? null, 'B' => $row['choice_b'] ?? null,
                    'C' => $row['choice_c'] ?? null, 'D' => $row['choice_d'] ?? null,
                ], fn ($c) => $c !== null && trim((string) $c) !== '');
                if (count($choices) < 2) {
                    $this->errors[] = "{$this->fileLabel} row {$line}: multiple choice needs at least 2 choices.";
                    continue;
                }
                if (! array_key_exists($correct, $choices)) {
                    $this->errors[] = "{$this->fileLabel} row {$line}: correct_answer '{$correct}' must be one of the filled choice letters (A–D).";
                    continue;
                }
            }

            $this->rows[] = [
                'assessment_id' => $this->assessment->id,
                'subject_id' => $this->assessment->subject_id,
                'section_id' => $this->assessment->section_id,
                'educator_id' => $this->assessment->educator_id,
                'question' => $question,
                'quiz_type' => $type,
                'choices' => $choices,
                'correct_answer' => $correct,
            ];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function validRows(): array
    {
        return $this->rows;
    }

    /** @return array<int, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
