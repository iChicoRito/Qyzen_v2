{{-- F8: academic terms list. KTDataTable styled from demo1 team-info Team Members table. --}}
@extends('admin.layout')
@section('title', 'Academic Terms')
@section('heading', 'Academic Terms')

@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('admin.academic-terms.create') }}" data-modal-target="#form_modal" data-modal-title="Add academic term">Add term</button>
@endsection

@section('content')
    @include('admin._status')
    <x-data-table id="terms_table" search-placeholder="Search terms">
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
                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Term</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Semester</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Year</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($terms as $term)
            <tr>
                <td><a href="{{ route('admin.academic-terms.show', $term) }}" class="leading-none font-medium text-sm text-mono hover:text-primary">{{ $term->term_name }}</a></td>
                <td>{{ $term->semester }}</td>
                <td class="text-secondary-foreground">{{ optional($term->year)->year ?? '—' }}</td>
                <td>
                    <span data-filter-value="status" data-filter-key="{{ $term->is_active ? 'active' : 'inactive' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $term->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $term->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <x-table-actions
                        :view-modal="route('admin.academic-terms.show', $term)"
                        view-modal-title="Academic term"
                        :edit-modal="route('admin.academic-terms.edit', $term)"
                        edit-modal-title="Edit academic term"
                        :delete="route('admin.academic-terms.destroy', $term)"
                        confirm="Delete this term? This cannot be undone." />
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary-foreground py-5">No academic terms.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="640px" />
@endsection
