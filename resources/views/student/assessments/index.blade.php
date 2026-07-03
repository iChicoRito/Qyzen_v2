{{-- H2 / Task 17: assessment list as a card grid (Upcoming-Events motif) + per-card
     confirmation modal. Enrolled-only, availability badge, can-take gate. --}}
@extends('student.layout')
@section('title', 'Assessments')
@section('heading', 'Assessments')
@section('content')
    @include('admin._status')

    {{-- Filter bar: client-side search + availability (parity with the old data-table). --}}
    <div id="assessment_filters" class="flex flex-wrap items-center gap-2.5 mb-5">
        <div class="w-64 max-w-full">
            <label class="kt-input">
                <i class="ki-filled ki-magnifier"></i>
                <input type="text" data-assessment-search placeholder="Search assessments" />
            </label>
        </div>
        <select class="kt-select w-48" data-assessment-availability>
            <option value="">All statuses</option>
            <option value="Available">Available</option>
            <option value="Reopened">Reopened</option>
            <option value="Starts Soon">Starts Soon</option>
            <option value="Not Ready Yet">Not Ready Yet</option>
            <option value="No Longer Available">No Longer Available</option>
        </select>
    </div>

    <div id="assessment_grid" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($assessments as $a)
            <div class="kt-card assessment-card"
                 data-availability="{{ $a->status_label }}"
                 data-search="{{ \Illuminate\Support\Str::lower($a->assessment_code.' '.optional($a->subject)->subject_code.' '.optional($a->subject)->subject_name.' '.optional($a->section)->section_name) }}">
                <div class="kt-card-content p-5 flex flex-col gap-4 h-full">
                    <div class="flex items-start gap-4">
                        {{-- Date chip --}}
                        <div class="border border-border rounded-lg shrink-0 overflow-hidden text-center">
                            <div class="bg-accent px-3 py-1">
                                <span class="text-xs font-medium text-secondary-foreground uppercase">{{ $a->start_date?->format('M') ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-center size-12">
                                <span class="font-medium text-mono text-xl tracking-tight">{{ $a->start_date?->format('d') ?? '--' }}</span>
                            </div>
                        </div>
                        {{-- Identity --}}
                        <div class="flex flex-col gap-1 min-w-0">
                            <span class="text-xs font-medium text-primary">{{ $a->assessment_code }}</span>
                            <span class="text-base font-medium text-mono leading-5 truncate">{{ optional($a->subject)->subject_name ?? 'Subject' }}</span>
                            <span class="text-sm text-secondary-foreground truncate">{{ optional($a->section)->section_name ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 mt-auto pt-1">
                        <span class="text-xs text-secondary-foreground">{{ $a->availability['remaining'] }} attempt(s) left</span>
                    </div>

                    {{-- Status-as-button: both open the details modal. Takeable shows a Start action
                         inside; otherwise the modal shows the reason and no Start. --}}
                    @if ($a->startable)
                        <button type="button" class="kt-btn kt-btn-primary w-full" data-kt-modal-toggle="#kt_take_{{ $a->id }}">
                            Take Assessment
                        </button>
                    @else
                        <button type="button" class="kt-btn kt-btn-secondary w-full" data-kt-modal-toggle="#kt_take_{{ $a->id }}">
                            <i class="ki-filled ki-information-2"></i> {{ $a->status_label }}
                        </button>
                    @endif
                </div>
            </div>

            @include('student.assessments._confirm-modal', ['a' => $a])
        @empty
            <div class="kt-card sm:col-span-2 xl:col-span-3"><div class="kt-card-content p-10 text-center text-secondary-foreground">No assessments yet.</div></div>
        @endforelse
    </div>
    <div id="assessment_no_match" class="hidden p-10 text-center text-sm text-secondary-foreground">No assessments match your filters.</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        var bar = document.getElementById('assessment_filters');
        var grid = document.getElementById('assessment_grid');
        if (!bar || !grid) return;
        var search = bar.querySelector('[data-assessment-search]');
        var avail = bar.querySelector('[data-assessment-availability]');
        var cards = grid.querySelectorAll('.assessment-card');
        var noMatch = document.getElementById('assessment_no_match');

        function apply() {
            var q = (search.value || '').trim().toLowerCase();
            var a = avail.value;
            var visible = 0;
            cards.forEach(function (card) {
                var show = (!q || (card.getAttribute('data-search') || '').indexOf(q) !== -1)
                    && (!a || card.getAttribute('data-availability') === a);
                card.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            if (noMatch) noMatch.classList.toggle('hidden', visible !== 0 || cards.length === 0);
        }
        search.addEventListener('input', apply);
        avail.addEventListener('change', apply);
    })();
</script>
@endpush
