@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-neutral-50" x-data="auditWizardPremium($el)" data-audits='@json($audits ?? [])'
        data-route-show="{{ route('audits.show', ['auditId' => '__ID__']) }}"
        data-route-gate="{{ route('audits.select.gate') }}">

        <!-- Top Bar -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-neutral-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-14 sm:h-16">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl bg-neutral-900 text-white grid place-items-center shadow">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6l6 6V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-base sm:text-lg font-semibold text-neutral-900 tracking-tight">SLIPTA Audits
                            </h1>
                            <p class="text-xs text-neutral-500 hidden sm:block">Select → Verify → Enter</p>
                        </div>
                    </div>
                    <button type="button" @click="hardRefresh()"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-neutral-200 bg-white text-sm font-medium text-neutral-700 hover:bg-neutral-50 active:scale-[0.98] transition">
                        <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.6m15.3 2A8 8 0 004.6 9M4.6 9H9m11 11v-5h-.6m0 0a8 8 0 01-15.4-2m15.4 2H15" />
                        </svg>
                        <span class="hidden sm:inline">Refresh</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Stepper (non-clickable) -->
        <div class="bg-white border-b border-neutral-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 sm:py-5">
                <nav class="flex items-center" aria-label="Progress">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-full grid place-items-center text-sm font-semibold"
                            :class="step >= 0 ? 'bg-neutral-900 text-white ring-4 ring-neutral-900/10' :
                                'bg-neutral-100 text-neutral-400'">
                            <svg x-show="step > 0" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span x-show="step === 0">1</span>
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-medium text-neutral-500">Step 1</p>
                            <p class="text-sm font-semibold" :class="step === 0 ? 'text-neutral-900' : 'text-neutral-600'">
                                Browse</p>
                        </div>
                    </div>
                    <div class="flex-1 h-0.5 mx-3 sm:mx-4 rounded-full"
                        :class="step >= 1 ? 'bg-neutral-900' : 'bg-neutral-200'"></div>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-full grid place-items-center text-sm font-semibold"
                            :class="step >= 1 ? 'bg-neutral-900 text-white ring-4 ring-neutral-900/10' :
                                'bg-neutral-100 text-neutral-400'">
                            <svg x-show="step > 1" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span x-show="step <= 1">2</span>
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-medium text-neutral-500">Step 2</p>
                            <p class="text-sm font-semibold" :class="step === 1 ? 'text-neutral-900' : 'text-neutral-600'">
                                Profile</p>
                        </div>
                    </div>
                    <div class="flex-1 h-0.5 mx-3 sm:mx-4 rounded-full"
                        :class="step >= 2 ? 'bg-neutral-900' : 'bg-neutral-200'"></div>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-full grid place-items-center text-sm font-semibold"
                            :class="step >= 2 ? 'bg-neutral-900 text-white ring-4 ring-neutral-900/10' :
                                'bg-neutral-100 text-neutral-400'">
                            <span>3</span>
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-medium text-neutral-500">Step 3</p>
                            <p class="text-sm font-semibold" :class="step === 2 ? 'text-neutral-900' : 'text-neutral-600'">
                                Enter</p>
                        </div>
                    </div>
                </nav>
            </div>
        </div>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 py-4 sm:py-6 pb-24">

            <!-- Banners -->
            <div class="space-y-3 mb-4" x-show="banner.error || banner.warn || banner.info">
                <div x-show="banner.error" x-transition
                    class="rounded-2xl border border-red-200 bg-red-50 p-4 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-red-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.7 7.3a1 1 0 00-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 101.4 1.4L10 11.4l1.3 1.3a1 1 0 001.4-1.4L11.4 10l1.3-1.3a1 1 0 00-1.4-1.4L10 8.6 8.7 7.3z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm font-medium text-red-900" x-text="banner.error"></p>
                </div>
                <div x-show="banner.warn" x-transition
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-amber-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M8.26 3.1c.77-1.36 2.71-1.36 3.48 0l5.58 9.92A2 2 0 0115.9 16H4.1a2 2 0 01-1.42-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" />
                    </svg>
                    <p class="text-sm font-medium text-amber-900" x-text="banner.warn"></p>
                </div>
                <div x-show="banner.info" x-transition
                    class="rounded-2xl border border-blue-200 bg-blue-50 p-4 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm font-medium text-blue-900" x-text="banner.info"></p>
                </div>
            </div>

            <!-- STEP 0: BROWSE -->
            <section x-show="step === 0" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                <!-- Filters -->
                <div class="bg-white rounded-2xl border border-neutral-200 p-4 sm:p-6 mb-4 shadow-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                        <div class="sm:col-span-2 lg:col-span-1">
                            <label class="block text-sm font-medium text-neutral-700 mb-1.5">Search</label>
                            <div class="relative">
                                <input type="text" x-model.debounce.200ms="search" placeholder="ID, lab, country…"
                                    class="block w-full pl-10 pr-3 py-2.5 border border-neutral-300 rounded-xl text-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900 bg-white">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1.5">Country</label>
                            <select x-model="country"
                                class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900 bg-white">
                                <option value="">All Countries</option>
                                <template x-for="c in countries" :key="c">
                                    <option :value="c" x-text="c"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1.5">Laboratory</label>
                            <select x-model="lab"
                                class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900 bg-white">
                                <option value="">All Labs</option>
                                <template x-for="l in labs" :key="l">
                                    <option :value="l" x-text="l"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Results -->
                <div class="space-y-3">
                    <template x-for="audit in paged" :key="audit.id">
                        <div @click="selectAudit(audit)"
                            class="bg-white rounded-2xl border border-neutral-200 p-4 sm:p-5 cursor-pointer hover:border-neutral-300 hover:shadow-md active:scale-[0.99] transition group">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-neutral-100 text-neutral-800">ID:
                                            <span x-text="audit.id"></span></span>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">In
                                            Progress</span>
                                    </div>
                                    <h3 class="text-base sm:text-lg font-semibold text-neutral-900 mb-1 group-hover:text-neutral-700"
                                        x-text="audit.lab_name"></h3>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-neutral-600">
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M7 7h.01M7 3h5l7 7v7a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                                            </svg>
                                            <span x-text="audit.lab_number || 'N/A'"></span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5s-3 1.343-3 3 1.343 3 3 3z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 22s8-4.5 8-12a8 8 0 10-16 0c0 7.5 8 12 8 12z" />
                                            </svg>
                                            <span x-text="audit.country_name"></span>
                                        </div>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-neutral-400 flex-shrink-0 group-hover:text-neutral-600 group-hover:translate-x-1 transition"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </template>

                    <div x-show="paged.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6l6 6V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-4 text-sm font-medium text-neutral-900">No audits found</p>
                        <p class="mt-1 text-sm text-neutral-500">Try adjusting your filters</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div x-show="filtered.length > perPage"
                    class="mt-4 flex items-center justify-between bg-white rounded-2xl border border-neutral-200 p-4 shadow-sm">
                    <button @click="prev()" :disabled="page === 1"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="page === 1 ? 'border-neutral-200 text-neutral-400' :
                            'border-neutral-300 text-neutral-700 hover:bg-neutral-50'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="hidden sm:inline">Previous</span>
                    </button>
                    <span class="text-sm text-neutral-600">
                        Page <span class="font-semibold text-neutral-900" x-text="page"></span> of <span
                            class="font-semibold text-neutral-900" x-text="Math.ceil(filtered.length / perPage)"></span>
                    </span>
                    <button @click="next()" :disabled="page * perPage >= filtered.length"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="page * perPage >= filtered.length ? 'border-neutral-200 text-neutral-400' :
                            'border-neutral-300 text-neutral-700 hover:bg-neutral-50'">
                        <span class="hidden sm:inline">Next</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </section>

            <!-- STEP 1: PROFILE -->
            <section x-show="step === 1" x-cloak x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                <!-- Assistant -->
                <div class="mb-4 bg-neutral-900 rounded-2xl p-4 sm:p-5 text-white shadow">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl bg-white/10 grid place-items-center">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold mb-1" x-text="assistantTitle"></h3>
                            <p class="text-sm text-white/80" x-text="assistantBody"></p>
                            <div x-show="unverifiedRequired.length" class="mt-3 p-3 bg-white/10 rounded-xl">
                                <p class="text-xs font-medium mb-2">Ensure you verify all default data</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="field in unverifiedRequired" :key="field">
                                        {{-- <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-white/20 text-white" x-text="field"></span> --}}
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Autosave status -->
                <div x-show="autosaveStatus" x-transition
                    class="mb-4 flex items-center justify-center gap-2 text-sm text-neutral-600 bg-white rounded-xl border border-neutral-200 py-2 px-4 shadow-sm">
                    <svg class="w-4 h-4 animate-spin" x-show="autosaveStatus === 'Saving draft…'" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
                    </svg>
                    <svg class="w-4 h-4 text-green-600" x-show="autosaveStatus === 'Draft saved.'" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <span x-text="autosaveStatus"></span>
                </div>

                <form @submit.prevent="saveProfile()" x-ref="profileForm" class="space-y-4">

                    <!-- Dates -->
                    <div class="bg-white rounded-2xl border border-neutral-200 p-4 sm:p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-neutral-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-neutral-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Audit Dates
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1.5">This Audit Date <span
                                        class="text-red-500">*</span></label>
                                <input type="date" x-model="profile.dates.this_audit"
                                    @input="trackTouch('dates.this_audit'); autoSave()"
                                    @keydown.enter.prevent="saveProfile()" required
                                    class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1.5">Last Audit Date</label>
                                <input type="date" x-model="profile.dates.last_audit"
                                    @input="trackTouch('dates.last_audit'); autoSave()"
                                    @keydown.enter.prevent="saveProfile()"
                                    class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-neutral-700 mb-1.5">Prior Official Status
                                    <span class="text-red-500">*</span></label>
                                <select x-model="profile.dates.prior_official_status"
                                    @change="trackTouch('dates.prior_official_status'); autoSave()" required
                                    class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                    <option value="not_audited">Not Previously Audited</option>
                                    <option value="0">0 Stars</option>
                                    <option value="1">1 Star</option>
                                    <option value="2">2 Stars</option>
                                    <option value="3">3 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="5">5 Stars</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Auditors -->
                    <div class="bg-white rounded-2xl border border-neutral-200 p-4 sm:p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-neutral-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-neutral-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857a5 5 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Audit Team
                        </h2>
                        <div x-show="auditorNames.length"
                            class="mb-4 p-3 bg-neutral-50 rounded-xl border border-neutral-200">
                            <p class="text-xs font-medium text-neutral-700 mb-2">Assigned Auditors:</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="name in auditorNames" :key="name">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-white border border-neutral-200 text-neutral-700"
                                        x-text="name"></span>
                                </template>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(aud, idx) in profile.auditors" :key="'aud-' + idx">
                                <div class="flex gap-2 items-start p-3 bg-neutral-50 rounded-xl border border-neutral-200">
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-neutral-700 mb-1">Name</label>
                                            <input type="text" x-model="aud.name" @input="autoSave()"
                                                class="block w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-neutral-700 mb-1">Affiliation</label>
                                            <input type="text" x-model="aud.affiliation" @input="autoSave()"
                                                class="block w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                        </div>
                                    </div>
                                    <button type="button" @click="removeAuditor(idx)"
                                        class="mt-6 p-2 rounded-lg text-neutral-400 hover:text-red-600 hover:bg-red-50 transition">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 7l-.9 12.1A2 2 0 0116.1 21H7.9a2 2 0 01-2-1.9L5 7m5 4v6m4-6v6M15 7V4a1 1 0 00-1-1H10a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="addAuditor()"
                                class="w-full px-4 py-2.5 border-2 border-dashed border-neutral-300 rounded-xl text-sm font-medium text-neutral-600 hover:border-neutral-400 hover:bg-neutral-50 transition flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Additional Auditor
                            </button>
                        </div>
                    </div>

                    <!-- Laboratory -->
                    <div class="bg-white rounded-2xl border border-neutral-200 p-4 sm:p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-neutral-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-neutral-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Laboratory Information
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1.5">Laboratory Name <span
                                        class="text-red-500">*</span></label>
                                <input type="text" x-model="profile.laboratory.name"
                                    @input="trackTouch('laboratory.name'); autoSave()" required
                                    class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1.5">Lab Number</label>
                                <input type="text" x-model="profile.laboratory.lab_number" @input="autoSave()"
                                    class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                            </div>

                            <!-- Country: ID + Name are both in the contract -->
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1.5">Country ID <span
                                        class="text-red-500">*</span></label>
                                <input type="number" x-model.number="profile.laboratory.country_id"
                                    @input="trackTouch('laboratory.country_id'); autoSave()" required
                                    class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1.5">Country Name</label>
                                <input type="text" x-model="profile.laboratory.country_name" @input="autoSave()"
                                    class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                            </div>

                            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1.5">Address</label>
                                    <input type="text" x-model="profile.laboratory.address" @input="autoSave()"
                                        class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1.5">City</label>
                                    <input type="text" x-model="profile.laboratory.city" @input="autoSave()"
                                        class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:col-span-2">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1.5">Phone</label>
                                    <input type="tel" x-model="profile.laboratory.phone" @input="autoSave()"
                                        class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1.5">Fax</label>
                                    <input type="tel" x-model="profile.laboratory.fax" @input="autoSave()"
                                        class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1.5">Email</label>
                                    <input type="email" x-model="profile.laboratory.email" @input="autoSave()"
                                        class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                </div>
                            </div>

                            <!-- GPS -->
                            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1.5">Latitude</label>
                                    <input type="number" step="any" x-model.number="profile.laboratory.gps.lat"
                                        @input="autoSave()" placeholder="e.g., -1.2921"
                                        class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1.5">Longitude</label>
                                    <input type="number" step="any" x-model.number="profile.laboratory.gps.lng"
                                        @input="autoSave()" placeholder="e.g., 36.8219"
                                        class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                </div>
                            </div>

                            <!-- Representative -->
                            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1.5">Representative
                                        Name</label>
                                    <input type="text" x-model="profile.laboratory.representative.name"
                                        @input="autoSave()"
                                        class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1.5">Work Phone</label>
                                    <input type="tel" x-model="profile.laboratory.representative.phone_work"
                                        @input="autoSave()"
                                        class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1.5">Personal Phone</label>
                                    <input type="tel" x-model="profile.laboratory.representative.phone_personal"
                                        @input="autoSave()"
                                        class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                </div>
                            </div>

                            <!-- Level & Affiliation -->
                            <div class="sm:col-span-2">
                                <h3 class="text-sm font-medium text-neutral-900 mb-3">Level & Affiliation</h3>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-neutral-700 mb-2">Level (select all that
                                        apply) <span class="text-red-500">*</span></label>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        <template x-for="opt in levelOptions" :key="'lvl-' + opt">
                                            <label
                                                class="flex items-start p-3 bg-neutral-50 rounded-xl border border-neutral-200 cursor-pointer hover:bg-neutral-100">
                                                <input type="checkbox" :value="opt"
                                                    x-model="profile.laboratory.level_affiliation.level"
                                                    @change="trackTouch('laboratory.level_affiliation.level'); autoSave()"
                                                    class="h-4 w-4 rounded border-neutral-300 text-neutral-900 focus:ring-2 focus:ring-neutral-900/10">
                                                <span class="ml-2 text-sm text-neutral-900"
                                                    x-text="labelCase(opt)"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-2">Affiliation (select all
                                        that apply) <span class="text-red-500">*</span></label>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                        <template x-for="opt in affiliationOptions" :key="'aff-' + opt">
                                            <label
                                                class="flex items-start p-3 bg-neutral-50 rounded-xl border border-neutral-200 cursor-pointer hover:bg-neutral-100">
                                                <input type="checkbox" :value="opt"
                                                    x-model="profile.laboratory.level_affiliation.affiliation"
                                                    @change="trackTouch('laboratory.level_affiliation.affiliation'); autoSave()"
                                                    class="h-4 w-4 rounded border-neutral-300 text-neutral-900 focus:ring-2 focus:ring-neutral-900/10">
                                                <span class="ml-2 text-sm text-neutral-900"
                                                    x-text="labelCase(opt)"></span>
                                            </label>
                                        </template>
                                    </div>

                                    <div x-show="profile.laboratory.level_affiliation.affiliation.includes('other')"
                                        class="mt-3">
                                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Please specify
                                            "Other"</label>
                                        <input type="text" x-model="profile.laboratory.level_affiliation.other_note"
                                            @input="autoSave()"
                                            class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Staffing Summary -->
                    <div class="bg-white rounded-2xl border border-neutral-200 p-4 sm:p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-neutral-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-neutral-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 4.35a4 4 0 110 5.3M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.2M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Staffing Summary
                        </h2>

                        <div class="space-y-4">
                            <!-- Reusable card -->
                            <template x-for="rk in staffingKeys" :key="'rk-' + rk">
                                <div class="p-4 bg-neutral-50 rounded-xl border border-neutral-200">
                                    <h3 class="text-sm font-medium text-neutral-900 mb-3"
                                        x-text="staffingLabels[rk] || rk"></h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-neutral-700 mb-1">Count</label>
                                            <input type="number" min="0"
                                                x-model.number="profile.staffing_summary[rk].count" @input="autoSave()"
                                                class="block w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-neutral-700 mb-1">Adequate?</label>
                                            <select x-model="profile.staffing_summary[rk].adequate" @change="autoSave()"
                                                class="block w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                                <option value="yes">Yes</option>
                                                <option value="no">No</option>
                                                <option value="insufficient">Insufficient</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- role-specific flags -->
                                    <div class="mt-2 grid grid-cols-2 gap-2"
                                        x-show="rk==='cleaners' || rk==='drivers_couriers'">
                                        <label class="inline-flex items-center gap-2 text-sm text-neutral-800">
                                            <input type="checkbox" x-model="profile.staffing_summary[rk].dedicated"
                                                @change="autoSave()"
                                                class="h-4 w-4 rounded border-neutral-300 text-neutral-900 focus:ring-2 focus:ring-neutral-900/10">
                                            Dedicated
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-sm text-neutral-800"
                                            x-show="rk==='cleaners'">
                                            <input type="checkbox"
                                                x-model="profile.staffing_summary.cleaners.trained_safety_waste"
                                                @change="autoSave()"
                                                class="h-4 w-4 rounded border-neutral-300 text-neutral-900 focus:ring-2 focus:ring-neutral-900/10">
                                            Trained (safety/waste)
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-sm text-neutral-800"
                                            x-show="rk==='drivers_couriers'">
                                            <input type="checkbox"
                                                x-model="profile.staffing_summary.drivers_couriers.trained_biosafety"
                                                @change="autoSave()"
                                                class="h-4 w-4 rounded border-neutral-300 text-neutral-900 focus:ring-2 focus:ring-neutral-900/10">
                                            Trained (biosafety)
                                        </label>
                                    </div>
                                </div>
                            </template>

                            <!-- Other Roles -->
                            <div class="p-4 bg-neutral-50 rounded-xl border border-neutral-200">
                                <h3 class="text-sm font-medium text-neutral-900 mb-3">Other Roles</h3>
                                <div class="space-y-3">
                                    <template x-for="(role, idx) in profile.staffing_summary.other_roles"
                                        :key="'role-' + idx">
                                        <div
                                            class="flex gap-2 items-start p-3 bg-white rounded-lg border border-neutral-200">
                                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                <div>
                                                    <label
                                                        class="block text-xs font-medium text-neutral-700 mb-1">Role</label>
                                                    <input type="text" x-model="role.role" @input="autoSave()"
                                                        class="block w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-xs font-medium text-neutral-700 mb-1">Count</label>
                                                    <input type="number" min="0" x-model.number="role.count"
                                                        @input="autoSave()"
                                                        class="block w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-xs font-medium text-neutral-700 mb-1">Adequate?</label>
                                                    <select x-model="role.adequate" @change="autoSave()"
                                                        class="block w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                                        <option value="yes">Yes</option>
                                                        <option value="no">No</option>
                                                        <option value="insufficient">Insufficient</option>
                                                    </select>
                                                </div>
                                                <div class="sm:col-span-3">
                                                    <label
                                                        class="block text-xs font-medium text-neutral-700 mb-1">Note</label>
                                                    <input type="text" x-model="role.note" @input="autoSave()"
                                                        class="block w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                                                </div>
                                            </div>
                                            <button type="button" @click="removeOtherRole(idx)"
                                                class="mt-6 p-2 rounded-lg text-neutral-400 hover:text-red-600 hover:bg-red-50 transition">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19 7l-.9 12.1A2 2 0 0116.1 21H7.9a2 2 0 01-2-1.9L5 7m5 4v6m4-6v6M15 7V4a1 1 0 00-1-1H10a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                    <button type="button" @click="addOtherRole()"
                                        class="w-full px-4 py-2.5 border-2 border-dashed border-neutral-300 rounded-lg text-sm font-medium text-neutral-600 hover:border-neutral-400 hover:bg-white transition flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Other Role
                                    </button>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1.5">General Staffing
                                    Notes</label>
                                <textarea x-model="profile.staffing_summary.notes" @input="autoSave()" rows="3"
                                    class="block w-full px-3 py-2.5 border border-neutral-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900 resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Server Error -->
                    <div x-show="serverError" x-transition
                        class="rounded-2xl border border-red-200 bg-red-50 p-4 flex items-start gap-3 shadow-sm">
                        <svg class="w-5 h-5 text-red-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.7 7.3a1 1 0 00-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 101.4 1.4L10 11.4l1.3 1.3a1 1 0 001.4-1.4L11.4 10l1.3-1.3a1 1 0 00-1.4-1.4L10 8.6 8.7 7.3z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm font-medium text-red-900" x-text="serverError"></p>
                    </div>

                </form>
            </section>

            <!-- STEP 2: ENTER -->
            <section x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="bg-white rounded-2xl border border-neutral-200 p-6 sm:p-8 shadow-sm text-center">
                    <div
                        class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-gradient-to-br from-green-500 to-green-600 mb-4 shadow">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-neutral-900 mb-2">Profile Saved</h2>
                    <p class="text-sm text-neutral-600 mb-6">You can now enter the audit workspace.</p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a :href="selected ? routeShow(selected.id) : '#'" :aria-disabled="!selected"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-neutral-900 px-6 py-3 text-sm font-semibold text-white hover:bg-black active:scale-[0.98] transition shadow">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Enter Audit Workspace
                        </a>
                        <button type="button" @click="backToBrowse()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-6 py-3 text-sm font-medium text-neutral-700 hover:bg-neutral-50 active:scale-[0.98] transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Choose Another Audit
                        </button>
                    </div>
                </div>
            </section>
        </main>

        <!-- Sticky Action Bars -->
        <div x-show="step === 1"
            class="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur border-t border-neutral-200 p-4 shadow-lg sm:hidden z-30">
            <div class="flex items-center gap-3">
                <button type="button" @click="backToBrowse()"
                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm font-medium text-neutral-700 active:scale-[0.98] transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back
                </button>
                <button type="button" @click="saveProfile()" :disabled="loading"
                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-neutral-900 px-4 py-3 text-sm font-semibold text-white active:scale-[0.98] transition disabled:opacity-50 disabled:cursor-not-allowed shadow">
                    <svg x-show="!loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Saving…' : 'Save & Continue'"></span>
                </button>
            </div>
        </div>

        <div x-show="step === 1" class="hidden sm:block fixed bottom-6 right-6 z-30">
            <div class="flex items-center gap-3 bg-white rounded-2xl border border-neutral-200 p-3 shadow-xl">
                <button type="button" @click="backToBrowse()"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50 active:scale-[0.98] transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Browse
                </button>
                <button type="button" @click="saveProfile()" :disabled="loading"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black active:scale-[0.98] transition disabled:opacity-50 disabled:cursor-not-allowed shadow">
                    <svg x-show="!loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Saving…' : 'Save & Continue'"></span>
                </button>
            </div>
        </div>

    </div>

    <script>
        /**
         * auditWizardPremium — hardened
         * - Authoritative fields (from DB) are HARD read-only in the UI and re-imposed on submit.
         * - Any user attempt to alter a locked field is auto-reverted with a clear banner.
         * - Smart, non-blocking validation remains (guides, never stonewalls).
         * - Fixes Alpine "_x_dataStack" crash by guarding init + usage patterns (see notes below).
         */
        (function() {
            // guard against double definition
            if (window.auditWizardPremium) return;

            window.auditWizardPremium = function auditWizardPremium(el) {
                return {
                    // listings
                    all: [],
                    filtered: [],
                    paged: [],
                    countries: [],
                    labs: [],
                    search: '',
                    country: '',
                    lab: '',
                    page: 1,
                    perPage: 10,

                    // wizard
                    step: 0,
                    selected: null,
                    ready: false,
                    loading: false,

                    // ui state
                    banner: {
                        error: '',
                        warn: '',
                        info: ''
                    },
                    routeShowTpl: '',
                    routeGate: '',
                    serverError: '',
                    autosaveStatus: '',
                    auditorNames: [],

                    // validation & guidance
                    touched: new Set(),
                    unverifiedRequired: [],
                    clientIssues: [],
                    fieldLabels: {
                        'dates.this_audit': 'This Audit Date',
                        'dates.prior_official_status': 'Prior Official Status',
                        'laboratory.name': 'Laboratory Name',
                        'laboratory.country_id': 'Country ID',
                        'laboratory.level_affiliation.level': 'Level',
                        'laboratory.level_affiliation.affiliation': 'Affiliation'
                    },

                    // authoritative (server-of-truth) values and locks
                    authoritative: {
                        laboratory: {}
                    },
                    lockPaths: new Set([
                        // add/remove here as needed; these are enforced as read-only
                        'laboratory.name',
                        'laboratory.lab_number',
                        'laboratory.country_id',
                        'laboratory.country_name'
                    ]),

                    // reference to inputs we will make readonly visually (no markup edits needed)
                    lockSelectorMap: {
                        'laboratory.name': 'input[x-model="profile.laboratory.name"]',
                        'laboratory.lab_number': 'input[x-model="profile.laboratory.lab_number"]',
                        'laboratory.country_id': 'input[x-model="profile.laboratory.country_id"]',
                        'laboratory.country_name': 'input[x-model="profile.laboratory.country_name"]'
                    },

                    // controlled lists
                    levelOptions: ['national', 'regional', 'district', 'facility', 'private_ref'],
                    affiliationOptions: ['public', 'private', 'faith_based', 'ngo', 'academic', 'military',
                        'other'],
                    staffingKeys: ['degree_professionals', 'diploma_professionals', 'certificate_professionals',
                        'data_clerks', 'phlebotomists', 'cleaners', 'drivers_couriers'
                    ],
                    staffingLabels: {
                        degree_professionals: 'Degree Professionals',
                        diploma_professionals: 'Diploma Professionals',
                        certificate_professionals: 'Certificate Professionals',
                        data_clerks: 'Data Clerks',
                        phlebotomists: 'Phlebotomists',
                        cleaners: 'Cleaners',
                        drivers_couriers: 'Drivers / Couriers'
                    },

                    profile: {
                        profile_version: 'v1',
                        dates: {
                            this_audit: '',
                            last_audit: '',
                            prior_official_status: 'not_audited'
                        },
                        auditors: [],
                        laboratory: {
                            name: '',
                            lab_number: '',
                            address: '',
                            city: '',
                            country_id: null,
                            country_name: '',
                            phone: '',
                            fax: '',
                            email: '',
                            gps: {
                                lat: null,
                                lng: null
                            },
                            representative: {
                                name: '',
                                phone_work: '',
                                phone_personal: ''
                            },
                            level_affiliation: {
                                level: [],
                                affiliation: [],
                                other_note: ''
                            }
                        },
                        staffing_summary: {
                            degree_professionals: {
                                count: 0,
                                adequate: 'insufficient'
                            },
                            diploma_professionals: {
                                count: 0,
                                adequate: 'insufficient'
                            },
                            certificate_professionals: {
                                count: 0,
                                adequate: 'insufficient'
                            },
                            data_clerks: {
                                count: 0,
                                adequate: 'insufficient'
                            },
                            phlebotomists: {
                                count: 0,
                                adequate: 'insufficient'
                            },
                            cleaners: {
                                count: 0,
                                adequate: 'insufficient',
                                dedicated: null,
                                trained_safety_waste: null
                            },
                            drivers_couriers: {
                                count: 0,
                                adequate: 'insufficient',
                                dedicated: null,
                                trained_biosafety: null
                            },
                            other_roles: [],
                            notes: ''
                        }
                    },

                    // assistant copy
                    get assistantTitle() {
                        if (!this.selected) return 'Select an audit to begin.';
                        if (this.ready) return 'Profile saved and ready.';
                        return 'Verify and complete the audit profile.';
                    },
                    get assistantBody() {
                        if (!this.selected) return 'Use the Browse list to pick a single in-progress audit.';
                        if (this.unverifiedRequired.length)
                        return 'Some required fields are prefilled or unverified. Confirm them before saving.';
                        return this.ready ?
                            'Everything necessary is present. You can still edit and re-save before entering.' :
                            'Fill the important fields, then Save & Continue.';
                    },

                    // lifecycle
                    init() {
                        // DEFENSIVE: el must exist
                        if (!el) {
                            console.warn('auditWizardPremium: root element missing');
                            return;
                        }

                        // parse inputs
                        try {
                            this.all = JSON.parse(el.getAttribute('data-audits') || '[]');
                        } catch {
                            this.all = [];
                        }
                        this.routeShowTpl = el.getAttribute('data-route-show') || '';
                        this.routeGate = el.getAttribute('data-route-gate') || '';

                        // build filters
                        this.rebuildOptions();
                        this.recompute();

                        this.$watch('search', () => {
                            this.page = 1;
                            this.recompute();
                        });
                        this.$watch('country', () => {
                            this.page = 1;
                            this.recompute();
                        });
                        this.$watch('lab', () => {
                            this.page = 1;
                            this.recompute();
                        });
                        this.$watch('page', () => {
                            this.recomputePage();
                        });

                        // cmd/ctrl+s quick save
                        window.addEventListener('keydown', (e) => {
                            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's' && this.step ===
                                1) {
                                e.preventDefault();
                                this.saveProfile();
                            }
                        });

                        this.banner.info = 'Select an audit to proceed.';
                    },

                    // filtering
                    rebuildOptions() {
                        const cs = {},
                            ls = {};
                        for (const a of this.all) {
                            if (a?.country_name) cs[a.country_name] = true;
                            if (a?.lab_name) ls[a.lab_name] = true;
                        }
                        this.countries = Object.keys(cs).sort();
                        this.labs = Object.keys(ls).sort();
                    },
                    recompute() {
                        const q = (this.search || '').toLowerCase().trim();
                        const hit = v => (v == null ? '' : String(v)).toLowerCase().includes(q);
                        const out = [];
                        for (const a of this.all) {
                            const byStatus = String(a.status || '').toLowerCase() === 'in_progress';
                            const byQ = !q || hit(a.id) || hit(a.lab_name) || hit(a.lab_number) || hit(a
                                .country_name);
                            const byC = !this.country || a.country_name === this.country;
                            const byL = !this.lab || a.lab_name === this.lab;
                            if (byStatus && byQ && byC && byL) out.push(a);
                        }
                        this.filtered = out;
                        const maxPage = Math.max(1, Math.ceil(this.filtered.length / this.perPage));
                        if (this.page > maxPage) this.page = maxPage;
                        this.recomputePage();
                    },
                    recomputePage() {
                        const s = (this.page - 1) * this.perPage,
                            e = this.page * this.perPage;
                        this.paged = this.filtered.slice(s, e);
                    },
                    next() {
                        if (this.page * this.perPage < this.filtered.length) this.page++;
                    },
                    prev() {
                        if (this.page > 1) this.page--;
                    },

                    // selection
                    async selectAudit(a) {
                        this.resetStateKeepList();
                        this.selected = a;
                        this.step = 1;
                        this.banner.info = 'Preparing profile…';
                        await this.prefillFromGate();
                        this.banner.info = '';
                        this.banner.warn = this.auditorNames.length ? '' :
                            'No auditors are assigned. Assignment is required before entering.';
                        // after DOM renders, enforce read-only visuals
                        this.$nextTick(() => this.applyReadOnlyLocks());
                    },

                    backToBrowse(reset = true) {
                        if (reset) this.resetStateKeepList();
                        this.step = 0;
                        this.banner.info = 'Select an audit to proceed.';
                    },

                    // fetch prefill and authoritative
                    async prefillFromGate() {
                        if (!this.selected) return;
                        try {
                            this.loading = true;
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content') || '';
                            const body = new URLSearchParams({
                                audit_id: String(this.selected.id)
                            });
                            const resp = await fetch(this.routeGate, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': token
                                },
                                body: body.toString()
                            });
                            const data = await resp.json().catch(() => ({}));
                            if (!resp.ok || (!data.ok && !data.snapshot)) {
                                this.banner.error = data?.error || 'Failed to load audit profile';
                                return;
                            }

                            const snap = data.snapshot || {};
                            const assigned = data.assigned_auditors || snap.auditors || [];
                            this.auditorNames = assigned
                                .map(a => a?.name ? [a.name, a.affiliation].filter(Boolean).join(' — ') : (a ||
                                    ''))
                                .filter(Boolean);

                            // ingest snapshot into profile
                            this.ingestSnapshot(snap);

                            // authoritative source of truth:
                            // 1) prefer data.authoritative.laboratory if given
                            // 2) else fall back to snapshot.laboratory
                            this.authoritative.laboratory = (data.authoritative && data.authoritative
                                    .laboratory) ?
                                JSON.parse(JSON.stringify(data.authoritative.laboratory)) :
                                JSON.parse(JSON.stringify(snap.laboratory || {}));

                            // immediately align profile with authoritative for locked paths
                            this.reimposeAuthoritative();

                            // populate auditors if profile empty
                            if (!Array.isArray(this.profile.auditors) || !this.profile.auditors.length) {
                                this.profile.auditors = assigned.map(a => ({
                                    name: a?.name || '',
                                    affiliation: a?.affiliation || ''
                                }));
                            }

                            // guidance (non-blocking)
                            this.computeGuidance();
                            this.ready = (data.code === 'ready');
                        } catch {
                            this.banner.error = 'Network error while loading profile';
                        } finally {
                            this.loading = false;
                        }
                    },

                    // ingest helpers
                    ingestSnapshot(s) {
                        const put = (obj, path, val) => {
                            const ks = path.split('.');
                            let t = obj;
                            for (let i = 0; i < ks.length - 1; i++) {
                                const k = ks[i];
                                t[k] = t[k] ?? {};
                                t = t[k];
                            }
                            t[ks[ks.length - 1]] = val;
                        };
                        const get = (o, p) => p.split('.').reduce((a, k) => a?.[k], o);

                        // dates
                        ['dates.this_audit', 'dates.last_audit', 'dates.prior_official_status'].forEach(k => {
                            const v = get(s, k);
                            if (v !== undefined && v !== null) put(this.profile, k, v);
                        });

                        // auditors (snapshot extras)
                        if (Array.isArray(s.auditors)) this.profile.auditors = s.auditors.filter(x => x && (x
                            .name || x.affiliation));

                        // lab
                        const lab = s.laboratory || {};
                        ['name', 'lab_number', 'address', 'city', 'phone', 'fax', 'email', 'country_name'].forEach(
                            k => {
                                if (lab[k] !== undefined && lab[k] !== null) this.profile.laboratory[k] = lab[
                                k];
                            });
                        if (lab.country_id !== undefined && lab.country_id !== null) this.profile.laboratory
                            .country_id = Number(lab.country_id);
                        if (lab.gps) {
                            if (lab.gps.lat !== undefined) this.profile.laboratory.gps.lat = Number(lab.gps.lat);
                            if (lab.gps.lng !== undefined) this.profile.laboratory.gps.lng = Number(lab.gps.lng);
                        }
                        if (lab.representative) {
                            const r = lab.representative;
                            if (r.name !== undefined) this.profile.laboratory.representative.name = r.name || '';
                            if (r.phone_work !== undefined) this.profile.laboratory.representative.phone_work = r
                                .phone_work || '';
                            if (r.phone_personal !== undefined) this.profile.laboratory.representative
                                .phone_personal = r.phone_personal || '';
                        }
                        if (lab.level_affiliation) {
                            const la = lab.level_affiliation;
                            if (Array.isArray(la.level)) this.profile.laboratory.level_affiliation.level = [...
                                new Set(la.level)
                            ];
                            if (Array.isArray(la.affiliation)) this.profile.laboratory.level_affiliation
                                .affiliation = [...new Set(la.affiliation)];
                            if (la.other_note !== undefined) this.profile.laboratory.level_affiliation.other_note =
                                la.other_note || '';
                        }

                        // staffing
                        const ss = s.staffing_summary || {};
                        for (const rk of this.staffingKeys) {
                            const node = ss[rk];
                            if (!node) continue;
                            if (node.count !== undefined) this.profile.staffing_summary[rk].count = Math.max(0,
                                parseInt(node.count || 0, 10));
                            if (node.adequate && ['yes', 'no', 'insufficient'].includes(node.adequate)) this.profile
                                .staffing_summary[rk].adequate = node.adequate;
                            ['dedicated', 'trained_safety_waste', 'trained_biosafety'].forEach(f => {
                                if (node[f] !== undefined) this.profile.staffing_summary[rk][f] = (node[
                                    f] === true || node[f] === '1');
                            });
                        }
                        if (Array.isArray(ss.other_roles)) {
                            this.profile.staffing_summary.other_roles = ss.other_roles.map(or => ({
                                role: or.role || '',
                                count: Math.max(0, parseInt(or.count || 0, 10)),
                                adequate: ['yes', 'no', 'insufficient'].includes(or.adequate) ? or
                                    .adequate : 'insufficient',
                                note: or.note || ''
                            }));
                        }
                        if (ss.notes !== undefined) this.profile.staffing_summary.notes = ss.notes || '';
                    },

                    // AUTHORITATIVE ENFORCEMENT -------------------------------------------
                    isLocked(path) {
                        return this.lockPaths.has(path);
                    },

                    reimposeAuthoritative() {
                        // for each locked path, copy authoritative => profile if different
                        const get = (o, p) => p.split('.').reduce((a, k) => a?.[k], o);
                        const put = (obj, path, val) => {
                            const ks = path.split('.');
                            let t = obj;
                            for (let i = 0; i < ks.length - 1; i++) {
                                const k = ks[i];
                                t[k] = t[k] ?? {};
                                t = t[k];
                            }
                            t[ks[ks.length - 1]] = val;
                        };

                        let reverted = [];
                        for (const path of this.lockPaths) {
                            const authVal = get(this.authoritative, path);
                            if (authVal === undefined) continue; // nothing to enforce
                            const curVal = get(this.profile, path);
                            if (String(curVal) !== String(authVal)) {
                                put(this.profile, path, authVal);
                                reverted.push(this.fieldLabels[path] || path);
                            }
                        }
                        if (reverted.length) {
                            this.banner.info = `Authoritative fields enforced: ${reverted.join(', ')}.`;
                        }
                    },

                    applyReadOnlyLocks() {
                        // make inputs readonly/disabled and style them, no Blade changes needed
                        const form = this.$refs.profileForm;
                        if (!form) return;
                        for (const path of this.lockPaths) {
                            const sel = this.lockSelectorMap[path];
                            if (!sel) continue;
                            const el = form.querySelector(sel);
                            if (!el) continue;
                            // disable typing and focus style
                            el.readOnly = true;
                            el.classList.add('bg-neutral-100', 'text-neutral-700', 'cursor-not-allowed');
                            el.setAttribute('aria-readonly', 'true');
                            // safety: if someone pastes programmatically, ensure value matches authoritative
                            el.addEventListener('input', () => {
                                this.reimposeAuthoritative();
                            }, {
                                passive: true
                            });
                            // click nudge
                            el.addEventListener('focus', () => {
                                this.banner.warn =
                                    'This field is locked to authoritative data. To change, update it in the master tables.';
                                setTimeout(() => {
                                    if (this.banner.warn?.startsWith('This field is locked')) this
                                        .banner.warn = '';
                                }, 2500);
                            });
                        }
                    },

                    // guidance (non-blocking)
                    trackTouch(path) {
                        this.touched.add(path);
                        this.computeGuidance();
                        this.autoSave();
                    },
                    computeGuidance() {
                        const required = [
                            'dates.this_audit',
                            'dates.prior_official_status',
                            'laboratory.name',
                            'laboratory.country_id',
                            'laboratory.level_affiliation.level',
                            'laboratory.level_affiliation.affiliation'
                        ];
                        const get = (o, p) => p.split('.').reduce((a, k) => a?.[k], o);
                        const isEmpty = (v) => v === undefined || v === null || (typeof v === 'string' && v
                        .trim() === '') || (Array.isArray(v) && v.length === 0);

                        this.unverifiedRequired = required.filter(k => !this.touched.has(k));

                        const issues = [];
                        for (const key of required) {
                            const value = get(this.profile, key);
                            if (isEmpty(value)) {
                                issues.push({
                                    path: key,
                                    label: this.fieldLabels[key] || key,
                                    severity: this.touched.has(key) ? 'warn' : 'info',
                                    hint: this.buildHintFor(key)
                                });
                            }
                        }
                        this.clientIssues = issues;
                        if (issues.length) {
                            const names = issues.map(i => i.label).join(', ');
                            this.banner.warn =
                                `Missing or unverified: ${names}. Saved anyway; review for completeness.`;
                        } else if (this.banner.warn?.startsWith('Missing or unverified:')) {
                            this.banner.warn = '';
                        }
                    },
                    buildHintFor(key) {
                        switch (key) {
                            case 'dates.this_audit':
                                return 'Enter the current audit date (YYYY-MM-DD).';
                            case 'dates.prior_official_status':
                                return 'Pick the previous official star status.';
                            case 'laboratory.name':
                                return 'This is locked to the master record.';
                            case 'laboratory.country_id':
                                return 'Numeric country ID from your master data.';
                            case 'laboratory.level_affiliation.level':
                                return 'Pick at least one level.';
                            case 'laboratory.level_affiliation.affiliation':
                                return 'Pick at least one affiliation.';
                            default:
                                return '';
                        }
                    },

                    focusFirstIssue() {
                        const map = {
                            'dates.this_audit': 'input[type="date"][x-model="profile.dates.this_audit"]',
                            'dates.prior_official_status': 'select[x-model="profile.dates.prior_official_status"]',
                            'laboratory.name': 'input[x-model="profile.laboratory.name"]',
                            'laboratory.country_id': 'input[x-model="profile.laboratory.country_id"]',
                            'laboratory.level_affiliation.level': 'input[x-model*="profile.laboratory.level_affiliation.level"]',
                            'laboratory.level_affiliation.affiliation': 'input[x-model*="profile.laboratory.level_affiliation.affiliation"]'
                        };
                        const first = this.clientIssues[0];
                        if (!first) return;
                        const sel = map[first.path];
                        const el = this.$refs.profileForm?.querySelector(sel);
                        if (el) el.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    },

                    // autosave
                    autosaveTimer: null,
                    autoSave() {
                        clearTimeout(this.autosaveTimer);
                        this.autosaveTimer = setTimeout(() => this.saveDraft(), 800);
                    },
                    async saveDraft() {
                        if (!this.selected) return;
                        // ALWAYS reimpose authoritative before sending draft
                        this.reimposeAuthoritative();
                        this.autosaveStatus = 'Saving draft…';
                        try {
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content') || '';
                            const payload = this.serializeProfile();
                            const resp = await fetch(this.routeGate, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': token
                                },
                                body: payload.toString()
                            });
                            if (resp.ok) {
                                this.autosaveStatus = 'Draft saved.';
                                setTimeout(() => this.autosaveStatus = '', 1500);
                            } else this.autosaveStatus = '';
                        } catch {
                            this.autosaveStatus = '';
                        }
                    },

                    labelCase(v) {
                        // tolerate null/undefined, convert snake_case to Title Case
                        const s = (v ?? '').toString().replace(/_/g, ' ').trim();
                        if (!s) return '';
                        return s.split(/\s+/).map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                    },

                    // save (non-blocking) + authoritative re-imposition
                    async saveProfile() {
                        if (!this.selected) return;

                        // guidance + focus
                        this.computeGuidance();
                        if (this.clientIssues.length) this.$nextTick(() => this.focusFirstIssue());

                        // RE-IMPOSE authoritative values on locked fields before submit (final guard)
                        this.reimposeAuthoritative();

                        this.serverError = '';
                        this.banner.error = '';
                        this.loading = true;
                        try {
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content') || '';
                            const payload = this.serializeProfile();
                            const resp = await fetch(this.routeGate, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': token
                                },
                                body: payload.toString()
                            });
                            const data = await resp.json().catch(() => ({}));

                            if (resp.ok && (data.ok || data.code === 'ready')) {
                                // refresh assigned auditors if provided
                                if (data.snapshot?.auditors) {
                                    this.auditorNames = data.snapshot.auditors.map(a => [a.name, a.affiliation]
                                        .filter(Boolean).join(' — '));
                                }
                                this.ready = (data.code === 'ready');
                                if (this.ready) {
                                    this.step = 2;
                                    this.banner.warn = '';
                                    this.clientIssues = [];
                                    this.unverifiedRequired = [];
                                } else {
                                    this.banner.info = 'Saved. You may continue refining before entering.';
                                }
                            } else {
                                this.serverError = data?.error || 'Failed to save profile';
                                if (Array.isArray(data?.missing) && data.missing.length) {
                                    this.banner.warn =
                                        `Server indicates missing: ${data.missing.join(', ')}. Review and save again.`;
                                }
                                this.$nextTick(() => this.focusFirstIssue());
                            }
                        } catch {
                            this.serverError = 'Network error during save';
                        } finally {
                            this.loading = false;
                        }
                    },

                    // serialization (unchanged contract)
                    serializeProfile() {
                        const params = new URLSearchParams();
                        params.append('audit_id', String(this.selected.id));
                        const flatten = (obj, prefix = 'profile') => {
                            for (const key in obj) {
                                const value = obj[key];
                                const fullKey = `${prefix}[${key}]`;
                                if (value === null || value === undefined) {
                                    params.append(fullKey, '');
                                } else if (Array.isArray(value)) {
                                    if (value.length === 0) {
                                        params.append(fullKey, '');
                                        continue;
                                    }
                                    if (typeof value[0] === 'object') {
                                        value.forEach((item, i) => flatten(item, `${fullKey}[${i}]`));
                                    } else {
                                        value.forEach(v => params.append(`${fullKey}[]`, v));
                                    }
                                } else if (typeof value === 'object') {
                                    flatten(value, fullKey);
                                } else {
                                    params.append(fullKey, String(value));
                                }
                            }
                        };
                        flatten(this.profile, 'profile');
                        return params;
                    },

                    // rows
                    addAuditor() {
                        this.profile.auditors.push({
                            name: '',
                            affiliation: ''
                        });
                        this.autoSave();
                    },
                    removeAuditor(i) {
                        this.profile.auditors.splice(i, 1);
                        this.autoSave();
                    },
                    addOtherRole() {
                        this.profile.staffing_summary.other_roles.push({
                            role: '',
                            count: 0,
                            adequate: 'insufficient',
                            note: ''
                        });
                        this.autoSave();
                    },
                    removeOtherRole(i) {
                        this.profile.staffing_summary.other_roles.splice(i, 1);
                        this.autoSave();
                    },

                    // helpers
                    routeShow(id) {
                        return (this.routeShowTpl || '').replace('__ID__', encodeURIComponent(id));
                    },
                    hardRefresh() {
                        window.location.reload();
                    },
                    resetStateKeepList() {
                        this.selected = null;
                        this.ready = false;
                        this.banner = {
                            error: '',
                            warn: '',
                            info: ''
                        };
                        this.serverError = '';
                        this.autosaveStatus = '';
                        this.auditorNames = [];
                        this.touched = new Set();
                        this.unverifiedRequired = [];
                        this.clientIssues = [];
                        this.authoritative = {
                            laboratory: {}
                        };
                        this.profile = {
                            profile_version: 'v1',
                            dates: {
                                this_audit: '',
                                last_audit: '',
                                prior_official_status: 'not_audited'
                            },
                            auditors: [],
                            laboratory: {
                                name: '',
                                lab_number: '',
                                address: '',
                                city: '',
                                country_id: null,
                                country_name: '',
                                phone: '',
                                fax: '',
                                email: '',
                                gps: {
                                    lat: null,
                                    lng: null
                                },
                                representative: {
                                    name: '',
                                    phone_work: '',
                                    phone_personal: ''
                                },
                                level_affiliation: {
                                    level: [],
                                    affiliation: [],
                                    other_note: ''
                                }
                            },
                            staffing_summary: {
                                degree_professionals: {
                                    count: 0,
                                    adequate: 'insufficient'
                                },
                                diploma_professionals: {
                                    count: 0,
                                    adequate: 'insufficient'
                                },
                                certificate_professionals: {
                                    count: 0,
                                    adequate: 'insufficient'
                                },
                                data_clerks: {
                                    count: 0,
                                    adequate: 'insufficient'
                                },
                                phlebotomists: {
                                    count: 0,
                                    adequate: 'insufficient'
                                },
                                cleaners: {
                                    count: 0,
                                    adequate: 'insufficient',
                                    dedicated: null,
                                    trained_safety_waste: null
                                },
                                drivers_couriers: {
                                    count: 0,
                                    adequate: 'insufficient',
                                    dedicated: null,
                                    trained_biosafety: null
                                },
                                other_roles: [],
                                notes: ''
                            }
                        };
                    }
                };
            };
        })();
    </script>
@endsection
