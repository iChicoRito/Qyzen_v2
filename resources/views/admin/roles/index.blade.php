{{-- F5: roles list. From public/metronic/dist/apps/user-management/roles/list.html. --}}
@extends('admin.layout')
@section('title', 'Roles')
@section('heading', 'Roles')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-toolbar ms-auto">
                <a href="{{ route('admin.roles.create') }}" class="btn btn-sm btn-primary">Add role</a>
            </div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Name</th><th>Description</th><th>Permissions</th><th>System</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->description ?: '—' }}</td>
                            <td>{{ $role->permissions_count }}</td>
                            <td>{{ $role->is_system ? 'Yes' : 'No' }}</td>
                            <td><span class="badge badge-light-{{ $role->is_active ? 'success' : 'danger' }}">{{ $role->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-sm btn-light">View</a>
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-light">Edit</a>
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline" onsubmit="return confirm('Delete this role?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No roles.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $roles->links() }}
        </div>
    </div>
@endsection
