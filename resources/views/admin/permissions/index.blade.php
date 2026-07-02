{{-- F6: permissions list + bulk-create modal (form repeater). --}}
@extends('admin.layout')
@section('title', 'Permissions')
@section('heading', 'Permissions')

@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" data-kt-modal-toggle="#perm_add_modal">Add permission</button>
@endsection

@section('content')
    @include('admin._status')

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
                        :view-modal="route('admin.permissions.show', $perm)"
                        view-modal-title="Permission"
                        :edit-modal="route('admin.permissions.edit', $perm)"
                        edit-modal-title="Edit permission"
                        :delete="route('admin.permissions.destroy', $perm)"
                        confirm="Delete this permission? This cannot be undone." />
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary-foreground py-5">No permissions.</td></tr>
        @endforelse
    </x-data-table>

    {{-- Shared modal for editing a single permission (AJAX-loaded). --}}
    <x-modal id="form_modal" width="640px" />

    {{-- Bulk-create modal with a CSP-safe form repeater (data-repeater-*, delegated in _modal-loader). --}}
    <x-modal id="perm_add_modal" title="Add permissions" width="720px">
        <form method="POST" action="{{ route('admin.permissions.store') }}">
            @csrf
            <div id="perm_rows" class="flex flex-col gap-4">
                <div class="rounded-lg border border-border p-4 flex flex-col gap-3" data-repeater-row>
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium text-mono">Permission no. <span data-repeater-index>1</span></h4>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive shrink-0" data-repeater-remove title="Remove permission">
                            <i class="ki-filled ki-trash"></i>
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">Resource</label>
                            <input name="permissions[0][resource]" class="kt-input" placeholder="e.g. sections" required pattern="[a-z_]+" title="Lowercase letters and underscores only">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">Action</label>
                            <input name="permissions[0][action]" class="kt-input" placeholder="e.g. view" required pattern="[a-z_]+" title="Lowercase letters and underscores only">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">Module</label>
                            <input name="permissions[0][module]" class="kt-input" placeholder="optional">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label">Status</label>
                            <select name="permissions[0][is_active]" class="kt-select">
                                <option value="1">Active</option><option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-between gap-2 mt-4 sticky bottom-0 bg-background border-t border-border py-3">
                <button type="button" class="kt-btn kt-btn-outline kt-btn-sm" data-repeater-add="#perm_rows">
                    <i class="ki-filled ki-plus"></i> Add row
                </button>
                <div class="flex gap-2">
                    <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
                    <button type="submit" class="kt-btn kt-btn-primary">Create</button>
                </div>
            </div>
        </form>
    </x-modal>
@endsection
