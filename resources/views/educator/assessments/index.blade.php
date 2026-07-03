@extends('educator.layout')
@section('title', 'Assessments')
@section('heading', 'Assessments')
@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('educator.assessments.create') }}" data-modal-target="#form_modal" data-modal-title="Add assessment">Add assessment</button>
@endsection
@section('content')
    @include('admin._status')
    @php
        $subjectOpts = $assessments->pluck('subject')->filter()->unique('id')->sortBy('subject_code')->values();
        $sectionOpts = $assessments->pluck('section')->filter()->unique('id')->sortBy('section_name')->values();
    @endphp
    <x-data-table id="assessments_table" search-placeholder="Search assessments">
        <x-slot:filters>
            <select data-filter="subject" class="kt-select w-36">
                <option value="">All subjects</option>
                @foreach ($subjectOpts as $s)<option value="{{ $s->id }}">{{ $s->subject_code }} — {{ $s->subject_name }}</option>@endforeach
            </select>
            <select data-filter="section" class="kt-select w-32">
                <option value="">All sections</option>
                @foreach ($sectionOpts as $s)<option value="{{ $s->id }}">{{ $s->section_name }}</option>@endforeach
            </select>
            <select data-filter="status" class="kt-select w-32">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </x-slot:filters>
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Code</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Section</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Term</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[200px]"><span class="kt-table-col"><span class="kt-table-col-label">Window</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($assessments as $a)
            <tr>
                <td class="text-mono font-medium text-sm">{{ $a->assessment_code }}</td>
                <td><span data-filter-value="subject" data-filter-key="{{ $a->subject_id }}" hidden></span>{{ optional($a->subject)->subject_name }}</td>
                <td><span data-filter-value="section" data-filter-key="{{ $a->section_id }}" hidden></span>{{ optional($a->section)->section_name }}</td>
                <td>{{ optional($a->academicTerm)->term_name }}</td>
                <td class="text-secondary-foreground">{{ $a->start_date?->format('Y-m-d') }} → {{ $a->end_date?->format('Y-m-d') }}</td>
                <td>
                    <span data-filter-value="status" data-filter-key="{{ $a->is_active ? 'active' : 'inactive' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $a->is_active ? 'success' : 'warning' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $a->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <x-table-actions
                        :edit-modal="route('educator.assessments.edit', $a)"
                        edit-modal-title="Edit assessment"
                        :delete="route('educator.assessments.destroy', $a)"
                        confirm="Delete this assessment and its questions? This cannot be undone." />
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-secondary-foreground py-5">No assessments.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="900px" />
@endsection
