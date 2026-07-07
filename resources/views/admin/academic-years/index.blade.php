{{-- F7: academic years list. KTDataTable styled from demo1 team-info Team Members table. --}}
@extends('admin.layout')
@section('title', 'Academic Years')
@section('heading', 'Academic Years')

@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('admin.academic-years.create') }}" data-modal-target="#form_modal" data-modal-title="Add academic year">Add year</button>
@endsection

@section('content')
    @include('admin._status')
    <x-data-table id="years_table" search-placeholder="Search years" :paginator="$years">
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
                    <th class="min-w-[180px]"><span class="kt-table-col"><span class="kt-table-col-label">Year</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Terms</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($years as $year)
            <tr>
                <td><a href="{{ route('admin.academic-years.show', $year) }}" class="leading-none font-medium text-sm text-mono hover:text-primary">{{ $year->year }}</a></td>
                <td>{{ $year->terms_count }}</td>
                <td>
                    <span data-filter-value="status" data-filter-key="{{ $year->is_active ? 'active' : 'inactive' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $year->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $year->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <x-table-actions
                        :view-modal="route('admin.academic-years.show', $year)"
                        view-modal-title="Academic year"
                        :edit-modal="route('admin.academic-years.edit', $year)"
                        edit-modal-title="Edit academic year"
                        :delete="route('admin.academic-years.destroy', $year)"
                        confirm="Delete this year and ALL its terms? This cannot be undone." />
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-secondary-foreground py-5">No academic years.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="640px" />
@endsection
