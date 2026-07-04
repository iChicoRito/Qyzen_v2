@extends('educator.layout')
@section('title', 'Scores')
@section('heading', 'Scores')
@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-kt-modal-toggle="#export_modal">Download Grades</button>
@endsection
@section('content')
    @include('admin._status')
    <x-data-table id="scores_table" :search="false">
        <x-slot:filters>
            <select data-filter="assessment" class="kt-select w-36">
                <option value="">All assessments</option>
                @foreach ($scores->pluck('assessment.assessment_code')->filter()->unique()->sort() as $code)
                    <option value="{{ $code }}">{{ $code }}</option>
                @endforeach
            </select>
            <select data-filter="subject" class="kt-select w-40">
                <option value="">All subjects</option>
                @foreach ($scores->pluck('subject')->filter()->unique('id')->sortBy('subject_name') as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->subject_code }} — {{ $sub->subject_name }}</option>
                @endforeach
            </select>
            <select data-filter="section" class="kt-select w-32">
                <option value="">All sections</option>
                @foreach ($scores->pluck('section')->filter()->unique('id')->sortBy('section_name') as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->section_name }}</option>
                @endforeach
            </select>
            <select data-filter="term" class="kt-select w-32">
                <option value="">All terms</option>
                @foreach ($scores->pluck('assessment.academicTerm')->filter()->unique('id')->sortBy('term_name') as $t)
                    <option value="{{ $t->id }}">{{ $t->term_name }}</option>
                @endforeach
            </select>
            <select data-filter="result" class="kt-select w-36">
                <option value="">All results</option>
                <option value="passed">Passed</option>
                <option value="failed">Failed</option>
            </select>
        </x-slot:filters>
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[220px]"><span class="kt-table-col"><span class="kt-table-col-label">Student</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Assessment</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Section</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Term</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">Score</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Result</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Submitted</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($scores as $s)
            @php $st = $s->student; $initial = strtoupper(mb_substr($st?->surname ?: ($st?->given_name ?: '?'), 0, 1)); @endphp
            <tr>
                <td>
                    <div class="flex items-center gap-2.5">
                        @if ($st?->profile_picture)
                            <img class="rounded-full size-9 shrink-0" src="{{ asset('storage/'.$st->profile_picture) }}" alt="{{ $st->name }}" />
                        @else
                            <span class="inline-flex items-center justify-center rounded-full size-9 shrink-0 bg-primary/10 text-primary text-sm font-semibold">{{ $initial }}</span>
                        @endif
                        <span class="text-sm"><span class="text-mono font-semibold">{{ $st?->surname ?? '—' }}</span> <span class="text-secondary-foreground">{{ $st?->given_name }}</span></span>
                    </div>
                </td>
                <td>
                    <span data-filter-value="assessment" data-filter-key="{{ $s->assessment?->assessment_code }}" hidden></span>
                    {{ optional($s->assessment)->assessment_code ?? '—' }}
                </td>
                <td>
                    <span data-filter-value="subject" data-filter-key="{{ $s->subject_id }}" hidden></span>
                    {{ optional($s->subject)->subject_code ?? '—' }}
                </td>
                <td>
                    <span data-filter-value="section" data-filter-key="{{ $s->section_id }}" hidden></span>
                    {{ optional($s->section)->section_name ?? '—' }}
                </td>
                <td class="text-secondary-foreground">
                    <span data-filter-value="term" data-filter-key="{{ $s->assessment?->term }}" hidden></span>
                    {{ optional($s->assessment?->academicTerm)->term_name ?? '—' }}
                </td>
                <td>{{ $s->score }}/{{ $s->total_questions }}</td>
                <td>
                    <span data-filter-value="result" data-filter-key="{{ $s->is_passed ? 'passed' : 'failed' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $s->is_passed ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $s->is_passed ? 'Passed' : 'Failed' }}
                    </span>
                </td>
                <td class="text-secondary-foreground">{{ optional($s->submitted_at)->format('Y-m-d H:i') ?? '—' }}</td>
                <td class="text-center">
                    <x-table-actions :view-modal="route('educator.scores.show', $s)" view-modal-title="Attempt detail" />
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-secondary-foreground py-5">No scores yet.</td></tr>
        @endforelse
    </x-data-table>

    @include('educator.scores._export_modal')

    <x-modal id="form_modal" width="760px" />
@endsection
