{{-- G1: educator dashboard — two-panel (main analytics + right calendar/details). --}}
@extends('educator.layout')

@section('title', 'Educator Dashboard')
@section('heading', 'Dashboard')

@section('content')
    {{-- Two-panel split: flexible main + fixed-width right rail (the static Metronic bundle has no
         col-span-N helpers, so the wide/narrow ratio is set with a nonce'd grid-template override). --}}
    <style nonce="{{ $cspNonce ?? '' }}">
        @media (min-width: 1280px) { #dash_layout { grid-template-columns: minmax(0, 1fr) 360px; } }
    </style>
    {{-- Full-width KPI row (above the two-panel split so the cards get room to breathe). --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-5 mb-5 lg:mb-7.5">
        <x-stat-card label="Sections handled" :value="$sectionCount" icon="abstract-26" />
        <x-stat-card label="Subjects taught" :value="$subjectCount" icon="book" />
        <x-stat-card label="Enrolled students" :value="$studentCount" icon="people" />
        <x-stat-card label="Pending assessments" :value="$pendingCount" icon="questionnaire-tablet" />
    </div>

    <div id="dash_layout" class="grid gap-5 lg:gap-7.5">
        {{-- ============ MAIN PANEL ============ --}}
        <div class="flex flex-col gap-5 lg:gap-7.5 min-w-0">
            {{-- Grouped bar: avg score % per section by subject --}}
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Average score by section</h3>
                </div>
                <div class="kt-card-content">
                    <div id="educator_perf_chart"></div>
                </div>
            </div>

            {{-- Area: quiz-activity trend --}}
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Quiz activity</h3>
                </div>
                <div class="kt-card-content">
                    <div id="educator_trend_chart"></div>
                </div>
            </div>

            {{-- Sections table --}}
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">My sections</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table kt-table-border align-middle text-sm w-full">
                            <thead>
                                <tr>
                                    <th class="text-start">Section</th>
                                    <th class="text-start">Subjects</th>
                                    <th class="text-center">Enrolled</th>
                                    <th class="text-center">Avg score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sectionTable as $row)
                                    <tr>
                                        <td class="font-medium text-mono">{{ $row['name'] }}</td>
                                        <td class="text-secondary-foreground">{{ $row['subjects'] }}</td>
                                        <td class="text-center">{{ $row['enrolled'] }}</td>
                                        <td class="text-center">{{ $row['avg'] !== null ? $row['avg'].'%' : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-secondary-foreground py-5">No sections yet.</td></tr>
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

            {{-- Teaching load + next class --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">Teaching load</h3></div>
                <div class="kt-card-content flex flex-col gap-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-foreground">Sections / Subjects</span>
                        <span class="font-medium text-mono">{{ $sectionCount }} / {{ $subjectCount }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-foreground">Students</span>
                        <span class="font-medium text-mono">{{ $studentCount }}</span>
                    </div>
                    <div class="kt-card-content border-t border-border pt-3 px-0 pb-0">
                        <span class="text-xs uppercase text-muted-foreground">Next assessment</span>
                        @if ($nextAssessment)
                            <div class="mt-1">
                                <span class="font-medium text-mono">{{ $nextAssessment->assessment_code }}</span>
                                <span class="text-secondary-foreground">· {{ $nextAssessment->subject?->subject_name }}</span>
                                <div class="text-xs text-muted-foreground">{{ optional($nextAssessment->start_date)->format('M j, Y') }}</div>
                            </div>
                        @else
                            <div class="mt-1 text-secondary-foreground">Nothing scheduled.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- This week's assessments --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">This week</h3></div>
                <div class="kt-card-content flex flex-col gap-2.5">
                    @forelse ($weekAssessments as $a)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-border p-3">
                            <div class="min-w-0">
                                <div class="font-medium text-mono truncate">{{ $a->assessment_code }}</div>
                                <div class="text-xs text-secondary-foreground truncate">{{ $a->subject?->subject_name }}</div>
                            </div>
                            <span class="text-xs text-muted-foreground shrink-0">{{ optional($a->end_date)->format('M j') }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-secondary-foreground">No assessments this week.</div>
                    @endforelse
                </div>
            </div>

            {{-- Notifications --}}
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Notifications</h3>
                    @if ($unreadCount)
                        <span class="kt-badge kt-badge-sm kt-badge-primary">{{ $unreadCount }} new</span>
                    @endif
                </div>
                <div class="kt-card-content flex flex-col gap-2.5">
                    @forelse ($recentNotifications as $n)
                        <a href="{{ $n->linkHref }}" class="flex flex-col gap-0.5 rounded-lg border border-border p-3 hover:bg-accent/40">
                            <span class="text-sm font-medium text-mono truncate">{{ $n->title }}</span>
                            <span class="text-xs text-secondary-foreground line-clamp-2">{{ $n->message }}</span>
                        </a>
                    @empty
                        <div class="text-sm text-secondary-foreground">No notifications.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function () {
            var mutedColor = '#99a1b7';
            // Grouped bar: avg score % per section, series per subject.
            var perfEl = document.getElementById('educator_perf_chart');
            if (perfEl && typeof ApexCharts !== 'undefined') {
                new ApexCharts(perfEl, {
                    chart: { type: 'bar', height: 300, toolbar: { show: false } },
                    series: @json($perfSeries),
                    xaxis: { categories: @json($perfCategories) },
                    yaxis: { max: 100, labels: { formatter: function (v) { return Math.round(v) + '%'; } } },
                    plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
                    dataLabels: { enabled: false },
                    legend: { position: 'top' },
                    noData: { text: 'No score data yet' },
                }).render();
            }
            // Area: quiz-activity trend.
            var trendEl = document.getElementById('educator_trend_chart');
            if (trendEl && typeof ApexCharts !== 'undefined') {
                new ApexCharts(trendEl, {
                    chart: { type: 'area', height: 260, toolbar: { show: false } },
                    series: [{ name: 'Quizzes taken', data: @json($trendData) }],
                    xaxis: { categories: @json($trendLabels) },
                    stroke: { curve: 'smooth', width: 2 },
                    dataLabels: { enabled: false },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
                    noData: { text: 'No quiz activity yet' },
                }).render();
            }
        });
    </script>
    @endpush
@endsection
