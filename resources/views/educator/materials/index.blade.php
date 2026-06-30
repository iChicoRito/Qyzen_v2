@extends('educator.layout')
@section('title', 'Materials')
@section('heading', 'Learning Materials')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-header border-0 pt-6"><div class="card-toolbar ms-auto">
            <a href="{{ route('educator.materials.create') }}" class="btn btn-sm btn-primary">Upload</a>
        </div></div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>File</th><th>Subject</th><th>Type</th><th>Size</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($groups as $rows)
                        @foreach ($rows as $m)
                            <tr>
                                <td>{{ $m->file_name }}</td>
                                <td>{{ optional($m->subject)->subject_code }}</td>
                                <td>{{ strtoupper($m->file_extension) }}</td>
                                <td>{{ $m->file_size ? number_format($m->file_size / 1024, 1).' KB' : '—' }}</td>
                                <td><span class="badge badge-light-{{ $m->is_active ? 'success' : 'danger' }}">{{ $m->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('educator.materials.download', $m) }}" class="btn btn-sm btn-light">Download</a>
                                    <a href="{{ route('educator.materials.edit', $m) }}" class="btn btn-sm btn-light">Edit</a>
                                    <form method="POST" action="{{ route('educator.materials.destroy', $m) }}" class="d-inline" onsubmit="return confirm('Delete this file?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light-danger">Delete</button></form>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No materials.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
