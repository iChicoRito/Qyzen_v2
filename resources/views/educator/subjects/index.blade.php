@extends('educator.layout')
@section('title', 'Subjects')
@section('heading', 'Subjects')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-header border-0 pt-6"><div class="card-toolbar ms-auto">
            <a href="{{ route('educator.subjects.create') }}" class="btn btn-sm btn-primary">Add subject</a>
        </div></div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Code</th><th>Name</th><th>Sections</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($groups as $rows)
                        @php $first = $rows->first(); @endphp
                        <tr>
                            <td>{{ $first->subject_code }}</td>
                            <td>{{ $first->subject_name }}</td>
                            <td>{{ $rows->pluck('section.section_name')->filter()->join(', ') ?: '—' }}</td>
                            <td><span class="badge badge-light-{{ $first->is_active ? 'success' : 'danger' }}">{{ $first->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('educator.subjects.edit', $first) }}" class="btn btn-sm btn-light">Edit</a>
                                <form method="POST" action="{{ route('educator.subjects.destroy', $first) }}" class="d-inline" onsubmit="return confirm('Delete this subject (all its sections)?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light-danger">Delete</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No subjects.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
