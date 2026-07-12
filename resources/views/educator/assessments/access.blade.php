{{-- Task 09: per-student post-window access. Reuses the exemptions modal behavior
     and delegated JS by keeping the same data-* hooks for search/select/action wiring. --}}
@extends('layouts.fragment')
@section('content')
    @include('admin._status')
    <div class="kt-card">
        <div class="kt-card-content p-5 flex flex-col gap-4" data-exempt-list>
            <h3 class="text-base font-semibold text-mono">Manage special access</h3>
            <p class="text-sm text-secondary-foreground">Grant selected students one additional attempt after the schedule closes — whether they missed it entirely or need a retake — without changing the assessment schedule for everyone else.</p>

            @if ($students->isEmpty())
                <div class="text-center text-secondary-foreground py-5">No students enrolled in this subject.</div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="kt-input grow min-w-52">
                        <i class="ki-filled ki-magnifier"></i>
                        <input type="text" placeholder="Search students..." data-exempt-search autocomplete="off">
                    </label>
                </div>

                <form method="POST" action="{{ route('educator.assessments.access.toggle', $assessment, false) }}" class="flex flex-col gap-4" data-assessment-modal-form data-no-spinner>
                    @csrf
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
                                        $granted = in_array($e->student_id, $accessStudentIds, true);
                                        $searchKey = mb_strtolower(optional($e->student)->name.' '.optional($e->student)->user_id);
                                    @endphp
                                    <tr data-exempt-row data-exempt-name="{{ $searchKey }}">
                                        <td class="text-center align-middle">
                                            <input type="checkbox" name="student_ids[]" value="{{ $e->student_id }}" class="kt-checkbox kt-checkbox-sm" data-exempt-checkbox @checked($granted)>
                                        </td>
                                        <td class="align-middle">
                                            <span class="text-sm font-medium text-mono truncate">{{ optional($e->student)->name }}</span>
                                        </td>
                                        <td class="align-middle text-secondary-foreground">{{ optional($e->student)->user_id }}</td>
                                        <td class="align-middle" data-exempt-status>
                                            @if ($granted)
                                                <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-info">Special Access</span>
                                            @else
                                                <span class="text-xs text-secondary-foreground">Default</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <span class="text-xs text-secondary-foreground px-1 hidden" data-exempt-no-match>No students match your search.</span>

                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="submit" class="kt-btn kt-btn-outline kt-btn-primary">Save Changes</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
