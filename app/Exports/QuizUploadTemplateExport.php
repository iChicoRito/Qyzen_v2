<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

// G6: headers-only template for bulk quiz upload.
class QuizUploadTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['What is 2+2?', 'multiple_choice', '3', '4', '5', '6', 'B'],
            ['Capital of France?', 'identification', '', '', '', '', 'Paris'],
        ];
    }

    public function headings(): array
    {
        return ['question', 'quiz_type', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct_answer'];
    }
}
