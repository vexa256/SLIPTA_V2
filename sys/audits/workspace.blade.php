@extends('layouts.app')

@section('title', 'Audit Workspace')
@section('breadcrumb', 'Audit Workspace')

@section('content')
<div x-data="auditWorkspace()" x-init="init()" class="space-y-6">

    {{-- Flash messages --}}
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4">
            <div class="flex items-start space-x-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-lg mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-900">Error</p>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif
    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4">
            <div class="flex items-start space-x-3">
                <i class="fas fa-check-circle text-green-600 text-lg mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-green-900">Success</p>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Server-side validation errors --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4">
            <div class="flex items-start space-x-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-lg mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-900 mb-1">Validation Errors</p>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Scope Banner -->
    <div class="bg-gradient-to-r from-neutral-900 to-neutral-800 rounded-2xl p-6 text-white shadow-lg">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <i class="fas fa-shield-alt text-2xl"></i>
                    <h2 class="text-xl font-semibold">Your Access Scope</h2>
                </div>
                <p class="text-neutral-300 text-sm mb-4">Viewing data within your authorized scope</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white/10 rounded-xl p-3 backdrop-blur-sm">
                        <p class="text-xs text-neutral-300 mb-1">Role</p>
                        <p class="font-semibold" x-text="scopeInfo.role"></p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-3 backdrop-blur-sm" x-show="scopeInfo.countries !== null">
                        <p class="text-xs text-neutral-300 mb-1">Countries</p>
                        <p class="font-semibold" x-text="scopeInfo.countries === 'all' ? 'All Countries' : scopeInfo.countries + ' Country(ies)'"></p>
                    </div>
                    <div class="bg-white/10 rounded-xl p-3 backdrop-blur-sm" x-show="scopeInfo.labs !== null">
                        <p class="text-xs text-neutral-300 mb-1">Laboratories</p>
                        <p class="font-semibold" x-text="scopeInfo.labs === 'all' ? 'All Laboratories' : scopeInfo.labs + ' Laboratory(ies)'"></p>
                    </div>
                </div>
            </div>
            <button @click="showScopeDetails = !showScopeDetails" class="ml-4 p-2 hover:bg-white/10 rounded-lg transition-colors">
                <i class="fas fa-info-circle text-xl"></i>
            </button>
        </div>
        <div x-show="showScopeDetails" x-collapse class="mt-4 pt-4 border-t border-white/20">
            <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm">
                <h3 class="font-medium mb-2 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>What You Can Do
                </h3>
                <ul class="text-sm text-neutral-200 space-y-1">
                    <li x-show="permissions.create" class="flex items-center">
                        <i class="fas fa-check-circle text-green-400 mr-2 text-xs"></i>
                        Create audits for authorized laboratories
                    </li>
                    <li x-show="permissions.link" class="flex items-center">
                        <i class="fas fa-check-circle text-green-400 mr-2 text-xs"></i>
                        Link audits within your scope
                    </li>
                    <li x-show="permissions.assign" class="flex items-center">
                        <i class="fas fa-check-circle text-green-400 mr-2 text-xs"></i>
                        Assign team members to audits
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">Audit Workspace</h1>
            <p class="text-sm text-neutral-500 mt-1">Manage audits, track progress, and assign teams</p>
        </div>
        <button @click="openModal('create')" class="inline-flex items-center px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
            <i class="fas fa-plus mr-2"></i>New Audit
        </button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <template x-for="(stat, key) in stats" :key="key">
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider" x-text="stat.label"></p>
                        <p class="text-2xl font-semibold text-neutral-900 mt-1" x-text="stat.value"></p>
                        <p class="text-xs text-neutral-400 mt-1" x-text="stat.sub"></p>
                    </div>
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="stat.bg">
                        <i :class="stat.icon + ' text-lg'"></i>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Audits Table with Enhanced Features -->
    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm">
        <div class="p-6 border-b border-neutral-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                <div>
                    <h2 class="text-lg font-medium text-neutral-900">Audit Management</h2>
                    <p class="text-sm text-neutral-500 mt-1">
                        <span x-text="filteredAudits.length"></span> audit(s) visible in your scope
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
                        <template x-for="column in columns" :key="column.key">
                            <th @click="column.sortable ? sortBy(column.key) : null"
                                :class="column.sortable ? 'cursor-pointer hover:bg-neutral-100' : ''"
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                <div class="flex items-center space-x-1">
                                    <span x-text="column.label"></span>
                                    <template x-if="column.sortable">
                                        <span>
                                            <i x-show="sortColumn === column.key && sortDirection === 'asc'" class="fas fa-sort-up"></i>
                                            <i x-show="sortColumn === column.key && sortDirection === 'desc'" class="fas fa-sort-down"></i>
                                            <i x-show="sortColumn !== column.key" class="fas fa-sort text-neutral-300"></i>
                                        </span>
                                    </template>
                                </div>
                            </th>
                        </template>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-neutral-200">
                    <template x-for="(audit, index) in paginatedAudits" :key="audit.id">
                        <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-neutral-50'">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-neutral-900" x-text="audit.laboratory_name"></div>
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
                                <div class="flex items-center space-x-2">
                                    <div class="flex-1 bg-neutral-200 rounded-full h-2">
                                        <div class="bg-neutral-900 h-2 rounded-full transition-all duration-300"
                                             :style="`width: ${getProgress(audit)}%`"></div>
                                    </div>
                                    <span class="text-xs font-medium text-neutral-600" x-text="`${getProgress(audit)}%`"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="viewAudit(audit)"
                                            title="View Details"
                                            class="p-1.5 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 rounded-lg transition-colors">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button @click="editAudit(audit)"
                                            title="Edit Audit"
                                            class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="confirmDelete(audit)"
                                            title="Delete Audit"
                                            class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
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
                                <p class="text-xs text-neutral-400" x-show="search || statusFilter">Try changing your search filters</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div class="px-6 py-4 border-t border-neutral-200 bg-neutral-50 rounded-b-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center text-sm text-neutral-600">
                    <span>Show</span>
                    <select x-model="perPage" @change="updatePagination()" class="mx-2 px-2 py-1 border border-neutral-200 rounded-lg">
                        <option value="5">5</option>
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
                        <button @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-neutral-200'"
                                class="p-1.5 rounded-lg transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>

                        <template x-for="page in pageNumbers">
                            <button @click="goToPage(page)"
                                    :class="currentPage === page ? 'bg-neutral-900 text-white' : 'hover:bg-neutral-200'"
                                    class="px-3 py-1 rounded-lg transition-colors">
                                <span x-text="page"></span>
                            </button>
                        </template>

                        <button @click="goToPage(currentPage + 1)"
                                :disabled="currentPage >= totalPages"
                                :class="currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-neutral-200'"
                                class="p-1.5 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape="closeModal()">
        <div class="fixed inset-0 bg-neutral-900 bg-opacity-75 transition-opacity" @click="closeModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden" @click.stop>

                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-neutral-200 bg-neutral-50">
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-neutral-900" x-text="modalMode === 'edit' ? 'Edit Audit' : 'Audit Workflow'"></h2>
                        <p class="text-sm text-neutral-500 mt-1" x-text="modalMode === 'edit' ? 'Update audit details' : 'Create audits, link history, and assign teams'"></p>
                    </div>
                    <button @click="closeModal()" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                        <i class="fas fa-times text-neutral-600"></i>
                    </button>
                </div>

                <!-- Tab Navigation (only shown for Create mode) -->
                <div x-show="modalMode === 'create'" class="border-b border-neutral-200 bg-white">
                    <nav class="flex space-x-1 px-6">
                        <template x-for="(tab, key) in tabs" :key="key">
                            <button @click="changeTab(key)" :class="activeTab === key ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-700'" class="py-4 px-4 text-sm font-medium transition-colors">
                                <div class="flex items-center space-x-2">
                                    <i :class="tab.icon"></i>
                                    <span x-text="tab.label"></span>
                                    <span x-show="completed[key]" class="ml-1 text-green-600">
                                        <i class="fas fa-check-circle text-xs"></i>
                                    </span>
                                </div>
                            </button>
                        </template>
                    </nav>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 200px);">

                    <!-- CREATE TAB (or Edit Mode) -->
                    <div x-show="activeTab === 'create' || modalMode === 'edit'" class="space-y-6">
                        <div x-show="modalMode === 'create'" class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-info-circle text-blue-600 text-lg mt-0.5"></i>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-blue-900 mb-1">Scope Validation Active</h4>
                                    <p class="text-xs text-blue-800">You can only create audits for laboratories within your authorized scope.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4 bg-neutral-50 p-4 rounded-xl border border-neutral-200">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-neutral-600">Form Completion</span>
                                    <span class="text-xs font-medium text-neutral-900" x-text="`${formProgress('create')}%`"></span>
                                </div>
                                <div class="w-full bg-neutral-200 rounded-full h-2">
                                    <div class="bg-neutral-900 h-2 rounded-full transition-all duration-300" :style="`width: ${formProgress('create')}%`"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-neutral-500">Required</p>
                                <p class="text-sm font-medium text-neutral-900" x-text="`${countFilled('create')} / 2`"></p>
                            </div>
                        </div>

                        <form id="auditForm" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1">
                                        Laboratory <span class="text-red-500">*</span>
                                    </label>
                                    <select name="laboratory_id" x-model="forms.create.laboratory_id" @change="onLabChange()" class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" :class="errors.laboratory_id ? 'border-red-300 ring-2 ring-red-100' : 'border-neutral-200'">
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
                                    <input type="date" name="opened_on" x-model="forms.create.opened_on" :max="today" class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" :class="errors.opened_on ? 'border-red-300 ring-2 ring-red-100' : 'border-neutral-200'">
                                    <p x-show="errors.opened_on" x-text="errors.opened_on" class="text-xs text-red-600 mt-1"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1">Last Audit Date</label>
                                    <input type="date" name="last_audit_date" x-model="forms.create.last_audit_date" :max="forms.create.opened_on" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1">Prior Star Level</label>
                                    <select name="prior_official_status" x-model="forms.create.prior_official_status" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
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
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Link to Previous Audit</label>
                                <select name="previous_audit_id" x-model="forms.create.previous_audit_id" :disabled="!forms.create.laboratory_id" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                    <option value="">No previous audit</option>
                                    <template x-for="audit in prevAudits" :key="audit.id">
                                        <option :value="audit.id" x-text="`${formatDate(audit.opened_on)} - ${audit.status}${audit.calculated_star_level ? ' (' + audit.calculated_star_level + ' Stars)' : ''}`"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Auditor Notes</label>
                                <textarea name="auditor_notes" x-model="forms.create.auditor_notes" rows="3" maxlength="5000" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 resize-none"></textarea>
                                <p class="text-xs text-neutral-500 mt-1" x-text="`${(forms.create.auditor_notes || '').length} / 5000`"></p>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-neutral-200">
                                <p class="text-xs text-neutral-500">
                                    <i class="fas fa-info-circle mr-1"></i><span x-text="modalMode === 'edit' ? 'Updates will be applied to this audit' : 'You will be assigned as Lead Auditor'"></span>
                                </p>
                                <div class="flex items-center space-x-3">
                                    <button type="button" @click="resetForm('create')" class="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                                        Reset
                                    </button>
                                    <button type="button" @click="submitForm()" :disabled="loading" class="px-6 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                                        <span x-show="!loading && modalMode === 'create'"><i class="fas fa-plus mr-2"></i>Create Audit</span>
                                        <span x-show="!loading && modalMode === 'edit'"><i class="fas fa-save mr-2"></i>Update Audit</span>
                                        <span x-show="loading" class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>Processing...</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- LINK TAB -->
                    <div x-show="activeTab === 'link' && modalMode === 'create'" class="space-y-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-shield-alt text-blue-600 text-lg mt-0.5"></i>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-blue-900 mb-1">Scope Enforcement</h4>
                                    <p class="text-xs text-blue-800">Both audits must be within your scope and from the same laboratory.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <template x-for="(item, key) in linkStats" :key="key">
                                <div class="rounded-xl border p-4" :class="item.bg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-xs font-medium mb-1" :class="item.text" x-text="item.label"></p>
                                            <p class="text-xl font-semibold" :class="item.dark" x-text="item.value"></p>
                                        </div>
                                        <i :class="item.icon + ' text-2xl ' + item.iconColor"></i>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">
                                    Current Audit <span class="text-red-500">*</span>
                                </label>
                                <select x-model="forms.link.audit_id" @change="loadLinkable()" class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" :class="errors.audit_id ? 'border-red-300 ring-2 ring-red-100' : 'border-neutral-200'">
                                    <option value="">Select Current Audit</option>
                                    <template x-for="audit in audits" :key="audit.id">
                                        <option :value="audit.id" x-text="`${audit.laboratory_name} - ${formatDate(audit.opened_on)}`"></option>
                                    </template>
                                </select>
                                <p x-show="errors.audit_id" x-text="errors.audit_id" class="text-xs text-red-600 mt-1"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">
                                    Link to Previous Audit <span class="text-red-500">*</span>
                                </label>
                                <select x-model="forms.link.previous_audit_id" :disabled="!forms.link.audit_id || linkLoading" class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" :class="errors.previous_audit_id ? 'border-red-300 ring-2 ring-red-100' : 'border-neutral-200'">
                                    <option value="">
                                        <span x-show="linkLoading">Loading...</span>
                                        <span x-show="!linkLoading">Select Previous Audit</span>
                                    </option>
                                    <template x-for="audit in linkable" :key="audit.id">
                                        <option :value="audit.id" x-text="`${formatDate(audit.opened_on)} - ${audit.status}${audit.calculated_star_level ? ' (' + audit.calculated_star_level + ' Stars)' : ''}`"></option>
                                    </template>
                                </select>
                                <p x-show="errors.previous_audit_id" x-text="errors.previous_audit_id" class="text-xs text-red-600 mt-1"></p>
                            </div>

                            <div x-show="forms.link.audit_id && linkable.length === 0 && !linkLoading" class="p-4 bg-neutral-50 rounded-xl border border-neutral-200">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-info-circle text-neutral-400 text-xl"></i>
                                    <p class="text-sm text-neutral-600">No linkable audits found</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-neutral-200">
                                <button type="button" @click="resetForm('link')" class="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                                    Reset
                                </button>
                                <button type="button" @click="submitLink()" :disabled="loading" class="px-6 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                                    <span x-show="!loading"><i class="fas fa-link mr-2"></i>Link Audits</span>
                                    <span x-show="loading" class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>Linking...</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TEAM TAB -->
                    <div x-show="activeTab === 'team' && modalMode === 'create'" class="space-y-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-users-cog text-blue-600 text-lg mt-0.5"></i>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-blue-900 mb-1">Team Assignment Rules</h4>
                                    <ul class="text-xs text-blue-800 space-y-1 mt-2">
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-xs mr-2 mt-0.5"></i>
                                            <span>Exactly one Lead Auditor required</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-xs mr-2 mt-0.5"></i>
                                            <span>All members must be in your scope</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-xs mr-2 mt-0.5"></i>
                                            <span>No duplicate assignments</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <template x-for="(item, key) in teamStats" :key="key">
                                <div class="rounded-xl border p-4" :class="item.bg">
                                    <p class="text-xs font-medium mb-1" :class="item.text" x-text="item.label"></p>
                                    <p class="text-xl font-semibold" :class="item.dark" x-text="item.value"></p>
                                </div>
                            </template>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">
                                    Select Audit <span class="text-red-500">*</span>
                                </label>
                                <select x-model="forms.team.audit_id" @change="loadTeam()" class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" :class="errors.audit_id ? 'border-red-300 ring-2 ring-red-100' : 'border-neutral-200'">
                                    <option value="">Select Audit</option>
                                    <template x-for="audit in audits" :key="audit.id">
                                        <option :value="audit.id" x-text="`${audit.laboratory_name} - ${formatDate(audit.opened_on)}`"></option>
                                    </template>
                                </select>
                                <p x-show="errors.audit_id" x-text="errors.audit_id" class="text-xs text-red-600 mt-1"></p>
                            </div>

                            <div x-show="team.length > 0" class="bg-neutral-50 rounded-xl border border-neutral-200 p-4">
                                <h4 class="text-sm font-medium text-neutral-900 mb-3 flex items-center">
                                    <i class="fas fa-users mr-2 text-neutral-600"></i>Current Team
                                </h4>
                                <div class="space-y-2">
                                    <template x-for="member in team" :key="member.id">
                                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-neutral-200">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 bg-neutral-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-user text-neutral-600 text-xs"></i>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-neutral-900" x-text="member.name"></p>
                                                    <p class="text-xs text-neutral-500" x-text="member.email"></p>
                                                </div>
                                            </div>
                                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium" :class="getRoleClass(member.role)" x-text="formatRole(member.role)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-sm font-medium text-neutral-700">
                                        Team Members <span class="text-red-500">*</span>
                                    </label>
                                    <button type="button" @click="addMember()" class="inline-flex items-center px-3 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-lg text-xs font-medium transition-colors">
                                        <i class="fas fa-plus mr-1.5"></i>Add Member
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <template x-for="(member, idx) in forms.team.team_members" :key="idx">
                                        <div class="p-4 bg-white rounded-xl border-2 border-neutral-200 hover:border-neutral-300 transition-colors">
                                            <div class="flex items-start justify-between mb-3">
                                                <div class="flex items-center space-x-2">
                                                    <div class="w-8 h-8 bg-neutral-100 rounded-lg flex items-center justify-center">
                                                        <i class="fas fa-user text-neutral-600 text-xs"></i>
                                                    </div>
                                                    <h5 class="text-sm font-medium text-neutral-900">Member <span x-text="idx + 1"></span></h5>
                                                </div>
                                                <button type="button" @click="removeMember(idx)" class="p-1.5 hover:bg-neutral-100 rounded-lg transition-colors">
                                                    <i class="fas fa-times text-neutral-600 text-xs"></i>
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-medium text-neutral-600 mb-1">Auditor</label>
                                                    <select x-model="member.user_id" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                                        <option value="">Select Auditor</option>
                                                        @foreach($availableAuditors as $auditor)
                                                            <option value="{{ $auditor->id }}">{{ $auditor->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-neutral-600 mb-1">Role</label>
                                                    <select x-model="member.role" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                                        <option value="">Select Role</option>
                                                        <option value="lead">Lead Auditor</option>
                                                        <option value="member">Team Member</option>
                                                        <option value="observer">Observer</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="forms.team.team_members.length === 0" class="text-center py-12 bg-neutral-50 rounded-xl border-2 border-dashed border-neutral-200">
                                    <div class="w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-users text-neutral-400 text-2xl"></i>
                                    </div>
                                    <p class="text-sm font-medium text-neutral-900 mb-1">No team members added</p>
                                    <button type="button" @click="addMember()" class="inline-flex items-center px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-lg text-sm font-medium transition-colors mt-3">
                                        <i class="fas fa-plus mr-2"></i>Add First Member
                                    </button>
                                </div>
                                <p x-show="errors.team_members" x-text="errors.team_members" class="text-xs text-red-600 mt-1"></p>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-neutral-200">
                                <button type="button" @click="resetForm('team')" class="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                                    Reset
                                </button>
                                <button type="button" @click="submitTeam()" :disabled="loading" class="px-6 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                                    <span x-show="!loading"><i class="fas fa-users mr-2"></i>Assign Team</span>
                                    <span x-show="loading" class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>Assigning...</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div> <!-- /content -->
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-neutral-900 bg-opacity-75 transition-opacity" @click="deleteModal = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden" @click.stop>
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
                        <button @click="deleteAudit()" :disabled="loading" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!loading">Delete</span>
                            <span x-show="loading" class="flex items-center justify-center">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Deleting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $bootstrap = [
        'audits'      => $audits,
        'stats'       => $stats,
        'userContext' => $userContext,
        'csrf'        => csrf_token(),
    ];
@endphp

<script>
function auditWorkspace() {
    // Bootstrap data from server
    const data = @json($bootstrap);

    // Context from server
    const ctx = data.userContext || {};

    return {
        // State
        modalOpen: false,
        deleteModal: false,
        modalMode: 'create', // 'create' or 'edit'
        activeTab: 'create',
        loading: false,
        linkLoading: false,
        showScopeDetails: false,
        today: new Date().toISOString().split('T')[0],
        audits: data.audits || [],
        deleteAuditId: null,
        deleteMessage: '',

        // Table state
        search: '',
        statusFilter: '',
        currentPage: 1,
        perPage: 10,
        sortColumn: 'laboratory_name',
        sortDirection: 'asc',

        // Navigation and tabs
        tabs: {
            create: {label: 'Create Audit', icon: 'fas fa-plus-circle'},
            link:   {label: 'Link to Prior', icon: 'fas fa-link'},
            team:   {label: 'Assign Team', icon: 'fas fa-users'}
        },
        completed: {create: false, link: false, team: false},

        // Table columns
        columns: [
            { key: 'laboratory_name', label: 'Laboratory', sortable: true },
            { key: 'country_name', label: 'Country', sortable: true },
            { key: 'opened_on', label: 'Audit Date', sortable: true },
            { key: 'status', label: 'Status', sortable: true },
            { key: 'progress', label: 'Progress', sortable: false }
        ],

        // Dynamic data
        errors: {},
        prevAudits: [],
        linkable: [],
        team: [],

        // User scope info
        scopeInfo: {
            role: ctx.is_system_admin ? 'System Administrator'
                 : ctx.is_project_coordinator ? 'Project Coordinator'
                 : ctx.is_country_coordinator ? 'Country Coordinator'
                 : ctx.is_lead_auditor ? 'Lead Auditor'
                 : 'Auditor',
            countries: (ctx.has_global_access ? 'all' : (Array.isArray(ctx.country_ids) ? ctx.country_ids.length : null)),
            labs:      (ctx.has_global_access ? 'all' : (Array.isArray(ctx.laboratory_ids) ? ctx.laboratory_ids.length : null))
        },

        // Permissions
        permissions: {
            viewAll: !!ctx.has_global_access,
            create:  !!(ctx.has_global_access || ctx.is_country_coordinator || ctx.is_lead_auditor),
            link:    !!(ctx.has_global_access || ctx.is_country_coordinator || ctx.is_lead_auditor),
            assign:  !!(ctx.has_global_access || ctx.is_country_coordinator || ctx.is_lead_auditor)
        },

        // Form data
        forms: {
            create: {
                id: '',
                laboratory_id: '',
                opened_on: '',
                last_audit_date: '',
                prior_official_status: '',
                previous_audit_id: '',
                auditor_notes: ''
            },
            link: {audit_id: '', previous_audit_id: ''},
            team: {audit_id: '', team_members: []}
        },

        // Stats displays
        stats: {
            total:     {label: 'Total Audits',    value: (data.stats?.overall?.total_audits ?? 0),        sub: 'In your scope',                                              bg: 'bg-neutral-100', icon: 'fas fa-clipboard-list text-neutral-600'},
            rate:      {label: 'Completion Rate', value: `${data.stats?.overall?.completion_rate ?? 0}%`, sub: '',                                                          bg: 'bg-green-100',   icon: 'fas fa-check-circle text-green-600'},
            duration:  {label: 'Avg Duration',    value: (data.stats?.overall?.avg_duration_days ?? 0),   sub: 'days',                                                      bg: 'bg-blue-100',    icon: 'fas fa-calendar-alt text-blue-600'},
            mine:      {label: 'My Audits',       value: ((data.stats?.overall?.my_as_lead ?? 0) + (data.stats?.overall?.my_as_member ?? 0)), sub: `${data.stats?.overall?.my_as_lead ?? 0} as lead`, bg: 'bg-purple-100',  icon: 'fas fa-user-check text-purple-600'}
        },

        linkStats: {
            improved:   {label: 'Improved',   value: (data.stats?.link?.improved ?? 0),   bg: 'bg-gradient-to-br from-green-50 to-green-100/50 border-green-200',    text: 'text-green-700',   dark: 'text-green-900',   icon: 'fas fa-arrow-up',   iconColor: 'text-green-600'},
            maintained: {label: 'Maintained', value: (data.stats?.link?.maintained ?? 0), bg: 'bg-gradient-to-br from-neutral-50 to-neutral-100/50 border-neutral-200', text: 'text-neutral-600', dark: 'text-neutral-900', icon: 'fas fa-minus',    iconColor: 'text-neutral-600'},
            declined:   {label: 'Declined',   value: (data.stats?.link?.declined ?? 0),   bg: 'bg-gradient-to-br from-red-50 to-red-100/50 border-red-200',        text: 'text-red-700',     dark: 'text-red-900',     icon: 'fas fa-arrow-down', iconColor: 'text-red-600'}
        },

        teamStats: {
            total:   {label: 'Total Members', value: (data.stats?.team?.total_members ?? 0), bg: 'bg-gradient-to-br from-neutral-50 to-neutral-100/50 border-neutral-200', text: 'text-neutral-600', dark: 'text-neutral-900'},
            lead:    {label: 'Lead Auditors', value: (data.stats?.team?.lead_count ?? 0),    bg: 'bg-gradient-to-br from-green-50 to-green-100/50 border-green-200',    text: 'text-green-700',   dark: 'text-green-900'},
            members: {label: 'Members',       value: (data.stats?.team?.member_count ?? 0),  bg: 'bg-gradient-to-br from-blue-50 to-blue-100/50 border-blue-200',      text: 'text-blue-700',    dark: 'text-blue-900'},
            avg:     {label: 'Avg Team Size', value: (data.stats?.team?.avg_team_size ?? 0), bg: 'bg-gradient-to-br from-purple-50 to-purple-100/50 border-purple-200',  text: 'text-purple-700',  dark: 'text-purple-900'}
        },

        // Computed properties
        get filteredAudits() {
            let filtered = [...this.audits];

            // Apply search filter
            if (this.search) {
                const term = this.search.toLowerCase();
                filtered = filtered.filter(audit => {
                    return (
                        (audit.laboratory_name?.toLowerCase().includes(term) || false) ||
                        (audit.country_name?.toLowerCase().includes(term) || false)
                    );
                });
            }

            // Apply status filter
            if (this.statusFilter) {
                filtered = filtered.filter(audit => audit.status === this.statusFilter);
            }

            // Apply sorting
            filtered.sort((a, b) => {
                const aVal = a[this.sortColumn] || '';
                const bVal = b[this.sortColumn] || '';

                // Handle date sorting
                if (this.sortColumn === 'opened_on') {
                    const dateA = new Date(aVal);
                    const dateB = new Date(bVal);
                    return this.sortDirection === 'asc'
                        ? dateA - dateB
                        : dateB - dateA;
                }

                // Handle string sorting
                const compareResult = String(aVal).localeCompare(String(bVal));
                return this.sortDirection === 'asc'
                    ? compareResult
                    : -compareResult;
            });

            return filtered;
        },

        get paginatedAudits() {
            const startIndex = (this.currentPage - 1) * this.perPage;
            return this.filteredAudits.slice(startIndex, startIndex + this.perPage);
        },

        get totalPages() {
            return Math.ceil(this.filteredAudits.length / this.perPage);
        },

        get pageNumbers() {
            const pages = [];
            const maxVisiblePages = 5;

            if (this.totalPages <= maxVisiblePages) {
                // Show all pages if there are fewer than maxVisiblePages
                for (let i = 1; i <= this.totalPages; i++) {
                    pages.push(i);
                }
            } else {
                // Calculate which pages to show
                let startPage = Math.max(1, this.currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = startPage + maxVisiblePages - 1;

                // Adjust if endPage exceeds totalPages
                if (endPage > this.totalPages) {
                    endPage = this.totalPages;
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }

                for (let i = startPage; i <= endPage; i++) {
                    pages.push(i);
                }
            }

            return pages;
        },

        get paginationInfo() {
            const from = this.filteredAudits.length === 0
                ? 0
                : (this.currentPage - 1) * this.perPage + 1;

            const to = Math.min(from + this.perPage - 1, this.filteredAudits.length);

            return { from, to };
        },

        // Initialization
        init() {
            this.updateFilters();

            // Check URL for flash messages
            @if (session('error'))
                this.notify('error', @json(session('error')));
            @endif

            @if (session('success'))
                this.notify('success', @json(session('success')));
            @endif
        },

        // Modal methods
        openModal(mode = 'create') {
            this.modalMode = mode;
            this.modalOpen = true;
            this.activeTab = 'create';
            document.body.style.overflow = 'hidden';
            this.errors = {};
        },

        closeModal() {
            this.modalOpen = false;
            document.body.style.overflow = '';
            this.errors = {};
        },

        changeTab(tab) {
            this.activeTab = tab;
            this.errors = {};
        },

        // Table methods
        updateFilters() {
            this.currentPage = 1; // Reset to first page when filters change
        },

        sortBy(column) {
            // Only sort if the column is sortable
            const col = this.columns.find(c => c.key === column);
            if (!col || !col.sortable) return;

            // Toggle direction if already sorting by this column
            if (this.sortColumn === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                this.sortDirection = 'asc';
            }
        },

        updatePagination() {
            this.currentPage = 1; // Reset to first page when changing items per page
        },

        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
        },

        // Audit actions
        viewAudit(audit) {
            // Navigate to audit detail page
            window.location.href = `/audits/${audit.id}`;
        },

        editAudit(audit) {
            // Reset form first to clear any previous data
            this.resetForm('create');

            // Set mode to edit
            this.modalMode = 'edit';

            // Populate form with audit data
            this.forms.create = {
                id: audit.id,
                laboratory_id: audit.laboratory_id,
                opened_on: audit.opened_on,
                last_audit_date: audit.last_audit_date || '',
                prior_official_status: audit.prior_official_status || '',
                previous_audit_id: audit.previous_audit_id || '',
                auditor_notes: audit.auditor_notes || ''
            };

            // Load previous audits for the selected laboratory
            this.prevAudits = this.audits.filter(a =>
                String(a.laboratory_id) === String(audit.laboratory_id) &&
                (a.status === 'completed' || a.status === 'in_progress') &&
                a.id !== audit.id // Exclude the current audit
            );

            // Open modal
            this.openModal('edit');
        },

        confirmDelete(audit) {
            this.deleteAuditId = audit.id;
            this.deleteMessage = `Are you sure you want to delete the audit for "${audit.laboratory_name}" created on ${this.formatDate(audit.opened_on)}? This action cannot be undone.`;
            this.deleteModal = true;
        },

        async deleteAudit() {
            if (!this.deleteAuditId) return;

            this.loading = true;

            try {
                const response = await fetch(`/audits/${this.deleteAuditId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': data.csrf,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || result.error || 'Failed to delete audit');
                }

                // Remove from audits list
                this.audits = this.audits.filter(a => a.id !== this.deleteAuditId);

                // Show success message
                this.notify('success', 'Audit deleted successfully');

                // Close modal
                this.deleteModal = false;
                this.deleteAuditId = null;

            } catch (error) {
                this.notify('error', `Delete failed: ${error.message}`);
            } finally {
                this.loading = false;
            }
        },

        // Form methods
        onLabChange() {
            this.prevAudits = this.audits.filter(a =>
                String(a.laboratory_id) === String(this.forms.create.laboratory_id) &&
                (a.status === 'completed' || a.status === 'in_progress')
            );

            if (this.modalMode === 'edit') {
                // When editing, exclude the current audit from previous audits
                this.prevAudits = this.prevAudits.filter(a => a.id !== this.forms.create.id);
            }

            delete this.errors.laboratory_id;
        },

        formProgress(type) {
            if (type === 'create') {
                const req = ['laboratory_id', 'opened_on'];
                const filled = req.filter(k => this.forms.create[k]).length;
                return Math.round((filled / req.length) * 100);
            }
            return 0;
        },

        countFilled(type) {
            if (type === 'create') {
                const req = ['laboratory_id', 'opened_on'];
                return req.filter(k => this.forms.create[k]).length;
            }
            return 0;
        },

        resetForm(type) {
            const defaults = {
                create: {
                    id: '',
                    laboratory_id: '',
                    opened_on: '',
                    last_audit_date: '',
                    prior_official_status: '',
                    previous_audit_id: '',
                    auditor_notes: ''
                },
                link: {audit_id: '', previous_audit_id: ''},
                team: {audit_id: '', team_members: []}
            };

            this.forms[type] = JSON.parse(JSON.stringify(defaults[type]));
            this.errors = {};

            if (type === 'link') this.linkable = [];
            if (type === 'team') this.team = [];
        },

        submitForm() {
            if (this.modalMode === 'edit') {
                this.submitEditForm();
            } else {
                this.submitCreateForm();
            }
        },

        async submitCreateForm() {
            this.errors = {};

            // Validation
            if (!this.forms.create.laboratory_id) this.errors.laboratory_id = 'Laboratory is required';
            if (!this.forms.create.opened_on) this.errors.opened_on = 'Audit date is required';

            if (Object.keys(this.errors).length > 0) {
                this.notify('error', 'Fix the highlighted errors and try again.');
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('{{ route('audits.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': data.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.forms.create)
                });

                // Try to get JSON response
                let result;
                try {
                    result = await response.json();
                } catch (e) {
                    // If not JSON, get the text
                    const text = await response.text();
                    if (response.ok) {
                        // If it's a successful redirect, reload the page
                        window.location.reload();
                        return;
                    } else {
                        throw new Error(text || 'Failed to create audit');
                    }
                }

                if (!response.ok) {
                    if (result.errors) {
                        this.errors = this.flattenErrors(result.errors);
                        throw new Error('Please correct the errors in the form');
                    } else {
                        throw new Error(result.message || result.error || 'Failed to create audit');
                    }
                }

                // Add to audits list if we get a success response with the audit data
                if (result.success && result.audit) {
                    this.audits.push(result.audit);
                }

                // Show success message
                this.notify('success', 'Audit created successfully');
                this.completed.create = true;

                // Close modal or keep it open for more actions
                setTimeout(() => {
                    this.closeModal();
                    // Reload to get the latest data
                    window.location.reload();
                }, 1500);

            } catch (error) {
                this.notify('error', `Create failed: ${error.message}`);
            } finally {
                this.loading = false;
            }
        },

        async submitEditForm() {
            this.errors = {};

            // Validation
            if (!this.forms.create.laboratory_id) this.errors.laboratory_id = 'Laboratory is required';
            if (!this.forms.create.opened_on) this.errors.opened_on = 'Audit date is required';

            if (Object.keys(this.errors).length > 0) {
                this.notify('error', 'Fix the highlighted errors and try again.');
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(`/audits/${this.forms.create.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': data.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.forms.create)
                });

                const result = await response.json();

                if (!response.ok) {
                    if (result.errors) {
                        this.errors = this.flattenErrors(result.errors);
                        throw new Error('Please correct the errors in the form');
                    } else {
                        throw new Error(result.message || result.error || 'Failed to update audit');
                    }
                }

                // Update local data
                const index = this.audits.findIndex(a => a.id === this.forms.create.id);
                if (index !== -1) {
                    this.audits[index] = { ...this.audits[index], ...result.audit };
                }

                // Show success and close modal
                this.notify('success', 'Audit updated successfully');
                this.closeModal();

            } catch (error) {
                this.notify('error', `Update failed: ${error.message}`);
            } finally {
                this.loading = false;
            }
        },

        async loadLinkable() {
            if (!this.forms.link.audit_id) {
                this.linkable = [];
                return;
            }

            const a = this.audits.find(x => String(x.id) === String(this.forms.link.audit_id));
            if (!a) {
                this.notify('error', 'Selected audit not found in your scope');
                return;
            }

            this.linkLoading = true;
            this.linkable = [];
            delete this.errors.audit_id;

            try {
                const url = `{{ route('audits.linkable') }}?laboratory_id=${a.laboratory_id}&current_audit_id=${this.forms.link.audit_id}`;
                const r = await fetch(url, { headers: { 'Accept': 'application/json' }});

                if (!r.ok) {
                    const text = await r.text();
                    throw new Error(text || `HTTP ${r.status}`);
                }

                const d = await r.json();

                if (d.success) {
                    this.linkable = d.audits || [];
                } else {
                    this.notify('error', d.error || 'Failed to load audits');
                }
            } catch (e) {
                console.error('Load linkable error:', e);
                this.notify('error', `Failed to load audits: ${e.message}`);
            } finally {
                this.linkLoading = false;
            }
        },

        async submitLink() {
            this.loading = true;
            this.errors = {};

            if (!this.forms.link.audit_id) this.errors.audit_id = 'Current audit is required';
            if (!this.forms.link.previous_audit_id) this.errors.previous_audit_id = 'Previous audit is required';

            if (Object.keys(this.errors).length) {
                this.loading = false;
                this.notify('error', 'Fix the highlighted errors and try again.');
                return;
            }

            try {
                const r = await fetch('{{ route('audits.link-prior') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': data.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.forms.link)
                });

                const d = await r.json().catch(async () => ({
                    success: false,
                    error: await r.text() || 'Non-JSON server response'
                }));

                if (!r.ok || !d.success) {
                    if (d.errors) this.errors = this.flattenErrors(d.errors);
                    this.notify('error', d.error || d.message || `HTTP ${r.status}`);
                    return;
                }

                this.completed.link = true;
                this.notify('success', 'Audits linked successfully');

                // Update local data
                const index = this.audits.findIndex(a => String(a.id) === String(this.forms.link.audit_id));
                if (index !== -1) {
                    this.audits[index].previous_audit_id = this.forms.link.previous_audit_id;
                }

                // Reset form
                this.resetForm('link');

                // Change tab back to create
                this.changeTab('create');

            } catch (e) {
                console.error('Link error:', e);
                this.notify('error', `Operation failed: ${e.message}`);
            } finally {
                this.loading = false;
            }
        },

        async loadTeam() {
            if (!this.forms.team.audit_id) {
                this.team = [];
                return;
            }

            delete this.errors.audit_id;

            try {
                const r = await fetch(`{{ route('audits.team') }}?audit_id=${this.forms.team.audit_id}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!r.ok) {
                    const text = await r.text();
                    throw new Error(text || `HTTP ${r.status}`);
                }

                const d = await r.json();

                if (d.success) {
                    this.team = d.team || [];
                } else {
                    this.notify('error', d.error || 'Failed to load team');
                }
            } catch (e) {
                console.error('Load team error:', e);
                this.notify('error', `Failed to load team: ${e.message}`);
            }
        },

        addMember() {
            this.forms.team.team_members.push({user_id: '', role: ''});
        },

        removeMember(idx) {
            this.forms.team.team_members.splice(idx, 1);
        },

        async submitTeam() {
            this.loading = true;
            this.errors = {};

            // Validation
            const members = this.forms.team.team_members || [];

            if (!this.forms.team.audit_id) this.errors.audit_id = 'Audit is required';
            if (members.length === 0) this.errors.team_members = 'Add at least one member';

            const missing = members.some(m => !m.user_id || !m.role);
            if (missing) this.errors.team_members = 'Every member must have a user and a role';

            const leadCount = members.filter(m => m.role === 'lead').length;
            if (leadCount !== 1) this.errors.team_members = 'Exactly one Lead Auditor required';

            const ids = members.map(m => m.user_id).filter(Boolean);
            if (ids.length !== new Set(ids).size) this.errors.team_members = 'Cannot assign the same user multiple times';

            if (Object.keys(this.errors).length) {
                this.loading = false;
                this.notify('error', this.errors.team_members || 'Fix the highlighted errors and try again.');
                return;
            }

            try {
                const r = await fetch('{{ route('audits.assign-team') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': data.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.forms.team)
                });

                const d = await r.json().catch(async () => ({
                    success: false,
                    error: await r.text() || 'Non-JSON server response'
                }));

                if (!r.ok || !d.success) {
                    if (d.errors) this.errors = this.flattenErrors(d.errors);
                    this.notify('error', d.error || d.message || `HTTP ${r.status}`);
                    return;
                }

                this.completed.team = true;
                this.notify('success', 'Team assigned successfully');

                // Reset form
                this.resetForm('team');

                // Change tab back to create
                this.changeTab('create');

            } catch (e) {
                console.error('Team error:', e);
                this.notify('error', `Operation failed: ${e.message}`);
            } finally {
                this.loading = false;
            }
        },

        // Helper methods
        flattenErrors(errors) {
            const flat = {};
            for (const field in errors) {
                flat[field] = Array.isArray(errors[field]) ? errors[field][0] : String(errors[field] || '');
            }
            return flat;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        formatStatus(status) {
            if (!status) return '';
            return status.replace('_', ' ').toUpperCase();
        },

        formatRole(role) {
            const roles = {
                'lead': 'Lead Auditor',
                'member': 'Team Member',
                'observer': 'Observer'
            };
            return roles[role] || role;
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

        getRoleClass(role) {
            const classes = {
                'lead': 'bg-green-100 text-green-700',
                'member': 'bg-blue-100 text-blue-700',
                'observer': 'bg-neutral-100 text-neutral-600'
            };
            return classes[role] || 'bg-neutral-100 text-neutral-600';
        },

        getProgress(audit) {
            return audit.status === 'completed' ? 100
                 : audit.status === 'in_progress' ? 60
                 : 15;
        },

        notify(type, msg) {
            const bg = {
                'success': 'bg-green-600',
                'error': 'bg-red-600'
            }[type] || 'bg-neutral-600';

            const icon = {
                'success': 'check-circle',
                'error': 'exclamation-circle'
            }[type] || 'info-circle';

            const n = document.createElement('div');
            n.className = `fixed bottom-4 right-4 ${bg} text-white px-4 py-3 rounded-xl shadow-lg flex items-center space-x-3 z-50 transform transition-all duration-300 translate-x-full max-w-md`;
            n.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <span class="text-sm font-medium flex-1">${msg}</span>
                <button onclick="this.parentElement.remove()" class="ml-2 hover:opacity-75">
                    <i class="fas fa-times text-sm"></i>
                </button>
            `;

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
