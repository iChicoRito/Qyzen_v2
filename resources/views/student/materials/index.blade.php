{{-- H9: student materials — enrollment-gated. --}}
@extends('student.layout')
@section('title', 'Materials')
@section('heading', 'Learning Materials')
@section('content')
    @php
        $fileIcon = fn (string $ext) => match (strtolower($ext)) {
            'pdf' => 'pdf.svg',
            'ppt', 'pptx', 'ppsx' => 'powerpoint.svg',
            'doc', 'docx' => 'word.svg',
            'rtf' => 'text.svg',
            default => 'text.svg',
        };
    @endphp
    @php $materials = $groups->flatten(); @endphp
    <x-data-table id="student_materials_table" search-placeholder="Search materials">
        <x-slot:filters>
            <select data-filter="subject" class="kt-select w-40">
                <option value="">All subjects</option>
                @foreach ($materials->pluck('subject')->filter()->unique('id')->sortBy('subject_name') as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->subject_code }} — {{ $sub->subject_name }}</option>
                @endforeach
            </select>
            <select data-filter="section" class="kt-select w-32">
                <option value="">All sections</option>
                @foreach ($materials->pluck('section')->filter()->unique('id')->sortBy('section_name') as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->section_name }}</option>
                @endforeach
            </select>
        </x-slot:filters>
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[200px]"><span class="kt-table-col"><span class="kt-table-col-label">File</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Section</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">Type</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Updated</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($groups as $rows)
            @foreach ($rows as $m)
                <tr>
                    <td class="text-mono font-medium text-sm">
                        <div class="flex items-center gap-2.5">
                            <img class="size-5 shrink-0" alt="{{ strtoupper($m->file_extension) }} file"
                                 src="{{ asset('metronic-tailwind-html-demos/dist/assets/media/file-types/'.$fileIcon($m->file_extension)) }}">
                            <span>{{ $m->file_name }}</span>
                        </div>
                    </td>
                    <td>
                        <span data-filter-value="subject" data-filter-key="{{ $m->subject_id }}" hidden></span>
                        {{ optional($m->subject)->subject_name }}
                    </td>
                    <td>
                        <span data-filter-value="section" data-filter-key="{{ $m->section_id }}" hidden></span>
                        {{ optional($m->section)->section_name ?? '—' }}
                    </td>
                    <td>{{ strtoupper($m->file_extension) }}</td>
                    <td class="text-secondary-foreground">{{ $m->updated_at?->format('Y-m-d') }}</td>
                    <td class="text-center">
                        <x-table-actions>
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="{{ route('student.materials.download', $m) }}">
                                    <span class="kt-menu-icon"><i class="ki-filled ki-exit-down"></i></span>
                                    <span class="kt-menu-title">Download</span>
                                </a>
                            </div>
                        </x-table-actions>
                    </td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="6" class="text-center text-secondary-foreground py-5">No materials available.</td></tr>
        @endforelse
    </x-data-table>
@endsection
