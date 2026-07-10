{{-- Task 51: question pool config for one assessment — pick eligible bank questions + draw
     size N. Full page (not a modal fragment): the checkbox-card grid needs room to breathe. --}}
@extends('educator.layout')
@section('title', 'Question Pool')
@section('heading', 'Question Pool — ' . $assessment->assessment_code)
@section('content')
    @include('admin._status')
    <div class="kt-card">
        <div class="kt-card-content p-5">
            <form method="POST" action="{{ route('educator.assessments.pool.update', $assessment) }}" class="flex flex-col gap-5">
                @csrf
                @method('PUT')

                @if ($bankQuestions->isEmpty())
                    <div class="kt-alert kt-alert-warning">
                        No bank questions yet.
                        <a href="{{ route('educator.quizzes.create', ['subject_id' => $assessment->subject_id]) }}" class="kt-link">Add one</a>.
                    </div>
                @endif

                @php $selectedIds = old('eligible_quiz_ids', $eligibleIds); @endphp
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between gap-2">
                        <label class="kt-form-label">Eligible Questions</label>
                        @if ($bankQuestions->isNotEmpty())
                            <div class="flex gap-2">
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-pool-select-all>Select All</button>
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-pool-select-none>Select None</button>
                            </div>
                        @endif
                    </div>
                    @if ($bankQuestions->total() > 8)
                        <div class="flex flex-wrap items-center gap-2">
                            <label class="kt-input grow min-w-52">
                                <i class="ki-filled ki-magnifier"></i>
                                <input type="text" placeholder="Search questions in this bank…" data-pool-search autocomplete="off">
                            </label>
                            <select class="kt-select w-40" data-pool-filter-type>
                                <option value="">All types</option>
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="identification">Identification</option>
                            </select>
                            <select class="kt-select w-56" data-pool-filter-batch>
                                <option value="">All batches</option>
                                @foreach ($batches as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    <span class="text-xs text-secondary-foreground" data-pool-summary>
                        {{ count($selectedIds) }} of {{ $bankQuestions->total() }} selected
                    </span>
                    @php $pageIds = $bankQuestions->getCollection()->pluck('id')->all(); @endphp
                    @foreach (array_diff($selectedIds, $pageIds) as $selectedId)
                        <input type="hidden" name="eligible_quiz_ids[]" value="{{ $selectedId }}">
                    @endforeach
                    <div class="grid grid-cols-1 gap-2.5 max-h-96 overflow-y-auto kt-scrollable-y">
                        @foreach ($bankQuestions as $q)
                            @php
                                $usedIn = $q->eligibleAssessments->where('id', '!=', $assessment->id)->pluck('assessment_code');
                                $desc = $q->quiz_type === 'multiple_choice' ? 'Multiple Choice' : 'Identification';
                                $origin = trim(($q->subject?->subject_code ?? '').' — '.($q->subject?->subject_name ?? ''));
                                if ($q->subject?->section) {
                                    $origin .= ' ('.$q->subject->section->section_name.')';
                                }
                                $desc .= $origin !== '' ? ' · '.$origin : '';
                                $desc .= $usedIn->isNotEmpty() ? ' · Also used in: '.$usedIn->join(', ') : ' · Not used elsewhere';
                            @endphp
                            <x-checkbox-card
                                name="eligible_quiz_ids[]"
                                :value="$q->id"
                                :title="\Illuminate\Support\Str::limit($q->question, 80)"
                                :desc="$desc"
                                :checked="in_array($q->id, $selectedIds)"
                                data-pool-option
                                data-pool-question-text="{{ mb_strtolower($q->question) }}"
                                data-pool-question-type="{{ $q->quiz_type }}"
                                data-pool-question-batch="{{ $q->batch_label }}" />
                        @endforeach
                        <span class="text-xs text-secondary-foreground px-1 hidden" data-pool-no-match>No questions match your filters.</span>
                    </div>
                    <div class="flex justify-center pt-2" data-pool-pagination>
                        {{ $bankQuestions->links() }}
                    </div>
                    @error('eligible_quiz_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
                </div>

                <div class="flex flex-col gap-1 max-w-xs">
                    <label class="kt-form-label">Draw Size (N)</label>
                    <input type="number" name="pool_size" class="kt-input" min="0"
                           value="{{ old('pool_size', $assessment->pool_size) }}" required>
                    <span class="text-xs text-secondary-foreground">How many questions each student randomly gets per attempt.</span>
                    @error('pool_size')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
                </div>

                <div class="flex gap-2">
                    <button class="kt-btn kt-btn-primary">Save Pool</button>
                    <a href="{{ route('educator.assessments.index') }}" class="kt-btn kt-btn-outline">Back</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        (function () {
            var summary = document.querySelector('[data-pool-summary]');
            var boxes = document.querySelectorAll('input[data-pool-option]');
            var total = boxes.length;
            if (!summary) return;

            function updateSummary() {
                var checked = document.querySelectorAll('input[data-pool-option]:checked').length;
                summary.textContent = checked + ' of ' + total + ' selected';
            }

            boxes.forEach(function (box) {
                box.addEventListener('change', updateSummary);
            });

            // Select All / Select None only affect currently VISIBLE questions (respects an
            // active search), so narrowing the search then clicking Select All ticks just the matches.
            var selectAll = document.querySelector('[data-pool-select-all]');
            var selectNone = document.querySelector('[data-pool-select-none]');
            if (selectAll) {
                selectAll.addEventListener('click', function () {
                    boxes.forEach(function (box) { if (box.dataset.poolHidden !== '1') box.checked = true; });
                    updateSummary();
                });
            }
            if (selectNone) {
                selectNone.addEventListener('click', function () {
                    boxes.forEach(function (box) { if (box.dataset.poolHidden !== '1') box.checked = false; });
                    updateSummary();
                });
            }

            // Search + Type + Batch all narrow the same list together (every active
            // criterion must match). Select All/None only ever touch what's currently visible.
            var search = document.querySelector('[data-pool-search]');
            var typeFilter = document.querySelector('[data-pool-filter-type]');
            var batchFilter = document.querySelector('[data-pool-filter-batch]');
            var noMatch = document.querySelector('[data-pool-no-match]');

            function applyFilters() {
                var term = search ? search.value.trim().toLowerCase() : '';
                var type = typeFilter ? typeFilter.value : '';
                var batch = batchFilter ? batchFilter.value : '';
                var visible = 0;

                boxes.forEach(function (box) {
                    var label = box.closest('label');
                    var match = (!term || (box.dataset.poolQuestionText || '').indexOf(term) !== -1)
                        && (!type || box.dataset.poolQuestionType === type)
                        && (!batch || box.dataset.poolQuestionBatch === batch);
                    if (label) label.classList.toggle('hidden', !match);
                    box.dataset.poolHidden = match ? '' : '1';
                    if (match) visible++;
                });
                if (noMatch) noMatch.classList.toggle('hidden', visible !== 0);
            }

            if (search) search.addEventListener('input', applyFilters);
            if (typeFilter) typeFilter.addEventListener('change', applyFilters);
            if (batchFilter) batchFilter.addEventListener('change', applyFilters);

            // Carry unsaved selections through pagination so changing pages does not discard them.
            var pagination = document.querySelector('[data-pool-pagination]');
            if (pagination) {
                pagination.addEventListener('click', function (event) {
                    var link = event.target.closest('a[href]');
                    if (!link) return;
                    var url = new URL(link.href, window.location.origin);
                    url.searchParams.delete('selected[]');
                    document.querySelectorAll('input[name="eligible_quiz_ids[]"]:checked').forEach(function (box) {
                        url.searchParams.append('selected[]', box.value);
                    });
                    link.href = url.toString();
                });
            }
        })();
    </script>
    @endpush
@endsection
