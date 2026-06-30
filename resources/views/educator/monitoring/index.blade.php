@extends('educator.layout')
@section('title', 'Monitoring')
@section('heading', 'Realtime Monitoring')
@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('educator.monitoring.index') }}" class="btn btn-sm btn-light">Refresh</a>
    </div>
    <div class="card"><div class="card-body">
        <table class="table align-middle table-row-dashed fs-6 gy-3">
            <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                <th>Assessment</th><th>Subject</th><th>Section</th><th>Enrolled</th><th>Online</th><th>Answering</th><th>Finished</th>
            </tr></thead>
            <tbody class="fw-semibold text-gray-700">
                @forelse ($assessments as $row)
                    <tr>
                        <td>{{ $row['assessment']->assessment_code }}</td>
                        <td>{{ optional($row['assessment']->subject)->subject_code }}</td>
                        <td>{{ optional($row['assessment']->section)->section_name }}</td>
                        <td>{{ $row['enrolled'] }}</td>
                        <td><span class="badge badge-light-success">{{ $row['online'] }}</span></td>
                        <td><span class="badge badge-light-warning">{{ $row['answering'] }}</span></td>
                        <td><span class="badge badge-light-primary">{{ $row['finished'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No active assessments.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p class="text-muted fs-7 mt-2">Live updates land in Stage I; refresh manually for now.</p>
    </div></div>
@endsection
