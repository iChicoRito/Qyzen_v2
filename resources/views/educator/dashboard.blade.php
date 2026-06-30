{{-- G1: educator dashboard. Metronic cards. --}}
@extends('educator.layout')
@section('title', 'Educator Dashboard')
@section('heading', 'Dashboard')

@php
    $cards = [
        ['label' => 'Sections', 'value' => $stats['sections'], 'icon' => 'ki-abstract-26'],
        ['label' => 'Subjects', 'value' => $stats['subjects'], 'icon' => 'ki-book'],
        ['label' => 'Assessments', 'value' => $stats['assessments'], 'icon' => 'ki-questionnaire-tablet'],
        ['label' => 'Active Assessments', 'value' => $stats['assessments_active'], 'icon' => 'ki-check-circle'],
    ];
@endphp

@section('content')
    <div class="row g-5 mb-5">
        @foreach ($cards as $card)
            <div class="col-sm-6 col-xl-3">
                <div class="card card-flush h-100"><div class="card-body d-flex align-items-center">
                    <i class="ki-duotone {{ $card['icon'] }} fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <div>
                        <div class="fs-2hx fw-bold text-dark">{{ number_format($card['value']) }}</div>
                        <div class="fs-6 fw-semibold text-gray-500">{{ $card['label'] }}</div>
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card card-flush">
        <div class="card-header"><h3 class="card-title fw-bold">Top Students</h3></div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Student</th><th>User ID</th><th class="text-end">Avg Score</th><th class="text-end">Attempts</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($topStudents as $row)
                        <tr>
                            <td>{{ optional($row->student)->name ?? '—' }}</td>
                            <td>{{ optional($row->student)->user_id ?? '—' }}</td>
                            <td class="text-end">{{ number_format($row->avg_score, 1) }}</td>
                            <td class="text-end">{{ $row->attempts }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No scores yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
