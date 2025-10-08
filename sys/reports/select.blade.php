@extends('layouts.app')

@section('title', 'SLIPTA Report Generation')

@section('breadcrumb', 'Reports')

@section('content')
<div class="min-h-screen bg-neutral-50" x-data="reportSelection()">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">SLIPTA Report Generation</h1>
                <p class="text-sm text-neutral-600 mt-1">Generate comprehensive audit reports with AI-powered insights</p>
            </div>
            <div class="flex items-center space-x-2 text-xs text-neutral-500">
                <i class="fas fa-shield-alt"></i>
                <span>Role: <span class="font-medium text-neutral-900">{{ ucwords(str_replace('_', ' ', $userContext['role_names']->first() ?? 'User')) }}</span></span>
            </div>
        </div>
    </div>

    <!-- Selection Card -->
    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-6">
        <div class="space-y-6">
            <!-- Step 1: Country Selection -->
            <div>
                <label class="block text-sm font-medium text-neutral-900 mb-2">
                    <span class="flex items-center">
                        <span class="flex items-center justify-center w-6 h-6 bg-neutral-900 text-white rounded-full text-xs mr-2">1</span>
                        Select Country
                    </span>
                </label>
                <select x-model="selectedCountry" @change="loadLaboratories()" class="block w-full px-3 py-2 bg-white border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                    <option value="">-- Select a Country --</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }} ({{ $country->code }})</option>
                    @endforeach
                </select>
                <p class="text-xs text-neutral-500 mt-1" x-show="!selectedCountry">Select a country to view available laboratories</p>
            </div>

            <!-- Step 2: Laboratory Selection -->
            <div x-show="selectedCountry" x-transition.opacity>
                <label class="block text-sm font-medium text-neutral-900 mb-2">
                    <span class="flex items-center">
                        <span class="flex items-center justify-center w-6 h-6 bg-neutral-900 text-white rounded-full text-xs mr-2">2</span>
                        Select Laboratory
                    </span>
                </label>
                <template x-if="loadingLabs">
                    <div class="flex items-center space-x-2 text-sm text-neutral-500 py-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-2 border-neutral-900 border-t-transparent"></div>
                        <span>Loading laboratories...</span>
                    </div>
                </template>
                <template x-if="!loadingLabs && laboratories.length === 0 && selectedCountry">
                    <div class="text-sm text-neutral-500 py-2">No laboratories with audits found in this country</div>
                </template>
                <select x-show="!loadingLabs && laboratories.length > 0" x-model="selectedLab" @change="loadAudits()" class="block w-full px-3 py-2 bg-white border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                    <option value="">-- Select a Laboratory --</option>
                    <template x-for="lab in laboratories" :key="lab.id">
                        <option :value="lab.id" x-text="`${lab.name} (${lab.lab_number}) - ${lab.lab_type}`"></option>
                    </template>
                </select>
                <p class="text-xs text-neutral-500 mt-1" x-show="laboratories.length > 0 && !selectedLab">Select a laboratory to view available audits</p>
            </div>

            <!-- Step 3: Audit Selection -->
            <div x-show="selectedLab" x-transition.opacity>
                <label class="block text-sm font-medium text-neutral-900 mb-2">
                    <span class="flex items-center">
                        <span class="flex items-center justify-center w-6 h-6 bg-neutral-900 text-white rounded-full text-xs mr-2">3</span>
                        Select Audit
                    </span>
                </label>
                <template x-if="loadingAudits">
                    <div class="flex items-center space-x-2 text-sm text-neutral-500 py-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-2 border-neutral-900 border-t-transparent"></div>
                        <span>Loading audits...</span>
                    </div>
                </template>
                <template x-if="!loadingAudits && audits.length === 0 && selectedLab">
                    <div class="text-sm text-neutral-500 py-2">No audits found for this laboratory</div>
                </template>
                <div x-show="!loadingAudits && audits.length > 0" class="space-y-2">
                    <template x-for="audit in audits" :key="audit.id">
                        <div @click="selectAudit(audit)"
                             :class="selectedAudit?.id === audit.id ? 'border-neutral-900 bg-neutral-50' : 'border-neutral-200 hover:border-neutral-300'"
                             class="border rounded-xl p-4 cursor-pointer transition-all duration-150">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-neutral-900">
                                            Audit opened on <span x-text="formatDate(audit.opened_on)"></span>
                                        </span>
                                        <span x-show="audit.status === 'completed'"
                                              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle text-xs mr-1"></i>
                                            Completed
                                        </span>
                                        <span x-show="audit.status === 'in_progress'"
                                              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock text-xs mr-1"></i>
                                            In Progress
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-4 mt-1 text-xs text-neutral-500">
                                        <span x-show="audit.closed_on">
                                            <i class="fas fa-calendar-check mr-1"></i>
                                            Closed: <span x-text="formatDate(audit.closed_on)"></span>
                                        </span>
                                        <span>
                                            <i class="fas fa-clipboard-check mr-1"></i>
                                            <span x-text="audit.response_count"></span> responses
                                        </span>
                                        <span x-show="audit.prior_official_status && audit.prior_official_status !== 'NOT_AUDITED'">
                                            <i class="fas fa-star mr-1"></i>
                                            Previous: <span x-text="audit.prior_official_status"></span> stars
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <i x-show="selectedAudit?.id === audit.id" class="fas fa-check-circle text-neutral-900 text-lg"></i>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Generate Button -->
            <div x-show="selectedAudit" x-transition.opacity class="pt-4 border-t border-neutral-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-neutral-600">
                        <template x-if="selectedAudit?.status === 'completed'">
                            <span class="flex items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                Ready to generate comprehensive SLIPTA report
                            </span>
                        </template>
                        <template x-if="selectedAudit?.status !== 'completed'">
                            <span class="flex items-center text-yellow-600">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Reports can only be generated for completed audits
                            </span>
                        </template>
                    </div>
                    <button @click="generateReport()"
                            :disabled="!selectedAudit || selectedAudit.status !== 'completed' || generating"
                            :class="!selectedAudit || selectedAudit.status !== 'completed' ? 'opacity-50 cursor-not-allowed' : 'hover:bg-black'"
                            class="flex items-center space-x-2 bg-neutral-900 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition-colors duration-150">
                        <template x-if="generating">
                            <div class="flex items-center space-x-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                                <span>Generating Report...</span>
                            </div>
                        </template>
                        <template x-if="!generating">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-file-pdf"></i>
                                <span>Generate PDF Report</span>
                            </div>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="bg-white rounded-2xl border border-neutral-200 p-4">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-neutral-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-brain text-neutral-600"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-neutral-900">AI-Powered Insights</h3>
                    <p class="text-xs text-neutral-600 mt-1">Advanced narrative analysis with performance trends and recommendations</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-neutral-200 p-4">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-neutral-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-neutral-600"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-neutral-900">Comprehensive Analytics</h3>
                    <p class="text-xs text-neutral-600 mt-1">Detailed section breakdown, findings analysis, and risk assessment</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-neutral-200 p-4">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-neutral-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-award text-neutral-600"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-neutral-900">Professional Format</h3>
                    <p class="text-xs text-neutral-600 mt-1">International standard compliance with award-winning design</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function reportSelection() {
    return {
        selectedCountry: '',
        selectedLab: '',
        selectedAudit: null,
        laboratories: [],
        audits: [],
        loadingLabs: false,
        loadingAudits: false,
        generating: false,

        async loadLaboratories() {
            if (!this.selectedCountry) {
                this.laboratories = [];
                this.selectedLab = '';
                this.audits = [];
                this.selectedAudit = null;
                return;
            }

            this.loadingLabs = true;
            this.laboratories = [];
            this.selectedLab = '';
            this.audits = [];
            this.selectedAudit = null;

            try {
                const response = await fetch(`/reports/laboratories/${this.selectedCountry}`);
                const data = await response.json();

                if (data.success) {
                    this.laboratories = data.laboratories;
                } else {
                    console.error('Failed to load laboratories:', data.error);
                }
            } catch (error) {
                console.error('Error loading laboratories:', error);
            } finally {
                this.loadingLabs = false;
            }
        },

        async loadAudits() {
            if (!this.selectedLab) {
                this.audits = [];
                this.selectedAudit = null;
                return;
            }

            this.loadingAudits = true;
            this.audits = [];
            this.selectedAudit = null;

            try {
                const response = await fetch(`/reports/audits/${this.selectedLab}`);
                const data = await response.json();

                if (data.success) {
                    this.audits = data.audits;
                } else {
                    console.error('Failed to load audits:', data.error);
                }
            } catch (error) {
                console.error('Error loading audits:', error);
            } finally {
                this.loadingAudits = false;
            }
        },

        selectAudit(audit) {
            this.selectedAudit = audit;
        },

        async generateReport() {
            if (!this.selectedAudit || this.selectedAudit.status !== 'completed') {
                return;
            }

            this.generating = true;

            try {
                // Trigger download
                window.location.href = `/reports/generate/${this.selectedAudit.id}`;

                // Reset after delay
                setTimeout(() => {
                    this.generating = false;
                }, 3000);
            } catch (error) {
                console.error('Error generating report:', error);
                this.generating = false;
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }
    };
}
</script>
@endsection
