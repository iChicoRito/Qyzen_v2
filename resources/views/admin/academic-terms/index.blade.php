{{-- F8: academic terms list (joined to year, ordered id DESC). --}}
@extends('admin.layout')
@section('title', 'Academic Terms')
@section('heading', 'Academic Terms')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-toolbar ms-auto"><a href="{{ route('admin.academic-terms.create') }}" class="btn btn-sm btn-primary">Add term</a></div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Term</th><th>Semester</th><th>Year</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($terms as $term)
                        <tr>
                            <td>{{ $term->term_name }}</td>
                            <td>{{ $term->semester }}</td>
                            <td>{{ optional($term->year)->year ?? '—' }}</td>
                            <td><span class="badge badge-light-{{ $term->is_active ? 'success' : 'danger' }}">{{ $term->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.academic-terms.show', $term) }}" class="btn btn-sm btn-light">View</a>
                                <a href="{{ route('admin.academic-terms.edit', $term) }}" class="btn btn-sm btn-light">Edit</a>
                                <form method="POST" action="{{ route('admin.academic-terms.destroy', $term) }}" class="d-inline" onsubmit="return confirm('Delete this term?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No academic terms.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $terms->links() }}
        </div>
    </div>
@endsection
