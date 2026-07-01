{{-- H2: assessment list — enrolled only, availability badges, can-take. --}}
@extends('student.layout')
@section('title', 'Assessments')
@section('heading', 'Assessments')
@php
    $badgeColor = ['Available' => 'success', 'Reopened' => 'info', 'Upcoming' => 'warning', 'Expired' => 'secondary', 'Schedule issue' => 'destructive'];
@endphp
@section('content')
    @include('admin._status')
    <x-data-table id="student_assessments_table" search-placeholder="Search assessments">
        <x-slot:filters>
            <select data-filter="availability" class="kt-select w-40">
                <option value="">All</option>
                <option value="Available">Available</option>
                <option value="Reopened">Reopened</option>
                <option value="Upcoming">Upcoming</option>
                <option value="Expired">Expired</option>
            </select>
        </x-slot:filters>
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Code</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Availability</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Attempts Left</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[160px] text-end">Action</th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($assessments as $a)
            @php $av = $a->availability; @endphp
            <tr>
                <td class="text-mono font-medium text-sm">{{ $a->assessment_code }}</td>
                <td>{{ optional($a->subject)->subject_code }}</td>
                <td>
                    <span data-filter-value="availability" data-filter-key="{{ $av['badge'] }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $badgeColor[$av['badge']] ?? 'secondary' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $av['badge'] }}
                    </span>
                </td>
                <td>{{ $av['remaining'] }}</td>
                <td class="text-end">
                    <div class="inline-flex gap-1.5">
                        <a href="{{ route('student.assessments.details', $a) }}" class="kt-btn kt-btn-sm kt-btn-outline">Details</a>
                        @if ($av['can_take'])
                            <a href="{{ route('student.take-quiz', $a) }}" class="kt-btn kt-btn-sm kt-btn-primary">Start</a>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary-foreground py-5">No assessments yet.</td></tr>
        @endforelse
    </x-data-table>
@endsection
