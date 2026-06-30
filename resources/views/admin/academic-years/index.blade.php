{{-- F7: academic years list (ordered DESC). --}}
@extends('admin.layout')
@section('title', 'Academic Years')
@section('heading', 'Academic Years')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-toolbar ms-auto"><a href="{{ route('admin.academic-years.create') }}" class="btn btn-sm btn-primary">Add year</a></div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Year</th><th>Terms</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($years as $year)
                        <tr>
                            <td>{{ $year->year }}</td>
                            <td>{{ $year->terms_count }}</td>
                            <td><span class="badge badge-light-{{ $year->is_active ? 'success' : 'danger' }}">{{ $year->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.academic-years.show', $year) }}" class="btn btn-sm btn-light">View</a>
                                <a href="{{ route('admin.academic-years.edit', $year) }}" class="btn btn-sm btn-light">Edit</a>
                                <form method="POST" action="{{ route('admin.academic-years.destroy', $year) }}" class="d-inline" onsubmit="return confirm('Delete this year and ALL its terms?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No academic years.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $years->links() }}
        </div>
    </div>
@endsection
