{{-- F5: roles list. KTDataTable styled from demo1 team-info Team Members table. --}}
@extends('admin.layout')
@section('title', 'Roles')
@section('heading', 'Roles')

@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('admin.roles.create') }}" data-modal-target="#form_modal" data-modal-title="Add role">Add role</button>
@endsection

@section('content')
    @include('admin._status')
    <x-data-table id="roles_table" search-placeholder="Search roles" :paginator="$roles">
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
                    <th class="min-w-[160px]" data-sort="name"><span class="kt-table-col"><span class="kt-table-col-label">Name</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[220px]" data-sort="description"><span class="kt-table-col"><span class="kt-table-col-label">Description</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]" data-sort="permissions"><span class="kt-table-col"><span class="kt-table-col-label">Permissions</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]" data-sort="system"><span class="kt-table-col"><span class="kt-table-col-label">System</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[100px]" data-sort="status"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($roles as $role)
            <tr>
                <td><a href="{{ route('admin.roles.show', $role) }}" class="leading-none font-medium text-sm text-mono hover:text-primary">{{ $role->name }}</a></td>
                <td class="text-secondary-foreground">{{ $role->description ?: '—' }}</td>
                <td>{{ $role->permissions_count }}</td>
                <td>{{ $role->is_system ? 'Yes' : 'No' }}</td>
                <td>
                    <span data-filter-value="status" data-filter-key="{{ $role->is_active ? 'active' : 'inactive' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $role->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $role->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <x-table-actions
                        :view-modal="route('admin.roles.show', $role)"
                        view-modal-title="Role"
                        :edit-modal="route('admin.roles.edit', $role)"
                        edit-modal-title="Edit role"
                        :delete="$role->is_system ? null : route('admin.roles.destroy', $role)"
                        confirm="Delete this role? This cannot be undone." />
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary-foreground py-5">No roles.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="640px" />
@endsection
