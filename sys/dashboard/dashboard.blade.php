@extends('layouts.app')

@section('title', 'Dashboard - SLIPTA Digital Assessment System')
@section('breadcrumb', 'Dashboard')

@section('content')
<div class="space-y-6" x-data="dashboardApp()">

    <!-- Executive KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Total Laboratories -->
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-600">Total Laboratories</h3>
                <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <div class="flex items-baseline justify-between">
                <p class="text-3xl font-bold text-neutral-900">{{ $kpis['total_laboratories']['total'] }}</p>
                @if($kpis['total_laboratories']['trend'] != 0)
                <span class="text-sm font-medium {{ $kpis['total_laboratories']['trend'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $kpis['total_laboratories']['trend'] >= 0 ? '+' : '' }}{{ number_format($kpis['total_laboratories']['trend'], 1) }}%
                </span>
                @endif
            </div>
            <div class="mt-3 flex items-center space-x-2 text-xs text-neutral-500">
                @foreach($kpis['total_laboratories']['by_type'] as $type => $count)
                <span class="px-2 py-1 bg-neutral-50 rounded">{{ ucfirst($type) }}: {{ $count }}</span>
                @endforeach
            </div>
        </div>

        <!-- Total Audits -->
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-600">Total Audits</h3>
                <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div class="flex items-baseline justify-between">
                <p class="text-3xl font-bold text-neutral-900">{{ $kpis['total_audits']['total'] }}</p>
                @if($kpis['total_audits']['trend'] != 0)
                <span class="text-sm font-medium {{ $kpis['total_audits']['trend'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $kpis['total_audits']['trend'] >= 0 ? '+' : '' }}{{ number_format($kpis['total_audits']['trend'], 1) }}%
                </span>
                @endif
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                <div class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-center">
                    <div class="font-semibold">{{ $kpis['total_audits']['in_progress'] }}</div>
                    <div class="text-blue-600">In Progress</div>
                </div>
                <div class="px-2 py-1 bg-green-50 text-green-700 rounded text-center">
                    <div class="font-semibold">{{ $kpis['total_audits']['completed'] }}</div>
                    <div class="text-green-600">Completed</div>
                </div>
            </div>
        </div>

        <!-- Average Star Rating -->
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-600">Average Star Rating</h3>
                <svg class="w-5 h-5 text-neutral-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                </svg>
            </div>
            <div class="flex items-baseline justify-between mb-2">
                <p class="text-3xl font-bold text-neutral-900">{{ number_format($kpis['average_star_rating']['average_stars'], 1) }}</p>
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                    <svg class="w-5 h-5 {{ $i <= floor($kpis['average_star_rating']['average_stars']) ? 'text-yellow-400' : 'text-neutral-300' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    @endfor
                </div>
            </div>
            <p class="text-xs text-neutral-500">Based on {{ $kpis['average_star_rating']['total_audits'] }} completed audits</p>
        </div>
    </div>

    <!-- Star Level Distribution Chart -->
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-neutral-900">Star Level Distribution</h2>
            <span class="text-sm text-neutral-500">Laboratory Quality Spread</span>
        </div>
        <div id="starDistributionChart" class="h-64"></div>
    </div>

    <!-- Audit Score Trends & Section Heatmap -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Score Trends -->
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-neutral-900">Audit Score Trends</h2>
                <select class="text-sm border border-neutral-200 rounded-lg px-3 py-1" x-model="trendsFilter">
                    <option value="all">All Labs</option>
                    <option value="recent">Last 3 Months</option>
                    <option value="year">This Year</option>
                </select>
            </div>
            <div id="scoreTrendsChart" class="h-64"></div>
        </div>

        <!-- Top & Bottom Sections -->
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-4">Section Performance</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-medium text-green-600 mb-2">Top Performing Sections</h3>
                    <div class="space-y-2">
                        @foreach(array_slice($performance['top_bottom_sections']['top'], 0, 3) as $section)
                        <div class="flex items-center justify-between p-2 bg-green-50 rounded-lg">
                            <span class="text-sm text-neutral-700">{{ $section['title'] }}</span>
                            <span class="text-sm font-semibold text-green-700">{{ number_format($section['average'], 1) }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-red-600 mb-2">Needs Improvement</h3>
                    <div class="space-y-2">
                        @foreach(array_slice($performance['top_bottom_sections']['bottom'], 0, 3) as $section)
                        <div class="flex items-center justify-between p-2 bg-red-50 rounded-lg">
                            <span class="text-sm text-neutral-700">{{ $section['title'] }}</span>
                            <span class="text-sm font-semibold text-red-700">{{ number_format($section['average'], 1) }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Performance Heatmap -->
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-neutral-900 mb-4">Section Performance Heatmap</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200">
                        <th class="text-left py-3 px-4 font-medium text-neutral-600">Section</th>
                        <th class="text-center py-3 px-4 font-medium text-neutral-600">Avg Score</th>
                        <th class="text-center py-3 px-4 font-medium text-neutral-600">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($performance['section_heatmap'] as $code => $data)
                    <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                        <td class="py-3 px-4 text-neutral-700">{{ $code }}. {{ $data['title'] }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                {{ $data['color'] === 'green' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $data['color'] === 'yellow' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $data['color'] === 'red' ? 'bg-red-100 text-red-700' : '' }}">
                                {{ number_format($data['average'], 1) }}%
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="inline-flex items-center justify-center w-8 h-8 rounded-full
                                {{ $data['color'] === 'green' ? 'bg-green-100' : '' }}
                                {{ $data['color'] === 'yellow' ? 'bg-yellow-100' : '' }}
                                {{ $data['color'] === 'red' ? 'bg-red-100' : '' }}">
                                @if($data['color'] === 'green')
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                @elseif($data['color'] === 'yellow')
                                <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                @else
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity & Quality Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Audit Activity -->
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-4">Recent Audit Activity</h2>
            <div class="space-y-3">
                @foreach(array_slice($operations['recent_activity'], 0, 5) as $activity)
                <div class="flex items-center justify-between p-3 border border-neutral-100 rounded-lg hover:bg-neutral-50 transition-colors">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-neutral-900">{{ $activity['lab_name'] }}</p>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $activity['status'] === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $activity['status'] === 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $activity['status'] === 'draft' ? 'bg-neutral-100 text-neutral-700' : '' }}">
                                {{ ucfirst(str_replace('_', ' ', $activity['status'])) }}
                            </span>
                            <span class="text-xs text-neutral-500">{{ $activity['opened_on'] }}</span>
                        </div>
                    </div>
                    @if($activity['stars'] !== null)
                    <div class="flex items-center ml-4">
                        @for($i = 1; $i <= 5; $i++)
                        <svg class="w-4 h-4 {{ $i <= $activity['stars'] ? 'text-yellow-400' : 'text-neutral-300' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        @endfor
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Quality Metrics -->
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-4">Quality Metrics</h2>
            <div class="space-y-4">
                <!-- Findings Severity -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-neutral-600">Findings by Severity</span>
                        <span class="text-sm font-semibold text-neutral-900">
                            {{ $quality['findings_severity']['high'] + $quality['findings_severity']['medium'] + $quality['findings_severity']['low'] }} Total
                        </span>
                    </div>
                    <div class="space-y-2">
                        @php
                            $totalFindings = $quality['findings_severity']['high'] + $quality['findings_severity']['medium'] + $quality['findings_severity']['low'];
                        @endphp
                        <div class="flex items-center">
                            <span class="w-16 text-xs text-neutral-600">High</span>
                            <div class="flex-1 h-2 bg-neutral-100 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500" style="width: {{ $totalFindings > 0 ? ($quality['findings_severity']['high'] / $totalFindings) * 100 : 0 }}%"></div>
                            </div>
                            <span class="w-12 text-right text-xs font-semibold text-neutral-900">{{ $quality['findings_severity']['high'] }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-16 text-xs text-neutral-600">Medium</span>
                            <div class="flex-1 h-2 bg-neutral-100 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-500" style="width: {{ $totalFindings > 0 ? ($quality['findings_severity']['medium'] / $totalFindings) * 100 : 0 }}%"></div>
                            </div>
                            <span class="w-12 text-right text-xs font-semibold text-neutral-900">{{ $quality['findings_severity']['medium'] }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-16 text-xs text-neutral-600">Low</span>
                            <div class="flex-1 h-2 bg-neutral-100 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500" style="width: {{ $totalFindings > 0 ? ($quality['findings_severity']['low'] / $totalFindings) * 100 : 0 }}%"></div>
                            </div>
                            <span class="w-12 text-right text-xs font-semibold text-neutral-900">{{ $quality['findings_severity']['low'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Evidence Documentation Rate -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-neutral-600">Evidence Documentation</span>
                        <span class="text-sm font-semibold text-neutral-900">{{ number_format($quality['evidence_rate']['percentage'], 1) }}%</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex-1 h-2 bg-neutral-100 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 transition-all duration-500" style="width: {{ $quality['evidence_rate']['percentage'] }}%"></div>
                        </div>
                    </div>
                    <p class="text-xs text-neutral-500 mt-1">{{ $quality['evidence_rate']['with_evidence'] }} of {{ $quality['evidence_rate']['total_nc'] }} non-conformances documented</p>
                </div>

                <!-- Response Completion -->
                @php
                    $avgCompletionRate = count($operations['completion_rate']) > 0
                        ? collect($operations['completion_rate'])->avg('completion_rate')
                        : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-neutral-600">Avg Response Completion</span>
                        <span class="text-sm font-semibold text-neutral-900">{{ number_format($avgCompletionRate, 1) }}%</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex-1 h-2 bg-neutral-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 transition-all duration-500" style="width: {{ $avgCompletionRate }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- My Audits Widget -->
    @if(count($myAudits) > 0)
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-neutral-900 mb-4">My Audits</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200">
                        <th class="text-left py-3 px-4 font-medium text-neutral-600">Laboratory</th>
                        <th class="text-center py-3 px-4 font-medium text-neutral-600">Status</th>
                        @if(isset($myAudits[0]['my_role']))
                        <th class="text-center py-3 px-4 font-medium text-neutral-600">My Role</th>
                        @endif
                        @if(isset($myAudits[0]['progress_percentage']))
                        <th class="text-center py-3 px-4 font-medium text-neutral-600">Progress</th>
                        @endif
                        @if(isset($myAudits[0]['stars']))
                        <th class="text-center py-3 px-4 font-medium text-neutral-600">Result</th>
                        @endif
                        <th class="text-center py-3 px-4 font-medium text-neutral-600">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($myAudits as $audit)
                    <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                        <td class="py-3 px-4 text-neutral-900 font-medium">{{ $audit['lab_name'] }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $audit['status'] === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $audit['status'] === 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $audit['status'] === 'draft' ? 'bg-neutral-100 text-neutral-700' : '' }}">
                                {{ ucfirst(str_replace('_', ' ', $audit['status'])) }}
                            </span>
                        </td>
                        @if(isset($audit['my_role']))
                        <td class="py-3 px-4 text-center text-neutral-600 capitalize">{{ str_replace('_', ' ', $audit['my_role']) }}</td>
                        @endif
                        @if(isset($audit['progress_percentage']))
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <div class="w-24 h-2 bg-neutral-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 transition-all duration-500" style="width: {{ $audit['progress_percentage'] }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-neutral-900">{{ number_format($audit['progress_percentage'], 0) }}%</span>
                            </div>
                        </td>
                        @endif
                        @if(isset($audit['stars']) && $audit['stars'] !== null)
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center">
                                @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $audit['stars'] ? 'text-yellow-400' : 'text-neutral-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                @endfor
                            </div>
                        </td>
                        @endif
                        <td class="py-3 px-4 text-center text-neutral-600 text-xs">{{ $audit['opened_on'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

<script>
function dashboardApp() {
    return {
        trendsFilter: 'all',

        init() {
            this.initCharts();
        },

        initCharts() {
            this.createStarDistributionChart();
            this.createScoreTrendsChart();
        },

        createStarDistributionChart() {
            const chartData = {!! json_encode($chartData['star_distribution'] ?? []) !!};

            if (!chartData || chartData.length === 0) {
                document.getElementById('starDistributionChart').innerHTML = '<p class="text-center text-neutral-500 py-8">No data available</p>';
                return;
            }

            const root = am5.Root.new("starDistributionChart");
            root.setThemes([am5themes_Animated.new(root)]);

            const chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                layout: root.verticalLayout
            }));

            const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                categoryField: "star",
                renderer: am5xy.AxisRendererX.new(root, {
                    minGridDistance: 30
                })
            }));

            xAxis.data.setAll(chartData);

            const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                renderer: am5xy.AxisRendererY.new(root, {})
            }));

            const series = chart.series.push(am5xy.ColumnSeries.new(root, {
                name: "Laboratories",
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: "count",
                categoryXField: "star",
                fill: am5.color("#0a0a0a"),
                stroke: am5.color("#0a0a0a")
            }));

            series.columns.template.setAll({
                cornerRadiusTL: 4,
                cornerRadiusTR: 4,
                strokeOpacity: 0
            });

            series.data.setAll(chartData);
            series.appear(250);
            chart.appear(250, 100);
        },

        createScoreTrendsChart() {
            const chartData = {!! json_encode($chartData['score_trends'] ?? []) !!};

            if (!chartData || chartData.length === 0) {
                document.getElementById('scoreTrendsChart').innerHTML = '<p class="text-center text-neutral-500 py-8">No data available</p>';
                return;
            }

            const root = am5.Root.new("scoreTrendsChart");
            root.setThemes([am5themes_Animated.new(root)]);

            const chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: true,
                panY: false,
                wheelY: "zoomX",
                layout: root.verticalLayout
            }));

            const xAxis = chart.xAxes.push(am5xy.DateAxis.new(root, {
                baseInterval: { timeUnit: "day", count: 1 },
                renderer: am5xy.AxisRendererX.new(root, {})
            }));

            const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                min: 0,
                max: 100,
                renderer: am5xy.AxisRendererY.new(root, {})
            }));

            const series = chart.series.push(am5xy.LineSeries.new(root, {
                name: "Score %",
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: "percentage",
                valueXField: "date_ts",
                stroke: am5.color("#0a0a0a"),
                fill: am5.color("#0a0a0a")
            }));

            series.strokes.template.setAll({
                strokeWidth: 2
            });

            series.bullets.push(function() {
                return am5.Bullet.new(root, {
                    sprite: am5.Circle.new(root, {
                        radius: 4,
                        fill: series.get("fill"),
                        stroke: root.interfaceColors.get("background"),
                        strokeWidth: 2
                    })
                });
            });

            series.data.setAll(chartData);
            series.appear(250);
            chart.appear(250, 100);
        }
    }
}
</script>
@endsection
