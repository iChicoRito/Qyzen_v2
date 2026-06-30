<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\Score;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

// G8: one assessment's scores → a worksheet. Best/latest per student.
class ScoresExport implements FromCollection, WithHeadings
{
    public function __construct(private Assessment $assessment) {}

    public function collection()
    {
        return Score::where('assessment_id', $this->assessment->id)
            ->where('educator_id', $this->assessment->educator_id)
            ->with('student:id,given_name,surname,user_id')
            ->get()
            ->map(fn (Score $s) => [
                $s->student?->user_id,
                $s->student?->name,
                $s->score,
                $s->total_questions,
                $s->is_passed ? 'Passed' : 'Failed',
                $s->status,
                optional($s->submitted_at)->toDateTimeString(),
            ]);
    }

    public function headings(): array
    {
        return ['Student ID', 'Name', 'Score', 'Total', 'Result', 'Status', 'Submitted At'];
    }
}
