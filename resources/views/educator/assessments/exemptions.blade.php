{{-- Task 01: per-student "cannot take this quiz" exemption. Modal-only fragment, rendered inside
     the shared modal under ?modal=1 (same convention as educator/enrollment/student.blade.php).
     The search/select-all/action-carry behavior is delegated in
     resources/views/partials/_modal-loader.blade.php — an injected <script> tag never executes
     when this view is loaded via innerHTML, so this file carries no page-local JS. --}}
@extends('layouts.fragment')
@section('content')
    @include('admin._status')
    <div class="kt-card">
        <div class="kt-card-content p-5 flex flex-col gap-4" data-exempt-list>
            <p class="text-sm text-secondary-foreground">Exempted students can't take this assessment, regardless of the schedule.</p>

            @if ($students->isEmpty())
                <div class="text-center text-secondary-foreground py-5">No students enrolled in this subject.</div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="kt-input grow min-w-52">
                        <i class="ki-filled ki-magnifier"></i>
                        <input type="text" placeholder="Search students…" data-exempt-search autocomplete="off">
                    </label>
                    <label class="flex items-center gap-2 text-sm shrink-0 cursor-pointer">
                        <input type="checkbox" class="kt-checkbox kt-checkbox-sm" data-exempt-select-all>
                        Select all
                    </label>
                </div>

                <form method="POST" action="{{ route('educator.assessments.exemptions.toggle', $assessment) }}" class="flex flex-col gap-4">
                    @csrf
                    {{-- The global submit-spinner script disables the clicked submit button while handling
                         the submit event, and a disabled control's name=value is dropped from what's sent —
                         so the action can't live on the button itself. A hidden field set on click (which
                         fires before submit) carries it instead. --}}
                    <input type="hidden" name="action" data-exempt-action-input>
                    <div class="grid grid-cols-1 gap-2.5 max-h-[24rem] overflow-y-auto kt-scrollable-y">
                        @foreach ($students as $e)
                            @php
                                $exempt = in_array($e->student_id, $exemptStudentIds, true);
                                $searchKey = mb_strtolower(optional($e->student)->name.' '.optional($e->student)->user_id);
                            @endphp
                            <label class="flex items-center gap-3 border border-border rounded-lg p-3 cursor-pointer" data-exempt-row data-exempt-name="{{ $searchKey }}">
                                {{-- Pre-checked when already exempted, so the box mirrors the badge and
                                     "Un-exempt Selected" works immediately without re-selecting. --}}
                                <input type="checkbox" name="student_ids[]" value="{{ $e->student_id }}" class="kt-checkbox kt-checkbox-sm shrink-0" data-exempt-checkbox @checked($exempt)>
                                <span class="flex flex-col min-w-0 grow">
                                    <span class="text-sm font-medium text-mono truncate">{{ optional($e->student)->name }}</span>
                                    <span class="text-xs text-secondary-foreground">{{ optional($e->student)->user_id }}</span>
                                </span>
                                @if ($exempt)
                                    <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-warning shrink-0">Exempted</span>
                                @endif
                            </label>
                        @endforeach
                        <span class="text-xs text-secondary-foreground px-1 hidden" data-exempt-no-match>No students match your search.</span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="kt-btn kt-btn-outline kt-btn-destructive" data-exempt-action="exempt">Exempt Selected</button>
                        <button type="submit" class="kt-btn kt-btn-outline" data-exempt-action="unexempt">Un-exempt Selected</button>
                        <button type="button" class="kt-btn kt-btn-outline ms-auto" data-modal-cancel>Close</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
