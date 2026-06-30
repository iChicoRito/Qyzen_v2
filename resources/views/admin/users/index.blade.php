{{-- F2: users list. Simplified from public/metronic/dist/apps/user-management/users/list.html. --}}
@extends('admin.layout')

@section('title', 'Users')
@section('heading', 'Users')

@section('content')
    @include('admin._status')

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm w-150px" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status')==='active')>Active</option>
                        <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
                    </select>
                    <select name="user_type" class="form-select form-select-sm w-150px" onchange="this.form.submit()">
                        <option value="">All types</option>
                        @foreach (['admin','educator','student'] as $t)
                            <option value="{{ $t }}" @selected(request('user_type')===$t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="card-toolbar gap-2">
                <a href="{{ route('admin.users.import.template') }}" class="btn btn-sm btn-light">Download template</a>
                <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_import_modal">Import students</button>
                <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">Add user</a>
            </div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                        <th>User ID</th><th>Name</th><th>Email</th><th>Type</th><th>Roles</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($users as $u)
                        <tr>
                            <td>{{ $u->user_id }}</td>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ ucfirst($u->user_type) }}</td>
                            <td>{{ $u->roles->pluck('name')->join(', ') }}</td>
                            <td>
                                <span class="badge badge-light-{{ $u->is_active ? 'success' : 'danger' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.show', $u) }}" class="btn btn-sm btn-light">View</a>
                                <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-light">Edit</a>
                                @if ($u->email_verified_at === null)
                                    <form method="POST" action="{{ route('admin.users.resend-verification', $u) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-light-warning">Resend</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="d-inline" onsubmit="return confirm('Delete this user?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No users.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $users->links() }}
        </div>
    </div>

    {{-- F3: import modal --}}
    <div class="modal fade" id="kt_import_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header"><h3 class="modal-title">Import students (xlsx)</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p class="text-muted">Columns: user_id, given_name, surname, email, status.</p>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
