@extends('layouts.app')

@section('title', 'Audit Linking')
@section('breadcrumb', 'Link Audits to Prior History')

@section('content')
<div x-data="auditLinking()" x-init="init()" class="space-y-6">

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

    {{-- Scope Awareness Banner --}}
    <div class="bg-gradient-to-br from-neutral-900 to-neutral-800 rounded-2xl p-6 text-white shadow-lg">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <i class="fas fa-link text-2xl"></i>
                    <h2 class="text-xl font-semibold">Audit Linking - Your Access Scope</h2>
                </div>
                <p class="text-neutral-300 text-sm mb-4">Link audits to their prior history for progression tracking</p>
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
        </div>
    </div>

    {{-- Linking Statistics Overview --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Total Audits</p>
                    <p class="text-2xl font-semibold text-neutral-900 mt-1" x-text="stats.total_audits"></p>
                    <p class="text-xs text-neutral-400 mt-1">In your scope</p>
                </div>
                <div class="w-12 h-12 bg-neutral-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-neutral-600 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Linked Audits</p>
                    <p class="text-2xl font-semibold text-neutral-900 mt-1" x-text="stats.linked_audits"></p>
                    <p class="text-xs text-neutral-400 mt-1"><span x-text="stats.linking_rate"></span>% linking rate</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-link text-green-600 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Unlinked</p>
                    <p class="text-2xl font-semibold text-neutral-900 mt-1" x-text="stats.unlinked_audits"></p>
                    <p class="text-xs text-neutral-400 mt-1">Pending linkage</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-unlink text-orange-600 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-neutral-500 uppercase tracking-wider">Improved</p>
                    <p class="text-2xl font-semibold text-neutral-900 mt-1" x-text="stats.improved"></p>
                    <p class="text-xs text-neutral-400 mt-1">Star level gains</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-arrow-up text-blue-600 text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Progression Analysis Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-green-50 to-green-100/50 border border-green-200 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-green-700 uppercase tracking-wider mb-1">Improved</p>
                    <p class="text-xl font-semibold text-green-900" x-text="stats.improved"></p>
                </div>
                <i class="fas fa-arrow-up text-2xl text-green-600"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-neutral-50 to-neutral-100/50 border border-neutral-200 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-neutral-600 uppercase tracking-wider mb-1">Maintained</p>
                    <p class="text-xl font-semibold text-neutral-900" x-text="stats.maintained"></p>
                </div>
                <i class="fas fa-minus text-2xl text-neutral-600"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-50 to-red-100/50 border border-red-200 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-red-700 uppercase tracking-wider mb-1">Declined</p>
                    <p class="text-xl font-semibold text-red-900" x-text="stats.declined"></p>
                </div>
                <i class="fas fa-arrow-down text-2xl text-red-600"></i>
            </div>
        </div>
    </div>

    {{-- Audits Table with Linking Interface --}}
    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm">
        <div class="p-6 border-b border-neutral-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                <div>
                    <h2 class="text-lg font-medium text-neutral-900">Link Audits to Prior History</h2>
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
                        x-model="linkStatusFilter"
                        @change="updateFilters()"
                        class="px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                    >
                        <option value="">All Audits</option>
                        <option value="linked">Linked</option>
                        <option value="unlinked">Unlinked</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50 border-b border-neutral-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Laboratory</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Audit Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Link Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">Actions</th>
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
                                <template x-if="audit.previous_audit_id">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-link mr-1 text-xs"></i>Linked
                                        </span>
                                        <span class="text-xs text-neutral-500" x-text="'to ' + formatDate(audit.previous_audit_date)"></span>
                                    </div>
                                </template>
                                <template x-if="!audit.previous_audit_id">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        <i class="fas fa-unlink mr-1 text-xs"></i>Unlinked
                                    </span>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-2">
                                    <template x-if="!audit.previous_audit_id">
                                        <button @click="openLinkModal(audit)"
                                                title="Link to Prior Audit"
                                                class="inline-flex items-center px-3 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-lg text-xs font-medium transition-colors">
                                            <i class="fas fa-link mr-1.5"></i>Link
                                        </button>
                                    </template>
                                    <template x-if="audit.previous_audit_id">
                                        <button @click="viewLinkDetails(audit)"
                                                title="View Link Details"
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </template>
                                    <template x-if="audit.previous_audit_id">
                                        <button @click="confirmUnlink(audit)"
                                                title="Unlink from Prior Audit"
                                                class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                                            <i class="fas fa-unlink"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="paginatedAudits.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-search text-neutral-300 text-4xl mb-3"></i>
                                <p class="text-sm text-neutral-500 mb-2">No audits found</p>
                                <p class="text-xs text-neutral-400" x-show="search || linkStatusFilter">Try changing your filters</p>
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

    {{-- Link Modal --}}
    <div x-show="linkModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape="closeLinkModal()">
        <div class="fixed inset-0 bg-neutral-900 bg-opacity-75 transition-opacity" @click="closeLinkModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl" @click.stop>

                {{-- Modal Header --}}
                <div class="flex items-center justify-between p-6 border-b border-neutral-200 bg-neutral-50 rounded-t-2xl">
                    <div>
                        <h2 class="text-xl font-semibold text-neutral-900">Link to Prior Audit</h2>
                        <p class="text-sm text-neutral-500 mt-1">Select the previous audit for this laboratory</p>
                    </div>
                    <button @click="closeLinkModal()" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                        <i class="fas fa-times text-neutral-600"></i>
                    </button>
                </div>

                {{-- Modal Content --}}
                <div class="p-6 space-y-4">
                    {{-- Current Audit Info --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <p class="text-xs font-medium text-blue-700 uppercase tracking-wider mb-2">Current Audit</p>
                        <p class="text-sm font-medium text-blue-900" x-text="selectedAudit?.laboratory_name"></p>
                        <p class="text-xs text-blue-700 mt-1">Audit Date: <span x-text="formatDate(selectedAudit?.opened_on)"></span></p>
                    </div>

                    {{-- Loading State --}}
                    <div x-show="linkableLoading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-2 border-neutral-900 border-t-transparent"></div>
                        <span class="ml-3 text-sm text-neutral-600">Loading available audits...</span>
                    </div>

                    {{-- Previous Audit Selection --}}
                    <div x-show="!linkableLoading">
                        <label class="block text-sm font-medium text-neutral-700 mb-2">
                            Select Previous Audit <span class="text-red-500">*</span>
                        </label>
                        <select x-model="selectedPreviousAuditId"
                                class="w-full px-3 py-2 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                                :class="errors.previous_audit_id ? 'border-red-300 ring-2 ring-red-100' : 'border-neutral-200'">
                            <option value="">Select previous audit...</option>
                            <template x-for="audit in linkableAudits" :key="audit.id">
                                <option :value="audit.id" x-text="`${formatDate(audit.opened_on)} - ${audit.status}${audit.calculated_star_level !== null ? ' (' + audit.calculated_star_level + ' Stars)' : ''}`"></option>
                            </template>
                        </select>
                        <p x-show="errors.previous_audit_id" x-text="errors.previous_audit_id" class="text-xs text-red-600 mt-1"></p>

                        <div x-show="linkableAudits.length === 0 && !linkableLoading" class="mt-4 p-4 bg-neutral-50 rounded-xl border border-neutral-200">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-info-circle text-neutral-400 text-xl"></i>
                                <p class="text-sm text-neutral-600">No linkable audits found for this laboratory</p>
                            </div>
                        </div>
                    </div>

                    {{-- Business Rules Info --}}
                    <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4">
                        <h4 class="text-sm font-medium text-neutral-900 mb-2 flex items-center">
                            <i class="fas fa-info-circle mr-2 text-neutral-600"></i>Linking Rules
                        </h4>
                        <ul class="text-xs text-neutral-700 space-y-1">
                            <li class="flex items-start">
                                <i class="fas fa-check text-neutral-500 mr-2 mt-0.5 text-xs"></i>
                                <span>Both audits must be from the same laboratory</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-neutral-500 mr-2 mt-0.5 text-xs"></i>
                                <span>Cannot create circular references</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-neutral-500 mr-2 mt-0.5 text-xs"></i>
                                <span>Both audits must be within your authorization scope</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end space-x-3 p-6 border-t border-neutral-200 bg-neutral-50 rounded-b-2xl">
                    <button @click="closeLinkModal()" class="px-4 py-2 border border-neutral-200 hover:bg-white text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                        Cancel
                    </button>
                    <button @click="submitLink()"
                            :disabled="loading || !selectedPreviousAuditId"
                            class="px-6 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                        <span x-show="!loading"><i class="fas fa-link mr-2"></i>Link Audits</span>
                        <span x-show="loading" class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>Linking...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Unlink Confirmation Modal --}}
    <div x-show="unlinkModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-neutral-900 bg-opacity-75 transition-opacity" @click="unlinkModalOpen = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md" @click.stop>
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-unlink text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-neutral-900 mb-2">Unlink Audit</h3>
                    <p class="text-sm text-neutral-600 mb-6" x-text="unlinkMessage"></p>
                    <div class="flex justify-center space-x-3">
                        <button @click="unlinkModalOpen = false" class="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                            Cancel
                        </button>
                        <button @click="executeUnlink()" :disabled="loading" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!loading">Unlink</span>
                            <span x-show="loading" class="flex items-center justify-center">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Unlinking...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Link Details Modal --}}
    <div x-show="detailsModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape="detailsModalOpen = false">
        <div class="fixed inset-0 bg-neutral-900 bg-opacity-75 transition-opacity" @click="detailsModalOpen = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl" @click.stop>
                <div class="flex items-center justify-between p-6 border-b border-neutral-200 bg-neutral-50 rounded-t-2xl">
                    <h2 class="text-xl font-semibold text-neutral-900">Audit Linkage Details</h2>
                    <button @click="detailsModalOpen = false" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                        <i class="fas fa-times text-neutral-600"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <p class="text-xs font-medium text-blue-700 uppercase tracking-wider mb-2">Current Audit</p>
                        <p class="text-sm font-medium text-blue-900" x-text="selectedAudit?.laboratory_name"></p>
                        <p class="text-xs text-blue-700 mt-1">Date: <span x-text="formatDate(selectedAudit?.opened_on)"></span></p>
                    </div>
                    <div class="flex items-center justify-center">
                        <i class="fas fa-arrow-down text-neutral-400 text-2xl"></i>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <p class="text-xs font-medium text-green-700 uppercase tracking-wider mb-2">Linked to Previous Audit</p>
                        <p class="text-sm font-medium text-green-900" x-text="selectedAudit?.laboratory_name"></p>
                        <p class="text-xs text-green-700 mt-1">Date: <span x-text="formatDate(selectedAudit?.previous_audit_date)"></span></p>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 p-6 border-t border-neutral-200 bg-neutral-50 rounded-b-2xl">
                    <button @click="detailsModalOpen = false" class="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $bootstrap = [
        'audits' => $currentAudits,
        'stats' => $stats,
        'userContext' => $userContext,
        'csrf' => csrf_token(),
    ];
@endphp

<script>
function auditLinking() {
    const data = @json($bootstrap);
    const ctx = data.userContext || {};

    return {
        // State
        linkModalOpen: false,
        unlinkModalOpen: false,
        detailsModalOpen: false,
        loading: false,
        linkableLoading: false,

        // Data
        audits: data.audits || [],
        linkableAudits: [],
        selectedAudit: null,
        selectedPreviousAuditId: '',
        unlinkAuditId: null,
        unlinkMessage: '',

        // Table state
        search: '',
        linkStatusFilter: '',
        currentPage: 1,
        perPage: 10,

        // Stats
        stats: data.stats || {},

        // User scope
        scopeInfo: {
            role: ctx.is_system_admin ? 'System Administrator'
                 : ctx.is_project_coordinator ? 'Project Coordinator'
                 : ctx.is_country_coordinator ? 'Country Coordinator'
                 : ctx.is_lead_auditor ? 'Lead Auditor'
                 : 'Auditor',
            countries: (ctx.has_global_access ? 'all' : (Array.isArray(ctx.country_ids) ? ctx.country_ids.length : null)),
            labs: (ctx.has_global_access ? 'all' : (Array.isArray(ctx.laboratory_ids) ? ctx.laboratory_ids.length : null))
        },

        // Errors
        errors: {},

        // Computed
        get filteredAudits() {
            let filtered = [...this.audits];

            // Search filter
            if (this.search) {
                const term = this.search.toLowerCase();
                filtered = filtered.filter(audit =>
                    audit.laboratory_name?.toLowerCase().includes(term) ||
                    audit.country_name?.toLowerCase().includes(term)
                );
            }

            // Link status filter
            if (this.linkStatusFilter === 'linked') {
                filtered = filtered.filter(audit => audit.previous_audit_id);
            } else if (this.linkStatusFilter === 'unlinked') {
                filtered = filtered.filter(audit => !audit.previous_audit_id);
            }

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
                for (let i = 1; i <= this.totalPages; i++) {
                    pages.push(i);
                }
            } else {
                let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
                let end = start + maxVisible - 1;

                if (end > this.totalPages) {
                    end = this.totalPages;
                    start = Math.max(1, end - maxVisible + 1);
                }

                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }
            }

            return pages;
        },

        get paginationInfo() {
            const from = this.filteredAudits.length === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
            const to = Math.min(from + this.perPage - 1, this.filteredAudits.length);
            return { from, to };
        },

        // Initialization
        init() {
            this.updateFilters();
        },

        // Modal methods
        async openLinkModal(audit) {
            this.selectedAudit = audit;
            this.selectedPreviousAuditId = '';
            this.errors = {};
            this.linkModalOpen = true;

            // Load linkable audits
            await this.loadLinkableAudits(audit.laboratory_id, audit.id);
        },

        closeLinkModal() {
            this.linkModalOpen = false;
            this.selectedAudit = null;
            this.selectedPreviousAuditId = '';
            this.linkableAudits = [];
            this.errors = {};
        },

        async loadLinkableAudits(laboratoryId, currentAuditId) {
            this.linkableLoading = true;
            this.linkableAudits = [];

            try {
                const url = `/audits/linking/linkable?laboratory_id=${laboratoryId}&current_audit_id=${currentAuditId}`;
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.linkableAudits = result.audits || [];
                } else {
                    this.notify('error', result.error || 'Failed to load linkable audits');
                }
            } catch (error) {
                console.error('Load linkable audits error:', error);
                this.notify('error', `Failed to load audits: ${error.message}`);
            } finally {
                this.linkableLoading = false;
            }
        },

        async submitLink() {
            this.loading = true;
            this.errors = {};

            // Validation
            if (!this.selectedPreviousAuditId) {
                this.errors.previous_audit_id = 'Previous audit is required';
                this.loading = false;
                return;
            }

            try {
                const response = await fetch('/audits/linking/link', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': data.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        audit_id: this.selectedAudit.id,
                        previous_audit_id: this.selectedPreviousAuditId
                    })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    if (result.errors) {
                        this.errors = result.errors;
                    }
                    this.notify('error', result.error || 'Failed to link audits');
                    return;
                }

                // Update local data
                const auditIndex = this.audits.findIndex(a => a.id === this.selectedAudit.id);
                if (auditIndex !== -1) {
                    this.audits[auditIndex].previous_audit_id = this.selectedPreviousAuditId;
                    const prevAudit = this.linkableAudits.find(a => a.id == this.selectedPreviousAuditId);
                    if (prevAudit) {
                        this.audits[auditIndex].previous_audit_date = prevAudit.opened_on;
                    }
                }

                // Update stats
                this.stats.linked_audits++;
                this.stats.unlinked_audits--;
                if (result.data?.progression === 'improved') {
                    this.stats.improved++;
                } else if (result.data?.progression === 'declined') {
                    this.stats.declined++;
                } else if (result.data?.progression === 'maintained') {
                    this.stats.maintained++;
                }
                this.stats.linking_rate = this.stats.total_audits > 0
                    ? ((this.stats.linked_audits / this.stats.total_audits) * 100).toFixed(1)
                    : 0;

                this.notify('success', 'Audits linked successfully');
                this.closeLinkModal();

            } catch (error) {
                console.error('Link submission error:', error);
                this.notify('error', `Operation failed: ${error.message}`);
            } finally {
                this.loading = false;
            }
        },

        confirmUnlink(audit) {
            this.unlinkAuditId = audit.id;
            this.unlinkMessage = `Are you sure you want to unlink the audit for "${audit.laboratory_name}" from its prior audit history?`;
            this.unlinkModalOpen = true;
        },

        async executeUnlink() {
            if (!this.unlinkAuditId) return;

            this.loading = true;

            try {
                const response = await fetch('/audits/linking/unlink', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': data.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        audit_id: this.unlinkAuditId
                    })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    this.notify('error', result.error || 'Failed to unlink audit');
                    return;
                }

                // Update local data
                const auditIndex = this.audits.findIndex(a => a.id === this.unlinkAuditId);
                if (auditIndex !== -1) {
                    this.audits[auditIndex].previous_audit_id = null;
                    this.audits[auditIndex].previous_audit_date = null;
                }

                // Update stats
                this.stats.linked_audits--;
                this.stats.unlinked_audits++;
                this.stats.linking_rate = this.stats.total_audits > 0
                    ? ((this.stats.linked_audits / this.stats.total_audits) * 100).toFixed(1)
                    : 0;

                this.notify('success', 'Audit unlinked successfully');
                this.unlinkModalOpen = false;
                this.unlinkAuditId = null;

            } catch (error) {
                console.error('Unlink error:', error);
                this.notify('error', `Operation failed: ${error.message}`);
            } finally {
                this.loading = false;
            }
        },

        viewLinkDetails(audit) {
            this.selectedAudit = audit;
            this.detailsModalOpen = true;
        },

        // Table methods
        updateFilters() {
            this.currentPage = 1;
        },

        updatePagination() {
            this.currentPage = 1;
        },

        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
        },

        // Utility methods
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
