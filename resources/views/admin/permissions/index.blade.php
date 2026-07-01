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

    <x-data-table id="permissions_table" search-placeholder="Search permissions">
        <x-slot:filters>
            <select data-filter="status" class="kt-select w-36">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </x-slot:filters>
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[220px]"><span class="kt-table-col"><span class="kt-table-col-label">Permission</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Module</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($permissions as $perm)
            <tr>
                <td class="leading-none font-medium text-sm text-mono">{{ $perm->permission_string }}</td>
                <td class="text-secondary-foreground">{{ $perm->module ?: '—' }}</td>
                <td>
                    <span data-filter-value="status" data-filter-key="{{ $perm->is_active ? 'active' : 'inactive' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $perm->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $perm->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <x-table-actions
                        :view="route('admin.permissions.show', $perm)"
                        :edit="route('admin.permissions.edit', $perm)"
                        :delete="route('admin.permissions.destroy', $perm)"
                        confirm="Delete this permission? This cannot be undone." />
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary-foreground py-5">No permissions.</td></tr>
        @endforelse
    </x-data-table>

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
