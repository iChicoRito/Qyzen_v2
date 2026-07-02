@extends('educator.layout')
@section('title', 'Subjects')
@section('heading', 'Subjects')
@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('educator.subjects.create') }}" data-modal-target="#form_modal" data-modal-title="Add subject">Add subject</button>
@endsection
@section('content')
    @include('admin._status')
    <x-data-table id="subjects_table" search-placeholder="Search subjects">
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
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Code</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[200px]"><span class="kt-table-col"><span class="kt-table-col-label">Name</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[180px]"><span class="kt-table-col"><span class="kt-table-col-label">Sections</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($groups as $rows)
            @php $first = $rows->first(); @endphp
            <tr>
                <td class="text-mono font-medium text-sm">{{ $first->subject_code }}</td>
                <td>{{ $first->subject_name }}</td>
                <td class="text-secondary-foreground">{{ $rows->pluck('section.section_name')->filter()->join(', ') ?: '—' }}</td>
                <td>
                    <span data-filter-value="status" data-filter-key="{{ $first->is_active ? 'active' : 'inactive' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $first->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $first->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <x-table-actions
                        :edit-modal="route('educator.subjects.edit', $first)"
                        edit-modal-title="Edit subject"
                        :delete="route('educator.subjects.destroy', $first)"
                        confirm="Delete this subject and all its sections? This cannot be undone." />
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary-foreground py-5">No subjects.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="640px" />
@endsection
