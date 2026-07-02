@extends('educator.layout')
@section('title', 'Enrollment')
@section('heading', 'Enrollment')
@section('toolbar')
    <a href="{{ route('educator.enrollment.import.template') }}" class="kt-btn kt-btn-sm kt-btn-outline">Download template</a>
    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-kt-modal-toggle="#kt_enroll_import">Import (xlsx)</button>
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('educator.enrollment.create') }}" data-modal-target="#form_modal" data-modal-title="Enroll students">Enroll students</button>
@endsection
@section('content')
    @include('admin._status')
    <x-data-table id="enrollment_table" search-placeholder="Search enrollments">
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
                    <th class="min-w-[200px]"><span class="kt-table-col"><span class="kt-table-col-label">Student</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">User ID</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[220px]"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($enrollments as $e)
            <tr>
                <td class="text-mono font-medium text-sm">{{ optional($e->student)->name ?? '—' }}</td>
                <td class="text-secondary-foreground">{{ optional($e->student)->user_id ?? '—' }}</td>
                <td>{{ optional($e->subject)->subject_code }} — {{ optional($e->subject)->subject_name }}</td>
                <td>
                    <span data-filter-value="status" data-filter-key="{{ $e->is_active ? 'active' : 'inactive' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $e->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $e->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <x-table-actions
                        :edit-modal="route('educator.enrollment.edit', $e)"
                        edit-modal-title="Edit enrollment"
                        :delete="route('educator.enrollment.destroy', $e)"
                        confirm="Remove this enrollment?" />
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary-foreground py-5">No enrollments.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="640px" />

    <div class="kt-modal" data-kt-modal="true" id="kt_enroll_import">
        <div class="kt-modal-content top-[15%]" style="width: 100%; max-width: min(92vw, 500px);">
            <form method="POST" action="{{ route('educator.enrollment.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="kt-modal-header">
                    <h3 class="kt-modal-title">Import enrollments</h3>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true"><i class="ki-filled ki-cross"></i></button>
                </div>
                <div class="kt-modal-body flex flex-col gap-3">
                    <p class="text-sm text-secondary-foreground">Columns: student_user_id, subject_code, section_name, status.</p>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="kt-input" required>
                </div>
                <div class="kt-modal-footer justify-end">
                    <button type="submit" class="kt-btn kt-btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
@endsection
