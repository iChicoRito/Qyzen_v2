@extends('educator.layout')
@section('title', 'Enrollment')
@section('heading', 'Enrollment')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-header border-0 pt-6"><div class="card-toolbar ms-auto gap-2">
            <a href="{{ route('educator.enrollment.import.template') }}" class="btn btn-sm btn-light">Download template</a>
            <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_enroll_import">Import (xlsx)</button>
            <a href="{{ route('educator.enrollment.create') }}" class="btn btn-sm btn-primary">Enroll students</a>
        </div></div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Student</th><th>User ID</th><th>Subject</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($enrollments as $e)
                        <tr>
                            <td>{{ optional($e->student)->name ?? '—' }}</td>
                            <td>{{ optional($e->student)->user_id ?? '—' }}</td>
                            <td>{{ optional($e->subject)->subject_code }} — {{ optional($e->subject)->subject_name }}</td>
                            <td><span class="badge badge-light-{{ $e->is_active ? 'success' : 'danger' }}">{{ $e->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('educator.enrollment.edit', $e) }}" class="btn btn-sm btn-light">Edit</a>
                                <form method="POST" action="{{ route('educator.enrollment.destroy', $e) }}" class="d-inline" onsubmit="return confirm('Remove this enrollment?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light-danger">Remove</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No enrollments.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $enrollments->links() }}
        </div>
    </div>

    <div class="modal fade" id="kt_enroll_import" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
        <form method="POST" action="{{ route('educator.enrollment.import') }}" enctype="multipart/form-data">@csrf
            <div class="modal-header"><h3 class="modal-title">Import enrollments</h3><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted">Columns: student_user_id, subject_code, section_name, status.</p>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control" required>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Import</button></div>
        </form>
    </div></div></div>
@endsection
