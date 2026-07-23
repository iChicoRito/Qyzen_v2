{{-- Task 27: Download Grades modal — Single (cascading Section → Subject → Assessment, with a
     debounced preview) and Multiple (All / By Term / By Semester) tabs. $exportOptions is this
     educator's own assessments, flattened + inlined below so the cascading selects need no
     fetch to populate. --}}
<x-modal id="export_modal" width="640px" title="Download Grades">
    <script nonce="{{ $cspNonce ?? '' }}" data-ajax-rerun>window.__exportOptions = @json($exportOptions);</script>
    <style nonce="{{ $cspNonce ?? '' }}">
        #export_modal .export-method-card:has(input:checked) {
            border-color: var(--color-primary);
            background: color-mix(in srgb, var(--color-primary) 6%, transparent);
        }
        #export_modal .export-method-card:has(input:focus-visible) {
            outline: 2px solid var(--color-primary);
            outline-offset: 1px;
        }
    </style>

    @if ($exportOptions->isEmpty())
        <div class="text-center text-secondary-foreground py-10">No assessments found yet.</div>
    @else
        <div class="kt-toggle-group mb-4" data-kt-tabs="true">
            <a class="kt-btn active" data-kt-tab-toggle="#export_single" href="#">Single</a>
            <a class="kt-btn" data-kt-tab-toggle="#export_multiple" href="#">Multiple</a>
        </div>

        <div id="export_single" class="flex flex-col gap-3 pb-5">
            <div class="flex flex-col gap-1">
                <label class="kt-form-label">Section</label>
                <select id="export_section" class="kt-select" data-kt-select="true" data-kt-select-enable-search="true" data-kt-select-search-placeholder="Search sections...">
                    <option value="">Select section</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="kt-form-label">Subject</label>
                <select id="export_subject" class="kt-select" disabled data-kt-select="true" data-kt-select-enable-search="true" data-kt-select-search-placeholder="Search subjects...">
                    <option value="">Select subject</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="kt-form-label">Assessment</label>
                <select id="export_assessment" class="kt-select" disabled>
                    <option value="">Select assessment</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="kt-form-label">Academic Term</label>
                <input type="text" id="export_term" class="kt-input" readonly placeholder="—" />
            </div>

            <div id="export_preview" class="hidden kt-card p-4 bg-muted/40 text-sm"></div>

            <div class="flex justify-end mt-2">
                <a id="export_single_btn" href="#" class="kt-btn kt-btn-primary opacity-50 pointer-events-none" aria-disabled="true">
                    Export
                </a>
            </div>
        </div>

        <div id="export_multiple" class="hidden flex flex-col gap-3 pb-5">
            <div class="flex flex-col gap-1.5">
                <label class="kt-form-label">Method</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <label class="export-method-card flex items-center gap-2 border border-border rounded-lg p-3 cursor-pointer transition-colors hover:border-primary/50">
                        <input type="radio" name="export_method" value="all" checked class="kt-radio kt-radio-sm shrink-0" data-export-method>
                        <span class="flex flex-col gap-0.5">
                            <span class="text-sm font-medium text-mono">All</span>
                            <span class="text-xs text-secondary-foreground">Every assessment across all your classes, terms, and semesters</span>
                        </span>
                    </label>
                    <label class="export-method-card flex items-center gap-2 border border-border rounded-lg p-3 cursor-pointer transition-colors hover:border-primary/50">
                        <input type="radio" name="export_method" value="term" class="kt-radio kt-radio-sm shrink-0" data-export-method>
                        <span class="flex flex-col gap-0.5">
                            <span class="text-sm font-medium text-mono">By Term</span>
                            <span class="text-xs text-secondary-foreground">Every assessment in one academic term (e.g. Prelim), across all subjects and sections</span>
                        </span>
                    </label>
                    <label class="export-method-card flex items-center gap-2 border border-border rounded-lg p-3 cursor-pointer transition-colors hover:border-primary/50">
                        <input type="radio" name="export_method" value="semester" class="kt-radio kt-radio-sm shrink-0" data-export-method>
                        <span class="flex flex-col gap-0.5">
                            <span class="text-sm font-medium text-mono">By Semester</span>
                            <span class="text-xs text-secondary-foreground">Every assessment in one academic year and semester (e.g. 2025-2026, 1st Semester)</span>
                        </span>
                    </label>
                </div>
            </div>

            <div id="export_term_picker" class="hidden flex flex-col gap-1">
                <label class="kt-form-label">Term</label>
                <select id="export_bulk_term" class="kt-select">
                    <option value="">Select term</option>
                </select>
            </div>

            <div id="export_semester_picker" class="hidden flex flex-col gap-2">
                <div class="flex flex-col gap-1">
                    <label class="kt-form-label">Academic Year</label>
                    <select id="export_bulk_year" class="kt-select">
                        <option value="">Select academic year</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="kt-form-label">Semester</label>
                    <select id="export_bulk_semester" class="kt-select">
                        <option value="">Select semester</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-2">
                <a id="export_bulk_btn" href="{{ route('educator.scores.export-bulk', ['type' => 'all']) }}" class="kt-btn kt-btn-primary">
                    Download
                </a>
            </div>
        </div>
    @endif
</x-modal>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}" data-ajax-rerun>
(function () {
    var options = window.__exportOptions || [];
    if (!options.length) return;

    var subjectSel = document.getElementById('export_subject');
    var sectionSel = document.getElementById('export_section');
    var assessmentSel = document.getElementById('export_assessment');
    var termInput = document.getElementById('export_term');
    var preview = document.getElementById('export_preview');
    var exportBtn = document.getElementById('export_single_btn');

    function unique(items, key) {
        var seen = {}, out = [];
        items.forEach(function (i) {
            var k = i[key];
            if (k === null || k === undefined || seen[k]) return;
            seen[k] = true;
            out.push(i);
        });
        return out;
    }

    function fillSelect(select, items, valueKey, labelKey, placeholder) {
        select.innerHTML = '<option value="">' + placeholder + '</option>';
        items.forEach(function (i) {
            var opt = document.createElement('option');
            opt.value = i[valueKey];
            opt.textContent = i[labelKey];
            select.appendChild(opt);
        });
    }

    function resetPreview() {
        preview.classList.add('hidden');
        preview.innerHTML = '';
        exportBtn.classList.add('opacity-50', 'pointer-events-none');
        exportBtn.setAttribute('aria-disabled', 'true');
        exportBtn.href = '#';
    }

    function resetFrom(level) {
        if (level <= 1) { subjectSel.innerHTML = '<option value="">Select subject</option>'; subjectSel.disabled = true; }
        if (level <= 2) { assessmentSel.innerHTML = '<option value="">Select assessment</option>'; assessmentSel.disabled = true; }
        termInput.value = '';
        resetPreview();
    }

    fillSelect(sectionSel, unique(options, 'sectionId'), 'sectionId', 'sectionLabel', 'Select section');

    sectionSel.addEventListener('change', function () {
        resetFrom(1);
        if (!sectionSel.value) return;
        var matches = options.filter(function (o) { return String(o.sectionId) === sectionSel.value; });
        fillSelect(subjectSel, unique(matches, 'subjectId'), 'subjectId', 'subjectLabel', 'Select subject');
        subjectSel.disabled = false;
    });

    subjectSel.addEventListener('change', function () {
        resetFrom(2);
        if (!subjectSel.value) return;
        var matches = options.filter(function (o) {
            return String(o.subjectId) === subjectSel.value && String(o.sectionId) === sectionSel.value;
        });
        fillSelect(assessmentSel, matches, 'uuid', 'assessmentCode', 'Select assessment');
        assessmentSel.disabled = false;
    });

    var previewTimer = null;
    assessmentSel.addEventListener('change', function () {
        resetPreview();
        var match = options.find(function (o) { return o.uuid === assessmentSel.value; });
        termInput.value = match ? match.termLabel : '';
        if (!match) return;

        clearTimeout(previewTimer);
        previewTimer = setTimeout(function () {
            fetch('{{ url("educator/scores/export/preview") }}/' + match.uuid, {
                headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin',
            })
                .then(function (r) { if (!r.ok) throw r; return r.json(); })
                .then(function (data) {
                    preview.innerHTML =
                        '<div class="grid grid-cols-3 gap-3 text-center">' +
                        '<div><div class="text-lg font-semibold">' + data.enrolled + '</div><div class="text-secondary-foreground text-xs">Enrolled</div></div>' +
                        '<div><div class="text-lg font-semibold text-success">' + data.withSubmission + '</div><div class="text-secondary-foreground text-xs">With Submission</div></div>' +
                        '<div><div class="text-lg font-semibold text-destructive">' + data.withoutSubmission + '</div><div class="text-secondary-foreground text-xs">No Submission</div></div>' +
                        '</div>';
                    preview.classList.remove('hidden');
                    exportBtn.classList.remove('opacity-50', 'pointer-events-none');
                    exportBtn.removeAttribute('aria-disabled');
                    exportBtn.href = '{{ url("educator/scores/export") }}/' + match.uuid;
                })
                .catch(function () {
                    if (window.KTToast) {
                        KTToast.show({ message: 'Could not load preview.', variant: 'destructive', appearance: 'outline', dismiss: true });
                    }
                });
        }, 300);
    });

    function spinOnClick(link) {
        link.addEventListener('click', function () {
            if (link.classList.contains('pointer-events-none') || link.dataset.spinning) return;
            link.dataset.spinning = '1';
            link.insertAdjacentHTML('beforeend', ' <i class="ki-filled ki-loading animate-spin ms-2"></i>');
        });
    }
    spinOnClick(exportBtn);

    // ---- Multiple tab ----
    var methodRadios = document.querySelectorAll('[data-export-method]');
    var termPicker = document.getElementById('export_term_picker');
    var semesterPicker = document.getElementById('export_semester_picker');
    var bulkTermSel = document.getElementById('export_bulk_term');
    var bulkYearSel = document.getElementById('export_bulk_year');
    var bulkSemesterSel = document.getElementById('export_bulk_semester');
    var bulkBtn = document.getElementById('export_bulk_btn');

    fillSelect(bulkTermSel, unique(options, 'termId'), 'termId', 'termLabel', 'Select term');
    fillSelect(bulkYearSel, unique(options, 'academicYear'), 'academicYear', 'academicYear', 'Select academic year');
    fillSelect(bulkSemesterSel, unique(options, 'semester'), 'semester', 'semester', 'Select semester');

    var bulkBase = bulkBtn.getAttribute('href');

    function updateBulkHref() {
        var method = document.querySelector('[data-export-method]:checked').value;
        termPicker.classList.toggle('hidden', method !== 'term');
        semesterPicker.classList.toggle('hidden', method !== 'semester');

        var params = new URLSearchParams({ type: method });
        if (method === 'term') { params.set('termId', bulkTermSel.value); }
        if (method === 'semester') { params.set('academicYear', bulkYearSel.value); params.set('semester', bulkSemesterSel.value); }
        bulkBtn.href = bulkBase.split('?')[0] + '?' + params.toString();
    }

    methodRadios.forEach(function (r) { r.addEventListener('change', updateBulkHref); });
    bulkTermSel.addEventListener('change', updateBulkHref);
    bulkYearSel.addEventListener('change', updateBulkHref);
    bulkSemesterSel.addEventListener('change', updateBulkHref);
    updateBulkHref();
    spinOnClick(bulkBtn);
})();
</script>
@endpush
