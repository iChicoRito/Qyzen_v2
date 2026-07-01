@extends('educator.layout')
@section('title', 'Quizzes')
@section('heading', 'Quizzes (Questions)')
@section('toolbar')
    <a href="{{ route('educator.quizzes.upload.template') }}" class="kt-btn kt-btn-sm kt-btn-outline">Download template</a>
    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-kt-modal-toggle="#kt_quiz_upload">Bulk upload</button>
    <a href="{{ route('educator.quizzes.create') }}" class="kt-btn kt-btn-sm kt-btn-primary">Add question</a>
@endsection
@section('content')
    @include('admin._status')
    <x-data-table id="quizzes_table" search-placeholder="Search assessments">
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Assessment</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">Total</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[90px]"><span class="kt-table-col"><span class="kt-table-col-label">MC</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">Identification</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($assessments as $a)
            <tr>
                <td class="text-mono font-medium text-sm">{{ $a->assessment_code }}</td>
                <td>{{ $a->quizzes_count }}</td>
                <td>{{ $a->multiple_choice_count }}</td>
                <td>{{ $a->identification_count }}</td>
                <td class="text-center">
                    <x-table-actions
                        :delete="$a->quizzes_count > 0 ? route('educator.quizzes.destroy-for-assessment', $a) : null"
                        confirm="Delete ALL questions for this assessment? This cannot be undone.">
                        <div class="kt-menu-item">
                            <a class="kt-menu-link" href="{{ route('educator.quizzes.create', ['assessment_id' => $a->id]) }}">
                                <span class="kt-menu-icon"><i class="ki-filled ki-plus-squared"></i></span>
                                <span class="kt-menu-title">Add question</span>
                            </a>
                        </div>
                    </x-table-actions>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary-foreground py-5">No assessments.</td></tr>
        @endforelse
    </x-data-table>

    <div class="kt-modal" data-kt-modal="true" id="kt_quiz_upload">
        <div class="kt-modal-content max-w-[500px] top-[15%]">
            <form method="POST" action="{{ route('educator.quizzes.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="kt-modal-header">
                    <h3 class="kt-modal-title">Bulk upload questions</h3>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true"><i class="ki-filled ki-cross"></i></button>
                </div>
                <div class="kt-modal-body flex flex-col gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Assessment</label>
                        <select name="assessment_id" class="kt-select" required>
                            @foreach ($assessments as $a)<option value="{{ $a->id }}">{{ $a->assessment_code }}</option>@endforeach
                        </select>
                    </div>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="kt-input" required>
                </div>
                <div class="kt-modal-footer justify-end">
                    <button type="submit" class="kt-btn kt-btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
@endsection
