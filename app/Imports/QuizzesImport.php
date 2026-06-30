<?php

namespace App\Imports;

use App\Models\Assessment;
use App\Models\Quiz;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

// G6: bulk question upload for one assessment.
// Columns: question, quiz_type, choice_a, choice_b, choice_c, choice_d, correct_answer.
// For identification, leave choice_* blank and put the answer in correct_answer.
class QuizzesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private int $created = 0;
    private int $skipped = 0;

    public function __construct(private Assessment $assessment) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $question = trim((string) ($row['question'] ?? ''));
            $type = trim((string) ($row['quiz_type'] ?? ''));
            $correct = trim((string) ($row['correct_answer'] ?? ''));

            if ($question === '' || ! in_array($type, ['multiple_choice', 'identification'], true) || $correct === '') {
                $this->skipped++;
                continue;
            }

            $choices = null;
            if ($type === 'multiple_choice') {
                $choices = array_filter([
                    'A' => $row['choice_a'] ?? null, 'B' => $row['choice_b'] ?? null,
                    'C' => $row['choice_c'] ?? null, 'D' => $row['choice_d'] ?? null,
                ], fn ($c) => $c !== null && $c !== '');
                if (count($choices) < 2 || ! array_key_exists($correct, $choices)) {
                    $this->skipped++;
                    continue;
                }
            }

            Quiz::create([
                'assessment_id' => $this->assessment->id,
                'subject_id' => $this->assessment->subject_id,
                'section_id' => $this->assessment->section_id,
                'educator_id' => $this->assessment->educator_id,
                'question' => $question,
                'quiz_type' => $type,
                'choices' => $choices,
                'correct_answer' => $correct,
            ]);
            $this->created++;
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function skippedCount(): int
    {
        return $this->skipped;
    }
}
