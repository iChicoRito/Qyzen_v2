{{-- H1: student dashboard. --}}
@extends('student.layout')
@section('title', 'Student Dashboard')
@section('heading', 'Dashboard')

@php
    $cards = [
        ['label' => 'Assessments', 'value' => $stats['assessments'], 'icon' => 'ki-questionnaire-tablet'],
        ['label' => 'Pending', 'value' => $stats['pending'], 'icon' => 'ki-time'],
        ['label' => 'Completed', 'value' => $stats['completed'], 'icon' => 'ki-check-circle'],
        ['label' => 'Avg Score', 'value' => $stats['avg_score'], 'icon' => 'ki-chart-simple'],
    ];
@endphp

@section('content')
    <div class="row g-5 mb-5">
        @foreach ($cards as $card)
            <div class="col-sm-6 col-xl-3">
                <div class="card card-flush h-100"><div class="card-body d-flex align-items-center">
                    <i class="ki-duotone {{ $card['icon'] }} fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <div>
                        <div class="fs-2hx fw-bold text-dark">{{ $card['value'] }}</div>
                        <div class="fs-6 fw-semibold text-gray-500">{{ $card['label'] }}</div>
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card card-flush">
        <div class="card-header"><h3 class="card-title fw-bold">Recent Results</h3></div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase"><th>Assessment</th><th>Score</th><th>Result</th><th>Submitted</th></tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($recent as $s)
                        <tr>
                            <td>{{ optional($s->assessment)->assessment_code ?? '—' }}</td>
                            <td>{{ $s->score }}/{{ $s->total_questions }}</td>
                            <td><span class="badge badge-light-{{ $s->is_passed ? 'success' : 'danger' }}">{{ ucfirst($s->status) }}</span></td>
                            <td>{{ optional($s->submitted_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No results yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
