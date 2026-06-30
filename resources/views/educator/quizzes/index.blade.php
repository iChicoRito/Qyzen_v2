@extends('educator.layout')
@section('title', 'Quizzes')
@section('heading', 'Quizzes (Questions)')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-header border-0 pt-6"><div class="card-toolbar ms-auto gap-2">
            <a href="{{ route('educator.quizzes.upload.template') }}" class="btn btn-sm btn-light">Download template</a>
            <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_quiz_upload">Bulk upload</button>
            <a href="{{ route('educator.quizzes.create') }}" class="btn btn-sm btn-primary">Add question</a>
        </div></div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Assessment</th><th>Total</th><th>MC</th><th>Identification</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($assessments as $a)
                        <tr>
                            <td>{{ $a->assessment_code }}</td>
                            <td>{{ $a->quizzes_count }}</td>
                            <td>{{ $a->multiple_choice_count }}</td>
                            <td>{{ $a->identification_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('educator.quizzes.create', ['assessment_id' => $a->id]) }}" class="btn btn-sm btn-light">Add</a>
                                @if ($a->quizzes_count > 0)
                                    <form method="POST" action="{{ route('educator.quizzes.destroy-for-assessment', $a) }}" class="d-inline" onsubmit="return confirm('Delete ALL questions for this assessment?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light-danger">Delete all</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No assessments.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $assessments->links() }}
        </div>
    </div>

    <div class="modal fade" id="kt_quiz_upload" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
        <form method="POST" action="{{ route('educator.quizzes.upload') }}" enctype="multipart/form-data">@csrf
            <div class="modal-header"><h3 class="modal-title">Bulk upload questions</h3><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label required">Assessment</label>
                <select name="assessment_id" class="form-select mb-3" required>
                    @foreach ($assessments as $a)<option value="{{ $a->id }}">{{ $a->assessment_code }}</option>@endforeach
                </select>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control" required>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Upload</button></div>
        </form>
    </div></div></div>
@endsection
