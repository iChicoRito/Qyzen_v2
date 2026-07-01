{{-- F6: permissions list + bulk-create. From public/metronic/dist/apps/user-management/permissions.html. --}}
@extends('admin.layout')
@section('title', 'Permissions')
@section('heading', 'Permissions')
@section('content')
    @include('admin._status')
    <div class="card mb-5">
        <div class="card-header"><h3 class="card-title">Bulk create</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.permissions.store') }}" id="kt_perm_repeater">
                @csrf
                <div id="perm_rows">
                    <div class="row g-2 mb-2 perm-row">
                        <div class="col-md-3"><input name="permissions[0][resource]" class="form-control" placeholder="resource (e.g. sections)"></div>
                        <div class="col-md-3"><input name="permissions[0][action]" class="form-control" placeholder="action (e.g. view)"></div>
                        <div class="col-md-3"><input name="permissions[0][module]" class="form-control" placeholder="module (optional)"></div>
                        <div class="col-md-2">
                            <select name="permissions[0][is_active]" class="form-select">
                                <option value="1">Active</option><option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-light btn-sm" onclick="addPermRow()">+ Add row</button>
                <button type="submit" class="btn btn-primary btn-sm">Create</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body pt-6">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                    <th>Permission</th><th>Module</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse ($permissions as $perm)
                        <tr>
                            <td>{{ $perm->permission_string }}</td>
                            <td>{{ $perm->module ?: '—' }}</td>
                            <td><span class="badge badge-light-{{ $perm->is_active ? 'success' : 'danger' }}">{{ $perm->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.permissions.show', $perm) }}" class="btn btn-sm btn-light">View</a>
                                <a href="{{ route('admin.permissions.edit', $perm) }}" class="btn btn-sm btn-light">Edit</a>
                                <form method="POST" action="{{ route('admin.permissions.destroy', $perm) }}" class="d-inline" onsubmit="return confirm('Delete this permission?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No permissions.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $permissions->links() }}
        </div>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        let permIdx = 1;
        function addPermRow() {
            const row = document.querySelector('.perm-row').cloneNode(true);
            row.querySelectorAll('input,select').forEach(el => {
                el.name = el.name.replace(/\[\d+\]/, '[' + permIdx + ']');
                if (el.tagName === 'INPUT') el.value = '';
            });
            document.getElementById('perm_rows').appendChild(row);
            permIdx++;
        }
    </script>
    @endpush
@endsection
