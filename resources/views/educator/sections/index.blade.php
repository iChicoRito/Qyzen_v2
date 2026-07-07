@extends('educator.layout')
@section('title', 'Sections')
@section('heading', 'Sections')
@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('educator.sections.create') }}" data-modal-target="#form_modal" data-modal-title="Add section">Add section</button>
@endsection
@section('content')
    @include('admin._status')
    <x-data-table id="sections_table" search-placeholder="Search sections" :paginator="$sections">
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
                    <th class="min-w-[180px]" data-sort="section"><span class="kt-table-col"><span class="kt-table-col-label">Name</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[200px]" data-sort="terms"><span class="kt-table-col"><span class="kt-table-col-label">Terms</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]" data-sort="status"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($sections as $section)
            <tr>
                <td class="text-mono font-medium text-sm">{{ $section->section_name }}</td>
                <td class="text-secondary-foreground">{{ $section->terms->pluck('term_name')->join(', ') ?: '—' }}</td>
                <td>
                    <span data-filter-value="status" data-filter-key="{{ $section->is_active ? 'active' : 'inactive' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $section->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $section->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <x-table-actions
                        :edit-modal="route('educator.sections.edit', $section)"
                        edit-modal-title="Edit section"
                        :delete="route('educator.sections.destroy', $section)"
                        confirm="Delete this section? This cannot be undone." />
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary-foreground py-5">No sections.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="640px" />
@endsection
