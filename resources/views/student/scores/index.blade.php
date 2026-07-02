{{-- H8: scores history (own only). --}}
@extends('student.layout')
@section('title', 'My Scores')
@section('heading', 'My Scores')
@section('content')
    <div class="grid grid-cols-3 gap-5 mb-5">
        <div class="kt-card"><div class="kt-card-content text-center p-5"><div class="text-2xl font-bold">{{ $summary['total'] }}</div><div class="text-secondary-foreground text-sm">Total</div></div></div>
        <div class="kt-card"><div class="kt-card-content text-center p-5"><div class="text-2xl font-bold text-green-600">{{ $summary['passed'] }}</div><div class="text-secondary-foreground text-sm">Passed</div></div></div>
        <div class="kt-card"><div class="kt-card-content text-center p-5"><div class="text-2xl font-bold text-destructive">{{ $summary['failed'] }}</div><div class="text-secondary-foreground text-sm">Failed</div></div></div>
    </div>

    <x-data-table id="student_scores_table" search-placeholder="Search scores">
        <x-slot:filters>
            <select data-filter="result" class="kt-select w-36">
                <option value="">All results</option>
                <option value="passed">Passed</option>
                <option value="failed">Failed</option>
            </select>
        </x-slot:filters>
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Assessment</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">Score</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Result</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Submitted</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($scores as $s)
            <tr>
                <td class="text-mono font-medium text-sm">{{ optional($s->assessment)->assessment_code ?? '—' }}</td>
                <td>{{ $s->score }}/{{ $s->total_questions }}</td>
                <td>
                    <span data-filter-value="result" data-filter-key="{{ $s->is_passed ? 'passed' : 'failed' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $s->is_passed ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ ucfirst($s->status) }}
                    </span>
                </td>
                <td class="text-secondary-foreground">{{ optional($s->submitted_at)->format('Y-m-d H:i') ?? '—' }}</td>
                <td class="text-center">
                    <x-table-actions :view-modal="route('student.scores.show', $s)" view-modal-title="Result" />
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary-foreground py-5">No scores yet.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="760px" />
@endsection
