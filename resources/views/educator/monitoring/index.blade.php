@extends('educator.layout')
@section('title', 'Monitoring')
@section('heading', 'Realtime Monitoring')
@section('toolbar')
    <a href="{{ route('educator.monitoring.index') }}" class="kt-btn kt-btn-sm kt-btn-outline">Refresh</a>
@endsection
@section('content')
    <x-data-table id="monitoring_table" search-placeholder="Search assessments">
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Assessment</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Section</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">Enrolled</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">Online</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Answering</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">Finished</span><span class="kt-table-col-sort"></span></span></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($assessments as $row)
            <tr>
                <td class="text-mono font-medium text-sm">{{ $row['assessment']->assessment_code }}</td>
                <td>{{ optional($row['assessment']->subject)->subject_code }}</td>
                <td>{{ optional($row['assessment']->section)->section_name }}</td>
                <td>{{ $row['enrolled'] }}</td>
                <td><span class="kt-badge kt-badge-outline kt-badge-success">{{ $row['online'] }}</span></td>
                <td><span class="kt-badge kt-badge-outline kt-badge-warning">{{ $row['answering'] }}</span></td>
                <td><span class="kt-badge kt-badge-outline kt-badge-primary">{{ $row['finished'] }}</span></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-secondary-foreground py-5">No active assessments.</td></tr>
        @endforelse
    </x-data-table>
    <p class="text-secondary-foreground text-sm mt-3">Live updates land in Stage I; refresh manually for now.</p>
@endsection
