{{-- H1: student dashboard — two-panel (main analytics + right calendar/details). --}}
@extends('student.layout')

@section('title', 'Student Dashboard')
@section('heading', 'Dashboard')

@php
    $badgeClass = fn ($b) => [
        'Available' => 'success', 'Reopened' => 'warning', 'Upcoming' => 'primary',
        'Expired' => 'destructive', 'Schedule issue' => 'secondary',
    ][$b] ?? 'secondary';
@endphp

@section('content')
    <style nonce="{{ $cspNonce ?? '' }}">
        @media (min-width: 1280px) { #dash_layout { grid-template-columns: minmax(0, 1fr) 360px; } }
    </style>
    {{-- Full-width KPI row (above the two-panel split so the cards get room to breathe). --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-5 mb-5 lg:mb-7.5">
        <x-stat-card label="Overall average" :value="round($overallAvg).'%'" icon="chart-line-up" />
        <x-stat-card label="Enrolled subjects" :value="$subjectCount" icon="book" />
        <x-stat-card label="Pass rate" :value="$passRate.'%'" icon="verify" />
        <x-stat-card label="Pending assessments" :value="$pendingCount" icon="questionnaire-tablet" />
    </div>

    <div id="dash_layout" class="grid gap-5 lg:gap-7.5">
        {{-- ============ MAIN PANEL ============ --}}
        <div class="flex flex-col gap-5 lg:gap-7.5 min-w-0">
            {{-- Donut: avg score % by subject + completion progress bars --}}
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                <div class="kt-card">
                    <div class="kt-card-header"><h3 class="kt-card-title">Average by subject</h3></div>
                    <div class="kt-card-content"><div id="student_donut_chart"></div></div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header"><h3 class="kt-card-title">Completion by subject</h3></div>
                    <div class="kt-card-content flex flex-col gap-4">
                        @forelse ($completion as $c)
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-mono truncate">{{ $c['name'] }}</span>
                                    <span class="text-secondary-foreground">{{ $c['pct'] }}%</span>
                                </div>
                                <div class="kt-progress"><div class="kt-progress-indicator" style="width: {{ $c['pct'] }}%"></div></div>
                            </div>
                        @empty
                            <div class="text-sm text-secondary-foreground">No subjects yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Upcoming assessments timeline --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">Upcoming assessments</h3></div>
                <div class="kt-card-content flex flex-col gap-2.5">
                    @forelse ($upcoming as $a)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-border p-3">
                            <div class="min-w-0">
                                <div class="font-medium text-mono truncate">{{ $a->assessment_code }}</div>
                                <div class="text-xs text-secondary-foreground truncate">{{ $a->subject?->subject_name }}</div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs text-muted-foreground">Due {{ optional($a->end_date)->format('M j') }}</span>
                                <span class="kt-badge kt-badge-sm kt-badge-{{ $badgeClass($badges[$a->id] ?? '') }}">{{ $badges[$a->id] ?? '—' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-secondary-foreground">No upcoming assessments.</div>
                    @endforelse
                </div>
            </div>

            {{-- My subjects table --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">My subjects</h3></div>
                <div class="kt-card-content p-0">
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table kt-table-border align-middle text-sm w-full">
                            <thead>
                                <tr>
                                    <th class="text-start">Subject</th>
                                    <th class="text-start">Section</th>
                                    <th class="text-start">Educator</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subjects as $s)
                                    <tr>
                                        <td class="font-medium text-mono">{{ $s->subject_name }}</td>
                                        <td class="text-secondary-foreground">{{ $s->section?->section_name ?? '—' }}</td>
                                        <td class="text-secondary-foreground">{{ $s->educator?->name ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-secondary-foreground py-5">Not enrolled in any subject.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ RIGHT PANEL ============ --}}
        <div class="flex flex-col gap-5 lg:gap-7.5 min-w-0">
            @include('partials._dashboard_calendar', ['events' => $calendarEvents])

            {{-- Enrollment summary + next deadline --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">My enrollment</h3></div>
                <div class="kt-card-content flex flex-col gap-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-secondary-foreground shrink-0">Section(s)</span>
                        <span class="font-medium text-mono text-end">{{ $sectionNames->join(', ') ?: '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-foreground">Subjects</span>
                        <span class="font-medium text-mono">{{ $subjectCount }}</span>
                    </div>
                    <div class="border-t border-border pt-3">
                        <span class="text-xs uppercase text-muted-foreground">Next deadline</span>
                        @if ($nextDeadline)
                            <div class="mt-1">
                                <span class="font-medium text-mono">{{ $nextDeadline->assessment_code }}</span>
                                <div class="text-xs text-muted-foreground">Due {{ optional($nextDeadline->end_date)->format('M j, Y') }}</div>
                            </div>
                        @else
                            <div class="mt-1 text-secondary-foreground">Nothing due.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent grades --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">Recent grades</h3></div>
                <div class="kt-card-content flex flex-col gap-2.5">
                    @forelse ($recentGrades as $g)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-border p-3">
                            <div class="min-w-0">
                                <div class="font-medium text-mono truncate">{{ $g->subject?->subject_name ?? 'Quiz' }}</div>
                                <div class="text-xs text-secondary-foreground">{{ $g->score }}/{{ $g->total_questions }}</div>
                            </div>
                            <span class="kt-badge kt-badge-sm kt-badge-{{ $g->is_passed ? 'success' : 'destructive' }}">
                                {{ $g->is_passed ? 'Passed' : 'Failed' }}
                            </span>
                        </div>
                    @empty
                        <div class="text-sm text-secondary-foreground">No grades yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function () {
            var donutEl = document.getElementById('student_donut_chart');
            if (donutEl && typeof ApexCharts !== 'undefined') {
                new ApexCharts(donutEl, {
                    chart: { type: 'donut', height: 300 },
                    series: @json($donutData),
                    labels: @json($donutLabels),
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: true, formatter: function (v) { return Math.round(v) + '%'; } },
                    noData: { text: 'No score data yet' },
                }).render();
            }
        });
    </script>
    @endpush
@endsection
