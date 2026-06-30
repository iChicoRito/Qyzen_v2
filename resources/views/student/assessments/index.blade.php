{{-- H2: assessment list — enrolled only, availability badges, can-take. --}}
@extends('student.layout')
@section('title', 'Assessments')
@section('heading', 'Assessments')
@php
    $badgeColor = ['Available' => 'success', 'Reopened' => 'info', 'Upcoming' => 'warning', 'Expired' => 'secondary', 'Schedule issue' => 'danger'];
@endphp
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body pt-6">
        <table class="table align-middle table-row-dashed fs-6 gy-3">
            <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                <th>Code</th><th>Subject</th><th>Availability</th><th>Attempts Left</th><th class="text-end">Action</th>
            </tr></thead>
            <tbody class="fw-semibold text-gray-700">
                @forelse ($assessments as $a)
                    @php $av = $a->availability; @endphp
                    <tr>
                        <td>{{ $a->assessment_code }}</td>
                        <td>{{ optional($a->subject)->subject_code }}</td>
                        <td><span class="badge badge-light-{{ $badgeColor[$av['badge']] ?? 'secondary' }}">{{ $av['badge'] }}</span></td>
                        <td>{{ $av['remaining'] }}</td>
                        <td class="text-end">
                            <a href="{{ route('student.assessments.details', $a) }}" class="btn btn-sm btn-light">Details</a>
                            @if ($av['can_take'])
                                <a href="{{ route('student.take-quiz', $a) }}" class="btn btn-sm btn-primary">Start</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No assessments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
@endsection
