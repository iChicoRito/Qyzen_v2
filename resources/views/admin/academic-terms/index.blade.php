{{-- F8: academic terms list. KTDataTable styled from demo1 team-info Team Members table. --}}
@extends('admin.layout')
@section('title', 'Academic Terms')
@section('heading', 'Academic Terms')

@section('toolbar')
    <a href="{{ route('admin.academic-terms.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">Add term</a>
@endsection

@section('content')
    @include('admin._status')
    <div class="kt-card kt-card-grid min-w-full">
        <div class="kt-card-header py-5 flex-wrap gap-2">
            <h3 class="kt-card-title">Academic Terms</h3>
            <label class="kt-input">
                <i class="ki-filled ki-magnifier"></i>
                <input data-kt-datatable-search="#terms_table" placeholder="Search terms" type="text" value="" />
            </label>
        </div>
        <div class="kt-card-content">
            <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="terms_table">
                <div class="kt-scrollable-x-auto">
                    <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                        <thead>
                            <tr>
                                <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Term</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Semester</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Year</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                                <th class="w-[150px] text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($terms as $term)
                                <tr>
                                    <td><a href="{{ route('admin.academic-terms.show', $term) }}" class="leading-none font-medium text-sm text-mono hover:text-primary">{{ $term->term_name }}</a></td>
                                    <td>{{ $term->semester }}</td>
                                    <td class="text-secondary-foreground">{{ optional($term->year)->year ?? '—' }}</td>
                                    <td>
                                        <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $term->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                                            <span class="kt-badge-dot size-1.5"></span>{{ $term->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="inline-flex gap-1.5">
                                            <a href="{{ route('admin.academic-terms.show', $term) }}" class="kt-btn kt-btn-sm kt-btn-outline">View</a>
                                            <a href="{{ route('admin.academic-terms.edit', $term) }}" class="kt-btn kt-btn-sm kt-btn-outline">Edit</a>
                                            <form method="POST" action="{{ route('admin.academic-terms.destroy', $term) }}" class="inline" onsubmit="return confirm('Delete this term?')">
                                                @csrf @method('DELETE')
                                                <button class="kt-btn kt-btn-sm kt-btn-outline kt-btn-destructive">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary-foreground py-5">No academic terms.</td></tr>
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
