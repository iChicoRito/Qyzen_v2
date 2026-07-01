{{-- F5: roles list. KTDataTable styled from demo1 team-info Team Members table. --}}
@extends('admin.layout')
@section('title', 'Roles')
@section('heading', 'Roles')

@section('toolbar')
    <a href="{{ route('admin.roles.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">Add role</a>
@endsection

@section('content')
    @include('admin._status')
    <div class="kt-card kt-card-grid min-w-full">
        <div class="kt-card-header py-5 flex-wrap gap-2">
            <h3 class="kt-card-title">Roles</h3>
            <label class="kt-input">
                <i class="ki-filled ki-magnifier"></i>
                <input data-kt-datatable-search="#roles_table" placeholder="Search roles" type="text" value="" />
            </label>
        </div>
        <div class="kt-card-content">
            <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="roles_table">
                <div class="kt-scrollable-x-auto">
                    <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                        <thead>
                            <tr>
                                <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Name</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="min-w-[220px]"><span class="kt-table-col"><span class="kt-table-col-label">Description</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Permissions</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">System</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="w-[150px] text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($roles as $role)
                                <tr>
                                    <td><a href="{{ route('admin.roles.show', $role) }}" class="leading-none font-medium text-sm text-mono hover:text-primary">{{ $role->name }}</a></td>
                                    <td class="text-secondary-foreground">{{ $role->description ?: '—' }}</td>
                                    <td>{{ $role->permissions_count }}</td>
                                    <td>{{ $role->is_system ? 'Yes' : 'No' }}</td>
                                    <td>
                                        <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $role->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                                            <span class="kt-badge-dot size-1.5"></span>{{ $role->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="inline-flex gap-1.5">
                                            <a href="{{ route('admin.roles.show', $role) }}" class="kt-btn kt-btn-sm kt-btn-outline">View</a>
                                            <a href="{{ route('admin.roles.edit', $role) }}" class="kt-btn kt-btn-sm kt-btn-outline">Edit</a>
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline" onsubmit="return confirm('Delete this role?')">
                                                @csrf @method('DELETE')
                                                <button class="kt-btn kt-btn-sm kt-btn-outline kt-btn-destructive">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-secondary-foreground py-5">No roles.</td></tr>
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
@endsection
