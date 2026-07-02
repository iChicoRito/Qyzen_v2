@php
    $q = $quiz ?? null;
    $choices = old('choices', $q?->choices ?? ['A' => '', 'B' => '', 'C' => '', 'D' => '']);
    $selectedAssessment = old('assessment_id', $q?->assessment_id ?? ($selectedAssessment ?? null));
    $type = old('quiz_type', $q?->quiz_type ?? 'multiple_choice');
@endphp
<div class="grid grid-cols-2 gap-5">
    <div class="flex flex-col gap-1 col-span-2">
        <label class="kt-form-label">Assessment</label>
        <select name="assessment_id" class="kt-select">
            @foreach ($assessments as $a)<option value="{{ $a->id }}" @selected($selectedAssessment==$a->id)>{{ $a->assessment_code }}</option>@endforeach
        </select>
        @error('assessment_id')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1 col-span-2">
        <label class="kt-form-label">Question</label>
        <textarea name="question" class="kt-textarea" rows="2">{{ old('question', $q?->question) }}</textarea>
        @error('question')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Type</label>
        <select name="quiz_type" class="kt-select" data-quiz-type>
            <option value="multiple_choice" @selected($type==='multiple_choice')>Multiple choice</option>
            <option value="identification" @selected($type==='identification')>Identification</option>
        </select>
    </div>

    <div class="flex flex-col gap-1 col-span-2" data-mc-choices @if($type!=='multiple_choice') hidden @endif>
        <label class="kt-form-label">Choices</label>
        <div class="flex flex-col gap-2">
            @foreach (['A','B','C','D'] as $key)
                <div class="flex items-center gap-2">
                    <span class="kt-badge kt-badge-outline w-8 justify-center">{{ $key }}</span>
                    <input name="choices[{{ $key }}]" class="kt-input" value="{{ $choices[$key] ?? '' }}">
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col gap-1 col-span-2">
        <label class="kt-form-label">Correct Answer</label>
        <input name="correct_answer" class="kt-input" value="{{ old('correct_answer', $q?->correct_answer) }}"
            placeholder="MC: choice key (A–D). Identification: the answer text.">
        <span class="text-xs text-secondary-foreground">For multiple-choice, enter the key (A, B, C or D).</span>
        @error('correct_answer')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
</div>
