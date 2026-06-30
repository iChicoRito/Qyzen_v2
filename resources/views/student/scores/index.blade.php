{{-- H8: scores history (own only). --}}
@extends('student.layout')
@section('title', 'My Scores')
@section('heading', 'My Scores')
@section('content')
    <div class="row g-5 mb-5">
        <div class="col-4"><div class="card card-flush"><div class="card-body text-center"><div class="fs-2hx fw-bold">{{ $summary['total'] }}</div><div class="text-gray-500">Total</div></div></div></div>
        <div class="col-4"><div class="card card-flush"><div class="card-body text-center"><div class="fs-2hx fw-bold text-success">{{ $summary['passed'] }}</div><div class="text-gray-500">Passed</div></div></div></div>
        <div class="col-4"><div class="card card-flush"><div class="card-body text-center"><div class="fs-2hx fw-bold text-danger">{{ $summary['failed'] }}</div><div class="text-gray-500">Failed</div></div></div></div>
    </div>
    <div class="card"><div class="card-header border-0 pt-6">
        <form method="GET" class="card-title">
            <select name="status" class="form-select form-select-sm w-150px" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="passed" @selected(request('status')==='passed')>Passed</option>
                <option value="failed" @selected(request('status')==='failed')>Failed</option>
            </select>
        </form>
    </div><div class="card-body pt-0">
        <table class="table align-middle table-row-dashed fs-6 gy-3">
            <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase"><th>Assessment</th><th>Score</th><th>Result</th><th>Submitted</th><th class="text-end"></th></tr></thead>
            <tbody class="fw-semibold text-gray-700">
                @forelse ($scores as $s)
                    <tr>
                        <td>{{ optional($s->assessment)->assessment_code ?? '—' }}</td>
                        <td>{{ $s->score }}/{{ $s->total_questions }}</td>
                        <td><span class="badge badge-light-{{ $s->is_passed ? 'success' : 'danger' }}">{{ ucfirst($s->status) }}</span></td>
                        <td>{{ optional($s->submitted_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="text-end"><a href="{{ route('student.scores.show', $s) }}" class="btn btn-sm btn-light">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No scores yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $scores->links() }}
    </div></div>
@endsection
