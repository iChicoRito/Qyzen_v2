{{-- G1: educator dashboard — two-panel (main analytics + right calendar/details). --}}
@extends('educator.layout')

@section('title', 'Educator Dashboard')
@section('heading', 'Dashboard')

@section('content')
    {{-- Two-panel split: flexible main + fixed-width right rail (the static Metronic bundle has no
         col-span-N helpers, so the wide/narrow ratio is set with a nonce'd grid-template override). --}}
    <style nonce="{{ $cspNonce ?? '' }}">
        @media (min-width: 1280px) { #dash_layout { grid-template-columns: minmax(0, 1fr) 360px; } }
        #educator_assessment_heatmap .apexcharts-legend { flex-direction: row !important; flex-wrap: wrap !important; }
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
            {{-- Area: quiz-activity trend --}}
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Quiz activity</h3>
                </div>
                <div class="kt-card-content">
                    <div id="educator_trend_chart"></div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">Assessments created</h3></div>
                <div class="kt-card-content"><div id="educator_assessment_heatmap"></div></div>
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

        </div>
    </div>

    @push('styles')
    <link href="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet" />
    @endpush

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function () {
            var mutedColor = '#99a1b7';
            var heatmapEl = document.getElementById('educator_assessment_heatmap');
            if (heatmapEl && typeof ApexCharts !== 'undefined') {
                var heatmapChart;
                var renderHeatmap = function () {
                    if (heatmapChart) {
                        heatmapChart.destroy();
                    }

                    var rootStyles = getComputedStyle(document.documentElement);
                    var isDark = document.documentElement.classList.contains('dark');
                    var heatmapLegendColor = rootStyles.getPropertyValue('--foreground').trim() || (isDark ? '#e5e7eb' : '#111827');
                    var heatmapPalette = isDark
                        ? ['#0f172a', '#1e3a8a', '#2563eb', '#60a5fa']
                        : ['#eef4ff', '#cfe0ff', '#8bb7ff', '#1b84ff'];

                    heatmapChart = new ApexCharts(heatmapEl, {
                        chart: {
                            type: 'heatmap',
                            height: 320,
                            toolbar: { show: false },
                            foreColor: heatmapLegendColor,
                        },
                        series: @json($assessmentHeatmap),
                        xaxis: { categories: @json($heatmapLabels) },
                        dataLabels: { enabled: false },
                        stroke: { width: 3, colors: [isDark ? '#111827' : 'var(--card)'] },
                        plotOptions: {
                            heatmap: {
                                radius: 2,
                                colorScale: {
                                    ranges: [
                                        { from: 0, to: 0, name: 'No activity', color: heatmapPalette[0] },
                                        { from: 1, to: 1, name: '1 assessment', color: heatmapPalette[1] },
                                        { from: 2, to: 3, name: '2-3 assessments', color: heatmapPalette[2] },
                                        { from: 4, to: Number.MAX_SAFE_INTEGER, name: '4+ assessments', color: heatmapPalette[3] },
                                    ],
                                },
                            },
                        },
                        legend: {
                            show: true,
                            position: 'bottom',
                            horizontalAlign: 'center',
                            fontSize: '12px',
                            labels: { colors: heatmapLegendColor },
                            markers: { width: 10, height: 10, radius: 2 },
                            itemMargin: { horizontal: 16, vertical: 0 },
                        },
                        tooltip: { y: { formatter: function (value) { return value + (value === 1 ? ' assessment' : ' assessments'); } } },
                        noData: { text: 'No assessments created yet' },
                    });

                    heatmapChart.render();
                };

                renderHeatmap();

                new MutationObserver(function () {
                    renderHeatmap();
                }).observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class', 'data-kt-theme-mode'],
                });
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






