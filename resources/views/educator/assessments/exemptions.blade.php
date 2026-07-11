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
                        <input type="text" placeholder="Search students..." data-exempt-search autocomplete="off">
                    </label>
                </div>

                <form method="POST" action="{{ route('educator.assessments.exemptions.toggle', $assessment, false) }}" class="flex flex-col gap-4" data-assessment-modal-form data-no-spinner>
                    @csrf
                    {{-- The global submit-spinner script disables the clicked submit button while handling
                         the submit event, and a disabled control's name=value is dropped from what's sent —
                         so the action can't live on the button itself. A hidden field set on click (which
                         fires before submit) carries it instead. --}}
                    <input type="hidden" name="action" data-exempt-action-input>

                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium text-mono">Why is this student being exempted?</span>
                        <textarea name="reason" rows="2" maxlength="255" class="kt-textarea" placeholder="Enter the reason for the exemption"></textarea>
                        <span class="text-xs text-secondary-foreground">This reason will be shown to the student.</span>
                    </label>

                    <div class="kt-scrollable-x-auto max-h-[24rem] overflow-y-auto kt-scrollable-y rounded-lg border border-border">
                        <table class="kt-table table-auto kt-table-border">
                            <thead>
                                <tr>
                                    <th class="w-[56px] text-center">
                                        <input type="checkbox" class="kt-checkbox kt-checkbox-sm" data-exempt-select-all aria-label="Select all">
                                    </th>
                                    <th class="min-w-[240px]">Student</th>
                                    <th class="min-w-[140px]">User ID</th>
                                    <th class="min-w-[120px]">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $e)
                                    @php
                                        $exempt = in_array($e->student_id, $exemptStudentIds, true);
                                        $searchKey = mb_strtolower(optional($e->student)->name.' '.optional($e->student)->user_id);
                                    @endphp
                                    <tr data-exempt-row data-exempt-name="{{ $searchKey }}">
                                        <td class="text-center align-middle">
                                            {{-- Pre-checked when already exempted, so "Un-exempt Selected" works immediately. --}}
                                            <input type="checkbox" name="student_ids[]" value="{{ $e->student_id }}" class="kt-checkbox kt-checkbox-sm" data-exempt-checkbox @checked($exempt)>
                                        </td>
                                        <td class="align-middle">
                                            <span class="text-sm font-medium text-mono truncate">{{ optional($e->student)->name }}</span>
                                        </td>
                                        <td class="align-middle text-secondary-foreground">{{ optional($e->student)->user_id }}</td>
                                        <td class="align-middle" data-exempt-status>
                                            @if ($exempt)
                                                <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-warning">Exempted</span>
                                            @else
                                                <span class="text-xs text-secondary-foreground">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <span class="text-xs text-secondary-foreground px-1 hidden" data-exempt-no-match>No students match your search.</span>

                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="submit" class="kt-btn kt-btn-outline kt-btn-destructive" data-exempt-action="exempt">Exempt Selected</button>
                        <button type="submit" class="kt-btn kt-btn-outline" data-exempt-action="unexempt">Un-exempt Selected</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
