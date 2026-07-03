@extends('educator.layout')
@section('title', 'Quizzes')
@section('heading', 'Quizzes (Questions)')
@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-kt-modal-toggle="#kt_quiz_upload">
        <i class="ki-filled ki-cloud-upload"></i> Upload File
    </button>
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('educator.quizzes.create') }}" data-modal-target="#form_modal" data-modal-title="Add question">Add question</button>
@endsection
@section('content')
    @include('admin._status')

    {{-- Client-side filters: hide accordion items whose data-* token doesn't match the picked
         value. Options are the distinct codes/subjects/sections across the visible assessments. --}}
    @if ($assessments->isNotEmpty())
        @php
            $codes = $assessments->pluck('assessment_code')->unique()->sort()->values();
            $subjects = $assessments->pluck('subject')->filter()->unique('id')->sortBy('subject_code')->values();
            $sections = $assessments->pluck('section')->filter()->unique('id')->sortBy('section_name')->values();
        @endphp
        <div id="quiz_filters" class="flex flex-nowrap items-center gap-2.5 mb-4">
            <select class="kt-select min-w-0 flex-1" data-quiz-filter="code">
                <option value="">All assessment codes</option>
                @foreach ($codes as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
            </select>
            <select class="kt-select min-w-0 flex-1" data-quiz-filter="subject">
                <option value="">All subjects</option>
                @foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->subject_code }} — {{ $s->subject_name }}</option>@endforeach
            </select>
            <select class="kt-select min-w-0 flex-1" data-quiz-filter="section">
                <option value="">All sections</option>
                @foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->section_name }}</option>@endforeach
            </select>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-outline shrink-0" data-quiz-filter-reset>Reset</button>
        </div>
    @endif

    {{-- KTUI Outline Style accordion (kt-accordion-outline): each item is a bordered card;
         outline CSS handles item borders + toggle/wrapper padding. --}}
    <div class="kt-accordion kt-accordion-outline" data-kt-accordion="true">
        @forelse ($assessments as $a)
            {{-- KTUI finds the toggle via a DIRECT-CHILD lookup, so the toggle button must be a
                 direct child of the item (no wrapper div around it). --}}
            <div class="kt-accordion-item" data-kt-accordion-item="true"
                 data-code="{{ $a->assessment_code }}" data-subject="{{ $a->subject_id }}" data-section="{{ $a->section_id }}">
                <button type="button" class="kt-accordion-toggle w-full flex items-center gap-3"
                        data-kt-accordion-toggle="#quiz_acc_{{ $a->id }}" aria-controls="quiz_acc_{{ $a->id }}">
                    <span class="flex flex-col items-start gap-0.5 min-w-0">
                        <span class="text-sm font-medium text-mono truncate">{{ $a->assessment_code }}</span>
                        <span class="text-xs text-secondary-foreground">
                            {{ optional($a->subject)->subject_name }}
                            @if ($a->section) · {{ $a->section->section_name }} @endif
                            · {{ $a->quizzes_count }} questions ({{ $a->multiple_choice_count }} MC / {{ $a->identification_count }} ID)
                        </span>
                    </span>
                    <span class="ms-auto kt-accordion-active:hidden inline-flex shrink-0"><i class="ki-filled ki-plus text-muted-foreground text-sm"></i></span>
                    <span class="ms-auto kt-accordion-active:inline-flex hidden shrink-0"><i class="ki-filled ki-minus text-muted-foreground text-sm"></i></span>
                </button>
                <div class="kt-accordion-content hidden" id="quiz_acc_{{ $a->id }}">
                    <div class="kt-accordion-wrapper flex flex-col gap-2">
                        @forelse ($a->quizzes as $i => $q)
                                <div class="rounded-lg border border-border p-3 flex items-start justify-between gap-3">
                                    <div class="flex flex-col gap-1 min-w-0">
                                        <span class="text-sm text-mono">{{ $i + 1 }}. {{ $q->question }}</span>
                                        <span class="text-xs text-secondary-foreground">
                                            {{ $q->quiz_type === 'multiple_choice' ? 'Multiple Choice' : 'Identification' }}
                                            · Answer:
                                            @if ($q->quiz_type === 'multiple_choice')
                                                {{ $q->correct_answer }}. {{ $q->choices[$q->correct_answer] ?? '' }}
                                            @else
                                                @php $ans = json_decode($q->correct_answer, true); @endphp
                                                {{ is_array($ans) ? implode(', ', $ans) : $q->correct_answer }}
                                            @endif
                                        </span>
                                    </div>
                                    <x-table-actions
                                        :edit-modal="route('educator.quizzes.edit', $q)" edit-modal-title="Edit question"
                                        :delete="route('educator.quizzes.destroy', $q)" confirm="Delete this question?" />
                                </div>
                            @empty
                                <span class="text-xs text-secondary-foreground px-1">No questions yet. Use “Add question” or Bulk upload.</span>
                            @endforelse
                            @if ($a->quizzes_count > 0)
                                <form method="POST" action="{{ route('educator.quizzes.destroy-for-assessment', $a) }}" class="pt-1">
                                    @csrf @method('DELETE')
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-outline text-destructive"
                                            data-confirm="Delete ALL questions for this assessment? This cannot be undone." data-confirm-title="Delete all?">
                                        <i class="ki-filled ki-trash"></i> Delete all questions
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-sm text-secondary-foreground">No assessments.</div>
            @endforelse
    </div>
    <div id="quiz_no_match" class="hidden p-6 text-center text-sm text-secondary-foreground">No assessments match the selected filters.</div>

    <x-modal id="form_modal" width="640px" />

    @php
        // Rich option label: "CODE — SUBJECT (SECTION)" (same as the manual quiz picker).
        $label = fn ($a) => trim($a->assessment_code
            . ($a->subject ? ' — ' . $a->subject->subject_name : '')
            . ($a->section ? ' (' . $a->section->section_name . ')' : ''));
    @endphp
    {{-- Bulk upload: static form in the x-modal slot (same pattern as admin permissions' perm_add_modal). --}}
    <x-modal id="kt_quiz_upload" title="Upload Quiz Files" width="640px">
        <form method="POST" action="{{ route('educator.quizzes.upload') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
            @csrf
            {{-- Template --}}
            <div class="flex items-center justify-between gap-3">
                <label class="kt-form-label">Template</label>
                <a href="{{ route('educator.quizzes.upload.template') }}" class="kt-btn kt-btn-sm kt-btn-outline shrink-0">
                    <i class="ki-filled ki-cloud-download"></i> Download
                </a>
            </div>

            {{-- Target Assessment --}}
            <div class="flex flex-col gap-1">
                <label class="kt-form-label">Target Assessment</label>
                <select name="assessment_ids[]" class="kt-select w-full" multiple required
                        data-kt-select="true" data-kt-select-multiple="true" data-kt-select-enable-search="true"
                        data-kt-select-placeholder="Select one or more assessments"
                        data-kt-select-search-placeholder="Search assessments…"
                        data-count-summary="assessment" data-target-assessments>
                    @foreach ($assessments as $a)<option value="{{ $a->id }}">{{ $label($a) }}</option>@endforeach
                </select>
                <span class="text-xs text-secondary-foreground" data-selected-list data-empty="No target assessments selected.">No target assessments selected.</span>
            </div>

            {{-- Files --}}
            <div class="flex flex-col gap-1">
                <label class="kt-form-label">Quiz Files</label>
                <input id="quiz_files" type="file" name="files[]" multiple accept=".xlsx,.xls,.csv" class="kt-input" required>
                {{-- Queued files render as Metronic "Recent Uploads" rows (see _modal-loader). --}}
                <div class="grid gap-2.5 mt-1" data-file-list data-empty="No files selected.">
                    <span class="text-xs text-secondary-foreground">No files selected.</span>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-1 sticky bottom-0 bg-background border-t border-border py-3">
                <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-cloud-upload"></i> Upload Quizzes
                </button>
            </div>
        </form>
    </x-modal>

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        (function () {
            var bar = document.getElementById('quiz_filters');
            if (!bar) return;
            var acc = document.querySelector('[data-kt-accordion]');
            var selects = bar.querySelectorAll('select[data-quiz-filter]');
            var items = acc ? acc.querySelectorAll('.kt-accordion-item') : [];
            var noMatch = document.getElementById('quiz_no_match');

            function apply() {
                var visible = 0;
                items.forEach(function (item) {
                    var show = true;
                    selects.forEach(function (sel) {
                        if (sel.value && item.getAttribute('data-' + sel.dataset.quizFilter) !== sel.value) show = false;
                    });
                    item.classList.toggle('hidden', !show);
                    if (show) visible++;
                });
                if (noMatch) noMatch.classList.toggle('hidden', visible !== 0);
            }

            selects.forEach(function (s) { s.addEventListener('change', apply); });
            bar.querySelector('[data-quiz-filter-reset]').addEventListener('click', function () {
                selects.forEach(function (s) { s.value = ''; });
                apply();
            });
        })();
    </script>
    @endpush
@endsection
