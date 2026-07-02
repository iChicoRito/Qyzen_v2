@php
    $q = $quiz ?? null;
    $choices = old('choices', $q?->choices ?? ['A' => '', 'B' => '', 'C' => '', 'D' => '']);
    $selectedAssessment = old('assessment_id', $q?->assessment_id ?? ($selectedAssessment ?? null));
    $type = old('quiz_type', $q?->quiz_type ?? 'multiple_choice');
    $correct = old('correct_answer', $q?->correct_answer);
    // Identification answers: correct_answer may be a plain string or a JSON array of accepted answers.
    $idAnswers = old('answers');
    if ($idAnswers === null) {
        $decoded = is_string($correct) ? json_decode($correct, true) : null;
        $idAnswers = is_array($decoded) ? $decoded : ($correct !== null && $correct !== '' ? [$correct] : ['']);
    }
    $isMc = $type === 'multiple_choice';
    $editing = $q !== null;
    // Rich option label: "CODE — SUBJECT (SECTION)".
    $label = fn ($a) => trim($a->assessment_code
        . ($a->subject ? ' — ' . $a->subject->subject_name : '')
        . ($a->section ? ' (' . $a->section->section_name . ')' : ''));
    $selectedIds = old('assessment_ids', $selectedAssessment ? [$selectedAssessment] : []);
@endphp
<div class="flex flex-col gap-5">
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Choose Assessment</label>
        @if ($editing)
            {{-- Edit acts on one question → single searchable select. --}}
            <select name="assessment_id" class="kt-select w-full"
                    data-kt-select="true" data-kt-select-enable-search="true"
                    data-kt-select-search-placeholder="Search assessments…">
                @foreach ($assessments as $a)<option value="{{ $a->id }}" @selected($selectedAssessment==$a->id)>{{ $label($a) }}</option>@endforeach
            </select>
        @else
            {{-- Create: add the same question to one or more assessments (searchable multi-select). --}}
            <select name="assessment_ids[]" class="kt-select w-full" multiple
                    data-kt-select="true" data-kt-select-multiple="true" data-kt-select-enable-search="true"
                    data-kt-select-placeholder="Select assessments…"
                    data-kt-select-search-placeholder="Search assessments…"
                    data-count-summary="assessment">
                @foreach ($assessments as $a)<option value="{{ $a->id }}" @selected(in_array($a->id, $selectedIds))>{{ $label($a) }}</option>@endforeach
            </select>
        @endif
        @error('assessment_id')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
        @error('assessment_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>

    {{-- Quiz Type: full-width select drives which section shows (delegated JS on [data-quiz-type]). --}}
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Quiz Type</label>
        <select name="quiz_type" class="kt-select w-full" data-quiz-type>
            <option value="multiple_choice" @selected($isMc)>Multiple Choice</option>
            <option value="identification" @selected(! $isMc)>Identification</option>
        </select>
    </div>

    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Question</label>
        <textarea name="question" class="kt-textarea" rows="2" placeholder="Enter the quiz question" required>{{ old('question', $q?->question) }}</textarea>
        @error('question')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>

    {{-- Multiple choice: text per choice + a radio to mark the single correct key. --}}
    <div class="flex flex-col gap-2" data-mc-choices @unless($isMc) hidden @endunless>
        <label class="kt-form-label">Choices</label>
        @foreach (['A','B','C','D'] as $key)
            <label class="flex items-center gap-3 rounded-lg border border-border p-3 cursor-pointer">
                <input type="radio" name="correct_answer" value="{{ $key }}" class="kt-radio shrink-0" @checked($correct===$key)>
                <span class="text-sm font-medium text-mono w-16 shrink-0">Choice {{ $key }}</span>
                <input name="choices[{{ $key }}]" class="kt-input" value="{{ $choices[$key] ?? '' }}" placeholder="Enter choice here">
            </label>
        @endforeach
        @error('choices')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
        @error('correct_answer')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>

    {{-- Identification: one or more accepted answers (repeater). --}}
    <div class="flex flex-col gap-2" data-id-answers @if($isMc) hidden @endif>
        <div class="flex items-center justify-between gap-2">
            <div class="flex flex-col">
                <label class="kt-form-label">Correct Answers</label>
                <span class="text-xs text-secondary-foreground">Add one or more accepted answers.</span>
            </div>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-outline shrink-0" data-repeater-add="#id_answer_rows">
                <i class="ki-filled ki-plus"></i> Add Answer
            </button>
        </div>
        <div id="id_answer_rows" class="flex flex-col gap-2">
            @foreach ($idAnswers as $ans)
                <div class="flex items-center gap-2" data-repeater-row>
                    <input name="answers[]" class="kt-input" value="{{ $ans }}" placeholder="Enter correct answer">
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive shrink-0" data-repeater-remove title="Remove answer">
                        <i class="ki-filled ki-trash"></i>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</div>
