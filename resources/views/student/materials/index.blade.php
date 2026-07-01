{{-- H9: student materials — enrollment-gated. --}}
@extends('student.layout')
@section('title', 'Materials')
@section('heading', 'Learning Materials')
@section('content')
    <x-data-table id="student_materials_table" search-placeholder="Search materials">
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[200px]"><span class="kt-table-col"><span class="kt-table-col-label">File</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">Type</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Updated</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[140px] text-end">Action</th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($groups as $rows)
            @foreach ($rows as $m)
                <tr>
                    <td class="text-mono font-medium text-sm">{{ $m->file_name }}</td>
                    <td>{{ optional($m->subject)->subject_code }}</td>
                    <td>{{ strtoupper($m->file_extension) }}</td>
                    <td class="text-secondary-foreground">{{ $m->updated_at?->format('Y-m-d') }}</td>
                    <td class="text-end">
                        <a href="{{ route('student.materials.download', $m) }}" class="kt-btn kt-btn-sm kt-btn-primary">Download</a>
                    </td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="5" class="text-center text-secondary-foreground py-5">No materials available.</td></tr>
        @endforelse
    </x-data-table>
@endsection
