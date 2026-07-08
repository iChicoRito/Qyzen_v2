<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// Task 51: configure an assessment's question pool — the eligible bank questions + how many (N)
// to randomly draw per attempt. Hard-rejects N > count(eligible) rather than silently clamping,
// matching the all-or-nothing validation style used by bulk Excel upload elsewhere in this app.
class UpdateQuestionPoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subjectId = $this->route('assessment')->subject_id;

        return [
            'eligible_quiz_ids' => ['array'],
            'eligible_quiz_ids.*' => [
                Rule::exists('tbl_quizzes', 'id')
                    ->where('educator_id', Auth::id())
                    ->where('subject_id', $subjectId),
            ],
            'pool_size' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $eligible = count(array_filter((array) $this->input('eligible_quiz_ids', [])));
            $poolSize = (int) $this->input('pool_size', 0);

            if ($poolSize > $eligible) {
                $v->errors()->add('pool_size', "Draw size ({$poolSize}) can't exceed the number of eligible questions selected ({$eligible}).");
            }
        });
    }
}
