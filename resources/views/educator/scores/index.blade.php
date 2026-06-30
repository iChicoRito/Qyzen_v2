@extends('educator.layout')
@section('title', 'Scores')
@section('heading', 'Scores')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-header border-0 pt-6"><div class="card-toolbar ms-auto">
            <a href="{{ route('educator.scores.export-bulk', ['method' => 'all']) }}" class="btn btn-sm btn-light">Export all (zip)</a>
        </div></div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Student</th><th>Assessment</th><th>Score</th><th>Result</th><th>Submitted</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($scores as $s)
                        <tr>
                            <td>{{ optional($s->student)->name ?? '—' }}</td>
                            <td>{{ optional($s->assessment)->assessment_code ?? '—' }}</td>
                            <td>{{ $s->score }}/{{ $s->total_questions }}</td>
                            <td><span class="badge badge-light-{{ $s->is_passed ? 'success' : 'danger' }}">{{ $s->is_passed ? 'Passed' : 'Failed' }}</span></td>
                            <td>{{ optional($s->submitted_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="text-end"><a href="{{ route('educator.scores.show', $s) }}" class="btn btn-sm btn-light">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No scores yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $scores->links() }}
        </div>
    </div>
@endsection
