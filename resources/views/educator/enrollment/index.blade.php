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

    <style nonce="{{ $cspNonce ?? '' }}">
        @media (min-width: 1024px) {
            #enrollment_layout { grid-template-columns: minmax(0, 1fr) 300px; transition: grid-template-columns .2s ease; }
            #enrollment_layout.kt-timeline-collapsed { grid-template-columns: minmax(0, 1fr) 3rem; }
        }
        #enrollment_layout.kt-timeline-collapsed #enrollment_import_timeline { overflow: hidden; }
        #enrollment_layout.kt-timeline-collapsed #enrollment_import_timeline .kt-card-title,
        #enrollment_layout.kt-timeline-collapsed #enrollment_import_timeline .kt-card-content { display: none; }
        #enrollment_layout.kt-timeline-collapsed #enrollment_import_timeline .qz-timeline-toggle-icon { transform: rotate(180deg); }
    </style>
    <div id="enrollment_layout" class="grid gap-5 lg:gap-7.5">
        <div class="min-w-0">
            <x-data-table id="enrollment_table" search-placeholder="Search subjects / sections" :paginator="$subjects">
                <x-slot:head>
                    <thead>
                        <tr>
                            <th class="min-w-[260px]" data-sort="subject"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                            <th class="min-w-[150px]" data-sort="section"><span class="kt-table-col"><span class="kt-table-col-label">Section</span><span class="kt-table-col-sort"></span></span></th>
                            <th class="min-w-[140px]" data-sort="enrolled"><span class="kt-table-col"><span class="kt-table-col-label">Enrolled</span><span class="kt-table-col-sort"></span></span></th>
                            <th class="w-[60px]"></th>
                        </tr>
                    </thead>
                </x-slot:head>
                @forelse ($subjects as $s)
                    <tr>
                        <td class="text-mono font-medium text-sm">{{ $s->subject_code }} - {{ $s->subject_name }}</td>
                        <td>{{ optional($s->section)->section_name ?? '-' }}</td>
                        <td>
                            <span class="text-sm text-mono">{{ $s->enrollments_count }}</span>
                            <span class="text-xs text-secondary-foreground">({{ $s->active_enrollments_count }} active)</span>
                        </td>
                        <td class="text-center">
                            <x-table-actions :view="route('educator.enrollment.subject', $s)">
                                <div class="kt-menu-separator"></div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link" href="#" data-confirm="Unenroll all students from {{ $s->subject_code }}? This cannot be undone." data-confirm-title="Unenroll all?">
                                        <span class="kt-menu-icon"><i class="ki-filled ki-cross-circle"></i></span>
                                        <span class="kt-menu-title">Un-enroll</span>
                                    </a>
                                    <form method="POST" action="{{ route('educator.enrollment.subject.unenrollAll', $s) }}" class="hidden">@csrf</form>
                                </div>
                            </x-table-actions>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-secondary-foreground py-5">No enrollments.</td></tr>
                @endforelse
            </x-data-table>
        </div>

        <div class="min-w-0" id="enrollment_import_timeline">
            @include('educator.enrollment._import-timeline')
        </div>
    </div>

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
                    <p class="text-sm text-secondary-foreground">Columns: student_user_id, subject_code, section_name, status. Only .xlsx files; you can select multiple.</p>
                    <input type="file" name="file[]" accept=".xlsx" class="kt-input" multiple required>
                </div>
                <div class="kt-modal-footer justify-end">
                    <button type="submit" class="kt-btn kt-btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        (function () {
            var panel = document.getElementById('enrollment_import_timeline');
            if (!panel) return;
            var url = '{{ route('educator.enrollment.imports.timeline') }}';
            var timer = null;

            function isActive() {
                var root = panel.querySelector('[data-import-timeline]');
                return root && root.dataset.active === '1';
            }
            function schedule() {
                clearTimeout(timer);
                if (isActive()) timer = setTimeout(poll, 4000);
            }
            function poll() {
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                    .then(function (r) { return r.ok ? r.text() : null; })
                    .then(function (html) {
                        if (html === null) return;
                        panel.innerHTML = html;
                        // The collapse toggle button is re-rendered every swap; KTUI only
                        // auto-inits [data-kt-toggle] elements present at DOMContentLoaded.
                        if (window.KTToggle) KTToggle.init();
                    })
                    .catch(function () {})
                    .finally(schedule);
            }
            schedule();
        })();
    </script>
    @endpush
@endsection
