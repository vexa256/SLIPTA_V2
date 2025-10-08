@extends('layouts.app')

@section('title', 'SLIPTA Sections Management')
@section('breadcrumb', 'SLIPTA Sections')

@section('content')
<div x-data="sectionsManager()" x-cloak class="space-y-6">
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">SLIPTA Sections</h1>
            <p class="text-sm text-neutral-600 mt-1">Manage WHO SLIPTA v3:2023 Section Catalog</p>
        </div>

        <div class="flex items-center gap-3">
            <button @click="initializeAll"
                    x-show="systemTotals.total_sections === 0"
                    class="bg-neutral-900 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-black transition-colors">
                <i class="fas fa-magic mr-2"></i>Initialize All Sections
            </button>

            <button @click="showCreateModal = true"
                    x-show="availableSections && Object.keys(availableSections).length > 0"
                    class="bg-neutral-900 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-black transition-colors">
                <i class="fas fa-plus mr-2"></i>Add Section
            </button>

            <button @click="refreshData"
                    class="hover:bg-neutral-100 px-3 py-2 rounded-xl text-neutral-600 transition-colors">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- System Status Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">Total Sections</p>
                    <p class="text-2xl font-semibold text-neutral-900" x-text="systemTotals.total_sections || 0"></p>
                </div>
                <div class="w-10 h-10 bg-neutral-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-layer-group text-neutral-600"></i>
                </div>
            </div>
            <div class="mt-2">
                <span class="text-xs text-neutral-500">of 12 required</span>
            </div>
        </div>

        <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">Total Questions</p>
                    <p class="text-2xl font-semibold text-neutral-900" x-text="systemTotals.total_questions || 0"></p>
                </div>
                <div class="w-10 h-10 bg-neutral-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-question-circle text-neutral-600"></i>
                </div>
            </div>
            <div class="mt-2">
                <span class="text-xs text-neutral-500">across all sections</span>
            </div>
        </div>

        <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">Actual Points</p>
                    <p class="text-2xl font-semibold text-neutral-900" x-text="systemTotals.total_points_actual || 0"></p>
                </div>
                <div class="w-10 h-10 bg-neutral-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calculator text-neutral-600"></i>
                </div>
            </div>
            <div class="mt-2">
                <span class="text-xs"
                      :class="systemTotals.total_points_actual === 367 ? 'text-green-600' : 'text-orange-600'">
                    of 367 WHO SLIPTA
                </span>
            </div>
        </div>

        <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600">System Status</p>
                    <p class="text-sm font-semibold"
                       :class="systemTotals.system_integrity_valid ? 'text-green-600' : 'text-orange-600'"
                       x-text="systemTotals.system_integrity_valid ? 'Valid' : 'Incomplete'"></p>
                </div>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                     :class="systemTotals.system_integrity_valid ? 'bg-green-100' : 'bg-orange-100'">
                    <i :class="systemTotals.system_integrity_valid ? 'fas fa-check-circle text-green-600' : 'fas fa-exclamation-triangle text-orange-600'"></i>
                </div>
            </div>
            <div class="mt-2">
                <span class="text-xs text-neutral-500">WHO SLIPTA compliance</span>
            </div>
        </div>
    </div>

    <!-- Sections Grid/Table -->
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-neutral-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium text-neutral-900">Section Catalog</h2>
                <div class="flex items-center gap-2">
                    <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-neutral-100' : ''"
                            class="p-2 rounded-lg hover:bg-neutral-100 transition-colors">
                        <i class="fas fa-table text-neutral-600"></i>
                    </button>
                    <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-neutral-100' : ''"
                            class="p-2 rounded-lg hover:bg-neutral-100 transition-colors">
                        <i class="fas fa-th-large text-neutral-600"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div x-show="viewMode === 'table'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="text-left py-3 px-6 text-xs font-medium text-neutral-600 uppercase tracking-wider">Section</th>
                        <th class="text-left py-3 px-6 text-xs font-medium text-neutral-600 uppercase tracking-wider">Title</th>
                        <th class="text-left py-3 px-6 text-xs font-medium text-neutral-600 uppercase tracking-wider">Questions</th>
                        <th class="text-left py-3 px-6 text-xs font-medium text-neutral-600 uppercase tracking-wider">Points</th>
                        <th class="text-left py-3 px-6 text-xs font-medium text-neutral-600 uppercase tracking-wider">Status</th>
                        <th class="text-left py-3 px-6 text-xs font-medium text-neutral-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    <template x-for="section in (sections || [])" :key="section.id">
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-neutral-900 text-white rounded-xl flex items-center justify-center font-semibold">
                                        <span x-text="section.code || '#'"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="text-sm font-medium text-neutral-900" x-text="section.title || 'Untitled'"></div>
                                <div class="text-xs text-neutral-500" x-text="section.description ? section.description.substring(0, 50) + '...' : 'No description'"></div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-sm font-medium text-neutral-900" x-text="section.question_count || 0"></span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-1">
                                    <span class="text-sm font-medium"
                                          :class="section.points_match_slipta ? 'text-green-600' : 'text-orange-600'"
                                          x-text="section.total_points || 0"></span>
                                    <span class="text-xs text-neutral-500">/</span>
                                    <span class="text-xs text-neutral-500" x-text="section.max_points_expected || 0"></span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="section.points_match_slipta ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'">
                                    <span x-text="section.points_match_slipta ? 'Complete' : 'Incomplete'"></span>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <button @click="showSectionDetails(section)"
                                            class="p-1 hover:bg-neutral-100 rounded text-neutral-600 hover:text-neutral-900">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button @click="editSection(section)"
                                            class="p-1 hover:bg-neutral-100 rounded text-neutral-600 hover:text-neutral-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="deleteSection(section)"
                                            class="p-1 hover:bg-red-100 rounded text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <!-- Empty state row -->
                    <tr x-show="!sections || sections.length === 0">
                        <td colspan="6" class="py-12 text-center">
                            <div class="text-neutral-400">
                                <i class="fas fa-layer-group text-4xl mb-4"></i>
                                <p class="text-lg font-medium mb-2">No sections found</p>
                                <p class="text-sm">Create your first SLIPTA section or initialize all sections to get started.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Grid View -->
        <div x-show="viewMode === 'grid'" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="section in (sections || [])" :key="section.id">
                    <div class="border border-neutral-200 rounded-xl p-4 hover:shadow-md transition-all duration-200">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-neutral-900 text-white rounded-xl flex items-center justify-center font-bold text-lg">
                                    <span x-text="section.code || '#'"></span>
                                </div>
                                <div>
                                    <h3 class="font-medium text-neutral-900 text-sm" x-text="section.title || 'Untitled'"></h3>
                                    <p class="text-xs text-neutral-500" x-text="(section.question_count || 0) + ' questions'"></p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                  :class="section.points_match_slipta ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'">
                                <span x-text="section.points_match_slipta ? 'Complete' : 'Incomplete'"></span>
                            </span>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-neutral-600">Points:</span>
                                <span class="font-medium"
                                      :class="section.points_match_slipta ? 'text-green-600' : 'text-orange-600'"
                                      x-text="(section.total_points || 0) + '/' + (section.max_points_expected || 0)"></span>
                            </div>
                            <div class="w-full bg-neutral-200 rounded-full h-2">
                                <div class="bg-neutral-900 h-2 rounded-full transition-all duration-300"
                                     :style="`width: ${section.max_points_expected > 0 ? (section.total_points / section.max_points_expected) * 100 : 0}%`"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="showSectionDetails(section)"
                                    class="flex-1 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-eye mr-1"></i>View
                            </button>
                            <button @click="editSection(section)"
                                    class="px-3 py-2 hover:bg-neutral-100 rounded-lg text-neutral-600 transition-colors">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click="deleteSection(section)"
                                    class="px-3 py-2 hover:bg-red-100 rounded-lg text-red-600 transition-colors">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Empty state for grid -->
                <div x-show="!sections || sections.length === 0" class="col-span-full text-center py-12">
                    <div class="text-neutral-400">
                        <i class="fas fa-layer-group text-4xl mb-4"></i>
                        <p class="text-lg font-medium mb-2">No sections found</p>
                        <p class="text-sm">Create your first SLIPTA section or initialize all sections to get started.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div x-show="showCreateModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-neutral-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div @click.away="showCreateModal = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">

            <div class="px-6 py-4 border-b border-neutral-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-neutral-900">Add SLIPTA Section</h3>
                    <button @click="showCreateModal = false" class="text-neutral-400 hover:text-neutral-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <form @submit.prevent="createSection" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Section Code</label>
                    <select x-model="createForm.code" required
                            class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                        <option value="">Select a section...</option>
                        <template x-for="(section, code) in availableSections" :key="code">
                            <option :value="code" x-text="`Section ${code}: ${section.title} (${section.max_points} points)`"></option>
                        </template>
                    </select>
                </div>

                <div x-show="createForm.code">
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Description (Optional)</label>
                    <textarea x-model="createForm.description" rows="3"
                              class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                              placeholder="Enter optional description..."></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showCreateModal = false"
                            class="flex-1 px-4 py-2 border border-neutral-300 rounded-xl text-neutral-700 hover:bg-neutral-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="!createForm.code"
                            class="flex-1 bg-neutral-900 text-white px-4 py-2 rounded-xl hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        Create Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="showEditModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-neutral-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div @click.away="showEditModal = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">

            <div class="px-6 py-4 border-b border-neutral-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-neutral-900">Edit Section</h3>
                    <button @click="showEditModal = false" class="text-neutral-400 hover:text-neutral-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <form @submit.prevent="updateSection" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Section Code</label>
                    <div class="px-3 py-2 bg-neutral-100 border border-neutral-300 rounded-xl text-neutral-700">
                        Section <span x-text="editForm.code"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Title</label>
                    <input type="text" x-model="editForm.title" readonly
                           class="w-full px-3 py-2 bg-neutral-100 border border-neutral-300 rounded-xl text-neutral-700">
                    <p class="text-xs text-neutral-500 mt-1">Title is locked per WHO SLIPTA specification</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Description</label>
                    <textarea x-model="editForm.description" rows="3"
                              class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                              placeholder="Enter description..."></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showEditModal = false"
                            class="flex-1 px-4 py-2 border border-neutral-300 rounded-xl text-neutral-700 hover:bg-neutral-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 bg-neutral-900 text-white px-4 py-2 rounded-xl hover:bg-black transition-colors">
                        Update Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div x-show="showViewModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-neutral-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div @click.away="showViewModal = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">

            <div class="px-6 py-4 border-b border-neutral-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-neutral-900 text-white rounded-xl flex items-center justify-center font-semibold">
                        <span x-text="currentSection?.code || '#'"></span>
                    </div>
                        <div>
                            <h3 class="text-lg font-semibold text-neutral-900" x-text="currentSection?.title || 'Section Details'"></h3>
                            <p class="text-sm text-neutral-600" x-text="currentSection ? `${currentSection.question_count || 0} questions • ${currentSection.total_points || 0}/${currentSection.max_points_expected || 0} points` : 'Loading...'"></p>
                        </div>
                    </div>
                    <button @click="showViewModal = false" class="text-neutral-400 hover:text-neutral-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <!-- Section Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-neutral-50 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-neutral-900" x-text="currentSection?.question_count || 0"></div>
                        <div class="text-sm text-neutral-600">Questions</div>
                    </div>
                    <div class="bg-neutral-50 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold"
                             :class="currentSection?.points_match_slipta ? 'text-green-600' : 'text-orange-600'"
                             x-text="currentSection?.total_points || 0"></div>
                        <div class="text-sm text-neutral-600">Actual Points</div>
                    </div>
                    <div class="bg-neutral-50 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-neutral-900" x-text="currentSection?.max_points_expected || 0"></div>
                        <div class="text-sm text-neutral-600">Expected Points</div>
                    </div>
                </div>

                <!-- Questions List (if any) -->
                <div x-show="sectionQuestions && sectionQuestions.length > 0">
                    <h4 class="font-medium text-neutral-900 mb-3">Questions in this Section</h4>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <template x-for="question in sectionQuestions" :key="question.id">
                            <div class="border border-neutral-200 rounded-lg p-3">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-neutral-900" x-text="question.q_code"></div>
                                        <div class="text-sm text-neutral-600 mt-1" x-text="question.text.substring(0, 100) + '...'"></div>
                                    </div>
                                    <div class="text-right ml-3">
                                        <div class="text-sm font-medium text-neutral-900" x-text="question.weight + ' pts'"></div>
                                        <div class="text-xs text-neutral-500" x-text="question.sub_question_count + ' sub-q'"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- No questions message -->
                <div x-show="!sectionQuestions || sectionQuestions.length === 0" class="text-center py-8">
                    <i class="fas fa-question-circle text-4xl text-neutral-300 mb-3"></i>
                    <p class="text-neutral-600">No questions added to this section yet.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-neutral-900 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 flex items-center space-x-4 shadow-2xl border border-neutral-200">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-neutral-900 border-t-transparent"></div>
            <span class="text-neutral-900 font-medium">Processing...</span>
        </div>
    </div>
</div>

<script>
function sectionsManager() {
    return {
        // State
        loading: false,
        sections: @json($sections ?? []),
        systemTotals: @json($systemTotals ?? []),
        availableSections: @json($availableSections ?? []),
        viewMode: 'table',

        // Modals
        showCreateModal: false,
        showEditModal: false,
        showViewModal: false,

        // Forms
        createForm: {
            code: '',
            description: ''
        },
        editForm: {
            id: null,
            code: '',
            title: '',
            description: ''
        },

        // View data
        currentSection: null,
        sectionQuestions: [],
        sectionAuditStats: null,

        // Initialize
        init() {
            // Ensure CSRF token exists
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.warn('CSRF token not found in page head');
            }

            // Validate initial data
            if (!Array.isArray(this.sections)) {
                console.warn('Sections data is not an array, initializing as empty');
                this.sections = [];
            }

            if (!this.systemTotals || typeof this.systemTotals !== 'object') {
                console.warn('System totals data is invalid, initializing with defaults');
                this.systemTotals = {
                    total_sections: 0,
                    total_questions: 0,
                    total_points_actual: 0,
                    total_points_expected: 367,
                    system_integrity_valid: false
                };
            }

            if (!this.availableSections || typeof this.availableSections !== 'object') {
                console.warn('Available sections data is invalid, initializing as empty');
                this.availableSections = {};
            }

            // Load initial data if empty
            if (!this.sections.length && this.systemTotals.total_sections === 0) {
                console.log('No sections found, loading data...');
                this.refreshData();
            }
        },

        // Refresh all data
        async refreshData() {
            this.loading = true;
            try {
                const response = await fetch('/slipta/sections/data', {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.sections = result.sections || [];
                    this.systemTotals = result.system_totals || {};
                    this.availableSections = result.available_sections || {};
                } else {
                    throw new Error(result.message || 'Failed to refresh data');
                }
            } catch (error) {
                console.error('Error refreshing data:', error);
                this.showNotification('Failed to refresh data: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        // Create section
        async createSection() {
            if (!this.createForm.code) {
                this.showNotification('Please select a section code', 'error');
                return;
            }

            this.loading = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }

                const response = await fetch('/slipta/sections', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.createForm)
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.showNotification(result.message, 'success');
                    this.showCreateModal = false;
                    this.resetCreateForm();
                    await this.refreshData();
                } else {
                    this.handleErrors(result.errors || result.message);
                }
            } catch (error) {
                console.error('Error creating section:', error);
                this.showNotification('Failed to create section: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        // Edit section
        editSection(section) {
            this.editForm = {
                id: section.id,
                code: section.code,
                title: section.title,
                description: section.description
            };
            this.showEditModal = true;
        },

        // Update section
        async updateSection() {
            if (!this.editForm.id) {
                this.showNotification('No section selected for update', 'error');
                return;
            }

            this.loading = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }

                const response = await fetch(`/slipta/sections/${this.editForm.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: this.editForm.title,
                        description: this.editForm.description
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.showNotification(result.message, 'success');
                    this.showEditModal = false;
                    await this.refreshData();
                } else {
                    this.handleErrors(result.errors || result.message);
                }
            } catch (error) {
                console.error('Error updating section:', error);
                this.showNotification('Failed to update section: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        // View section details
        async showSectionDetails(section) {
            this.currentSection = section;
            this.loading = true;
            this.showViewModal = true;
            this.sectionQuestions = [];
            this.sectionAuditStats = null;

            try {
                const response = await fetch(`/slipta/sections/${section.id}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.sectionQuestions = result.questions || [];
                    this.sectionAuditStats = result.audit_stats || null;
                } else {
                    throw new Error(result.message || 'Failed to load section details');
                }
            } catch (error) {
                console.error('Error loading section details:', error);
                this.showNotification('Failed to load section details: ' + error.message, 'error');
                this.sectionQuestions = [];
                this.sectionAuditStats = null;
            } finally {
                this.loading = false;
            }
        },

        // Delete section
        async deleteSection(section) {
            if (!section || !section.id) {
                this.showNotification('Invalid section data', 'error');
                return;
            }

            if (!confirm(`Are you sure you want to delete Section ${section.code}: ${section.title}?\n\nThis action cannot be undone.`)) {
                return;
            }

            this.loading = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }

                const response = await fetch(`/slipta/sections/${section.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.showNotification(result.message, 'success');
                    await this.refreshData();
                } else {
                    throw new Error(result.message || 'Failed to delete section');
                }
            } catch (error) {
                console.error('Error deleting section:', error);
                this.showNotification('Failed to delete section: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        // Initialize all sections
        async initializeAll() {
            if (!confirm('Initialize all 12 WHO SLIPTA sections?\n\nThis will create sections based on the official WHO SLIPTA v3:2023 specification.\n\nThis action should only be done once on a new system.')) {
                return;
            }

            this.loading = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }

                const response = await fetch('/slipta/sections/initialize-all', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.showNotification(result.message, 'success');
                    await this.refreshData();
                } else {
                    throw new Error(result.message || 'Failed to initialize sections');
                }
            } catch (error) {
                console.error('Error initializing sections:', error);
                this.showNotification('Failed to initialize sections: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        // Utility methods
        resetCreateForm() {
            this.createForm = { code: '', description: '' };
        },

        handleErrors(errors) {
            if (typeof errors === 'string') {
                this.showNotification(errors, 'error');
                return;
            }

            if (errors && typeof errors === 'object') {
                // Handle Laravel validation errors
                Object.values(errors).flat().forEach(error => {
                    this.showNotification(error, 'error');
                });
            } else {
                this.showNotification('An unknown error occurred', 'error');
            }
        },

        showNotification(message, type = 'info') {
            try {
                // Create toast notification
                const toast = document.createElement('div');
                const bgColor = {
                    success: 'bg-green-600 text-white',
                    error: 'bg-red-600 text-white',
                    warning: 'bg-yellow-600 text-white',
                    info: 'bg-neutral-700 text-white'
                }[type] || 'bg-neutral-700 text-white';

                const icon = {
                    success: 'check-circle',
                    error: 'exclamation-circle',
                    warning: 'exclamation-triangle',
                    info: 'info-circle'
                }[type] || 'info-circle';

                toast.className = `fixed bottom-4 right-4 flex items-center p-4 rounded-xl shadow-lg transform transition-all duration-300 translate-x-full z-50 max-w-sm ${bgColor}`;

                toast.innerHTML = `
                    <i class="fas fa-${icon} mr-3 text-lg flex-shrink-0"></i>
                    <span class="font-medium flex-1">${message || 'Unknown error'}</span>
                    <button onclick="this.parentElement.remove()" class="ml-4 hover:opacity-75 transition-opacity flex-shrink-0">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                document.body.appendChild(toast);

                // Animate in
                setTimeout(() => toast.classList.remove('translate-x-full'), 100);

                // Auto remove
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.classList.add('translate-x-full');
                        setTimeout(() => {
                            if (toast.parentElement) {
                                toast.remove();
                            }
                        }, 300);
                    }
                }, 5000);

            } catch (error) {
                console.error('Error showing notification:', error);
                // Fallback to alert if toast fails
                alert(message || 'An error occurred');
            }
        }
    }
}
</script>
@endsection
