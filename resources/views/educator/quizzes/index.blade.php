@extends('educator.layout')
@section('title', 'Question Bank')
@section('heading', 'Question Bank')
@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-kt-modal-toggle="#kt_quiz_upload">
        <i class="ki-filled ki-cloud-upload"></i> Upload File
    </button>
    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary"
            data-modal-url="{{ route('educator.quizzes.create') }}" data-modal-target="#form_modal" data-modal-title="Add question">Add question</button>
@endsection
@section('content')
    @include('admin._status')
    <x-data-table id="quizzes_table" search-placeholder="Search questions" :paginator="$quizzes">
        <x-slot:filters>
            <select data-filter="subject" class="kt-select w-40">
                <option value="">All subjects</option>
                @foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->subject_code }} — {{ $s->subject_name }}</option>@endforeach
            </select>
            <select data-filter="type" class="kt-select w-40">
                <option value="">All types</option>
                <option value="multiple_choice">Multiple Choice</option>
                <option value="identification">Identification</option>
            </select>
            <select data-filter="assessment" class="kt-select w-40">
                <option value="">All assessments</option>
                @foreach ($assessments as $a)<option value="{{ $a->id }}">{{ $a->assessment_code }}</option>@endforeach
            </select>
            <select data-filter="batch" class="kt-select w-56">
                <option value="">All batches</option>
                @foreach ($batches as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
            </select>
        </x-slot:filters>
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[240px]" data-sort="question"><span class="kt-table-col"><span class="kt-table-col-label">Question</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]">Type</th>
                    <th class="min-w-[160px]">Subject</th>
                    <th class="min-w-[160px]">Answer</th>
                    <th class="min-w-[160px]">Used In</th>
                    <th class="min-w-[200px]">Batch</th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($quizzes as $q)
            <tr>
                <td class="text-mono text-sm">{{ \Illuminate\Support\Str::limit($q->question, 70) }}</td>
                <td>
                    <span class="kt-badge kt-badge-outline">{{ $q->quiz_type === 'multiple_choice' ? 'Multiple Choice' : 'Identification' }}</span>
                </td>
                <td>{{ optional($q->subject)->subject_name }}</td>
                <td class="text-secondary-foreground text-sm">
                    @if ($q->quiz_type === 'multiple_choice')
                        {{ $q->correct_answer }}. {{ $q->choices[$q->correct_answer] ?? '' }}
                    @else
                        @php $ans = json_decode($q->correct_answer, true); @endphp
                        {{ is_array($ans) ? implode(', ', $ans) : $q->correct_answer }}
                    @endif
                </td>
                <td>
                    @forelse ($q->eligibleAssessments as $a)
                        <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $a->assessment_code }}</span>
                    @empty
                        <span class="text-xs text-secondary-foreground">Not used yet</span>
                    @endforelse
                </td>
                <td class="text-xs text-secondary-foreground">{{ $q->batch_label ?? '—' }}</td>
                <td class="text-center">
                    <x-table-actions
                        :edit-modal="route('educator.quizzes.edit', $q)" edit-modal-title="Edit question"
                        :delete="route('educator.quizzes.destroy', $q)" confirm="Delete this question?" />
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-secondary-foreground py-5">No questions yet. Use "Add question" or Bulk upload.</td></tr>
        @endforelse
    </x-data-table>

    <x-modal id="form_modal" width="640px" />

    {{-- Bulk upload: static form in the x-modal slot (same pattern as admin permissions' perm_add_modal). --}}
    <x-modal id="kt_quiz_upload" title="Upload Quiz Files" width="640px">
        <form method="POST" action="{{ route('educator.quizzes.upload') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
            @csrf
            {{-- Template --}}
            <div class="flex items-center justify-between gap-3">
                <label class="kt-form-label">Template</label>
                <a href="{{ route('educator.quizzes.upload.template') }}" class="kt-btn kt-btn-sm kt-btn-outline shrink-0">
                    <i class="ki-filled ki-cloud-download"></i> Download
                </a>
            </div>

            {{-- Target Subject --}}
            <div class="flex flex-col gap-1">
                <label class="kt-form-label">Target Subject</label>
                <select name="subject_id" class="kt-select w-full" required
                        data-kt-select="true" data-kt-select-enable-search="true"
                        data-kt-select-placeholder="Select a subject"
                        data-kt-select-search-placeholder="Search subjects…">
                    @foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->subject_code }} — {{ $s->subject_name }}</option>@endforeach
                </select>
            </div>

            {{-- Also add to assessments (optional) --}}
            <div class="flex flex-col gap-1">
                <label class="kt-form-label">Also Add To These Assessments <span class="text-secondary-foreground font-normal">(optional)</span></label>
                <select name="assessment_ids[]" class="kt-select w-full" multiple
                        data-kt-select="true" data-kt-select-multiple="true" data-kt-select-enable-search="true"
                        data-kt-select-placeholder="Not attached to any assessment yet"
                        data-kt-select-search-placeholder="Search assessments…">
                    @foreach ($assessments as $a)<option value="{{ $a->id }}">{{ trim($a->assessment_code . ($a->subject ? ' — ' . $a->subject->subject_name : '')) }}</option>@endforeach
                </select>
                <span class="text-xs text-secondary-foreground">Must match the target subject above.</span>
            </div>

            {{-- Files --}}
            <div class="flex flex-col gap-1">
                <label class="kt-form-label">Quiz Files</label>
                <input id="quiz_files" type="file" name="files[]" multiple accept=".xlsx,.xls,.csv" class="kt-input" required>
                {{-- Queued files render as Metronic "Recent Uploads" rows (see _modal-loader). --}}
                <div class="grid gap-2.5 mt-1" data-file-list data-empty="No files selected.">
                    <span class="text-xs text-secondary-foreground">No files selected.</span>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-1 sticky bottom-0 bg-background border-t border-border py-3">
                <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-cloud-upload"></i> Upload Quizzes
                </button>
            </div>
        </form>
    </x-modal>
@endsection
