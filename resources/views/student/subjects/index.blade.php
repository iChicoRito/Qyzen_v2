@extends('student.layout')

@section('title', 'Enrolled Subjects')
@section('heading', 'Enrolled Subjects')

@section('content')
    <x-data-table id="student_subjects_table" search-placeholder="Search enrolled subjects" :paginator="$enrollments">
        <x-slot:head>
            <thead><tr>
                <th class="min-w-[220px]" data-sort="educator"><span class="kt-table-col"><span class="kt-table-col-label">Educator</span><span class="kt-table-col-sort"></span></span></th>
                <th class="min-w-[220px]" data-sort="subject"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                <th class="min-w-[160px]" data-sort="section"><span class="kt-table-col"><span class="kt-table-col-label">Section</span><span class="kt-table-col-sort"></span></span></th>
            </tr></thead>
        </x-slot:head>
        @forelse ($enrollments as $enrollment)
            <tr>
                <td><div class="flex items-center gap-3">
                    @if ($enrollment->educator?->profile_picture)
                        <img class="size-9 rounded-full shrink-0" src="{{ asset($enrollment->educator->profile_picture) }}" alt="{{ $enrollment->educator->name }}" />
                    @else
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-medium">{{ str($enrollment->educator?->given_name ?? '?')->substr(0, 1) }}{{ str($enrollment->educator?->surname ?? '')->substr(0, 1) }}</span>
                    @endif
                    <span class="font-medium text-mono">{{ $enrollment->educator?->name ?? '—' }}</span>
                </div></td>
                <td><span class="font-medium text-mono">{{ $enrollment->subject?->subject_code }} — {{ $enrollment->subject?->subject_name ?? '—' }}</span></td>
                <td><span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-primary">{{ $enrollment->subject?->section?->section_name ?? '—' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-secondary-foreground py-5">No active subject enrollments.</td></tr>
        @endforelse
    </x-data-table>
@endsection
