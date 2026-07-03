{{-- H2 / Task 17: assessment list as a card grid (Upcoming-Events motif) + per-card
     confirmation modal. Enrolled-only, availability badge, can-take gate.
     Task 21: grid/list view toggle + subject/section/term/status filters. --}}
@extends('student.layout')
@section('title', 'Assessments')
@section('heading', 'Assessments')
@section('content')
    @include('admin._status')

    {{-- Filter bar: search + filters on the left, grid/list view toggle pushed to the far right. --}}
    <div id="assessment_filters" class="flex flex-wrap items-center gap-2.5 mb-5">
        <div class="w-full md:w-80 max-w-full">
            <label class="kt-input">
                <i class="ki-filled ki-magnifier"></i>
                <input type="text" data-assessment-search placeholder="Search assessments" />
            </label>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <select class="kt-select w-40" data-assessment-subject>
                <option value="">All subjects</option>
                @foreach ($assessments->pluck('subject')->filter()->unique('id')->sortBy('subject_name') as $s)
                    <option value="{{ $s->id }}">{{ $s->subject_name }}</option>
                @endforeach
            </select>
            <select class="kt-select w-36" data-assessment-section>
                <option value="">All sections</option>
                @foreach ($assessments->pluck('section')->filter()->unique('id')->sortBy('section_name') as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->section_name }}</option>
                @endforeach
            </select>
            <select class="kt-select w-32" data-assessment-term>
                <option value="">All terms</option>
                @foreach ($assessments->pluck('academicTerm')->filter()->unique('id')->sortBy('term_name') as $t)
                    <option value="{{ $t->id }}">{{ $t->term_name }}</option>
                @endforeach
            </select>
            <select class="kt-select w-48" data-assessment-availability>
                <option value="">All statuses</option>
                <option value="Available">Available</option>
                <option value="Reopened">Reopened</option>
                <option value="Already Taken">Already Taken</option>
                <option value="Starts Soon">Starts Soon</option>
                <option value="Not Ready Yet">Not Ready Yet</option>
                <option value="No Longer Available">No Longer Available</option>
            </select>
        </div>
        {{-- Grid/list view toggle (teams.html data-kt-tabs pattern), pushed to the far right. --}}
        <div class="kt-toggle-group ms-auto" data-kt-tabs="true">
            <a class="kt-btn kt-btn-icon active" data-kt-tab-toggle="#assessment_grid" href="#" aria-label="Grid view">
                <i class="ki-filled ki-category"></i>
            </a>
            <a class="kt-btn kt-btn-icon" data-kt-tab-toggle="#assessment_list" href="#" aria-label="List view">
                <i class="ki-filled ki-row-horizontal"></i>
            </a>
        </div>
    </div>

    @php
        // Shared filter data-attributes for both views (keeps grid card & list row in sync).
        $filterAttrs = fn ($a) => 'data-availability="'.e($a->status_label).'"'
            .' data-subject="'.e($a->subject_id).'"'
            .' data-section="'.e($a->section_id).'"'
            .' data-term="'.e($a->term).'"'
            .' data-search="'.e(\Illuminate\Support\Str::lower($a->assessment_code.' '.optional($a->subject)->subject_code.' '.optional($a->subject)->subject_name.' '.optional($a->section)->section_name)).'"';
    @endphp

    {{-- Grid view: vertical cards (default), teams.html grid-card style. --}}
    <div id="assessment_grid" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($assessments as $a)
            <div class="kt-card assessment-item" {!! $filterAttrs($a) !!}>
                <div class="kt-card-content grid gap-7 py-7.5">
                    {{-- Icon + identity (centered) --}}
                    <div class="grid place-items-center gap-4">
                        <div class="flex justify-center items-center size-14 rounded-full ring-1 ring-input bg-accent/60">
                            <i class="ki-filled ki-questionnaire-tablet text-2xl text-muted-foreground"></i>
                        </div>
                        <div class="grid place-items-center">
                            <span class="text-base font-medium text-mono mb-px">{{ optional($a->subject)->subject_name ?? 'Subject' }}</span>
                            <span class="text-sm text-secondary-foreground text-center">{{ $a->assessment_code }}{{ optional($a->section)->section_name ? ' · '.$a->section->section_name : '' }}</span>
                        </div>
                    </div>
                    {{-- Label / value rows with dashed dividers --}}
                    <div class="grid">
                        <div class="flex items-center justify-between flex-wrap mb-3.5 gap-2">
                            <span class="text-xs text-secondary-foreground uppercase">schedule</span>
                            <span class="text-sm font-medium text-mono">{{ $a->start_date?->format('M d, Y') ?? '—' }}</span>
                        </div>
                        <div class="border-t border-input border-dashed"></div>
                        <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                            <span class="text-xs text-secondary-foreground uppercase">term</span>
                            <span class="text-sm font-medium text-mono">{{ optional($a->academicTerm)->term_name ?? '—' }}</span>
                        </div>
                        <div class="border-t border-input border-dashed mb-3.5"></div>
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <span class="text-xs text-secondary-foreground uppercase">attempts left</span>
                            <span class="text-sm font-medium text-mono">{{ $a->availability['remaining'] }}</span>
                        </div>
                    </div>
                </div>
                {{-- Footer action (full width). Takeable → Start; else status label, non-actionable. --}}
                <div class="kt-card-footer">
                    @if ($a->startable)
                        <button type="button" class="kt-btn kt-btn-primary w-full justify-center" data-kt-modal-toggle="#kt_take_{{ $a->id }}">
                            Take Assessment
                        </button>
                    @else
                        <button type="button" class="kt-btn kt-btn-secondary w-full justify-center" data-kt-modal-toggle="#kt_take_{{ $a->id }}">
                            <i class="ki-filled ki-information-2"></i> {{ $a->status_label }}
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="kt-card sm:col-span-2 xl:col-span-3"><div class="kt-card-content p-10 text-center text-secondary-foreground">No assessments yet.</div></div>
        @endforelse
    </div>

    {{-- List view: horizontal rows (teams.html list layout, real assessment columns). --}}
    <div id="assessment_list" class="hidden">
        <div class="flex flex-col gap-5 lg:gap-7.5">
            @forelse ($assessments as $a)
                <div class="kt-card assessment-item p-7.5" {!! $filterAttrs($a) !!}>
                    <div class="flex flex-wrap justify-between items-center gap-7">
                        {{-- Identity: icon + code/subject/section --}}
                        <div class="flex items-center gap-4">
                            <div class="flex justify-center items-center size-14 shrink-0 rounded-full ring-1 ring-input bg-accent/60">
                                <i class="ki-filled ki-questionnaire-tablet text-2xl text-muted-foreground"></i>
                            </div>
                            <div class="grid gap-1 min-w-0">
                                <span class="text-xs font-medium text-primary">{{ $a->assessment_code }}</span>
                                <span class="text-base font-medium text-mono">{{ optional($a->subject)->subject_name ?? 'Subject' }}</span>
                                <span class="text-sm text-secondary-foreground">{{ optional($a->section)->section_name ?? '—' }}</span>
                            </div>
                        </div>

                        {{-- Columns + action --}}
                        <div class="flex flex-wrap items-center gap-6 lg:gap-12">
                            <div class="grid gap-1.5 justify-start lg:text-end lg:justify-end">
                                <span class="text-xs text-secondary-foreground uppercase">schedule</span>
                                <span class="text-sm font-medium text-mono">{{ $a->start_date?->format('M d, Y') ?? '—' }}</span>
                            </div>
                            <div class="grid gap-1.5 justify-start lg:text-end lg:justify-end">
                                <span class="text-xs text-secondary-foreground uppercase">term</span>
                                <span class="text-sm font-medium text-mono">{{ optional($a->academicTerm)->term_name ?? '—' }}</span>
                            </div>
                            <div class="grid gap-1.5 justify-start lg:text-end lg:justify-end">
                                <span class="text-xs text-secondary-foreground uppercase">attempts left</span>
                                <span class="text-sm font-medium text-mono">{{ $a->availability['remaining'] }}</span>
                            </div>
                            <div class="grid justify-end min-w-40">
                                @if ($a->startable)
                                    <button type="button" class="kt-btn kt-btn-primary" data-kt-modal-toggle="#kt_take_{{ $a->id }}">
                                        Take Assessment
                                    </button>
                                @else
                                    <button type="button" class="kt-btn kt-btn-secondary" data-kt-modal-toggle="#kt_take_{{ $a->id }}">
                                        <i class="ki-filled ki-information-2"></i> {{ $a->status_label }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="kt-card"><div class="kt-card-content p-10 text-center text-secondary-foreground">No assessments yet.</div></div>
            @endforelse
        </div>
    </div>

    <div id="assessment_no_match" class="hidden p-10 text-center text-sm text-secondary-foreground">No assessments match your filters.</div>

    {{-- Confirm modals rendered once; both grid card & list row toggle #kt_take_{id}. --}}
    @foreach ($assessments as $a)
        @include('student.assessments._confirm-modal', ['a' => $a])
    @endforeach
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        // View toggle is handled by KTUI data-kt-tabs. This only drives client-side filtering,
        // applied to items in BOTH the grid and list views (they mirror the same set).
        var bar = document.getElementById('assessment_filters');
        if (!bar) return;
        var search = bar.querySelector('[data-assessment-search]');
        var avail = bar.querySelector('[data-assessment-availability]');
        var subject = bar.querySelector('[data-assessment-subject]');
        var section = bar.querySelector('[data-assessment-section]');
        var term = bar.querySelector('[data-assessment-term]');
        var items = document.querySelectorAll('.assessment-item');
        var noMatch = document.getElementById('assessment_no_match');

        function apply() {
            var q = (search.value || '').trim().toLowerCase();
            var a = avail.value, s = subject.value, sec = section.value, t = term.value;
            var visible = 0;
            items.forEach(function (item) {
                var show = (!q || (item.getAttribute('data-search') || '').indexOf(q) !== -1)
                    && (!a || item.getAttribute('data-availability') === a)
                    && (!s || item.getAttribute('data-subject') === s)
                    && (!sec || item.getAttribute('data-section') === sec)
                    && (!t || item.getAttribute('data-term') === t);
                item.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            if (noMatch) noMatch.classList.toggle('hidden', visible !== 0 || items.length === 0);
        }
        [search, avail, subject, section, term].forEach(function (el) {
            el.addEventListener('input', apply);
            el.addEventListener('change', apply);
        });
    })();
</script>
@endpush
