@extends('educator.layout')
@section('title', 'Enrolled Students')
@section('heading', 'Enrolled Students')
@section('toolbar')
    <a href="{{ route('educator.enrollment.index') }}" class="kt-btn kt-btn-sm kt-btn-outline">
        <i class="ki-filled ki-arrow-left"></i> Back
    </a>
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('educator.enrollment.create') }}" data-modal-target="#form_modal" data-modal-title="Enroll students">Enroll students</button>
@endsection
@section('content')
    @include('admin._status')

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="text-base font-medium text-mono">{{ $subject->subject_code }} — {{ $subject->subject_name }}</span>
        @if ($subject->section)
            <span class="kt-badge kt-badge-outline kt-badge-secondary">{{ $subject->section->section_name }}</span>
        @endif
        <span class="text-sm text-secondary-foreground">· {{ $enrollments->count() }} enrolled</span>
    </div>

    <x-data-table id="enrollment_students_table" search-placeholder="Search students">
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
                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Student No.</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Surname</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Given Name</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($enrollments as $e)
            @php $st = $e->student; $initial = strtoupper(mb_substr(optional($st)->given_name ?: (optional($st)->surname ?: '?'), 0, 1)); @endphp
            <tr>
                <td>
                    <div class="flex items-center gap-2.5">
                        @if ($st && $st->profile_picture)
                            <img alt="{{ $st->name }}" class="rounded-full size-9 shrink-0" src="{{ asset('storage/'.$st->profile_picture) }}" />
                        @else
                            <span class="inline-flex items-center justify-center rounded-full size-9 shrink-0 bg-primary/10 text-primary text-sm font-semibold">{{ $initial }}</span>
                        @endif
                        <span class="text-mono font-medium text-sm">{{ optional($st)->user_id ?? '—' }}</span>
                    </div>
                </td>
                <td class="text-mono text-sm">{{ optional($e->student)->surname ?? '—' }}</td>
                <td class="text-mono text-sm">{{ optional($e->student)->given_name ?? '—' }}</td>
                <td>
                    <span data-filter-value="status" data-filter-key="{{ $e->is_active ? 'active' : 'inactive' }}" hidden></span>
                    <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $e->is_active ? 'success' : 'destructive' }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $e->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <x-table-actions
                        :view-modal="$st ? route('educator.enrollment.student', $st) : null"
                        view-modal-title="Student"
                        :edit-modal="route('educator.enrollment.edit', $e)"
                        edit-modal-title="Edit enrollment"
                        :delete="route('educator.enrollment.destroy', $e)"
                        confirm="Remove this enrollment?" />
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary-foreground py-5">No students enrolled.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="640px" />
@endsection
