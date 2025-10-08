@extends('layouts.app')

@section('title', 'Audit Management')
@section('breadcrumb', 'Audit Management')

@section('content')
<div x-data="auditManager()" x-init="init()" class="space-y-6">

    {{-- Flash Messages --}}
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <div class="flex items-start space-x-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-lg mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-900">Error</p>
                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4">
            <div class="flex items-start space-x-3">
                <i class="fas fa-check-circle text-green-600 text-lg mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-green-900">Success</p>
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">Audit Management</h1>
            <p class="text-sm text-neutral-500 mt-1">Create, view, and manage SLIPTA audits</p>
        </div>
        <button @click="openModal('create')" class="inline-flex items-center px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
            <i class="fas fa-plus mr-2"></i>Create Audit
        </button>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Total Audits</p>
                    <p class="text-2xl font-semibold text-neutral-900 mt-1" x-text="stats.total"></p>
                    <p class="text-xs text-neutral-400 mt-1">In your scope</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-neutral-100">
                    <i class="fas fa-clipboard-list text-lg text-neutral-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">In Progress</p>
                    <p class="text-2xl font-semibold text-neutral-900 mt-1" x-text="stats.in_progress"></p>
                    <p class="text-xs text-neutral-400 mt-1">Active audits</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-100">
                    <i class="fas fa-tasks text-lg text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Completed</p>
                    <p class="text-2xl font-semibold text-neutral-900 mt-1" x-text="stats.completed"></p>
                    <p class="text-xs text-neutral-400 mt-1" x-text="`${stats.completion_rate}% rate`"></p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-green-100">
                    <i class="fas fa-check-circle text-lg text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">My Audits</p>
                    <p class="text-2xl font-semibold text-neutral-900 mt-1" x-text="stats.my_audits"></p>
                    <p class="text-xs text-neutral-400 mt-1">Created by me</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-purple-100">
                    <i class="fas fa-user-check text-lg text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Audits Table --}}
    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm">
        <div class="p-6 border-b border-neutral-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                <div>
                    <h2 class="text-lg font-medium text-neutral-900">Audits</h2>
                    <p class="text-sm text-neutral-500 mt-1">
                        <span x-text="filteredAudits.length"></span> audit(s) visible
                    </p>
                </div>
                <div class="flex flex-col md:flex-row space-y-3 md:space-y-0 md:space-x-3">
                    <input
                        type="text"
                        x-model="search"
                        @input="updateFilters()"
                        placeholder="Search audits..."
                        class="px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 w-full md:w-64"
                    >
                    <select
                        x-model="statusFilter"
                        @change="updateFilters()"
                        class="px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                    >
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50 border-b border-neutral-200">
                    <tr>
                        <th @click="sortBy('laboratory_name')" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider cursor-pointer hover:bg-neutral-100">
                            <div class="flex items-center space-x-1">
                                <span>Laboratory</span>
                                <i x-show="sortColumn === 'laboratory_name'" :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Country</th>
                        <th @click="sortBy('opened_on')" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider cursor-pointer hover:bg-neutral-100">
                            <div class="flex items-center space-x-1">
                                <span>Audit Date</span>
                                <i x-show="sortColumn === 'opened_on'" :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Score</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-neutral-200">
                    <template x-for="(audit, index) in paginatedAudits" :key="audit.id">
                        <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-neutral-50'">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-neutral-900" x-text="audit.laboratory_name"></div>
                                <div class="text-xs text-neutral-500" x-text="audit.lab_number"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-neutral-600" x-text="audit.country_name"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-neutral-600" x-text="formatDate(audit.opened_on)"></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="getStatusClass(audit.status)"
                                      x-text="formatStatus(audit.status)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div x-show="audit.score" class="flex items-center space-x-2">
                                    <div class="flex items-center space-x-0.5">
                                        <template x-for="i in 5" :key="i">
                                            <i class="fas fa-star text-xs" :class="i <= (audit.score?.star_level || 0) ? 'text-neutral-900' : 'text-neutral-300'"></i>
                                        </template>
                                    </div>
                                    <span class="text-xs text-neutral-600" x-text="audit.score ? `${audit.score.percentage}%` : ''"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="viewAudit(audit)" title="View Details" class="p-1.5 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 rounded-lg transition-colors">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button @click="editAudit(audit)" title="Edit Audit" class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button
                                        @click="confirmDelete(audit)"
                                        :disabled="!audit.can_delete"
                                        :title="audit.can_delete ? 'Delete Audit' : 'Cannot delete: audit has data or is not draft'"
                                        :class="audit.can_delete ? 'text-red-600 hover:text-red-800 hover:bg-red-50' : 'text-neutral-300 cursor-not-allowed'"
                                        class="p-1.5 rounded-lg transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="paginatedAudits.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-search text-neutral-300 text-4xl mb-3"></i>
                                <p class="text-sm text-neutral-500 mb-2">No audits found</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-neutral-200 bg-neutral-50 rounded-b-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center text-sm text-neutral-600">
                    <span>Show</span>
                    <select x-model="perPage" @change="updatePagination()" class="mx-2 px-2 py-1 border border-neutral-200 rounded-lg">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span>entries</span>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="text-sm text-neutral-600">
                        Showing <span x-text="paginationInfo.from"></span> to <span x-text="paginationInfo.to"></span> of <span x-text="filteredAudits.length"></span>
                    </span>

                    <div class="flex items-center space-x-1">
                        <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-neutral-200'" class="p-1.5 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>

                        <template x-for="page in pageNumbers">
                            <button @click="goToPage(page)" :class="currentPage === page ? 'bg-neutral-900 text-white' : 'hover:bg-neutral-200'" class="px-3 py-1 rounded-lg transition-colors">
                                <span x-text="page"></span>
                            </button>
                        </template>

                        <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= totalPages" :class="currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-neutral-200'" class="p-1.5 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape="closeModal()">
        <div class="fixed inset-0 bg-neutral-900 bg-opacity-75 transition-opacity" @click="closeModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl" @click.stop>

                {{-- Modal Header --}}
                <div class="flex items-center justify-between p-6 border-b border-neutral-200">
                    <div>
                        <h2 class="text-xl font-semibold text-neutral-900" x-text="modalMode === 'edit' ? 'Edit Audit' : modalMode === 'view' ? 'Audit Details' : 'Create Audit'"></h2>
                        <p class="text-sm text-neutral-500 mt-1" x-text="modalMode === 'edit' ? 'Update audit information' : modalMode === 'view' ? 'View audit details' : 'Create a new SLIPTA audit'"></p>
                    </div>
                    <button @click="closeModal()" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                        <i class="fas fa-times text-neutral-600"></i>
                    </button>
                </div>

                {{-- Modal Content --}}
                <div class="p-6">
                    {{-- View Mode --}}
                    <div x-show="modalMode === 'view'" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-neutral-500 mb-1">Laboratory</label>
                                <p class="text-sm text-neutral-900" x-text="currentAudit.laboratory_name"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-neutral-500 mb-1">Country</label>
                                <p class="text-sm text-neutral-900" x-text="currentAudit.country_name"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-neutral-500 mb-1">Status</label>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusClass(currentAudit.status)" x-text="formatStatus(currentAudit.status)"></span>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-neutral-500 mb-1">Audit Date</label>
                                <p class="text-sm text-neutral-900" x-text="formatDate(currentAudit.opened_on)"></p>
                            </div>
                            <div x-show="currentAudit.closed_on">
                                <label class="block text-xs font-medium text-neutral-500 mb-1">Closed Date</label>
                                <p class="text-sm text-neutral-900" x-text="formatDate(currentAudit.closed_on)"></p>
                            </div>
                            <div x-show="currentAudit.score">
                                <label class="block text-xs font-medium text-neutral-500 mb-1">Score</label>
                                <div class="flex items-center space-x-2">
                                    <div class="flex items-center space-x-0.5">
                                        <template x-for="i in 5" :key="i">
                                            <i class="fas fa-star" :class="i <= (currentAudit.score?.star_level || 0) ? 'text-neutral-900' : 'text-neutral-300'"></i>
                                        </template>
                                    </div>
                                    <span class="text-sm text-neutral-600" x-text="currentAudit.score ? `${currentAudit.score.percentage}%` : ''"></span>
                                </div>
                            </div>
                        </div>
                        <div x-show="currentAudit.auditor_notes">
                            <label class="block text-xs font-medium text-neutral-500 mb-1">Notes</label>
                            <p class="text-sm text-neutral-900" x-text="currentAudit.auditor_notes"></p>
                        </div>
                    </div>

                    {{-- Edit/Create Mode --}}
                    <form x-show="modalMode !== 'view'" @submit.prevent="submitForm()" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">
                                    Laboratory <span class="text-red-500">*</span>
                                </label>
                                <select x-model="form.laboratory_id" :disabled="modalMode === 'edit'" class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" :class="errors.laboratory_id ? 'border-red-300' : 'border-neutral-200'">
                                    <option value="">Select Laboratory</option>
                                    @foreach($laboratories as $lab)
                                        <option value="{{ $lab->id }}">{{ $lab->name }} - {{ $lab->country_name }}</option>
                                    @endforeach
                                </select>
                                <p x-show="errors.laboratory_id" x-text="errors.laboratory_id" class="text-xs text-red-600 mt-1"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">
                                    Audit Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" x-model="form.opened_on" :max="today" class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" :class="errors.opened_on ? 'border-red-300' : 'border-neutral-200'">
                                <p x-show="errors.opened_on" x-text="errors.opened_on" class="text-xs text-red-600 mt-1"></p>
                            </div>

                            <div x-show="modalMode === 'edit'">
                                <label class="block text-sm font-medium text-neutral-700 mb-1">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select x-model="form.status" class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" :class="errors.status ? 'border-red-300' : 'border-neutral-200'">
                                    <option value="draft">Draft</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <p x-show="errors.status" x-text="errors.status" class="text-xs text-red-600 mt-1"></p>
                            </div>

                            <div x-show="modalMode === 'edit' && form.status === 'completed'">
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Closed Date</label>
                                <input type="date" x-model="form.closed_on" :min="form.opened_on" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Last Audit Date</label>
                                <input type="date" x-model="form.last_audit_date" :max="form.opened_on" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Prior Star Level</label>
                                <select x-model="form.prior_official_status" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                    <option value="">Select Prior Status</option>
                                    <option value="NOT_AUDITED">Not Previously Audited</option>
                                    <option value="0">0 Stars (&lt;55%)</option>
                                    <option value="1">1 Star (55-64%)</option>
                                    <option value="2">2 Stars (65-74%)</option>
                                    <option value="3">3 Stars (75-84%)</option>
                                    <option value="4">4 Stars (85-94%)</option>
                                    <option value="5">5 Stars (≥95%)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Auditor Notes</label>
                            <textarea x-model="form.auditor_notes" rows="3" maxlength="5000" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 resize-none"></textarea>
                            <p class="text-xs text-neutral-500 mt-1" x-text="`${(form.auditor_notes || '').length} / 5000`"></p>
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-neutral-200">
                            <button type="button" @click="closeModal()" class="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="loading" class="px-6 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50">
                                <span x-show="!loading" x-text="modalMode === 'edit' ? 'Update Audit' : 'Create Audit'"></span>
                                <span x-show="loading" class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>Processing...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-neutral-900 bg-opacity-75" @click="deleteModal = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md" @click.stop>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-neutral-900 mb-2">Delete Audit</h3>
                    <p class="text-sm text-neutral-600 mb-6" x-text="deleteMessage"></p>
                    <div class="flex justify-center space-x-3">
                        <button @click="deleteModal = false" class="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                            Cancel
                        </button>
                        <button @click="deleteAudit()" :disabled="loading" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50">
                            <span x-show="!loading">Delete</span>
                            <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
$bootstrap = [
    'audits' => $audits,
    'stats' => $stats,
    'csrf' => csrf_token(),
];
@endphp

<script>
function auditManager() {
    const data = @json($bootstrap);

    return {
        audits: data.audits || [],
        stats: data.stats || {},

        // Modal state
        modalOpen: false,
        modalMode: 'create', // 'create', 'edit', 'view'
        deleteModal: false,
        loading: false,
        today: new Date().toISOString().split('T')[0],

        // Table state
        search: '',
        statusFilter: '',
        currentPage: 1,
        perPage: 10,
        sortColumn: 'opened_on',
        sortDirection: 'desc',

        // Form state
        form: {
            id: '',
            laboratory_id: '',
            status: 'draft',
            opened_on: '',
            closed_on: '',
            last_audit_date: '',
            prior_official_status: '',
            auditor_notes: ''
        },
        currentAudit: {},
        deleteAuditId: null,
        deleteMessage: '',
        errors: {},

        // Computed
        get filteredAudits() {
            let filtered = [...this.audits];

            if (this.search) {
                const term = this.search.toLowerCase();
                filtered = filtered.filter(a =>
                    (a.laboratory_name?.toLowerCase().includes(term)) ||
                    (a.country_name?.toLowerCase().includes(term)) ||
                    (a.lab_number?.toLowerCase().includes(term))
                );
            }

            if (this.statusFilter) {
                filtered = filtered.filter(a => a.status === this.statusFilter);
            }

            filtered.sort((a, b) => {
                const aVal = a[this.sortColumn] || '';
                const bVal = b[this.sortColumn] || '';

                if (this.sortColumn === 'opened_on') {
                    return this.sortDirection === 'asc'
                        ? new Date(aVal) - new Date(bVal)
                        : new Date(bVal) - new Date(aVal);
                }

                const compareResult = String(aVal).localeCompare(String(bVal));
                return this.sortDirection === 'asc' ? compareResult : -compareResult;
            });

            return filtered;
        },

        get paginatedAudits() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredAudits.slice(start, start + this.perPage);
        },

        get totalPages() {
            return Math.ceil(this.filteredAudits.length / this.perPage);
        },

        get pageNumbers() {
            const pages = [];
            const maxVisible = 5;

            if (this.totalPages <= maxVisible) {
                for (let i = 1; i <= this.totalPages; i++) pages.push(i);
            } else {
                let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
                let end = start + maxVisible - 1;

                if (end > this.totalPages) {
                    end = this.totalPages;
                    start = Math.max(1, end - maxVisible + 1);
                }

                for (let i = start; i <= end; i++) pages.push(i);
            }

            return pages;
        },

        get paginationInfo() {
            const from = this.filteredAudits.length === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
            const to = Math.min(from + this.perPage - 1, this.filteredAudits.length);
            return { from, to };
        },

        init() {
            // Any initialization
        },

        openModal(mode, audit = null) {
            this.modalMode = mode;
            this.errors = {};

            if (mode === 'create') {
                this.form = {
                    laboratory_id: '',
                    status: 'draft',
                    opened_on: '',
                    closed_on: '',
                    last_audit_date: '',
                    prior_official_status: '',
                    auditor_notes: ''
                };
            } else if (mode === 'edit' && audit) {
                this.form = {
                    id: audit.id,
                    laboratory_id: audit.laboratory_id,
                    status: audit.status,
                    opened_on: audit.opened_on,
                    closed_on: audit.closed_on || '',
                    last_audit_date: audit.last_audit_date || '',
                    prior_official_status: audit.prior_official_status || '',
                    auditor_notes: audit.auditor_notes || ''
                };
            } else if (mode === 'view' && audit) {
                this.currentAudit = audit;
            }

            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.modalOpen = false;
            document.body.style.overflow = '';
        },

        async submitForm() {
            this.loading = true;
            this.errors = {};

            try {
                const url = this.modalMode === 'create'
                    ? '/audits'
                    : `/audits/${this.form.id}`;

                const method = this.modalMode === 'create' ? 'POST' : 'PUT';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': data.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const result = await response.json();

                if (!response.ok) {
                    if (result.errors) {
                        this.errors = result.errors;
                        this.notify('error', 'Please correct the errors in the form');
                    } else {
                        throw new Error(result.error || 'Operation failed');
                    }
                    return;
                }

                if (this.modalMode === 'create') {
                    this.audits.unshift(result.audit);
                    this.stats.total++;
                    this.stats.draft++;
                } else {
                    const index = this.audits.findIndex(a => a.id === this.form.id);
                    if (index !== -1) {
                        this.audits[index] = { ...this.audits[index], ...result.audit };
                    }
                }

                this.notify('success', result.message);
                this.closeModal();

            } catch (error) {
                this.notify('error', error.message);
            } finally {
                this.loading = false;
            }
        },

        viewAudit(audit) {
            this.openModal('view', audit);
        },

        editAudit(audit) {
            this.openModal('edit', audit);
        },

        confirmDelete(audit) {
            if (!audit.can_delete) {
                this.notify('error', audit.has_data
                    ? 'Cannot delete audit with recorded data'
                    : 'Only draft audits can be deleted');
                return;
            }

            this.deleteAuditId = audit.id;
            this.deleteMessage = `Delete audit for "${audit.laboratory_name}" from ${this.formatDate(audit.opened_on)}? This cannot be undone.`;
            this.deleteModal = true;
        },

        async deleteAudit() {
            if (!this.deleteAuditId) return;

            this.loading = true;

            try {
                const response = await fetch(`/audits/${this.deleteAuditId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': data.csrf,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || 'Delete failed');
                }

                this.audits = this.audits.filter(a => a.id !== this.deleteAuditId);
                this.stats.total--;
                this.notify('success', 'Audit deleted successfully');
                this.deleteModal = false;

            } catch (error) {
                this.notify('error', error.message);
            } finally {
                this.loading = false;
            }
        },

        updateFilters() {
            this.currentPage = 1;
        },

        sortBy(column) {
            if (this.sortColumn === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                this.sortDirection = 'asc';
            }
        },

        updatePagination() {
            this.currentPage = 1;
        },

        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        formatStatus(status) {
            return (status || '').replace('_', ' ').toUpperCase();
        },

        getStatusClass(status) {
            const classes = {
                'completed': 'bg-green-100 text-green-800',
                'in_progress': 'bg-blue-100 text-blue-800',
                'draft': 'bg-neutral-100 text-neutral-800',
                'cancelled': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-neutral-100 text-neutral-800';
        },

        notify(type, msg) {
            const bg = { 'success': 'bg-green-600', 'error': 'bg-red-600' }[type] || 'bg-neutral-600';
            const icon = { 'success': 'check-circle', 'error': 'exclamation-circle' }[type] || 'info-circle';

            const n = document.createElement('div');
            n.className = `fixed bottom-4 right-4 ${bg} text-white px-4 py-3 rounded-xl shadow-lg flex items-center space-x-3 z-50 transform transition-all duration-300 translate-x-full max-w-md`;
            n.innerHTML = `<i class="fas fa-${icon}"></i><span class="text-sm font-medium flex-1">${msg}</span><button onclick="this.parentElement.remove()" class="hover:opacity-75"><i class="fas fa-times text-sm"></i></button>`;

            document.body.appendChild(n);
            setTimeout(() => n.classList.remove('translate-x-full'), 100);
            setTimeout(() => {
                n.classList.add('translate-x-full');
                setTimeout(() => n.remove(), 300);
            }, 5000);
        }
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
