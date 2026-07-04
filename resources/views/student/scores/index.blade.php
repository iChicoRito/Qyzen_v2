{{-- H8 / Task 23: Scores history — one row per submitted attempt (own only), newest first.
     Search/sort/paginate + dropdown filters come from <x-data-table> (KTDataTable). Reset Filters
     and column show/hide are the only bespoke controls. "View Score" opens task 22's scores/show
     as a look-back-only modal fragment (?modal=1). --}}
@extends('student.layout')
@section('title', 'My Scores')
@section('heading', 'My Scores')
@section('content')
    @php
        // Filter dropdown choices are built from the student's own rows (spec Phase 4).
        $fAssessments = $scores->map->assessment->filter()->unique('id')->sortBy('assessment_code');
        $fSubjects = $scores->map(fn ($s) => optional($s->assessment)->subject)->filter()->unique('id')->sortBy('subject_name');
        $fTerms = $scores->map(fn ($s) => optional($s->assessment)->academicTerm)->filter()->unique('id')->sortBy('term_name');
    @endphp

    <x-data-table id="student_scores_table" search-placeholder="Search scores">
        <x-slot:filters>
            <select data-filter="assessment" class="kt-select w-40">
                <option value="">All assessments</option>
                @foreach ($fAssessments as $a)
                    <option value="{{ $a->id }}">{{ $a->assessment_code }}</option>
                @endforeach
            </select>
            <select data-filter="subject" class="kt-select w-40">
                <option value="">All subjects</option>
                @foreach ($fSubjects as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->subject_name }}</option>
                @endforeach
            </select>
            <select data-filter="term" class="kt-select w-36">
                <option value="">All terms</option>
                @foreach ($fTerms as $t)
                    <option value="{{ $t->id }}">{{ $t->term_name }}</option>
                @endforeach
            </select>
            <select data-filter="result" class="kt-select w-32">
                <option value="">All statuses</option>
                <option value="passed">Passed</option>
                <option value="failed">Failed</option>
            </select>
            <button type="button" class="kt-btn kt-btn-outline" data-scores-reset disabled><i class="ki-filled ki-arrows-circle"></i> Reset</button>
        </x-slot:filters>
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">Assessment</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[170px]"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Academic Term</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Score</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">Attempts</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Percentage</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Submitted At</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($scores as $s)
            @php
                $a = $s->assessment;
                $pct = $s->total_questions ? round($s->score / $s->total_questions * 100) : 0;
                $best = $bestByAssessment[$s->assessment_id] ?? $s->score;
            @endphp
            <tr>
                <td class="text-mono font-medium text-sm">
                    {{-- Hidden filter tokens live inside a cell so they survive KTDataTable's tbody rebuild. --}}
                    <span data-filter-value="assessment" data-filter-key="{{ $s->assessment_id }}" hidden></span>
                    <span data-filter-value="subject" data-filter-key="{{ optional($a)->subject_id }}" hidden></span>
                    <span data-filter-value="term" data-filter-key="{{ optional($a)->term }}" hidden></span>
                    <span data-filter-value="result" data-filter-key="{{ $s->is_passed ? 'passed' : 'failed' }}" hidden></span>
                    {{ optional($a)->assessment_code ?? '—' }}
                </td>
                <td>
                    <div class="flex flex-col items-start gap-1">
                        <span class="text-sm text-mono">{{ optional(optional($a)->subject)->subject_name ?? '—' }}</span>
                        <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-primary">{{ optional(optional($a)->section)->section_name ?? '—' }}</span>
                    </div>
                </td>
                <td class="text-secondary-foreground">{{ optional(optional($a)->academicTerm)->term_name ?? '—' }}</td>
                <td>
                    <div class="flex flex-col">
                        <span class="text-mono">{{ $s->score }}/{{ $s->total_questions }}</span>
                        <span class="text-xs text-secondary-foreground">Best {{ $best }}/{{ $s->total_questions }}</span>
                    </div>
                </td>
                <td class="text-mono">{{ $attemptsByAssessment[$s->assessment_id] ?? 1 }}</td>
                <td class="text-mono">{{ $pct }}%</td>
                <td>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $s->is_passed ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $s->is_passed ? 'PASSED' : 'FAILED' }}
                    </span>
                </td>
                <td class="text-secondary-foreground">{{ optional($s->submitted_at)->format('M d, Y g:i A') ?? 'Not submitted' }}</td>
                <td class="text-center">
                    <x-table-actions :view-modal="route('student.scores.show', $s)" view-modal-title="Score details" />
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-secondary-foreground py-5">No scores yet.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="760px" />

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        (function () {
            var wrap = document.getElementById('student_scores_table');
            if (!wrap) return;
            var card = wrap.closest('.kt-card');
            var searchInput = card ? card.querySelector('input[data-kt-datatable-search]') : null;
            var selects = card ? card.querySelectorAll('select[data-filter]') : [];
            var resetBtn = card ? card.querySelector('[data-scores-reset]') : null;

            // ---- Reset Filters (enabled only when something is applied) ----
            function anyActive() {
                if (searchInput && searchInput.value.trim() !== '') return true;
                return Array.prototype.some.call(selects, function (s) { return s.value !== ''; });
            }
            function refreshReset() { if (resetBtn) resetBtn.disabled = !anyActive(); }
            if (searchInput) ['input', 'change'].forEach(function (e) { searchInput.addEventListener(e, refreshReset); });
            selects.forEach(function (s) { s.addEventListener('change', refreshReset); });
            if (resetBtn) resetBtn.addEventListener('click', function () {
                if (searchInput) { searchInput.value = ''; searchInput.dispatchEvent(new Event('input', { bubbles: true })); }
                selects.forEach(function (s) { s.value = ''; s.dispatchEvent(new Event('change', { bubbles: true })); });
                refreshReset();
            });
        })();
    </script>
    @endpush
@endsection
