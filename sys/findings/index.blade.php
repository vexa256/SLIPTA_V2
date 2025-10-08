@extends('layouts.app')

@section('breadcrumb', 'Audit Findings Management')

@section('content')
<div class="flex flex-col space-y-4" x-data="findingsIndexApp()" x-init="init()">

    {{-- Header Section --}}
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-3 lg:space-y-0">
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-3 mb-1">
                    <a href="{{ route('findings.index') }}" class="text-neutral-400 hover:text-neutral-900 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-semibold text-neutral-900 truncate">{{ $audit->lab_name }}</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-600">
                    <span class="flex items-center">
                        <i class="fas fa-building mr-1.5 text-neutral-400"></i>
                        {{ $audit->lab_number ?? 'N/A' }}
                    </span>
                    <span class="text-neutral-300">•</span>
                    <span class="flex items-center">
                        <i class="fas fa-globe mr-1.5 text-neutral-400"></i>
                        {{ $audit->country_name }}
                    </span>
                    <span class="text-neutral-300">•</span>
                    <span class="flex items-center">
                        <i class="fas fa-calendar mr-1.5 text-neutral-400"></i>
                        {{ \Carbon\Carbon::parse($audit->opened_on)->format('d M Y') }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Status Badge --}}
                @php
                    $statusConfig = [
                        'draft' => ['bg' => 'bg-neutral-100', 'text' => 'text-neutral-800', 'icon' => 'fa-file'],
                        'in_progress' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-spinner'],
                        'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-circle'],
                        'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-times-circle'],
                    ];
                    $config = $statusConfig[$audit->status] ?? $statusConfig['draft'];
                @endphp
                <div class="flex items-center space-x-2 px-3 py-1.5 {{ $config['bg'] }} rounded-xl">
                    <i class="fas {{ $config['icon'] }} {{ $config['text'] }} text-sm"></i>
                    <span class="text-xs font-medium {{ $config['text'] }}">{{ ucfirst(str_replace('_', ' ', $audit->status)) }}</span>
                </div>

                {{-- Star Rating --}}
                <div class="flex items-center space-x-2 px-3 py-1.5 bg-neutral-50 border border-neutral-200 rounded-xl">
                    <div class="flex items-center space-x-0.5">
                        @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star text-sm {{ $i <= $score['stars'] ? 'text-neutral-900' : 'text-neutral-300' }}"></i>
                        @endfor
                    </div>
                    <span class="text-xs font-medium text-neutral-900">{{ $score['stars'] }} Star</span>
                </div>

                {{-- Score Badge --}}
                <div class="flex items-center space-x-2 px-3 py-1.5 bg-neutral-900 text-white rounded-xl">
                    <span class="text-xs font-semibold">{{ $score['percentage'] }}%</span>
                    <span class="text-xs text-neutral-400">{{ $score['earned'] }}/{{ $score['adjusted_denominator'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm">
        <div class="border-b border-neutral-200 px-4">
            <nav class="flex space-x-1 overflow-x-auto scrollbar-none" role="tablist">
                <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    <i class="fas fa-chart-line mr-2"></i>Overview
                </button>
                <button @click="activeTab = 'sections'" :class="activeTab === 'sections' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    <i class="fas fa-layer-group mr-2"></i>Sections
                </button>
                <button @click="activeTab = 'findings'" :class="activeTab === 'findings' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    <i class="fas fa-clipboard-list mr-2"></i>Findings
                    @if($diagnostics['nc_without_finding']->count() > 0)
                    <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full">{{ $diagnostics['nc_without_finding']->count() }}</span>
                    @endif
                </button>
                <button @click="activeTab = 'diagnostics'" :class="activeTab === 'diagnostics' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    <i class="fas fa-stethoscope mr-2"></i>Diagnostics
                </button>
                <button @click="activeTab = 'unanswered'" :class="activeTab === 'unanswered' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    <i class="fas fa-question-circle mr-2"></i>Unanswered
                    @if($diagnostics['unanswered']->count() > 0)
                    <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-orange-600 rounded-full">{{ $diagnostics['unanswered']->count() }}</span>
                    @endif
                </button>
                <button @click="activeTab = 'undocumented'" :class="activeTab === 'undocumented' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    <i class="fas fa-file-excel mr-2"></i>Undocumented P/N
                    @if($diagnostics['nc_without_finding']->count() > 0)
                    <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full">{{ $diagnostics['nc_without_finding']->count() }}</span>
                    @endif
                </button>
                <button @click="activeTab = 'evidence'" :class="activeTab === 'evidence' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    <i class="fas fa-flag mr-2"></i>Evidence Gaps
                    @if($diagnostics['evidence_missing']->count() > 0)
                    <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-yellow-600 rounded-full">{{ $diagnostics['evidence_missing']->count() }}</span>
                    @endif
                </button>
                <button @click="activeTab = 'actions'" :class="activeTab === 'actions' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    <i class="fas fa-tasks mr-2"></i>Actions
                </button>
                <button @click="activeTab = 'reports'" :class="activeTab === 'reports' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-600 hover:text-neutral-900'" class="px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    <i class="fas fa-file-download mr-2"></i>Reports
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-4">

            {{-- Overview Tab --}}
            <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                {{-- Quick Stats Row --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                    <div class="bg-gradient-to-br from-neutral-50 to-neutral-100 border border-neutral-200 rounded-xl p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-neutral-600 uppercase tracking-wider">Total Points</span>
                            <i class="fas fa-calculator text-neutral-400 text-sm"></i>
                        </div>
                        <div class="text-2xl font-bold text-neutral-900">{{ $score['earned'] }}</div>
                        <div class="text-xs text-neutral-600 mt-1">of {{ $score['adjusted_denominator'] }} available</div>
                    </div>

                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-blue-700 uppercase tracking-wider">Completion</span>
                            <i class="fas fa-tasks text-blue-400 text-sm"></i>
                        </div>
                        @php
                            $totalQuestions = 151;
                            $answered = $diagnostics['unanswered']->count() > 0 ? $totalQuestions - $diagnostics['unanswered']->count() : $totalQuestions;
                            $progressPct = round(($answered / $totalQuestions) * 100, 1);
                        @endphp
                        <div class="text-2xl font-bold text-blue-900">{{ $progressPct }}%</div>
                        <div class="text-xs text-blue-700 mt-1">{{ $answered }}/{{ $totalQuestions }} questions</div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-green-700 uppercase tracking-wider">Compliant</span>
                            <i class="fas fa-check-circle text-green-400 text-sm"></i>
                        </div>
                        @php
                            $yCount = DB::table('audit_responses')->where('audit_id', $audit->id)->where('answer', 'Y')->count();
                        @endphp
                        <div class="text-2xl font-bold text-green-900">{{ $yCount }}</div>
                        <div class="text-xs text-green-700 mt-1">Yes responses</div>
                    </div>

                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-xl p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-orange-700 uppercase tracking-wider">Findings</span>
                            <i class="fas fa-exclamation-triangle text-orange-400 text-sm"></i>
                        </div>
                        <div class="text-2xl font-bold text-orange-900">{{ $findings->flatten()->count() }}</div>
                        <div class="text-xs text-orange-700 mt-1">across {{ $findings->keys()->count() }} sections</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                    {{-- Score Card --}}
                    <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-neutral-600 uppercase tracking-wider">Overall Score</span>
                            <i class="fas fa-award text-neutral-400"></i>
                        </div>
                        <div class="flex items-baseline space-x-2 mb-2">
                            <span class="text-3xl font-bold text-neutral-900">{{ $score['percentage'] }}%</span>
                            <span class="text-sm text-neutral-600">{{ $score['star_label'] }}</span>
                        </div>
                        <div class="flex items-center space-x-1 mb-3">
                            @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= $score['stars'] ? 'text-neutral-900' : 'text-neutral-300' }}"></i>
                            @endfor
                        </div>
                        <div class="space-y-1 text-xs text-neutral-600">
                            <div class="flex justify-between">
                                <span>Earned Points</span>
                                <span class="font-medium text-neutral-900">{{ $score['earned'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Adjusted Total</span>
                                <span class="font-medium text-neutral-900">{{ $score['adjusted_denominator'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>NA Excluded</span>
                                <span class="font-medium text-neutral-900">{{ $score['na_points_excluded'] }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Card --}}
                    <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-neutral-600 uppercase tracking-wider">Progress</span>
                            <i class="fas fa-chart-pie text-neutral-400"></i>
                        </div>
                        <div class="space-y-3">
                            @php
                                $totalQuestions = 151;
                                $answered = $diagnostics['unanswered']->count() > 0 ? $totalQuestions - $diagnostics['unanswered']->count() : $totalQuestions;
                                $progressPct = round(($answered / $totalQuestions) * 100, 1);
                            @endphp
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-neutral-600">Questions Answered</span>
                                    <span class="font-medium text-neutral-900">{{ $answered }}/{{ $totalQuestions }}</span>
                                </div>
                                <div class="w-full bg-neutral-200 rounded-full h-2">
                                    <div class="bg-neutral-900 h-2 rounded-full transition-all" style="width: {{ $progressPct }}%"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="bg-white rounded-lg p-2 border border-neutral-200">
                                    <div class="text-neutral-600 mb-1">Findings</div>
                                    <div class="text-lg font-bold text-neutral-900">{{ $findings->flatten()->count() }}</div>
                                </div>
                                <div class="bg-white rounded-lg p-2 border border-neutral-200">
                                    <div class="text-neutral-600 mb-1">P/N Responses</div>
                                    <div class="text-lg font-bold text-neutral-900">{{ $diagnostics['nc_without_finding']->count() + $findings->flatten()->count() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Status Card --}}
                    <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-neutral-600 uppercase tracking-wider">Closure Status</span>
                            <i class="fas fa-{{ $closureValidation['can_close'] ? 'check-circle' : 'exclamation-triangle' }} text-{{ $closureValidation['can_close'] ? 'green' : 'orange' }}-600"></i>
                        </div>
                        @if($closureValidation['can_close'])
                        <div class="flex flex-col space-y-2">
                            <div class="flex items-center space-x-2 text-sm text-green-800 bg-green-100 rounded-lg p-2">
                                <i class="fas fa-check-circle"></i>
                                <span class="font-medium">Ready to Close</span>
                            </div>
                            <p class="text-xs text-neutral-600">All validation criteria met. Audit can be closed.</p>
                            @if($audit->status === 'in_progress' && !$readOnly)
                            <button @click="closeAudit()" :disabled="isClosing" class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 disabled:bg-neutral-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-colors">
                                <span x-show="!isClosing">Close Audit</span>
                                <span x-show="isClosing">Closing...</span>
                            </button>
                            @endif
                        </div>
                        @else
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2 text-sm text-orange-800 bg-orange-100 rounded-lg p-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span class="font-medium">{{ $closureValidation['blocker_count'] }} Blockers</span>
                            </div>
                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                @foreach($closureValidation['blockers'] as $blocker)
                                <div class="text-xs text-neutral-700 bg-white rounded-lg p-2 border border-neutral-200">
                                    <span class="font-medium">{{ $blocker['message'] }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Response Distribution Analytics --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-white border border-neutral-200 rounded-xl">
                        <div class="px-4 py-3 border-b border-neutral-200">
                            <h3 class="text-sm font-semibold text-neutral-900">Response Distribution</h3>
                        </div>
                        <div class="p-4">
                            @php
                                $responses = DB::table('audit_responses')->where('audit_id', $audit->id)->get();
                                $yCount = $responses->where('answer', 'Y')->count();
                                $pCount = $responses->where('answer', 'P')->count();
                                $nCount = $responses->where('answer', 'N')->count();
                                $naCount = $responses->where('answer', 'NA')->count();
                                $total = $responses->count();
                            @endphp
                            <div class="space-y-3">
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="flex items-center text-green-700 font-medium">
                                            <i class="fas fa-check-circle mr-2"></i>Yes (Y)
                                        </span>
                                        <span class="font-semibold text-neutral-900">{{ $yCount }} ({{ $total > 0 ? round(($yCount/$total)*100, 1) : 0 }}%)</span>
                                    </div>
                                    <div class="w-full bg-neutral-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $total > 0 ? ($yCount/$total)*100 : 0 }}%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="flex items-center text-orange-700 font-medium">
                                            <i class="fas fa-minus-circle mr-2"></i>Partial (P)
                                        </span>
                                        <span class="font-semibold text-neutral-900">{{ $pCount }} ({{ $total > 0 ? round(($pCount/$total)*100, 1) : 0 }}%)</span>
                                    </div>
                                    <div class="w-full bg-neutral-200 rounded-full h-2">
                                        <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $total > 0 ? ($pCount/$total)*100 : 0 }}%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="flex items-center text-red-700 font-medium">
                                            <i class="fas fa-times-circle mr-2"></i>No (N)
                                        </span>
                                        <span class="font-semibold text-neutral-900">{{ $nCount }} ({{ $total > 0 ? round(($nCount/$total)*100, 1) : 0 }}%)</span>
                                    </div>
                                    <div class="w-full bg-neutral-200 rounded-full h-2">
                                        <div class="bg-red-600 h-2 rounded-full" style="width: {{ $total > 0 ? ($nCount/$total)*100 : 0 }}%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="flex items-center text-neutral-600 font-medium">
                                            <i class="fas fa-ban mr-2"></i>Not Applicable (NA)
                                        </span>
                                        <span class="font-semibold text-neutral-900">{{ $naCount }} ({{ $total > 0 ? round(($naCount/$total)*100, 1) : 0 }}%)</span>
                                    </div>
                                    <div class="w-full bg-neutral-200 rounded-full h-2">
                                        <div class="bg-neutral-400 h-2 rounded-full" style="width: {{ $total > 0 ? ($naCount/$total)*100 : 0 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-neutral-200 rounded-xl">
                        <div class="px-4 py-3 border-b border-neutral-200">
                            <h3 class="text-sm font-semibold text-neutral-900">Scoring Breakdown</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-lg">
                                <span class="text-xs font-medium text-neutral-700">Total Possible Points</span>
                                <span class="text-sm font-bold text-neutral-900">367</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <span class="text-xs font-medium text-blue-700">NA Points Excluded</span>
                                <span class="text-sm font-bold text-blue-900">-{{ $score['na_points_excluded'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-lg">
                                <span class="text-xs font-medium text-neutral-700">Adjusted Denominator</span>
                                <span class="text-sm font-bold text-neutral-900">{{ $score['adjusted_denominator'] }}</span>
                            </div>
                            <div class="border-t-2 border-neutral-200 my-2"></div>
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <span class="text-xs font-medium text-green-700">Points Earned</span>
                                <span class="text-lg font-bold text-green-900">{{ $score['earned'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-neutral-900 rounded-xl">
                                <span class="text-sm font-medium text-white">Final Score</span>
                                <span class="text-2xl font-bold text-white">{{ $score['percentage'] }}%</span>
                            </div>
                            <div class="flex items-center justify-center space-x-1 pt-2">
                                @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star text-lg {{ $i <= $score['stars'] ? 'text-neutral-900' : 'text-neutral-300' }}"></i>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section Performance Tab --}}
            <div x-show="activeTab === 'sections'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white border border-neutral-200 rounded-xl">
                    <div class="px-4 py-3 border-b border-neutral-200 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-neutral-900">Section Performance</h3>
                        <span class="text-xs text-neutral-600">12 sections analyzed</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-neutral-50 border-b border-neutral-200">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-neutral-600 uppercase">Section</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-neutral-600 uppercase">Max Points</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-neutral-600 uppercase">Findings</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-neutral-600 uppercase">Status</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-neutral-600 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200">
                                @foreach($sections as $section)
                                @php
                                    $sectionFindings = ($findings->get($section->id) ?? collect())->count();
                                    $sectionMaxPoints = [1=>22, 2=>26, 3=>34, 4=>24, 5=>38, 6=>24, 7=>27, 8=>71, 9=>24, 10=>13, 11=>7, 12=>57][$section->code] ?? 0;
                                @endphp
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-shrink-0 w-8 h-8 bg-neutral-100 rounded-lg flex items-center justify-center">
                                                <span class="text-xs font-bold text-neutral-600">{{ $section->code }}</span>
                                            </div>
                                            <div class="font-medium text-neutral-900 truncate">{{ $section->title }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-neutral-100 text-neutral-700 text-xs font-semibold">
                                            {{ $sectionMaxPoints }} pts
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg {{ $sectionFindings > 0 ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700' }} font-semibold text-xs">
                                            {{ $sectionFindings }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($sectionFindings === 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                            <i class="fas fa-check-circle mr-1"></i>Complete
                                        </span>
                                        @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-orange-100 text-orange-700 text-xs font-medium">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ $sectionFindings }} issues
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button @click="activeTab = 'findings'; scrollToSection({{ $section->id }})" class="text-xs text-neutral-600 hover:text-neutral-900 font-medium transition-colors">
                                            View <i class="fas fa-arrow-right ml-1"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Findings Tab --}}
            <div x-show="activeTab === 'findings'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-4">
                    @if(!$readOnly && $audit->status === 'in_progress')
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-semibold text-neutral-900">Manage Findings</h3>
                        <button @click="openAddModal()" :disabled="isSubmitting" class="px-3 py-2 bg-neutral-900 hover:bg-neutral-800 disabled:bg-neutral-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Finding
                        </button>
                    </div>
                    @endif

                    @foreach($sections as $section)
                    @php
                        $sectionFindings = $findings->get($section->id) ?? collect();
                    @endphp
                    <div class="bg-white border border-neutral-200 rounded-xl" :id="'section-' + {{ $section->id }}">
                        <div class="px-4 py-3 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-semibold text-neutral-900">Section {{ $section->code }}: {{ $section->title }}</h4>
                                <p class="text-xs text-neutral-600 mt-0.5">{{ $sectionFindings->count() }} findings</p>
                            </div>
                            @if(!$readOnly && $audit->status === 'in_progress')
                            <button @click="openAddModal({{ $section->id }})" :disabled="isSubmitting" class="px-3 py-1.5 bg-white hover:bg-neutral-50 disabled:bg-neutral-100 disabled:cursor-not-allowed border border-neutral-200 text-neutral-700 text-xs font-medium rounded-lg transition-colors">
                                <i class="fas fa-plus mr-1.5"></i>Add
                            </button>
                            @endif
                        </div>
                        @if($sectionFindings->isEmpty())
                        <div class="p-8 text-center">
                            <i class="fas fa-clipboard-list text-neutral-300 text-3xl mb-2"></i>
                            <p class="text-sm text-neutral-600">No findings recorded for this section</p>
                        </div>
                        @else
                        <div class="divide-y divide-neutral-200">
                            @foreach($sectionFindings as $finding)
                            <div class="p-4 hover:bg-neutral-50 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2 mb-2">
                                            @if($finding->q_code)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-700 text-xs font-medium">
                                                Q{{ $finding->q_code }}
                                            </span>
                                            @endif
                                            @php
                                                $sevColors = [
                                                    'high' => 'bg-red-100 text-red-800',
                                                    'medium' => 'bg-orange-100 text-orange-800',
                                                    'low' => 'bg-yellow-100 text-yellow-800',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $sevColors[$finding->severity] ?? 'bg-neutral-100 text-neutral-700' }}">
                                                {{ ucfirst($finding->severity ?? 'medium') }}
                                            </span>
                                        </div>
                                        <h5 class="text-sm font-semibold text-neutral-900 mb-1">{{ $finding->title }}</h5>
                                        <p class="text-xs text-neutral-600 line-clamp-2">{{ $finding->description }}</p>
                                        @if($finding->question_text)
                                        <p class="text-xs text-neutral-500 mt-1 italic">Related: {{ Str::limit($finding->question_text, 100) }}</p>
                                        @endif
                                    </div>
                                    @if(!$readOnly && $audit->status === 'in_progress')
                                    <div class="flex items-center space-x-1 ml-4">
                                        <button @click="openEditModal({{ $finding->id }}, {{ json_encode($finding->title) }}, {{ json_encode($finding->description) }}, '{{ $finding->severity }}')" :disabled="isSubmitting" class="p-2 text-neutral-400 hover:text-neutral-900 hover:bg-neutral-100 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors">
                                            <i class="fas fa-edit text-sm"></i>
                                        </button>
                                        <button @click="deleteFinding({{ $finding->id }})" :disabled="isDeleting === {{ $finding->id }}" class="p-2 text-neutral-400 hover:text-red-600 hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Diagnostics Tab (Visual Overview) --}}
            <div x-show="activeTab === 'diagnostics'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-neutral-900">Diagnostic Overview</h3>

                    {{-- Key Metrics Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-question-circle text-orange-600"></i>
                                </div>
                                <button @click="activeTab = 'unanswered'" class="text-xs text-neutral-600 hover:text-neutral-900">View <i class="fas fa-arrow-right ml-1"></i></button>
                            </div>
                            <div class="text-2xl font-bold text-neutral-900 mb-1">{{ $diagnostics['unanswered']->count() }}</div>
                            <div class="text-xs text-neutral-600">Unanswered Questions</div>
                            @if($diagnostics['unanswered']->count() > 0)
                            <div class="mt-2 text-xs text-orange-700 bg-orange-50 rounded px-2 py-1">Critical Blocker</div>
                            @else
                            <div class="mt-2 text-xs text-green-700 bg-green-50 rounded px-2 py-1">✓ Complete</div>
                            @endif
                        </div>

                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-excel text-red-600"></i>
                                </div>
                                <button @click="activeTab = 'undocumented'" class="text-xs text-neutral-600 hover:text-neutral-900">View <i class="fas fa-arrow-right ml-1"></i></button>
                            </div>
                            <div class="text-2xl font-bold text-neutral-900 mb-1">{{ $diagnostics['nc_without_finding']->count() }}</div>
                            <div class="text-xs text-neutral-600">Undocumented P/N</div>
                            @if($diagnostics['nc_without_finding']->count() > 0)
                            <div class="mt-2 text-xs text-red-700 bg-red-50 rounded px-2 py-1">High Priority</div>
                            @else
                            <div class="mt-2 text-xs text-green-700 bg-green-50 rounded px-2 py-1">✓ Complete</div>
                            @endif
                        </div>

                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-flag text-yellow-600"></i>
                                </div>
                                <button @click="activeTab = 'evidence'" class="text-xs text-neutral-600 hover:text-neutral-900">View <i class="fas fa-arrow-right ml-1"></i></button>
                            </div>
                            <div class="text-2xl font-bold text-neutral-900 mb-1">{{ $diagnostics['evidence_missing']->count() }}</div>
                            <div class="text-xs text-neutral-600">Evidence Gaps</div>
                            @if($diagnostics['evidence_missing']->count() > 0)
                            <div class="mt-2 text-xs text-yellow-700 bg-yellow-50 rounded px-2 py-1">Warning Only</div>
                            @else
                            <div class="mt-2 text-xs text-green-700 bg-green-50 rounded px-2 py-1">✓ Complete</div>
                            @endif
                        </div>

                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-clipboard-list text-blue-600"></i>
                                </div>
                                <button @click="activeTab = 'findings'" class="text-xs text-neutral-600 hover:text-neutral-900">View <i class="fas fa-arrow-right ml-1"></i></button>
                            </div>
                            <div class="text-2xl font-bold text-neutral-900 mb-1">{{ $findings->flatten()->count() }}</div>
                            <div class="text-xs text-neutral-600">Total Findings</div>
                            <div class="mt-2 text-xs text-blue-700 bg-blue-50 rounded px-2 py-1">{{ $findings->keys()->count() }} Sections</div>
                        </div>
                    </div>

                    {{-- Additional Diagnostic Items --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <h4 class="text-sm font-semibold text-neutral-900 mb-3">Other Diagnostic Items</h4>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-lg">
                                    <span class="text-xs text-neutral-700">NA Without Justification</span>
                                    <span class="text-sm font-bold text-neutral-900">{{ $diagnostics['na_without_justification']->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-lg">
                                    <span class="text-xs text-neutral-700">Missing Comments (P/N/NA)</span>
                                    <span class="text-sm font-bold text-neutral-900">{{ $diagnostics['missing_comments']->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-lg">
                                    <span class="text-xs text-neutral-700">Composite Violations</span>
                                    <span class="text-sm font-bold text-neutral-900">{{ $diagnostics['composite_violations']->count() }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <h4 class="text-sm font-semibold text-neutral-900 mb-3">Closure Status</h4>
                            @if($closureValidation['can_close'])
                            <div class="flex items-center space-x-2 text-sm text-green-800 bg-green-50 rounded-lg p-3 mb-3">
                                <i class="fas fa-check-circle text-lg"></i>
                                <div>
                                    <div class="font-medium">Ready to Close</div>
                                    <div class="text-xs text-green-600">All validation criteria met</div>
                                </div>
                            </div>
                            @else
                            <div class="flex items-center space-x-2 text-sm text-orange-800 bg-orange-50 rounded-lg p-3 mb-3">
                                <i class="fas fa-exclamation-triangle text-lg"></i>
                                <div>
                                    <div class="font-medium">{{ $closureValidation['blocker_count'] }} Blockers Identified</div>
                                    <div class="text-xs text-orange-600">Address blockers to proceed</div>
                                </div>
                            </div>
                            @endif

                            @if(isset($closureValidation['evidence_flag_message']))
                            <div class="text-xs text-yellow-700 bg-yellow-50 rounded-lg p-3">
                                <i class="fas fa-info-circle mr-1"></i>{{ $closureValidation['evidence_flag_message'] }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Unanswered Questions Tab --}}
            <div x-show="activeTab === 'unanswered'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white border border-neutral-200 rounded-xl">
                    <div class="px-4 py-3 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-neutral-900">Unanswered Questions</h4>
                            <p class="text-xs text-neutral-600 mt-0.5">{{ $diagnostics['unanswered']->count() }} questions pending</p>
                        </div>
                        @if($diagnostics['unanswered']->count() > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-lg bg-red-100 text-red-800 text-xs font-medium">
                            <i class="fas fa-exclamation-circle mr-1"></i>Critical Blocker
                        </span>
                        @endif
                    </div>
                    @if($diagnostics['unanswered']->isEmpty())
                    <div class="p-8 text-center">
                        <i class="fas fa-check-circle text-green-600 text-3xl mb-2"></i>
                        <p class="text-sm text-neutral-600">All questions answered</p>
                    </div>
                    @else
                    <div class="divide-y divide-neutral-200">
                        @foreach($diagnostics['unanswered'] as $item)
                        <div class="p-4 hover:bg-neutral-50">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-12 h-12 bg-neutral-100 rounded-lg flex items-center justify-center">
                                    <span class="text-xs font-bold text-neutral-600">S{{ $item->section_code }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="text-xs font-medium text-neutral-900">Q{{ $item->q_code }}</span>
                                        <span class="text-xs text-neutral-500">{{ $item->section_title }}</span>
                                    </div>
                                    <p class="text-xs text-neutral-600">{{ $item->text }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- Undocumented P/N Tab --}}
            <div x-show="activeTab === 'undocumented'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white border border-neutral-200 rounded-xl">
                    <div class="px-4 py-3 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-neutral-900">P/N Responses Without Findings</h4>
                            <p class="text-xs text-neutral-600 mt-0.5">{{ $diagnostics['nc_without_finding']->count() }} items need documentation</p>
                        </div>
                        @if($diagnostics['nc_without_finding']->count() > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-lg bg-orange-100 text-orange-800 text-xs font-medium">
                            <i class="fas fa-exclamation-triangle mr-1"></i>High Priority
                        </span>
                        @endif
                    </div>
                    @if($diagnostics['nc_without_finding']->isEmpty())
                    <div class="p-8 text-center">
                        <i class="fas fa-check-circle text-green-600 text-3xl mb-2"></i>
                        <p class="text-sm text-neutral-600">All P/N responses documented</p>
                    </div>
                    @else
                    <div class="divide-y divide-neutral-200">
                        @foreach($diagnostics['nc_without_finding'] as $item)
                        <div class="p-4 hover:bg-neutral-50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="text-xs font-medium text-neutral-900">Q{{ $item->q_code }}</span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $item->answer === 'N' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800' }}">
                                            {{ $item->answer }}
                                        </span>
                                        <span class="text-xs text-neutral-500">{{ $item->section_title }}</span>
                                    </div>
                                    <p class="text-xs text-neutral-600 mb-1">{{ $item->text }}</p>
                                    @if($item->comment)
                                    <p class="text-xs text-neutral-500 italic">Comment: {{ Str::limit($item->comment, 80) }}</p>
                                    @endif
                                </div>
                                @if(!$readOnly && $audit->status === 'in_progress')
                                <button @click="openAddModalForQuestion({{ $item->id }}, '{{ $item->q_code }}', {{ $item->section_id ?? 'null' }})" :disabled="isSubmitting" class="ml-3 px-2 py-1 bg-neutral-900 hover:bg-neutral-800 disabled:bg-neutral-300 disabled:cursor-not-allowed text-white text-xs font-medium rounded-lg transition-colors">
                                    Add Finding
                                </button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- Evidence Gaps Tab --}}
            <div x-show="activeTab === 'evidence'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="bg-white border border-neutral-200 rounded-xl">
                    <div class="px-4 py-3 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-neutral-900">Evidence Missing (Flags)</h4>
                            <p class="text-xs text-neutral-600 mt-0.5">{{ $diagnostics['evidence_missing']->count() }} P/N items without evidence</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-lg bg-yellow-100 text-yellow-800 text-xs font-medium">
                            <i class="fas fa-flag mr-1"></i>Warning Only
                        </span>
                    </div>
                    <div class="p-3 bg-yellow-50 border-b border-yellow-200">
                        <p class="text-xs text-yellow-800"><i class="fas fa-info-circle mr-1"></i> Evidence gaps are informational warnings and do not block audit closure</p>
                    </div>
                    @if($diagnostics['evidence_missing']->isEmpty())
                    <div class="p-8 text-center">
                        <i class="fas fa-check-circle text-green-600 text-3xl mb-2"></i>
                        <p class="text-sm text-neutral-600">All P/N responses have evidence</p>
                    </div>
                    @else
                    <div class="divide-y divide-neutral-200">
                        @foreach($diagnostics['evidence_missing'] as $item)
                        <div class="p-4 hover:bg-neutral-50">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-paperclip text-yellow-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="text-xs font-medium text-neutral-900">Q{{ $item->q_code }}</span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $item->answer === 'N' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800' }}">
                                            {{ $item->answer }}
                                        </span>
                                        <span class="text-xs text-neutral-500">Section {{ $item->section_code }}</span>
                                    </div>
                                    <p class="text-xs text-neutral-600 truncate">{{ $item->text }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- Actions Tab --}}
            <div x-show="activeTab === 'actions'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-4">
                    @if(!$readOnly && $audit->status === 'in_progress')
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-semibold text-neutral-900">Available Actions</h3>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {{-- Auto-Sync --}}
                        @if(!$readOnly && $audit->status === 'in_progress')
                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <div class="flex items-start space-x-3 mb-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-sync text-blue-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-neutral-900 mb-1">Auto-Sync Findings</h4>
                                    <p class="text-xs text-neutral-600">Automatically create findings for P/N responses and update action plans</p>
                                </div>
                            </div>
                            <button @click="autoSync()" :disabled="isSyncing" class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-neutral-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-colors">
                                <span x-show="!isSyncing">Run Auto-Sync</span>
                                <span x-show="isSyncing">Syncing...</span>
                            </button>
                        </div>
                        @endif

                        {{-- Generate Action Plans --}}
                        @if(!$readOnly && $audit->status === 'in_progress')
                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <div class="flex items-start space-x-3 mb-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-tasks text-green-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-neutral-900 mb-1">Generate Action Plans</h4>
                                    <p class="text-xs text-neutral-600">Create CAPA action plans for all findings</p>
                                </div>
                            </div>
                            <button @click="generateActionPlans()" :disabled="isGenerating" class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 disabled:bg-neutral-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-colors">
                                <span x-show="!isGenerating">Generate Plans</span>
                                <span x-show="isGenerating">Generating...</span>
                            </button>
                        </div>
                        @endif

                        {{-- Close Audit --}}
                        @if($closureValidation['can_close'] && !$readOnly && $audit->status === 'in_progress')
                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <div class="flex items-start space-x-3 mb-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-flag-checkered text-green-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-neutral-900 mb-1">Close Audit</h4>
                                    <p class="text-xs text-neutral-600">All validations passed. Finalize this audit</p>
                                </div>
                            </div>
                            <button @click="closeAudit()" :disabled="isClosing" class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 disabled:bg-neutral-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-colors">
                                <span x-show="!isClosing">Close Audit</span>
                                <span x-show="isClosing">Closing...</span>
                            </button>
                        </div>
                        @endif

                        {{-- Reopen Audit --}}
                        @if($audit->status === 'completed' && $userContext['has_global_view'])
                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <div class="flex items-start space-x-3 mb-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-folder-open text-orange-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-neutral-900 mb-1">Reopen Audit</h4>
                                    <p class="text-xs text-neutral-600">Requires system admin justification</p>
                                </div>
                            </div>
                            <button @click="reopenAudit()" :disabled="isReopening" class="w-full px-3 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-neutral-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-colors">
                                <span x-show="!isReopening">Reopen Audit</span>
                                <span x-show="isReopening">Reopening...</span>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Reports Tab --}}
            <div x-show="activeTab === 'reports'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-neutral-900">Available Reports</h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {{-- Comprehensive Report --}}
                        <div class="bg-white border border-neutral-200 rounded-xl p-4">
                            <div class="flex items-start space-x-3 mb-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-neutral-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-pdf text-neutral-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-neutral-900 mb-1">Comprehensive Report</h4>
                                    <p class="text-xs text-neutral-600">Full audit report with findings, scores, and action plans</p>
                                </div>
                            </div>
                            <button @click="window.location.href='{{ route('findings.download-report', $audit->id) }}'" :disabled="isDownloading" class="w-full px-3 py-2 bg-neutral-900 hover:bg-neutral-800 disabled:bg-neutral-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-colors">
                                <i class="fas fa-download mr-2"></i>
                                <span x-show="!isDownloading">Download PDF</span>
                                <span x-show="isDownloading">Generating...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add/Edit Finding Modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-neutral-900 bg-opacity-75" @click="closeModal()"></div>

            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-neutral-900" x-text="modalMode === 'add' ? 'Add Finding' : 'Edit Finding'"></h3>
                    <button @click="closeModal()" type="button" class="p-2 text-neutral-400 hover:text-neutral-900 hover:bg-neutral-100 rounded-lg transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1">Section <span class="text-red-600">*</span></label>
                        <select x-model="formData.section_id" @change="loadQuestions()" required class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900">
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                            <option value="{{ $section->id }}">Section {{ $section->code }}: {{ $section->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="availableQuestions.length > 0">
                        <label class="block text-sm font-medium text-neutral-700 mb-1">Question (Optional)</label>
                        <select x-model="formData.question_id" class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900">
                            <option value="">Select Question</option>
                            <template x-for="q in availableQuestions" :key="q.id">
                                <option :value="q.id" x-text="q.q_code + ': ' + q.text.substring(0, 80) + '...'"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1">Severity <span class="text-red-600">*</span></label>
                        <select x-model="formData.severity" required class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1">Title <span class="text-red-600">*</span></label>
                        <input type="text" x-model="formData.title" required minlength="5" maxlength="191" class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900" placeholder="Brief summary of the finding">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1">Description <span class="text-red-600">*</span></label>
                        <textarea x-model="formData.description" required minlength="10" rows="4" class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900" placeholder="Detailed description of the finding"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button @click="closeModal()" class="px-4 py-2 bg-white border border-neutral-300 text-neutral-700 text-sm font-medium rounded-xl hover:bg-neutral-50 transition-colors">
                            Cancel
                        </button>
                        <button @click="submitFinding()" :disabled="isSubmitting" class="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 disabled:bg-neutral-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-colors">
                            <span x-show="!isSubmitting" x-text="modalMode === 'add' ? 'Add Finding' : 'Update Finding'"></span>
                            <span x-show="isSubmitting">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function findingsIndexApp() {
    return {
        // Tab state
        activeTab: 'overview',

        // Modal state
        showModal: false,
        modalMode: 'add',

        // Loading states
        isSubmitting: false,
        isSyncing: false,
        isGenerating: false,
        isClosing: false,
        isReopening: false,
        isDownloading: false,
        isDeleting: null,

        // Form data
        formData: {
            id: null,
            section_id: '',
            question_id: null,
            title: '',
            description: '',
            severity: 'medium'
        },

        // Questions for dropdown
        availableQuestions: [],

        init() {
            console.log('Findings index initialized');
        },

        scrollToSection(sectionId) {
            setTimeout(() => {
                const element = document.getElementById('section-' + sectionId);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 100);
        },

        async loadQuestions() {
            if (!this.formData.section_id) {
                this.availableQuestions = [];
                return;
            }

            try {
                const response = await fetch(`/api/sections/${this.formData.section_id}/questions`);
                if (response.ok) {
                    this.availableQuestions = await response.json();
                }
            } catch (error) {
                console.error('Failed to load questions:', error);
            }
        },

        openAddModal(sectionId = null) {
            this.modalMode = 'add';
            this.formData = {
                id: null,
                section_id: sectionId || '',
                question_id: null,
                title: '',
                description: '',
                severity: 'medium'
            };
            this.availableQuestions = [];
            if (sectionId) {
                this.loadQuestions();
            }
            this.showModal = true;
        },

        openAddModalForQuestion(questionId, qCode, sectionId) {
            this.modalMode = 'add';
            this.formData = {
                id: null,
                section_id: sectionId || '',
                question_id: questionId,
                title: `[${qCode}] Non-conformance detected`,
                description: '',
                severity: 'medium'
            };
            this.availableQuestions = [];
            if (sectionId) {
                this.loadQuestions();
            }
            this.showModal = true;
        },

        openEditModal(id, title, description, severity) {
            this.modalMode = 'edit';
            this.formData = {
                id: id,
                section_id: '',
                question_id: null,
                title: title,
                description: description,
                severity: severity || 'medium'
            };
            this.availableQuestions = [];
            this.showModal = true;
        },

        closeModal() {
            if (!this.isSubmitting) {
                this.showModal = false;
            }
        },

        async submitFinding() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;

            try {
                const url = this.modalMode === 'add'
                    ? '{{ route("findings.store") }}'
                    : `/findings/${this.formData.id}`;

                const method = this.modalMode === 'add' ? 'POST' : 'PUT';

                // Clean data: convert empty strings to null for optional fields
                const payload = {
                    audit_id: {{ $audit->id }},
                    section_id: this.formData.section_id ? parseInt(this.formData.section_id) : null,
                    question_id: this.formData.question_id ? parseInt(this.formData.question_id) : null,
                    title: this.formData.title.trim(),
                    description: this.formData.description.trim(),
                    severity: this.formData.severity || 'medium'
                };

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Success', data.message || 'Finding saved successfully', 'success');
                    this.showModal = false;
                    setTimeout(() => location.reload(), 1000);
                } else {
                    // Show validation errors if available
                    if (data.errors) {
                        const firstError = Object.values(data.errors)[0][0];
                        this.showNotification('Validation Error', firstError, 'error');
                    } else {
                        this.showNotification('Error', data.error || 'Failed to save finding', 'error');
                    }
                }
            } catch (error) {
                console.error('Submit error:', error);
                this.showNotification('Error', 'An error occurred', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async deleteFinding(id) {
            if (this.isDeleting === id) return;

            if (!confirm('Are you sure you want to delete this finding? This action cannot be undone.')) return;

            this.isDeleting = id;

            try {
                const response = await fetch(`{{ route('findings.index') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Success', 'Finding deleted', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showNotification('Error', data.error || 'Failed to delete', 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                this.showNotification('Error', 'An error occurred', 'error');
            } finally {
                this.isDeleting = null;
            }
        },

        async autoSync() {
            if (this.isSyncing) return;

            if (!confirm('Run auto-sync? This will create findings for P/N responses and update action plans.')) return;

            this.isSyncing = true;

            try {
                const response = await fetch('{{ route("findings.auto-sync", $audit->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Success', `Synced: ${data.report.findings_created} findings created`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showNotification('Error', data.error || 'Sync failed', 'error');
                }
            } catch (error) {
                console.error('Sync error:', error);
                this.showNotification('Error', 'An error occurred', 'error');
            } finally {
                this.isSyncing = false;
            }
        },

        async generateActionPlans() {
            if (this.isGenerating) return;

            if (!confirm('Generate action plans for all findings? Existing plans will not be duplicated.')) return;

            this.isGenerating = true;

            try {
                const response = await fetch('{{ route("findings.generate-action-plans", $audit->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Success', `Generated ${data.created_count} action plans`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showNotification('Error', data.error || 'Generation failed', 'error');
                }
            } catch (error) {
                console.error('Generate error:', error);
                this.showNotification('Error', 'An error occurred', 'error');
            } finally {
                this.isGenerating = false;
            }
        },

        async closeAudit() {
            if (this.isClosing) return;

            if (!confirm('Close this audit? This action finalizes all findings and scores. This cannot be undone without admin approval.')) return;

            this.isClosing = true;

            try {
                const response = await fetch('{{ route("findings.close-audit", $audit->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Success', 'Audit closed successfully', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showNotification('Error', data.error || 'Cannot close audit', 'error');
                }
            } catch (error) {
                console.error('Close error:', error);
                this.showNotification('Error', 'An error occurred', 'error');
            } finally {
                this.isClosing = false;
            }
        },

        async reopenAudit() {
            if (this.isReopening) return;

            const reason = prompt('Enter justification for reopening this audit (minimum 20 characters):');
            if (!reason || reason.length < 20) {
                this.showNotification('Error', 'Valid justification required (minimum 20 characters)', 'error');
                return;
            }

            this.isReopening = true;

            try {
                const response = await fetch('{{ route("findings.reopen-audit", $audit->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ reason: reason })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Success', 'Audit reopened', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showNotification('Error', data.error || 'Cannot reopen', 'error');
                }
            } catch (error) {
                console.error('Reopen error:', error);
                this.showNotification('Error', 'An error occurred', 'error');
            } finally {
                this.isReopening = false;
            }
        },

        showNotification(title, message, type) {
            const bgColors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                info: 'bg-blue-600'
            };
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle'
            };

            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 ${bgColors[type]} text-white px-4 py-3 rounded-xl shadow-lg flex items-center space-x-3 z-50 animate-fade-in`;
            toast.innerHTML = `
                <i class="fas ${icons[type]}"></i>
                <div>
                    <div class="font-medium text-sm">${title}</div>
                    <div class="text-xs opacity-90">${message}</div>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
.scrollbar-none::-webkit-scrollbar { display: none; }
.scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endsection
