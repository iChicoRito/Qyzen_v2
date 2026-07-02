@extends('educator.layout')
@section('title', 'Materials')
@section('heading', 'Learning Materials')
@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('educator.materials.create') }}" data-modal-target="#form_modal" data-modal-title="Upload materials">Upload</button>
@endsection
@section('content')
    @include('admin._status')
    <x-data-table id="materials_table" search-placeholder="Search materials">
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
                    <th class="min-w-[200px]"><span class="kt-table-col"><span class="kt-table-col-label">File</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">Type</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Size</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($groups as $rows)
            @foreach ($rows as $m)
                <tr>
                    <td class="text-mono font-medium text-sm">{{ $m->file_name }}</td>
                    <td>{{ optional($m->subject)->subject_code }}</td>
                    <td>{{ strtoupper($m->file_extension) }}</td>
                    <td class="text-secondary-foreground">{{ $m->file_size ? number_format($m->file_size / 1024, 1).' KB' : '—' }}</td>
                    <td>
                        <span data-filter-value="status" data-filter-key="{{ $m->is_active ? 'active' : 'inactive' }}" hidden></span>
                        <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $m->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                            <span class="kt-badge-dot size-1.5"></span>{{ $m->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <x-table-actions
                            :edit-modal="route('educator.materials.edit', $m)"
                            edit-modal-title="Edit material"
                            :delete="route('educator.materials.destroy', $m)"
                            confirm="Delete this file? This cannot be undone.">
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="{{ route('educator.materials.download', $m) }}">
                                    <span class="kt-menu-icon"><i class="ki-filled ki-exit-down"></i></span>
                                    <span class="kt-menu-title">Download</span>
                                </a>
                            </div>
                        </x-table-actions>
                    </td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="6" class="text-center text-secondary-foreground py-5">No materials.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="640px" />
@endsection
