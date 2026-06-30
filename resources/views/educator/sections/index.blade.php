@extends('educator.layout')
@section('title', 'Sections')
@section('heading', 'Sections')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-header border-0 pt-6"><div class="card-toolbar ms-auto">
            <a href="{{ route('educator.sections.create') }}" class="btn btn-sm btn-primary">Add section</a>
        </div></div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Name</th><th>Terms</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($sections as $section)
                        <tr>
                            <td>{{ $section->section_name }}</td>
                            <td>{{ $section->terms->pluck('term_name')->join(', ') ?: '—' }}</td>
                            <td><span class="badge badge-light-{{ $section->is_active ? 'success' : 'danger' }}">{{ $section->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('educator.sections.edit', $section) }}" class="btn btn-sm btn-light">Edit</a>
                                <form method="POST" action="{{ route('educator.sections.destroy', $section) }}" class="d-inline" onsubmit="return confirm('Delete this section?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light-danger">Delete</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No sections.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $sections->links() }}
        </div>
    </div>
@endsection
