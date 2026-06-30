@php
    $q = $quiz ?? null;
    $choices = old('choices', $q?->choices ?? ['A' => '', 'B' => '', 'C' => '', 'D' => '']);
    $selectedAssessment = old('assessment_id', $q?->assessment_id ?? ($selectedAssessment ?? null));
@endphp
<div class="row g-4">
    <div class="col-12"><label class="form-label required">Assessment</label>
        <select name="assessment_id" class="form-select">
            @foreach ($assessments as $a)<option value="{{ $a->id }}" @selected($selectedAssessment==$a->id)>{{ $a->assessment_code }}</option>@endforeach
        </select></div>
    <div class="col-12"><label class="form-label required">Question</label>
        <textarea name="question" class="form-control" rows="2">{{ old('question', $q?->question) }}</textarea></div>
    <div class="col-md-4"><label class="form-label required">Type</label>
        <select name="quiz_type" class="form-select" id="quiz_type" onchange="toggleChoices()">
            <option value="multiple_choice" @selected(old('quiz_type', $q?->quiz_type)==='multiple_choice')>Multiple choice</option>
            <option value="identification" @selected(old('quiz_type', $q?->quiz_type)==='identification')>Identification</option>
        </select></div>

    <div class="col-12" id="mc_choices">
        <label class="form-label">Choices</label>
        @foreach (['A','B','C','D'] as $key)
            <div class="input-group mb-2">
                <span class="input-group-text">{{ $key }}</span>
                <input name="choices[{{ $key }}]" class="form-control" value="{{ $choices[$key] ?? '' }}">
            </div>
        @endforeach
    </div>

    <div class="col-md-6"><label class="form-label required">Correct Answer</label>
        <input name="correct_answer" class="form-control" value="{{ old('correct_answer', $q?->correct_answer) }}"
            placeholder="MC: choice key (A–D). Identification: the answer text.">
        <div class="form-text">For multiple-choice, enter the key (A, B, C or D).</div></div>
</div>
@push('scripts')
<script>
    function toggleChoices() {
        document.getElementById('mc_choices').style.display =
            document.getElementById('quiz_type').value === 'multiple_choice' ? '' : 'none';
    }
    toggleChoices();
</script>
@endpush
