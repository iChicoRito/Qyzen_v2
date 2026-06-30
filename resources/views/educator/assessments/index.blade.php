@extends('educator.layout')
@section('title', 'Assessments')
@section('heading', 'Assessments')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-header border-0 pt-6"><div class="card-toolbar ms-auto">
            <a href="{{ route('educator.assessments.create') }}" class="btn btn-sm btn-primary">Add assessment</a>
        </div></div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Code</th><th>Subject</th><th>Section</th><th>Term</th><th>Window</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($assessments as $a)
                        <tr>
                            <td>{{ $a->assessment_code }}</td>
                            <td>{{ optional($a->subject)->subject_code }}</td>
                            <td>{{ optional($a->section)->section_name }}</td>
                            <td>{{ optional($a->academicTerm)->term_name }}</td>
                            <td>{{ $a->start_date?->format('Y-m-d') }} → {{ $a->end_date?->format('Y-m-d') }}</td>
                            <td><span class="badge badge-light-{{ $a->is_active ? 'success' : 'warning' }}">{{ $a->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('educator.quizzes.create', ['assessment_id' => $a->id]) }}" class="btn btn-sm btn-light">+ Questions</a>
                                <a href="{{ route('educator.scores.export', $a) }}" class="btn btn-sm btn-light">Export</a>
                                <a href="{{ route('educator.assessments.edit', $a) }}" class="btn btn-sm btn-light">Edit</a>
                                <form method="POST" action="{{ route('educator.assessments.destroy', $a) }}" class="d-inline" onsubmit="return confirm('Delete assessment and its questions?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light-danger">Delete</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No assessments.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $assessments->links() }}
        </div>
    </div>
@endsection
