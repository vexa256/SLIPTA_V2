@extends('layouts.app')

@section('title', 'Action Plans Management')

@section('content')
<div x-data="actionPlansIndex()" x-init="init()" class="min-h-screen bg-neutral-50">

    <!-- Page Header -->
    <div class="bg-white border-b border-neutral-200 sticky top-0 z-40">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-neutral-900">Action Plans</h1>
                    <p class="text-sm text-neutral-500 mt-1">Manage corrective and improvement actions</p>
                </div>
                <div class="flex items-center space-x-3">
                    @if($ctx['is_admin'] || $ctx['is_country_coord'] || $ctx['is_auditor'])
                    <a href="{{ route('action-plans.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-xl hover:bg-black transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create Action Plan
                    </a>
                    @endif

                    <button @click="showHelp = !showHelp"
                            class="p-2 text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex items-center space-x-1 mt-4 overflow-x-auto scrollbar-hide">
                <button @click="switchTab('overview')"
                        :class="activeTab === 'overview' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-100'"
                        class="px-4 py-2 text-sm font-medium rounded-xl transition-colors whitespace-nowrap">
                    Overview
                </button>
                <button @click="switchTab('all')"
                        :class="activeTab === 'all' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-100'"
                        class="px-4 py-2 text-sm font-medium rounded-xl transition-colors whitespace-nowrap">
                    All Plans
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full" :class="activeTab === 'all' ? 'bg-white/20' : 'bg-neutral-200'">
                        {{ $meta['total'] }}
                    </span>
                </button>
                <button @click="switchTab('my_plans')"
                        :class="activeTab === 'my_plans' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-100'"
                        class="px-4 py-2 text-sm font-medium rounded-xl transition-colors whitespace-nowrap">
                    My Plans
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full" :class="activeTab === 'my_plans' ? 'bg-white/20' : 'bg-neutral-200'">
                        {{ $meta['my_plans'] }}
                    </span>
                </button>
                <button @click="switchTab('overdue')"
                        :class="activeTab === 'overdue' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-100'"
                        class="px-4 py-2 text-sm font-medium rounded-xl transition-colors whitespace-nowrap">
                    Overdue
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800">
                        {{ $meta['overdue'] }}
                    </span>
                </button>
                <button @click="activeTab = 'analytics'"
                        :class="activeTab === 'analytics' ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-100'"
                        class="px-4 py-2 text-sm font-medium rounded-xl transition-colors whitespace-nowrap">
                    Analytics
                </button>
            </div>
        </div>
    </div>

    <!-- Contextual Help Panel -->
    <div x-show="showHelp"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-blue-50 border-b border-blue-200 px-6 py-4">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-medium text-blue-900">Action Plans Help</h3>
                <div class="mt-2 text-sm text-blue-800 space-y-1">
                    <p><strong>What's Happening:</strong> Action plans track corrective actions for audit findings, risks, and improvement opportunities.</p>
                    <p><strong>What to Do:</strong> Create action plans for findings, assign responsibilities, set due dates, and monitor progress.</p>
                    <p><strong>Your Access:</strong>
                        @if($ctx['is_admin']) Full system access - manage all action plans
                        @elseif($ctx['is_country_coord']) Country-level access - manage plans in your country
                        @elseif($ctx['is_auditor']) Audit-level access - manage plans for your assigned audits
                        @elseif($ctx['is_lab_role']) Laboratory access - manage plans for your laboratory
                        @endif
                    </p>
                </div>
            </div>
            <button @click="showHelp = false" class="flex-shrink-0 text-blue-600 hover:text-blue-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="p-6">

        <!-- Overview Tab -->
        <div x-show="activeTab === 'overview'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">

            <!-- Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

                <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-neutral-600">Total Plans</p>
                            <p class="text-2xl font-semibold text-neutral-900 mt-1">{{ $meta['total'] }}</p>
                        </div>
                        <div class="w-12 h-12 bg-neutral-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-neutral-500">Across all audits</div>
                </div>

                <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-neutral-600">Open</p>
                            <p class="text-2xl font-semibold text-neutral-900 mt-1">{{ $meta['open'] }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-neutral-500">Awaiting action</div>
                </div>

                <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-neutral-600">In Progress</p>
                            <p class="text-2xl font-semibold text-neutral-900 mt-1">{{ $meta['in_progress'] }}</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-neutral-500">Currently active</div>
                </div>

                <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-neutral-600">Overdue</p>
                            <p class="text-2xl font-semibold text-red-900 mt-1">{{ $meta['overdue'] }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-red-600">Require immediate attention</div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

                <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
                    <h3 class="text-sm font-medium text-neutral-900 mb-4">Status Distribution</h3>
                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                    <span class="text-sm text-neutral-600">Open</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-neutral-900">{{ $meta['open'] }}</span>
                                    <span class="text-xs text-neutral-500">
                                        ({{ $meta['total'] > 0 ? round(($meta['open'] / $meta['total']) * 100, 1) : 0 }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-neutral-100 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $meta['total'] > 0 ? ($meta['open'] / $meta['total']) * 100 : 0 }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <span class="text-sm text-neutral-600">In Progress</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-neutral-900">{{ $meta['in_progress'] }}</span>
                                    <span class="text-xs text-neutral-500">
                                        ({{ $meta['total'] > 0 ? round(($meta['in_progress'] / $meta['total']) * 100, 1) : 0 }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-neutral-100 rounded-full h-2">
                                <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $meta['total'] > 0 ? ($meta['in_progress'] / $meta['total']) * 100 : 0 }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span class="text-sm text-neutral-600">Closed</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-neutral-900">{{ $meta['closed'] }}</span>
                                    <span class="text-xs text-neutral-500">
                                        ({{ $meta['total'] > 0 ? round(($meta['closed'] / $meta['total']) * 100, 1) : 0 }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-neutral-100 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $meta['total'] > 0 ? ($meta['closed'] / $meta['total']) * 100 : 0 }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-neutral-400 rounded-full"></div>
                                    <span class="text-sm text-neutral-600">Deferred</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-neutral-900">{{ $meta['deferred'] }}</span>
                                    <span class="text-xs text-neutral-500">
                                        ({{ $meta['total'] > 0 ? round(($meta['deferred'] / $meta['total']) * 100, 1) : 0 }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-neutral-100 rounded-full h-2">
                                <div class="bg-neutral-400 h-2 rounded-full" style="width: {{ $meta['total'] > 0 ? ($meta['deferred'] / $meta['total']) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
                    <h3 class="text-sm font-medium text-neutral-900 mb-4">Quick Stats</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-neutral-100">
                            <span class="text-sm text-neutral-600">My Assigned Plans</span>
                            <span class="text-lg font-semibold text-neutral-900">{{ $meta['my_plans'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-neutral-100">
                            <span class="text-sm text-neutral-600">Overdue Plans</span>
                            <span class="text-lg font-semibold text-red-900">{{ $meta['overdue'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-neutral-100">
                            <span class="text-sm text-neutral-600">Completion Rate</span>
                            <span class="text-lg font-semibold text-green-900">
                                {{ $meta['total'] > 0 ? round(($meta['closed'] / $meta['total']) * 100, 1) : 0 }}%
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <span class="text-sm text-neutral-600">Active Plans</span>
                            <span class="text-lg font-semibold text-neutral-900">
                                {{ $meta['open'] + $meta['in_progress'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-neutral-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button @click="switchTab('my_plans')"
                            class="flex items-center justify-center px-4 py-3 bg-neutral-50 hover:bg-neutral-100 border border-neutral-200 rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-neutral-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="text-sm font-medium text-neutral-900">View My Plans</span>
                    </button>

                    <button @click="switchTab('overdue')"
                            class="flex items-center justify-center px-4 py-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="text-sm font-medium text-red-900">Review Overdue</span>
                    </button>

                    @if($ctx['is_admin'] || $ctx['is_country_coord'] || $ctx['is_auditor'])
                    <a href="{{ route('action-plans.create') }}"
                       class="flex items-center justify-center px-4 py-3 bg-neutral-900 hover:bg-black text-white rounded-xl transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="text-sm font-medium">Create New Plan</span>
                    </a>
                    @endif

                    <button @click="activeTab = 'analytics'"
                            class="flex items-center justify-center px-4 py-3 bg-neutral-50 hover:bg-neutral-100 border border-neutral-200 rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-neutral-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="text-sm font-medium text-neutral-900">View Analytics</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- All/My Plans/Overdue Tabs (Filtered Table) -->
        <div x-show="['all', 'my_plans', 'overdue'].includes(activeTab)"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">

            <!-- Filters -->
            <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4 mb-6">
                <div class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                        @if($ctx['is_admin'])
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Country</label>
                            <select x-model="filters.country_id"
                                    class="block w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                <option value="">All Countries</option>
                                @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Status</label>
                            <select x-model="filters.status"
                                    class="block w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                <option value="">All Statuses</option>
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="closed">Closed</option>
                                <option value="deferred">Deferred</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Type</label>
                            <select x-model="filters.type"
                                    class="block w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                <option value="">All Types</option>
                                <option value="finding">Finding</option>
                                <option value="risk_opportunity">Risk & Opportunity</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Section</label>
                            <select x-model="filters.section_id"
                                    class="block w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                <option value="">All Sections</option>
                                @foreach($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->code }}. {{ $section->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Responsible Person</label>
                            <select x-model="filters.responsible_user_id"
                                    class="block w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Audit ID</label>
                            <input type="number" x-model="filters.audit_id" placeholder="Filter by audit..."
                                   class="block w-full px-3 py-2 bg-white border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button @click="applyFilters()"
                                class="inline-flex items-center px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-xl hover:bg-black transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            Apply Filters
                        </button>
                        <button @click="clearFilters()"
                                class="inline-flex items-center px-4 py-2 bg-white text-neutral-700 border border-neutral-300 text-sm font-medium rounded-xl hover:bg-neutral-50 transition-colors">
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Action Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Laboratory</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Responsible</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-neutral-200">
                            @forelse($plans as $plan)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-neutral-900 line-clamp-2">
                                            {{ $plan->recommendation }}
                                        </div>
                                        <div class="flex items-center mt-1 space-x-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                {{ $plan->type === 'finding' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $plan->type === 'risk_opportunity' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $plan->type === 'other' ? 'bg-neutral-100 text-neutral-800' : '' }}">
                                                {{ ucfirst(str_replace('_', ' ', $plan->type)) }}
                                            </span>
                                            @if($plan->section_title)
                                            <span class="text-xs text-neutral-500">{{ $plan->section_code }}. {{ $plan->section_title }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-neutral-900">{{ $plan->lab_name }}</div>
                                    @if($plan->country_name)
                                    <div class="text-xs text-neutral-500">{{ $plan->country_name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $plan->status === 'open' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $plan->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $plan->status === 'closed' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $plan->status === 'deferred' ? 'bg-neutral-100 text-neutral-800' : '' }}">
                                        {{ ucfirst(str_replace('_', ' ', $plan->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-neutral-900">
                                        {{ $plan->responsible_name ?? 'Unassigned' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-neutral-900">
                                        {{ \Carbon\Carbon::parse($plan->due_date)->format('M d, Y') }}
                                    </div>
                                    @if($plan->days_until_due < 0)
                                    <div class="text-xs text-red-600 font-medium">
                                        {{ abs($plan->days_until_due) }} days overdue
                                    </div>
                                    @elseif($plan->days_until_due <= 7)
                                    <div class="text-xs text-yellow-600 font-medium">
                                        Due in {{ $plan->days_until_due }} days
                                    </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('action-plans.show', $plan->id) }}"
                                           class="p-1.5 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 rounded-lg transition-colors"
                                           title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        @if($ctx['is_admin'] || $ctx['is_country_coord'] || $ctx['is_auditor'])
                                        <a href="{{ route('action-plans.edit', $plan->id) }}"
                                           class="p-1.5 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 rounded-lg transition-colors"
                                           title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-neutral-500">No action plans found</p>
                                    @if($ctx['is_admin'] || $ctx['is_country_coord'] || $ctx['is_auditor'])
                                    <a href="{{ route('action-plans.create') }}"
                                       class="inline-flex items-center mt-4 px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-xl hover:bg-black transition-colors">
                                        Create Action Plan
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($plans->hasPages())
                <div class="bg-neutral-50 px-6 py-4 border-t border-neutral-200">
                    {{ $plans->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- Analytics Tab -->
        <div x-show="activeTab === 'analytics'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">

            <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="mt-2 text-sm text-neutral-500">Advanced analytics and reporting dashboard</p>
                    <a href="{{ route('action-plans.dashboard') }}"
                       class="inline-flex items-center mt-4 px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-xl hover:bg-black transition-colors">
                        View Full Analytics Dashboard
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function actionPlansIndex() {
    return {
        activeTab: '{{ request("tab", "overview") }}',
        showHelp: false,
        filters: {
            country_id: '{{ request("country_id", "") }}',
            status: '{{ request("status", "") }}',
            type: '{{ request("type", "") }}',
            section_id: '{{ request("section_id", "") }}',
            responsible_user_id: '{{ request("responsible_user_id", "") }}',
            audit_id: '{{ request("audit_id", "") }}'
        },

        init() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab')) {
                this.activeTab = urlParams.get('tab');
            }
        },

        switchTab(tab) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);

            // Clear filters when switching tabs
            url.searchParams.delete('country_id');
            url.searchParams.delete('status');
            url.searchParams.delete('type');
            url.searchParams.delete('section_id');
            url.searchParams.delete('responsible_user_id');
            url.searchParams.delete('audit_id');

            window.location.href = url.toString();
        },

        applyFilters() {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', this.activeTab);

            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    url.searchParams.set(key, this.filters[key]);
                } else {
                    url.searchParams.delete(key);
                }
            });

            window.location.href = url.toString();
        },

        clearFilters() {
            window.location.href = '{{ route("action-plans.index") }}?tab=' + this.activeTab;
        }
    }
}
</script>
@endsection
