@extends('layouts.app')

@section('title', 'Countries Management')
@section('breadcrumb', 'Countries')

@section('content')
<div class="min-h-screen" x-data="countriesApp()" x-init="init()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">Countries Management</h1>
            <p class="text-sm text-neutral-500 mt-1 leading-tight">Manage SLIPTA system countries and regions</p>
        </div>
        @if($context['is_admin'])
        <button @click="openModal()" class="bg-neutral-900 text-white px-4 py-2 rounded-xl border shadow-sm hover:bg-black transition duration-150">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Country
        </button>
        @endif
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="bg-white border border-neutral-200 shadow-sm rounded-2xl p-4">
            <div class="text-xs text-neutral-500 leading-tight">Total Countries</div>
            <div class="text-2xl font-semibold text-neutral-900" x-text="pagination.total || 0"></div>
        </div>
        <div class="bg-white border border-neutral-200 shadow-sm rounded-2xl p-4">
            <div class="text-xs text-neutral-500 leading-tight">Active Countries</div>
            <div class="text-2xl font-semibold text-neutral-900" x-text="stats.active || 0"></div>
        </div>
        <div class="bg-white border border-neutral-200 shadow-sm rounded-2xl p-4">
            <div class="text-xs text-neutral-500 leading-tight">Regions</div>
            <div class="text-2xl font-semibold text-neutral-900" x-text="stats.regions || 0"></div>
        </div>
        <div class="bg-white border border-neutral-200 shadow-sm rounded-2xl p-4">
            <div class="text-xs text-neutral-500 leading-tight">Laboratories</div>
            <div class="text-2xl font-semibold text-neutral-900" x-text="stats.laboratories || 0"></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-neutral-200 shadow-sm rounded-2xl p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input type="text" x-model="filters.search" @input.debounce.300ms="loadCountries()"
                    placeholder="Search countries..."
                    class="w-full bg-white border border-neutral-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-neutral-900/10 focus:outline-none">
            </div>
            <div>
                <select x-model="filters.region" @change="loadCountries()"
                    class="w-full bg-white border border-neutral-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-neutral-900/10 focus:outline-none">
                    <option value="">All Regions</option>
                    <template x-for="region in availableRegions" :key="region">
                        <option :value="region" x-text="region"></option>
                    </template>
                </select>
            </div>
            <div>
                <select x-model="filters.status" @change="loadCountries()"
                    class="w-full bg-white border border-neutral-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-neutral-900/10 focus:outline-none">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex items-center justify-between">
                <button @click="resetFilters()" class="hover:bg-neutral-100 rounded-xl px-3 py-2 text-sm text-neutral-700 transition duration-150">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset
                </button>
                <span class="text-sm text-neutral-500" x-text="`${pagination.total} countries`"></span>
            </div>
        </div>
    </div>

    <!-- Countries Table -->
    <div class="bg-white border border-neutral-200 shadow-sm rounded-2xl overflow-hidden">
        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-neutral-900 border-t-transparent mx-auto"></div>
            <p class="text-sm text-neutral-500 mt-2 leading-tight">Loading countries...</p>
        </div>

        <!-- Table -->
        <div x-show="!loading && countries.length > 0" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 border-b border-neutral-200">
                    <tr>
                        <th class="text-left text-xs font-medium text-neutral-600 uppercase tracking-wider py-2.5 px-3">Country</th>
                        <th class="text-left text-xs font-medium text-neutral-600 uppercase tracking-wider py-2.5 px-3">Code</th>
                        <th class="text-left text-xs font-medium text-neutral-600 uppercase tracking-wider py-2.5 px-3">Region</th>
                        <th class="text-left text-xs font-medium text-neutral-600 uppercase tracking-wider py-2.5 px-3">Status</th>
                        <th class="text-left text-xs font-medium text-neutral-600 uppercase tracking-wider py-2.5 px-3">Labs</th>
                        <th class="text-left text-xs font-medium text-neutral-600 uppercase tracking-wider py-2.5 px-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    <template x-for="country in countries" :key="country.id">
                        <tr class="hover:bg-neutral-50 transition duration-150">
                            <td class="py-2.5 px-3">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-neutral-100 rounded-xl flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-neutral-900" x-text="country.name"></div>
                                        <div class="text-xs text-neutral-500 leading-tight" x-text="formatDate(country.created_at)"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="inline-block bg-neutral-100 text-neutral-800 px-2 py-1 rounded text-xs font-medium" x-text="country.code"></span>
                            </td>
                            <td class="py-2.5 px-3 text-neutral-700" x-text="country.region || '—'"></td>
                            <td class="py-2.5 px-3">
                                <span class="inline-block px-2 py-1 rounded text-xs font-medium"
                                    :class="country.is_active ? 'bg-neutral-900 text-white' : 'bg-neutral-200 text-neutral-700'"
                                    x-text="country.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="py-2.5 px-3">
                                <button @click="showDetails(country.id)" class="text-neutral-700 hover:text-neutral-900 transition duration-150">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span x-text="country.laboratory_count || 0"></span>
                                </button>
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="flex items-center gap-1">
                                    <button @click="showDetails(country.id)"
                                        class="p-1 hover:bg-neutral-100 rounded transition duration-150"
                                        title="View Details">
                                        <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <template x-if="canEdit">
                                        <button @click="editCountry(country)"
                                            class="p-1 hover:bg-neutral-100 rounded transition duration-150"
                                            title="Edit Country">
                                            <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </template>
                                    <template x-if="canDelete">
                                        <button @click="deleteCountry(country)"
                                            class="p-1 hover:bg-neutral-100 rounded transition duration-150"
                                            title="Delete Country">
                                            <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && countries.length === 0" class="p-8 text-center">
            <svg class="w-12 h-12 text-neutral-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-sm font-medium text-neutral-900 mb-1">No countries found</h3>
            <p class="text-sm text-neutral-500 leading-tight">Try adjusting your search criteria or add a new country.</p>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.total > 0" class="bg-neutral-50 border-t border-neutral-200 py-2.5 px-3">
            <div class="flex items-center justify-between">
                <div class="text-sm text-neutral-500">
                    <span x-text="`Showing ${pagination.from} to ${pagination.to} of ${pagination.total} countries`"></span>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page <= 1"
                        class="px-3 py-1 text-sm bg-white border border-neutral-200 rounded-xl hover:bg-neutral-50 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150">
                        Previous
                    </button>
                    <template x-for="page in getPageNumbers()" :key="page">
                        <button @click="changePage(page)"
                            :class="page === pagination.current_page ? 'bg-neutral-900 text-white' : 'bg-white text-neutral-700 hover:bg-neutral-50'"
                            class="px-3 py-1 text-sm border border-neutral-200 rounded-xl transition duration-150"
                            x-text="page"></button>
                    </template>
                    <button @click="changePage(pagination.current_page + 1)"
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="px-3 py-1 text-sm bg-white border border-neutral-200 rounded-xl hover:bg-neutral-50 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Country Modal -->
    <div x-show="showModal" @click.away="closeModal()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-neutral-900/50 z-50 flex items-center justify-center p-4">

        <div @click.stop
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="bg-white border border-neutral-200 shadow-sm rounded-2xl max-w-md w-full">

            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-neutral-900" x-text="modalTitle"></h3>
                    <button @click="closeModal()" class="p-1 hover:bg-neutral-100 rounded transition duration-150">
                        <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveCountry()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Country Name</label>
                            <input type="text" x-model="form.name" required maxlength="120"
                                class="w-full bg-white border border-neutral-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-neutral-900/10 focus:outline-none"
                                :class="errors.name ? 'border-red-300' : 'border-neutral-200'">
                            <p x-show="errors.name && errors.name[0]" class="text-red-600 text-xs mt-1 leading-tight" x-text="errors.name ? errors.name[0] : ''"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">ISO Country Code</label>
                            <input type="text" x-model="form.code" required maxlength="3" pattern="[A-Z]{3}"
                                class="w-full bg-white border border-neutral-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-neutral-900/10 focus:outline-none"
                                :class="errors.code ? 'border-red-300' : 'border-neutral-200'"
                                placeholder="e.g., USA, GBR, KEN" @input="form.code = form.code.toUpperCase()">
                            <p x-show="errors.code && errors.code[0]" class="text-red-600 text-xs mt-1 leading-tight" x-text="errors.code ? errors.code[0] : ''"></p>
                            <p class="text-neutral-500 text-xs mt-1 leading-tight">3-letter ISO 3166-1 alpha-3 code</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Region</label>
                            <input type="text" x-model="form.region" maxlength="100"
                                class="w-full bg-white border border-neutral-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-neutral-900/10 focus:outline-none"
                                placeholder="e.g., East Africa, West Europe">
                            <p x-show="errors.region && errors.region[0]" class="text-red-600 text-xs mt-1 leading-tight" x-text="errors.region ? errors.region[0] : ''"></p>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.is_active" class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900/10">
                                <span class="ml-2 text-sm text-neutral-700">Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-neutral-200">
                        <button type="button" @click="closeModal()"
                            class="px-3 py-2 text-sm text-neutral-700 border border-neutral-200 rounded-xl hover:bg-neutral-50 transition duration-150">
                            Cancel
                        </button>
                        <button type="submit" :disabled="saving"
                            class="bg-neutral-900 text-white px-3 py-2 text-sm rounded-xl hover:bg-black disabled:opacity-50 transition duration-150">
                            <span x-show="!saving" x-text="form.id ? 'Update' : 'Create'"></span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div x-show="showDetailsModal" @click.away="closeDetailsModal()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-neutral-900/50 z-50 flex items-center justify-center p-4">

        <div @click.stop
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="bg-white border border-neutral-200 shadow-sm rounded-2xl max-w-lg w-full">

            <div class="p-4" x-show="selectedCountry">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-neutral-900">Country Details</h3>
                    <button @click="closeDetailsModal()" class="p-1 hover:bg-neutral-100 rounded transition duration-150">
                        <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-neutral-500 leading-tight">Country Name</label>
                            <p class="text-sm text-neutral-900 mt-1" x-text="selectedCountry?.name"></p>
                        </div>
                        <div>
                            <label class="block text-xs text-neutral-500 leading-tight">ISO Code</label>
                            <p class="text-sm text-neutral-900 mt-1" x-text="selectedCountry?.code"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-neutral-500 leading-tight">Region</label>
                            <p class="text-sm text-neutral-900 mt-1" x-text="selectedCountry?.region || 'Not specified'"></p>
                        </div>
                        <div>
                            <label class="block text-xs text-neutral-500 leading-tight">Status</label>
                            <span class="inline-block px-2 py-1 rounded text-xs font-medium mt-1"
                                :class="selectedCountry?.is_active ? 'bg-neutral-900 text-white' : 'bg-neutral-200 text-neutral-700'"
                                x-text="selectedCountry?.is_active ? 'Active' : 'Inactive'"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-neutral-500 leading-tight">Laboratories</label>
                            <p class="text-sm text-neutral-900 mt-1" x-text="relatedData?.laboratory_count || 0"></p>
                        </div>
                        <div>
                            <label class="block text-xs text-neutral-500 leading-tight">Audits</label>
                            <p class="text-sm text-neutral-900 mt-1" x-text="relatedData?.audit_count || 0"></p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-neutral-500 leading-tight">Created</label>
                        <p class="text-sm text-neutral-900 mt-1" x-text="formatDate(selectedCountry?.created_at)"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function countriesApp() {
    return {
        countries: [],
        availableRegions: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
            from: 0,
            to: 0
        },
        stats: {
            active: 0,
            regions: 0,
            laboratories: 0
        },
        loading: true,
        saving: false,
        showModal: false,
        showDetailsModal: false,
        selectedCountry: null,
        relatedData: null,
        modalTitle: '',
        canEdit: {{ $context['is_admin'] ? 'true' : 'false' }},
        canDelete: {{ $context['is_admin'] ? 'true' : 'false' }},
        form: {
            id: null,
            name: '',
            code: '',
            region: '',
            is_active: true
        },
        filters: {
            search: '',
            region: '',
            status: '',
            page: 1
        },
        errors: {},
        notifications: [],

        init() {
            // Initialize with server data
            this.countries = @json($countries);
            this.availableRegions = @json($regions);
            this.pagination = @json($pagination);
            this.calculateStats();

            // Load URL parameters
            const url = new URL(window.location);
            this.filters.search = url.searchParams.get('search') || '';
            this.filters.region = url.searchParams.get('region') || '';
            this.filters.status = url.searchParams.get('status') || '';
            this.filters.page = parseInt(url.searchParams.get('page')) || 1;

            // Debug CSRF token availability
            this.debugCSRFToken();

            this.loading = false;
        },

        debugCSRFToken() {
            const token = this.getCSRFToken();
            if (!token) {
                console.error('❌ CSRF Token not found in meta tag');
                this.showNotification('Security token missing. Please refresh the page.', 'error');
            } else {
                console.log('✅ CSRF Token found:', token.substring(0, 10) + '...');
            }
        },

        getCSRFToken() {
            // Try multiple ways to get CSRF token
            let token = null;

            // Method 1: Meta tag
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                token = metaToken.getAttribute('content');
            }

            // Method 2: Laravel default meta tag
            if (!token) {
                const laravelToken = document.querySelector('meta[name="_token"]');
                if (laravelToken) {
                    token = laravelToken.getAttribute('content');
                }
            }

            // Method 3: Hidden input (fallback)
            if (!token) {
                const hiddenInput = document.querySelector('input[name="_token"]');
                if (hiddenInput) {
                    token = hiddenInput.value;
                }
            }

            return token;
        },

        async refreshCSRFToken() {
            try {
                console.log('🔄 Attempting to refresh CSRF token...');
                const response = await fetch('/csrf-token', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.token) {
                        // Update meta tag
                        let metaTag = document.querySelector('meta[name="csrf-token"]');
                        if (!metaTag) {
                            metaTag = document.createElement('meta');
                            metaTag.name = 'csrf-token';
                            document.head.appendChild(metaTag);
                        }
                        metaTag.content = data.token;
                        console.log('✅ CSRF token refreshed successfully');
                        return data.token;
                    }
                }
            } catch (error) {
                console.error('❌ Failed to refresh CSRF token:', error);
            }
            return null;
        },

        async makeRequest(url, options = {}) {
            let token = this.getCSRFToken();

            // If no token found, try to refresh it
            if (!token) {
                console.log('🔄 No CSRF token found, attempting refresh...');
                token = await this.refreshCSRFToken();
                if (!token) {
                    this.showNotification('Security token unavailable. Please refresh the page.', 'error');
                    return null;
                }
            }

            const defaultHeaders = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            };

            // Only set Content-Type for non-GET requests with body
            if (options.method && options.method !== 'GET' && options.body) {
                defaultHeaders['Content-Type'] = 'application/json';
            }

            const requestOptions = {
                ...options,
                headers: {
                    ...defaultHeaders,
                    ...options.headers
                }
            };

            console.log('🚀 Making request to:', url, {
                method: requestOptions.method || 'GET',
                hasToken: !!token,
                tokenPreview: token ? token.substring(0, 10) + '...' : 'none'
            });

            try {
                const response = await fetch(url, requestOptions);

                // Handle CSRF token mismatch (419)
                if (response.status === 419) {
                    console.log('🔄 CSRF token expired, refreshing...');
                    const newToken = await this.refreshCSRFToken();
                    if (newToken) {
                        // Retry with new token
                        requestOptions.headers['X-CSRF-TOKEN'] = newToken;
                        console.log('🔄 Retrying request with new token...');
                        return await fetch(url, requestOptions);
                    } else {
                        this.showNotification('Session expired. Please refresh the page.', 'error');
                        return null;
                    }
                }

                return response;
            } catch (error) {
                console.error('❌ Request failed:', error);
                this.showNotification('Network error. Please check your connection.', 'error');
                return null;
            }
        },

        calculateStats() {
            this.stats.active = this.countries.filter(c => c.is_active).length;
            this.stats.regions = new Set(this.countries.map(c => c.region).filter(Boolean)).size;
            this.stats.laboratories = this.countries.reduce((sum, c) => sum + (c.laboratory_count || 0), 0);
        },

        async loadCountries() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    search: this.filters.search,
                    region: this.filters.region,
                    status: this.filters.status,
                    page: this.filters.page,
                    per_page: 15
                });

                // Update URL without page reload
                const url = new URL(window.location);
                url.search = params.toString();
                window.history.replaceState({}, '', url);

                const response = await this.makeRequest(`/countries?${params}`, {
                    method: 'GET'
                });

                if (!response) return;

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('❌ Server error:', response.status, errorText);
                    this.showNotification(`Server error: ${response.status}`, 'error');
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    this.countries = data.data.countries;
                    this.pagination = data.data.pagination;
                    this.availableRegions = data.data.regions;
                    this.calculateStats();
                    console.log('✅ Countries loaded successfully');
                } else {
                    this.showNotification(data.message || 'Failed to load countries', 'error');
                }
            } catch (error) {
                console.error('❌ Load countries error:', error);
                this.showNotification('Failed to load countries', 'error');
            } finally {
                this.loading = false;
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page && page !== this.pagination.current_page) {
                this.filters.page = page;
                this.loadCountries();
            }
        },

        getPageNumbers() {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const delta = 2;
            const range = [];
            const rangeWithDots = [];

            for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
                range.push(i);
            }

            if (current - delta > 2) {
                rangeWithDots.push(1, '...');
            } else {
                rangeWithDots.push(1);
            }

            rangeWithDots.push(...range);

            if (current + delta < last - 1) {
                rangeWithDots.push('...', last);
            } else if (last > 1) {
                rangeWithDots.push(last);
            }

            return rangeWithDots.filter((item, index, arr) =>
                item !== '...' || index === 0 || arr[index - 1] !== '...'
            );
        },

        openModal() {
            this.resetForm();
            this.modalTitle = 'Add New Country';
            this.showModal = true;
        },

        editCountry(country) {
            this.form = { ...country };
            this.modalTitle = 'Edit Country';
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.resetForm();
        },

        resetForm() {
            this.form = {
                id: null,
                name: '',
                code: '',
                region: '',
                is_active: true
            };
            this.errors = {};
        },

        async saveCountry() {
            if (this.saving) return; // Prevent double submission

            this.saving = true;
            this.errors = {};

            try {
                // Always use POST to /countries - controller handles create/update logic
                const url = '/countries';

                console.log('💾 Saving country:', 'POST', url, this.form);

                const response = await this.makeRequest(url, {
                    method: 'POST',
                    body: JSON.stringify(this.form)
                });

                if (!response) {
                    this.saving = false;
                    return;
                }

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showNotification(data.message || 'Country saved successfully', 'success');
                    this.closeModal();
                    await this.loadCountries();
                    console.log('✅ Country saved successfully');
                } else if (response.status === 422) {
                    this.errors = data.errors || {};
                    this.showNotification('Please correct the validation errors', 'error');
                    console.log('⚠️ Validation errors:', this.errors);
                } else {
                    this.showNotification(data.message || 'Failed to save country', 'error');
                    console.error('❌ Save failed:', data);
                }
            } catch (error) {
                console.error('❌ Save country error:', error);
                this.showNotification('Failed to save country', 'error');
            } finally {
                this.saving = false;
            }
        },

        async showDetails(countryId) {
            try {
                console.log('👁️ Loading country details:', countryId);
                const response = await this.makeRequest(`/countries/${countryId}`, {
                    method: 'GET'
                });

                if (!response) return;

                const data = await response.json();
                if (response.ok && data.success) {
                    this.selectedCountry = data.data.country;
                    this.relatedData = data.data.relatedData;
                    this.showDetailsModal = true;
                    console.log('✅ Country details loaded');
                } else {
                    this.showNotification(data.message || 'Failed to load country details', 'error');
                }
            } catch (error) {
                console.error('❌ Details error:', error);
                this.showNotification('Failed to load country details', 'error');
            }
        },

        closeDetailsModal() {
            this.showDetailsModal = false;
            this.selectedCountry = null;
            this.relatedData = null;
        },

        async deleteCountry(country) {
            const confirmed = confirm(`Are you sure you want to delete "${country.name}"? This action cannot be undone.`);
            if (!confirmed) return;

            try {
                console.log('🗑️ Deleting country:', country.id);
                const response = await this.makeRequest(`/countries/${country.id}`, {
                    method: 'DELETE'
                });

                if (!response) return;

                const data = await response.json();
                if (response.ok && data.success) {
                    this.showNotification(data.message || 'Country deleted successfully', 'success');
                    await this.loadCountries();
                    console.log('✅ Country deleted successfully');
                } else {
                    this.showNotification(data.message || 'Failed to delete country', 'error');
                }
            } catch (error) {
                console.error('❌ Delete error:', error);
                this.showNotification('Failed to delete country', 'error');
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                region: '',
                status: '',
                page: 1
            };
            this.loadCountries();
        },

        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        showNotification(message, type = 'info') {
            const id = Date.now() + Math.random();
            const notification = {
                id: id,
                message: message,
                type: type,
                show: true,
                timestamp: new Date().toLocaleTimeString()
            };

            this.notifications.push(notification);
            console.log(`📢 Notification [${type.toUpperCase()}]:`, message);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                this.removeNotification(id);
            }, 5000);
        },

        removeNotification(id) {
            const index = this.notifications.findIndex(n => n.id === id);
            if (index > -1) {
                this.notifications[index].show = false;
                setTimeout(() => {
                    this.notifications.splice(index, 1);
                }, 300);
            }
        },

        getNotificationClasses(type) {
            const baseClasses = 'mb-2 max-w-sm w-full bg-white border rounded-2xl shadow-lg p-4 transition-all duration-300 ease-in-out transform';

            const typeClasses = {
                success: 'border-green-200 bg-green-50',
                error: 'border-red-200 bg-red-50',
                warning: 'border-yellow-200 bg-yellow-50',
                info: 'border-blue-200 bg-blue-50'
            };

            return `${baseClasses} ${typeClasses[type] || typeClasses.info}`;
        },

        getNotificationIcon(type) {
            const icons = {
                success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
                info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
            };
            return icons[type] || icons.info;
        },

        getNotificationColor(type) {
            const colors = {
                success: 'text-green-600',
                error: 'text-red-600',
                warning: 'text-yellow-600',
                info: 'text-blue-600'
            };
            return colors[type] || colors.info;
        }
    }
}
</script>
@endsection
