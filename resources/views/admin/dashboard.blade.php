{{-- F1: admin dashboard — two-panel (main analytics + right calendar/details). --}}
@extends('admin.layout')

@section('title', 'Admin Dashboard')
@section('heading', 'Dashboard')

@section('content')
    {{-- The prebuilt Metronic CSS doesn't ship sm:grid-cols-3 / xl:grid-cols-5, so the 5-card KPI row
         is sized here (same nonce'd-override approach as #dash_layout). --}}
    <style nonce="{{ $cspNonce ?? '' }}">
        #kpi_row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (min-width: 768px) { #kpi_row { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (min-width: 1280px) { #kpi_row { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
        @media (min-width: 1280px) { #dash_layout { grid-template-columns: minmax(0, 1fr) 360px; } }
    </style>
    {{-- Full-width KPI row (above the two-panel split so the cards get room to breathe). --}}
    <div id="kpi_row" class="grid gap-5 mb-5 lg:mb-7.5">
        <x-stat-card label="Educators" :value="$educatorCount" icon="teacher" />
        <x-stat-card label="Students" :value="$studentCount" icon="people" />
        <x-stat-card label="Sections" :value="$sectionCount" icon="abstract-26" />
        <x-stat-card label="Subjects" :value="$subjectCount" icon="book" />
        <x-stat-card label="Avg score" :value="round($systemAvg).'%'" icon="chart-simple" />
    </div>

    <div id="dash_layout" class="grid gap-5 lg:gap-7.5">
        {{-- ============ MAIN PANEL ============ --}}
        <div class="flex flex-col gap-5 lg:gap-7.5 min-w-0">
            {{-- Bar: avg score % per educator --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">Performance by educator</h3></div>
                <div class="kt-card-content"><div id="admin_perf_chart"></div></div>
            </div>

            {{-- Line: institution-wide quiz activity --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">Quiz activity (institution-wide)</h3></div>
                <div class="kt-card-content"><div id="admin_trend_chart"></div></div>
            </div>

            {{-- Educators table --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">Educators</h3></div>
                <div class="kt-card-content p-0">
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table kt-table-border align-middle text-sm w-full">
                            <thead>
                                <tr>
                                    <th class="text-start">Name</th>
                                    <th class="text-center">Sections</th>
                                    <th class="text-center">Students</th>
                                    <th class="text-center">Avg performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($educatorTable as $row)
                                    <tr>
                                        <td class="font-medium text-mono">{{ $row['name'] }}</td>
                                        <td class="text-center">{{ $row['sections'] }}</td>
                                        <td class="text-center">{{ $row['students'] }}</td>
                                        <td class="text-center">{{ $row['avg'] !== null ? $row['avg'].'%' : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-secondary-foreground py-5">No educators.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Recent activity --}}
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Recent registrations</h3>
                </div>
                <div class="kt-card-content flex flex-col gap-2.5">
                    @forelse ($recentUsers as $u)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-border p-3">
                            <div class="min-w-0">
                                <div class="font-medium text-mono truncate">{{ $u->name }}</div>
                                <div class="text-xs text-secondary-foreground">{{ $u->created_at?->format('M j, Y') }}</div>
                            </div>
                            <span class="kt-badge kt-badge-sm kt-badge-outline text-capitalize shrink-0">{{ $u->user_type }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-secondary-foreground">No registrations.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ============ RIGHT PANEL ============ --}}
        <div class="flex flex-col gap-5 lg:gap-7.5 min-w-0">
            @include('partials._dashboard_calendar', ['events' => $calendarEvents])

            {{-- System health quick stats --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">System health</h3></div>
                <div class="kt-card-content flex flex-col gap-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-foreground">Active today</span>
                        <span class="font-medium text-mono">{{ $activeToday }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-foreground">Pending approvals</span>
                        <span class="font-medium text-mono">{{ $pendingApprovals }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-secondary-foreground">Flagged attempts</span>
                        <span class="kt-badge kt-badge-sm kt-badge-{{ $flaggedCount ? 'warning' : 'secondary' }}">{{ $flaggedCount }}</span>
                    </div>
                    <div class="border-t border-border pt-3">
                        <span class="text-xs uppercase text-muted-foreground">Latest import</span>
                        @if ($latestImport)
                            <div class="mt-1">
                                <span class="font-medium text-mono truncate">{{ $latestImport->original_filename }}</span>
                                <div class="text-xs text-muted-foreground">
                                    {{ ucfirst($latestImport->status) }} ·
                                    {{ (int) $latestImport->created_count }} created,
                                    {{ (int) $latestImport->failed_count }} failed
                                </div>
                            </div>
                        @else
                            <div class="mt-1 text-secondary-foreground">No imports yet.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Critical alerts: flagged attempts --}}
            <div class="kt-card">
                <div class="kt-card-header"><h3 class="kt-card-title">Flagged attempts</h3></div>
                <div class="kt-card-content flex flex-col gap-2.5">
                    @forelse ($flaggedRecent as $s)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-border p-3">
                            <div class="min-w-0">
                                <div class="font-medium text-mono truncate">{{ $s->student?->name ?? 'Student' }}</div>
                                <div class="text-xs text-secondary-foreground truncate">{{ $s->assessment?->assessment_code }}</div>
                            </div>
                            <span class="kt-badge kt-badge-sm kt-badge-warning shrink-0">{{ $s->warning_attempts }}×</span>
                        </div>
                    @empty
                        <div class="text-sm text-secondary-foreground">No flagged attempts.</div>
                    @endforelse
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
            var perfEl = document.getElementById('admin_perf_chart');
            if (perfEl && typeof ApexCharts !== 'undefined') {
                new ApexCharts(perfEl, {
                    chart: { type: 'bar', height: 300, toolbar: { show: false } },
                    series: [{ name: 'Avg score', data: @json($barData) }],
                    xaxis: { categories: @json($barCategories) },
                    yaxis: { max: 100, labels: { formatter: function (v) { return Math.round(v) + '%'; } } },
                    plotOptions: { bar: { columnWidth: '50%', borderRadius: 4, distributed: true } },
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    noData: { text: 'No score data yet' },
                }).render();
            }
            var trendEl = document.getElementById('admin_trend_chart');
            if (trendEl && typeof ApexCharts !== 'undefined') {
                new ApexCharts(trendEl, {
                    chart: { type: 'line', height: 260, toolbar: { show: false } },
                    series: [{ name: 'Quizzes taken', data: @json($trendData) }],
                    xaxis: { categories: @json($trendLabels) },
                    stroke: { curve: 'smooth', width: 2 },
                    dataLabels: { enabled: false },
                    noData: { text: 'No quiz activity yet' },
                }).render();
            }
        });
    </script>
    @endpush
@endsection
