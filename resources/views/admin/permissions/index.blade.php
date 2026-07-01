{{-- F6: permissions list + bulk-create. KTDataTable styled from demo1 team-info Team Members table. --}}
@extends('admin.layout')
@section('title', 'Permissions')
@section('heading', 'Permissions')
@section('content')
    @include('admin._status')

    <div class="kt-card mb-5">
        <div class="kt-card-header"><h3 class="kt-card-title">Bulk create</h3></div>
        <div class="kt-card-content p-5">
            <form method="POST" action="{{ route('admin.permissions.store') }}" id="kt_perm_repeater">
                @csrf
                <div id="perm_rows">
                    <div class="grid md:grid-cols-4 gap-2 mb-2 perm-row">
                        <input name="permissions[0][resource]" class="kt-input" placeholder="resource (e.g. sections)">
                        <input name="permissions[0][action]" class="kt-input" placeholder="action (e.g. view)">
                        <input name="permissions[0][module]" class="kt-input" placeholder="module (optional)">
                        <select name="permissions[0][is_active]" class="kt-select">
                            <option value="1">Active</option><option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <button type="button" class="kt-btn kt-btn-outline kt-btn-sm" onclick="addPermRow()">+ Add row</button>
                    <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div class="kt-card kt-card-grid min-w-full">
        <div class="kt-card-header py-5 flex-wrap gap-2">
            <h3 class="kt-card-title">Permissions</h3>
            <label class="kt-input">
                <i class="ki-filled ki-magnifier"></i>
                <input data-kt-datatable-search="#permissions_table" placeholder="Search permissions" type="text" value="" />
            </label>
        </div>
        <div class="kt-card-content">
            <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="permissions_table">
                <div class="kt-scrollable-x-auto">
                    <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                        <thead>
                            <tr>
                                <th class="min-w-[220px]"><span class="kt-table-col"><span class="kt-table-col-label">Permission</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Module</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="w-[150px] text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($permissions as $perm)
                                <tr>
                                    <td class="leading-none font-medium text-sm text-mono">{{ $perm->permission_string }}</td>
                                    <td class="text-secondary-foreground">{{ $perm->module ?: '—' }}</td>
                                    <td>
                                        <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $perm->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                                            <span class="kt-badge-dot size-1.5"></span>{{ $perm->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="inline-flex gap-1.5">
                                            <a href="{{ route('admin.permissions.show', $perm) }}" class="kt-btn kt-btn-sm kt-btn-outline">View</a>
                                            <a href="{{ route('admin.permissions.edit', $perm) }}" class="kt-btn kt-btn-sm kt-btn-outline">Edit</a>
                                            <form method="POST" action="{{ route('admin.permissions.destroy', $perm) }}" class="inline" onsubmit="return confirm('Delete this permission?')">
                                                @csrf @method('DELETE')
                                                <button class="kt-btn kt-btn-sm kt-btn-outline kt-btn-destructive">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-secondary-foreground py-5">No permissions.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                    <div class="flex items-center gap-2 order-2 md:order-1">
                        Show
                        <select class="kt-select w-16" data-kt-datatable-size="true" name="perpage"></select>
                        per page
                    </div>
                    <div class="flex items-center gap-4 order-1 md:order-2">
                        <span data-kt-datatable-info="true"></span>
                        <div class="kt-datatable-pagination" data-kt-datatable-pagination="true"></div>
                    </div>
                </div>
            </div>
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
