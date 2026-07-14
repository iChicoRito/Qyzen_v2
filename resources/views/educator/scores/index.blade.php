@extends('educator.layout')
@section('title', 'Scores')
@section('heading', 'Scores')
@section('toolbar')
    <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" data-kt-modal-toggle="#score_upload_modal">Upload offline scores</button>
    <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" data-kt-modal-toggle="#export_modal">Download Grades</button>
@endsection
@section('content')
    @include('admin._status')
    <x-data-table id="scores_table" search-placeholder="Search students, assessments, subjects" :paginator="$scores">
        <x-slot:filters>
            <select data-filter="assessment" data-depends-on="subject" class="kt-select w-36">
                <option value="">All assessments</option>
                @foreach ($filterAssessments as $code)
                    <option value="{{ $code }}">{{ $code }}</option>
                @endforeach
            </select>
            <select data-filter="subject" data-depends-on="section" class="kt-select w-40">
                <option value="">All subjects</option>
                @foreach ($filterSubjects as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->subject_code }} — {{ $sub->subject_name }}</option>
                @endforeach
            </select>
            <select data-filter="section" class="kt-select w-32">
                <option value="">All sections</option>
                @foreach ($filterSections as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->section_name }}</option>
                @endforeach
            </select>
            <select data-filter="term" class="kt-select w-32">
                <option value="">All terms</option>
                @foreach ($filterTerms as $t)
                    <option value="{{ $t->id }}">{{ $t->term_name }}</option>
                @endforeach
            </select>
            <select data-filter="result" class="kt-select w-36">
                <option value="">All results</option>
                <option value="passed">Passed</option>
                <option value="failed">Failed</option>
            </select>
        </x-slot:filters>
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[220px]" data-sort="student"><span class="kt-table-col"><span class="kt-table-col-label">Student</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[140px]" data-sort="assessment"><span class="kt-table-col"><span class="kt-table-col-label">Assessment</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[160px]" data-sort="subject"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]" data-sort="section"><span class="kt-table-col"><span class="kt-table-col-label">Section</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[120px]" data-sort="term"><span class="kt-table-col"><span class="kt-table-col-label">Term</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]" data-sort="score"><span class="kt-table-col"><span class="kt-table-col-label">Best score</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[100px]" data-sort="attempts"><span class="kt-table-col"><span class="kt-table-col-label">Attempts</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[160px]" data-sort="submitted"><span class="kt-table-col"><span class="kt-table-col-label">Latest submission</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($scores as $s)
            @php $st = $s->student; $initial = strtoupper(mb_substr($st?->surname ?: ($st?->given_name ?: '?'), 0, 1)); @endphp
            <tr>
                <td>
                    <div class="flex items-center gap-2.5">
                        @if ($st?->profile_picture)
                            <img class="rounded-full size-9 shrink-0" src="{{ \Illuminate\Support\Facades\Storage::disk('profile_media')->url($st->profile_picture) }}" alt="{{ $st->name }}" />
                        @else
                            <span class="inline-flex items-center justify-center rounded-full size-9 shrink-0 bg-primary/10 text-primary text-sm font-semibold">{{ $initial }}</span>
                        @endif
                        <span class="text-sm"><span class="text-mono font-semibold">{{ $st?->surname ?? '—' }}</span> <span class="text-secondary-foreground">{{ $st?->given_name }}</span></span>
                    </div>
                </td>
                <td>
                    <span data-filter-value="assessment" data-filter-key="{{ $s->assessment?->assessment_code }}" hidden></span>
                    {{ optional($s->assessment)->assessment_code ?? '—' }}
                </td>
                <td>
                    <span data-filter-value="subject" data-filter-key="{{ $s->subject_id }}" hidden></span>
                    {{ optional($s->subject)->subject_code ?? '—' }}
                </td>
                <td>
                    <span data-filter-value="section" data-filter-key="{{ $s->section_id }}" hidden></span>
                    {{ optional($s->section)->section_name ?? '—' }}
                </td>
                <td class="text-secondary-foreground">
                    <span data-filter-value="term" data-filter-key="{{ $s->assessment?->term }}" hidden></span>
                    {{ optional($s->assessment?->academicTerm)->term_name ?? '—' }}
                </td>
                <td>Best: {{ $s->best_score ?? 0 }}/{{ $s->best_total_questions ?? 0 }}</td>
                <td>{{ $s->attempts_count }} {{ $s->attempts_count === 1 ? 'attempt' : 'attempts' }}</td>
                <td class="text-secondary-foreground">{{ optional($s->submitted_at)->format('Y-m-d H:i') ?? '—' }}</td>
                <td class="text-center">
                    <x-table-actions :view-modal="route('educator.scores.show', $s)" view-modal-title="Attempt detail">
                        <div class="kt-menu-item">
                            <a class="kt-menu-link" href="#" data-modal-url="{{ route('educator.scores.delete', $s) }}" data-modal-target="#form_modal" data-modal-title="Delete score">
                                <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span>
                                <span class="kt-menu-title">Delete Score</span>
                            </a>
                        </div>
                    </x-table-actions>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-secondary-foreground py-5">No scores yet.</td></tr>
        @endforelse
    </x-data-table>

    @include('educator.scores._export_modal')

    @php
        $twoLine = fn(string $v1, string $v2) =>
            '<div class=\"flex items-center justify-between gap-2\">'
            . '<div class=\"flex flex-col gap-0.5\">'
            . '<span class=\"text-sm font-medium\">{{text}}</span>'
            . '<span class=\"text-xs text-secondary-foreground\">{{' . $v2 . '}}</span>'
            . '</div>'
            . '<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"size-3.5 shrink-0 hidden text-primary kt-select-option-selected:block\">'
            . '<path d=\"M20 6 9 17l-5-5\"/></svg></div>';
    @endphp
    <div class="kt-modal" data-kt-modal="true" id="score_upload_modal">
        <div class="kt-modal-content top-[15%]" style="width: 100%; max-width: min(92vw, 500px);">
            <form method="POST" action="{{ route('educator.scores.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="kt-modal-header">
                    <h3 class="kt-modal-title">Upload offline scores</h3>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="kt-modal-body flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-3">
                        <label class="kt-form-label">Template</label>
                        <a href="{{ route('educator.scores.upload.template') }}" class="kt-btn kt-btn-sm kt-btn-outline shrink-0">
                            <i class="ki-filled ki-cloud-download"></i> Download
                        </a>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Term</label>
                        <select id="upload_term_sel" name="term_id" class="kt-select" required
                            data-kt-select="true"
                            data-kt-select-placeholder="Select term">
                            <option value="">Select term</option>
                        </select>
                        @error('term_id')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
                    </div>
                    {{-- Section filter (not posted — narrows assessment list) --}}
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Section <span class="text-secondary-foreground font-normal">(optional filter)</span></label>
                        <select id="upload_section_sel" class="kt-select"
                            data-kt-select="true"
                            data-kt-select-config='{"optionTemplate":"{{ $twoLine('text','term') }}"}'
                            data-kt-select-enable-search="true"
                            data-kt-select-placeholder="All sections">
                            <option value="">All sections</option>
                        </select>
                    </div>
                    {{-- Assessment — posts assessment_uuid --}}
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Assessment</label>
                        <select id="upload_assessment_sel" name="assessment_uuid" class="kt-select" required
                            data-kt-select="true"
                            data-kt-select-config='{"optionTemplate":"{{ $twoLine('text','description') }}"}'
                            data-kt-select-enable-search="true"
                            data-kt-select-placeholder="Select assessment">
                            <option value="">Select assessment</option>
                        </select>
                        @error('assessment_uuid')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
                    </div>
                    <p class="text-sm text-secondary-foreground">Columns: student_id, score. Question count comes from the selected assessment.</p>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="kt-input" required>
                    @error('file')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
                </div>
                <div class="kt-modal-footer justify-end">
                    <button type="submit" class="kt-btn kt-btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}" data-ajax-rerun>
    (function () {
        var opts = window.__exportOptions || [];
        if (!opts.length) return;

        var termSel = document.getElementById('upload_term_sel');
        var secSel = document.getElementById('upload_section_sel');
        var assSel = document.getElementById('upload_assessment_sel');

        var seenTerms = {};
        opts.forEach(function (o) {
            if (!o.termId || seenTerms[o.termId]) return;
            seenTerms[o.termId] = true;
            var el = document.createElement('option');
            el.value = o.termId;
            el.textContent = o.termLabel || 'Untitled term';
            termSel.appendChild(el);
        });

        // Unique sections
        var seen = {};
        opts.forEach(function (o) {
            if (seen[o.sectionId]) return;
            seen[o.sectionId] = true;
            var el = document.createElement('option');
            el.value = o.sectionId;
            el.textContent = o.sectionLabel;
            el.setAttribute('data-kt-select-option', JSON.stringify({ term: o.termLabel || '' }));
            secSel.appendChild(el);
        });

        function fillAssessments(sectionId) {
            while (assSel.options.length > 1) assSel.remove(1);
            opts.forEach(function (o) {
                if (!termSel.value || String(o.termId) !== String(termSel.value)) return;
                if (sectionId && String(o.sectionId) !== String(sectionId)) return;
                var el = document.createElement('option');
                el.value = o.uuid;
                el.textContent = o.assessmentCode;
                el.setAttribute('data-kt-select-option', JSON.stringify({ description: o.subjectLabel || '' }));
                assSel.appendChild(el);
            });
        }

        termSel.addEventListener('change', function () {
            assSel.value = '';
            fillAssessments(secSel.value || null);
        });
        secSel.addEventListener('change', function () {
            assSel.value = '';
            fillAssessments(secSel.value || null);
        });
    })();
    </script>
    @endpush

    <x-modal id="form_modal" width="760px" />
@endsection
