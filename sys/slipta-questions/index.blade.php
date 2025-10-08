@extends('layouts.app')

@section('title', 'SLIPTA Question Management')
@section('breadcrumb', 'Question Management')

@section('content')
<div class="min-h-screen bg-neutral-50" x-data="sliptaQuestionsApp()" x-init="init()">
    <!-- Page Header -->
    <div class="bg-white border-b border-neutral-200 shadow-sm">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-neutral-900">SLIPTA Question Management</h1>
                    <p class="text-sm text-neutral-600 mt-1">WHO SLIPTA v3:2023 - 367-point system integrity</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- System Integrity Status -->
                    <div class="flex items-center space-x-2 bg-neutral-50 px-4 py-2 rounded-xl border border-neutral-200">
                        <div class="flex items-center space-x-1">
                            <template x-for="i in 5" :key="i">
                                <i class="fas fa-star text-sm transition-colors"
                                   :class="systemTotals.system_integrity_valid ? 'text-neutral-900' : 'text-neutral-300'"></i>
                            </template>
                        </div>
                        <span class="text-sm font-medium text-neutral-700"
                              x-text="systemTotals.total_points_actual + '/' + systemTotals.total_points_expected + ' Points'"></span>
                    </div>

                    <!-- Progress Indicator -->
                    <div class="flex items-center space-x-3">
                        <div class="w-32 bg-neutral-200 rounded-full h-2.5">
                            <div class="bg-neutral-900 h-2.5 rounded-full transition-all duration-500 ease-out"
                                 :style="`width: ${systemTotals.completion_percentage}%`"></div>
                        </div>
                        <span class="text-sm font-medium text-neutral-600" x-text="systemTotals.completion_percentage + '%'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-6">
        <!-- Step 1: Section Selection -->
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-neutral-900">Step 1: Select SLIPTA Section</h2>
                <span class="text-xs text-neutral-500 bg-neutral-100 px-2 py-1 rounded-lg">Required First</span>
            </div>

            <!-- Section Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <template x-for="section in sections" :key="section.id">
                    <div class="border border-neutral-200 rounded-xl p-4 cursor-pointer transition-all duration-200 hover:shadow-md"
                         :class="selectedSection && selectedSection.id === section.id ? 'border-neutral-900 bg-neutral-50' : 'hover:bg-neutral-50'"
                         @click="selectSection(section.id)">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-semibold text-neutral-900" x-text="`Section ${section.code}`"></h3>
                            <div class="flex items-center space-x-1">
                                <i class="fas fa-check-circle text-sm"
                                   :class="section.is_complete ? 'text-green-600' : 'text-neutral-300'"></i>
                            </div>
                        </div>
                        <p class="text-sm text-neutral-600 mb-3 line-clamp-2" x-text="section.title"></p>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-neutral-500" x-text="`${section.question_count} Questions`"></span>
                            <span class="font-medium"
                                  :class="section.is_complete ? 'text-green-600' : 'text-neutral-700'"
                                  x-text="`${section.total_points}/${section.max_points_expected}`"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Step 2: Question Management -->
        <div x-show="selectedSection" x-transition.opacity.scale.origin.top>

            <!-- Section Header & Controls -->
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900">
                            <span x-text="selectedSection ? `Section ${selectedSection.code}: ${selectedSection.title}` : ''"></span>
                        </h2>
                        <div class="flex items-center space-x-4 text-sm text-neutral-600 mt-1" x-show="selectedSection">
                            <span x-text="`${selectedSection.question_count} Questions`"></span>
                            <span x-text="`${selectedSection.total_points}/${selectedSection.max_points_expected} Points`"></span>
                            <span x-show="selectedSection.points_remaining > 0" class="text-orange-600"
                                  x-text="`${selectedSection.points_remaining} Points Remaining`"></span>
                            <span x-show="selectedSection.is_complete" class="text-green-600 font-medium">✓ Complete</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <!-- Search -->
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-neutral-400 text-sm"></i>
                            <input type="text" x-model="searchQuery" @input.debounce.300ms="searchQuestions()"
                                   class="pl-10 pr-4 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900"
                                   placeholder="Search questions...">
                        </div>

                        <!-- Create Question Button -->
                        <button @click="showCreateForm = true" :disabled="!selectedSection"
                                class="bg-neutral-900 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-black transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-plus mr-2"></i>Create Question
                        </button>

                        <!-- Bulk Import Button -->
                        <button @click="showImportForm = true" :disabled="!selectedSection"
                                class="border border-neutral-200 px-4 py-2 rounded-xl text-sm font-medium hover:bg-neutral-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-upload mr-2"></i>Bulk Import
                        </button>
                    </div>
                </div>
            </div>

            <!-- Questions List with Inline Sub-Questions -->
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-6 mb-6" x-show="selectedSection">
                <div x-show="questions.length === 0" class="text-center py-12">
                    <i class="fas fa-question-circle text-4xl text-neutral-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-neutral-900 mb-2">No Questions Found</h3>
                    <p class="text-neutral-600 mb-6">Create your first question or import from WHO SLIPTA.</p>
                    <button @click="showCreateForm = true"
                            class="bg-neutral-900 text-white px-6 py-3 rounded-xl font-medium hover:bg-black transition-colors">
                        Create First Question
                    </button>
                </div>

                <!-- Questions Cards with Sub-Questions Visible -->
                <div x-show="questions.length > 0" class="space-y-4">
                    <template x-for="question in questions" :key="question.id">
                        <div class="border border-neutral-200 rounded-xl p-5 hover:shadow-md transition-all duration-200"
                             :class="selectedQuestion && selectedQuestion.id === question.id ? 'border-neutral-900 bg-neutral-50' : ''">

                            <!-- Question Header -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <span class="text-sm font-bold text-neutral-900 bg-neutral-100 px-2 py-1 rounded"
                                              x-text="question.q_code"></span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="question.weight === 3 ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-800'"
                                              x-text="question.weight + ' pts'"></span>
                                        <span x-show="question.has_audit_responses"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>In Use
                                        </span>
                                        <span x-show="question.sub_question_count > 0"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-list mr-1"></i><span x-text="question.sub_question_count"></span> Sub-questions
                                        </span>
                                    </div>
                                    <p class="text-sm text-neutral-900 leading-relaxed" x-text="question.text"></p>
                                    <p x-show="question.iso_reference" class="text-xs text-neutral-500 mt-2" x-text="question.iso_reference"></p>
                                </div>

                                <div class="flex items-center space-x-2 ml-4">
                                    <button @click="viewQuestionDetails(question)"
                                            class="text-neutral-600 hover:text-neutral-900 p-2 rounded-lg hover:bg-neutral-100 transition-colors"
                                            title="View Full Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button @click="editQuestion(question)" :disabled="!question.can_edit_weight && !question.can_edit_code"
                                            class="text-neutral-600 hover:text-neutral-900 p-2 rounded-lg hover:bg-neutral-100 transition-colors disabled:opacity-50"
                                            title="Edit Question">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="deleteQuestion(question)" :disabled="!question.can_delete"
                                            class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors disabled:opacity-50"
                                            title="Delete Question">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Sub-Questions Preview (Always Visible if Present) -->
                            <div x-show="question.sub_question_count > 0" class="mt-4 pt-4 border-t border-neutral-200">
                                <button @click="toggleSubQuestions(question.id)"
                                        class="flex items-center space-x-2 text-sm font-medium text-neutral-700 hover:text-neutral-900 mb-3">
                                    <i class="fas fa-chevron-down transition-transform duration-200"
                                       :class="expandedQuestions.includes(question.id) ? 'rotate-180' : ''"></i>
                                    <span x-text="expandedQuestions.includes(question.id) ? 'Hide Sub-questions' : 'Show ' + question.sub_question_count + ' Sub-questions'"></span>
                                </button>

                                <div x-show="expandedQuestions.includes(question.id)"
                                     x-transition.opacity.duration.200ms
                                     class="space-y-2">
                                    <template x-for="(sub, idx) in getQuestionSubQuestions(question.id)" :key="sub.id">
                                        <div class="bg-neutral-50 rounded-lg p-3 border border-neutral-200">
                                            <div class="flex items-start space-x-3">
                                                <span class="text-xs font-bold text-neutral-500 bg-white px-2 py-1 rounded" x-text="sub.sub_code"></span>
                                                <p class="text-sm text-neutral-700 flex-1" x-text="sub.text"></p>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="!getQuestionSubQuestions(question.id).length" class="text-center py-2">
                                        <span class="text-xs text-neutral-500">Loading sub-questions...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Audit Stats Preview (if available) -->
                            <div x-show="question.audit_response_count > 0" class="mt-4 pt-4 border-t border-neutral-200">
                                <div class="flex items-center space-x-4 text-xs">
                                    <span class="text-neutral-500">Audit Responses:</span>
                                    <span class="font-medium text-neutral-700" x-text="question.audit_response_count + ' total'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Question Modal -->
    <div x-show="showCreateForm || showEditForm"
         x-transition.opacity
         class="fixed inset-0 bg-neutral-900 bg-opacity-75 flex items-center justify-center z-50 p-4">

        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
             @click.away="closeModals()">
            <div class="p-6 border-b border-neutral-200">
                <h2 class="text-xl font-semibold text-neutral-900" x-text="showEditForm ? 'Edit Question' : 'Create New Question'"></h2>
            </div>

            <form @submit.prevent="showEditForm ? updateQuestion() : createQuestion()" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Question Code -->
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-2">Question Code *</label>
                        <input type="text" x-model="questionForm.q_code" required
                               :disabled="showEditForm && editingQuestion?.has_audit_responses"
                               class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900 disabled:bg-neutral-50 disabled:cursor-not-allowed">
                        <p x-show="showEditForm && editingQuestion?.has_audit_responses" class="text-xs text-orange-600 mt-1">
                            Cannot change - audit responses exist
                        </p>
                    </div>

                    <!-- Weight -->
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-2">Weight (Points) *</label>
                        <select x-model="questionForm.weight" required
                                :disabled="showEditForm && editingQuestion?.has_audit_responses"
                                class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900 disabled:bg-neutral-50 disabled:cursor-not-allowed">
                            <option value="">Select Weight</option>
                            <option value="2">2 Points</option>
                            <option value="3">3 Points</option>
                        </select>
                        <p x-show="showEditForm && editingQuestion?.has_audit_responses" class="text-xs text-orange-600 mt-1">
                            Cannot change - audit responses exist
                        </p>
                    </div>

                    <!-- ISO Reference -->
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-2">ISO Reference</label>
                        <input type="text" x-model="questionForm.iso_reference"
                               placeholder="e.g., ISO15189:2022 Clause 5.1"
                               class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                    </div>

                    <!-- Requires All Subs -->
                    <div class="flex items-center">
                        <input type="checkbox" x-model="questionForm.requires_all_subs_for_yes"
                               class="h-4 w-4 text-neutral-900 focus:ring-neutral-900/10 border-neutral-200 rounded">
                        <label class="ml-2 text-sm font-medium text-neutral-700">
                            Requires all sub-questions for Yes response
                        </label>
                    </div>
                </div>

                <!-- Question Text -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Question Text *</label>
                    <textarea x-model="questionForm.text" required rows="4"
                              placeholder="Enter the complete question text as per WHO SLIPTA..."
                              class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900"></textarea>
                </div>

                <!-- Sub-Questions -->
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-4">
                        <label class="text-sm font-medium text-neutral-700">Sub-Questions</label>
                        <button type="button" @click="addSubQuestion()"
                                class="text-sm text-neutral-600 hover:text-neutral-900 px-3 py-1 rounded-lg hover:bg-neutral-50">
                            <i class="fas fa-plus mr-1"></i>Add Sub-Question
                        </button>
                    </div>

                    <div x-show="questionForm.sub_questions.length === 0" class="text-center py-6 border-2 border-dashed border-neutral-200 rounded-xl">
                        <p class="text-sm text-neutral-500">No sub-questions added</p>
                        <button type="button" @click="addSubQuestion()" class="text-sm text-neutral-600 hover:text-neutral-900 mt-2">
                            Click to add the first sub-question
                        </button>
                    </div>

                    <div x-show="questionForm.sub_questions.length > 0" class="space-y-3">
                        <template x-for="(subQuestion, index) in questionForm.sub_questions" :key="index">
                            <div class="border border-neutral-200 rounded-xl p-4">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-3">
                                        <div>
                                            <input type="text" x-model="subQuestion.sub_code"
                                                   placeholder="e.g., a, b, 1.1.a"
                                                   class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                        </div>
                                        <div class="md:col-span-3">
                                            <input type="text" x-model="subQuestion.text"
                                                   placeholder="Sub-question text..."
                                                   class="w-full px-3 py-2 border border-neutral-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                        </div>
                                    </div>
                                    <button type="button" @click="removeSubQuestion(index)"
                                            class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-neutral-200">
                    <button type="button" @click="closeModals()"
                            class="px-4 py-2 border border-neutral-200 rounded-xl text-sm font-medium hover:bg-neutral-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="formLoading"
                            class="bg-neutral-900 text-white px-6 py-2 rounded-xl text-sm font-medium hover:bg-black transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!formLoading" x-text="showEditForm ? 'Update Question' : 'Create Question'"></span>
                        <span x-show="formLoading" class="flex items-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Import Modal -->
    <div x-show="showImportForm"
         x-transition.opacity
         class="fixed inset-0 bg-neutral-900 bg-opacity-75 flex items-center justify-center z-50 p-4">

        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full" @click.away="closeModals()">
            <div class="p-6 border-b border-neutral-200">
                <h2 class="text-xl font-semibold text-neutral-900">Bulk Import Questions</h2>
                <p class="text-sm text-neutral-600 mt-1">Import questions from WHO SLIPTA specification</p>
            </div>

            <div class="p-6">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-neutral-700 mb-2">Import Data (JSON Format)</label>
                    <textarea x-model="importData" rows="10" placeholder='[{"q_code": "1.1", "weight": 2, "text": "Question text...", "sub_questions": [{"sub_code": "a", "text": "Sub-question text"}]}]'
                              class="w-full px-3 py-2 border border-neutral-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900 font-mono text-xs"></textarea>
                </div>

                <!-- Import Actions -->
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" @click="closeModals()"
                            class="px-4 py-2 border border-neutral-200 rounded-xl text-sm font-medium hover:bg-neutral-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" @click="performImport()" :disabled="!importData || formLoading"
                            class="bg-neutral-900 text-white px-6 py-2 rounded-xl text-sm font-medium hover:bg-black transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!formLoading">Import Questions</span>
                        <span x-show="formLoading" class="flex items-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Importing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" class="fixed inset-0 bg-neutral-900 bg-opacity-75 flex items-center justify-center z-50 backdrop-blur-sm"
         x-transition.opacity>
        <div class="bg-white rounded-2xl p-8 flex items-center space-x-4 shadow-2xl border border-neutral-200">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-neutral-900 border-t-transparent"></div>
            <span class="text-neutral-900 font-medium">Loading...</span>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
</div>

<script>
function sliptaQuestionsApp() {
    return {
        // State Management
        loading: false,
        formLoading: false,
        searchQuery: '',
        expandedQuestions: [],
        subQuestionsCache: {},

        // Data Collections
        sections: @json($sections ?? []),
        selectedSection: @json($selectedSection ?? null),
        questions: @json($questions ?? []),
        selectedQuestion: @json($selectedQuestion ?? null),
        subQuestions: @json($subQuestions ?? []),
        auditStats: @json($auditStats ?? null),
        systemTotals: @json($systemTotals ?? []),

        // Modal States
        showCreateForm: false,
        showEditForm: false,
        showImportForm: false,

        // Form Data
        questionForm: {
            q_code: '',
            weight: '',
            text: '',
            iso_reference: '',
            requires_all_subs_for_yes: false,
            sub_questions: []
        },
        editingQuestion: null,
        importData: '',

        // Initialization
        init() {
            if (this.selectedSection) {
                this.selectSection(this.selectedSection.id);
            }

            if (this.selectedQuestion) {
                this.expandedQuestions.push(this.selectedQuestion.id);
                this.loadSubQuestions(this.selectedQuestion.id);
            }

            this.setupCSRF();
        },

        setupCSRF() {
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                window.csrfToken = token.getAttribute('content');
            }
        },

        // Section Management
        async selectSection(sectionId) {
            if (!sectionId) return;

            try {
                this.loading = true;

                const response = await fetch(`/slipta/questions?section_id=${sectionId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.selectedSection = data.selected_section;
                    this.questions = data.questions || [];
                    this.systemTotals = data.system_totals || this.systemTotals;

                    // Clear previous state
                    this.selectedQuestion = null;
                    this.subQuestions = [];
                    this.auditStats = null;
                    this.expandedQuestions = [];
                    this.subQuestionsCache = {};

                    this.showToast('Section loaded successfully', 'success');
                } else {
                    this.showToast(data.message || 'Failed to load section', 'error');
                }
            } catch (error) {
                console.error('Error selecting section:', error);
                this.showToast('Error loading section', 'error');
            } finally {
                this.loading = false;
            }
        },

        // Sub-Questions Management
        toggleSubQuestions(questionId) {
            const index = this.expandedQuestions.indexOf(questionId);
            if (index > -1) {
                this.expandedQuestions.splice(index, 1);
            } else {
                this.expandedQuestions.push(questionId);
                if (!this.subQuestionsCache[questionId]) {
                    this.loadSubQuestions(questionId);
                }
            }
        },

        async loadSubQuestions(questionId) {
            try {
                const response = await fetch(`/slipta/questions/get-details/${questionId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.subQuestionsCache[questionId] = data.sub_questions || [];
                }
            } catch (error) {
                console.error('Error loading sub-questions:', error);
            }
        },

        getQuestionSubQuestions(questionId) {
            return this.subQuestionsCache[questionId] || [];
        },

        // Question Detail View
        async viewQuestionDetails(question) {
            try {
                const response = await fetch(`/slipta/questions/get-details/${question.id}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.selectedQuestion = data.selected_question;
                    this.subQuestions = data.sub_questions || [];
                    this.auditStats = data.audit_stats;

                    // Auto-expand sub-questions
                    if (!this.expandedQuestions.includes(question.id)) {
                        this.expandedQuestions.push(question.id);
                        this.subQuestionsCache[question.id] = data.sub_questions || [];
                    }
                }
            } catch (error) {
                console.error('Error loading question details:', error);
                this.showToast('Error loading question details', 'error');
            }
        },

        async searchQuestions() {
            if (!this.selectedSection) return;

            try {
                const response = await fetch(`/slipta/questions/get-section/${this.selectedSection.id}?search=${encodeURIComponent(this.searchQuery)}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.questions = data.questions || [];
                }
            } catch (error) {
                console.error('Error searching questions:', error);
            }
        },

        // Create Question
        resetQuestionForm() {
            this.questionForm = {
                q_code: '',
                weight: '',
                text: '',
                iso_reference: '',
                requires_all_subs_for_yes: false,
                sub_questions: []
            };
            this.editingQuestion = null;
        },

        async createQuestion() {
            if (!this.selectedSection) {
                this.showToast('Please select a section first', 'error');
                return;
            }

            try {
                this.formLoading = true;

                const formData = {
                    ...this.questionForm,
                    section_id: this.selectedSection.id
                };

                const response = await fetch('/slipta/questions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    this.questions = data.questions || this.questions;
                    this.systemTotals = data.system_totals || this.systemTotals;

                    this.showToast(data.message || 'Question created successfully', 'success');
                    this.closeModals();

                    // Auto-load sub-questions for new question
                    if (data.created_question_id && this.questionForm.sub_questions.length > 0) {
                        await this.loadSubQuestions(data.created_question_id);
                    }
                } else {
                    if (data.errors) {
                        this.handleValidationErrors(data.errors);
                    } else {
                        this.showToast(data.message || 'Failed to create question', 'error');
                    }
                }
            } catch (error) {
                console.error('Error creating question:', error);
                this.showToast('Error creating question', 'error');
            } finally {
                this.formLoading = false;
            }
        },

        // Edit Question
        async editQuestion(question) {
            this.editingQuestion = question;
            this.questionForm = {
                q_code: question.q_code || '',
                weight: question.weight || '',
                text: question.text || '',
                iso_reference: question.iso_reference || '',
                requires_all_subs_for_yes: question.requires_all_subs_for_yes || false,
                sub_questions: []
            };

            // Load existing sub-questions
            if (question.sub_question_count > 0) {
                await this.loadSubQuestions(question.id);
                const subs = this.subQuestionsCache[question.id] || [];
                this.questionForm.sub_questions = subs.map(sub => ({
                    sub_code: sub.sub_code,
                    text: sub.text
                }));
            }

            this.showEditForm = true;
        },

        async updateQuestion() {
            if (!this.editingQuestion) return;

            try {
                this.formLoading = true;

                const response = await fetch(`/slipta/questions/${this.editingQuestion.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify(this.questionForm)
                });

                const data = await response.json();

                if (data.success) {
                    this.selectedQuestion = data.selected_question || this.selectedQuestion;
                    this.questions = data.questions || this.questions;
                    this.systemTotals = data.system_totals || this.systemTotals;

                    // Update sub-questions cache
                    if (data.sub_questions) {
                        this.subQuestionsCache[this.editingQuestion.id] = data.sub_questions;
                    }

                    this.showToast(data.message || 'Question updated successfully', 'success');
                    this.closeModals();
                } else {
                    if (data.errors) {
                        this.handleValidationErrors(data.errors);
                    } else {
                        this.showToast(data.message || 'Failed to update question', 'error');
                    }
                }
            } catch (error) {
                console.error('Error updating question:', error);
                this.showToast('Error updating question', 'error');
            } finally {
                this.formLoading = false;
            }
        },

        // Delete Question
        async deleteQuestion(question) {
            if (!question.can_delete) {
                this.showToast('Cannot delete question - audit responses exist', 'warning');
                return;
            }

            if (!confirm(`Are you sure you want to delete question "${question.q_code}"? This action cannot be undone.`)) {
                return;
            }

            try {
                this.loading = true;

                const response = await fetch(`/slipta/questions/${question.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.questions = data.questions || this.questions.filter(q => q.id !== question.id);
                    this.systemTotals = data.system_totals || this.systemTotals;

                    // Clear cache and selection
                    delete this.subQuestionsCache[question.id];
                    const idx = this.expandedQuestions.indexOf(question.id);
                    if (idx > -1) this.expandedQuestions.splice(idx, 1);

                    if (this.selectedQuestion && this.selectedQuestion.id === question.id) {
                        this.selectedQuestion = null;
                        this.subQuestions = [];
                        this.auditStats = null;
                    }

                    this.showToast(data.message || 'Question deleted successfully', 'success');
                } else {
                    this.showToast(data.message || 'Failed to delete question', 'error');
                }
            } catch (error) {
                console.error('Error deleting question:', error);
                this.showToast('Error deleting question', 'error');
            } finally {
                this.loading = false;
            }
        },

        // Sub-Question Management
        addSubQuestion() {
            this.questionForm.sub_questions.push({
                sub_code: '',
                text: ''
            });
        },

        removeSubQuestion(index) {
            this.questionForm.sub_questions.splice(index, 1);
        },

        // Bulk Import
        async performImport() {
            if (!this.selectedSection) {
                this.showToast('Please select a section first', 'error');
                return;
            }

            if (!this.importData.trim()) {
                this.showToast('Please enter import data', 'error');
                return;
            }

            try {
                this.formLoading = true;

                const questions = JSON.parse(this.importData);

                const response = await fetch('/slipta/questions/bulk-import', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify({
                        section_id: this.selectedSection.id,
                        questions: questions
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.questions = data.questions || this.questions;
                    this.systemTotals = data.system_totals || this.systemTotals;

                    this.showToast(`Successfully imported ${data.created_count || 0} questions`, 'success');
                    this.closeModals();
                } else {
                    if (data.errors) {
                        this.handleValidationErrors(data.errors);
                    } else {
                        this.showToast(data.message || 'Import failed', 'error');
                    }
                }
            } catch (error) {
                console.error('Import error:', error);
                this.showToast('Invalid JSON format or import error', 'error');
            } finally {
                this.formLoading = false;
            }
        },

        // Modal Management
        closeModals() {
            this.showCreateForm = false;
            this.showEditForm = false;
            this.showImportForm = false;
            this.resetQuestionForm();
            this.importData = '';
        },

        // Utility Functions
        handleValidationErrors(errors) {
            const errorMessages = [];
            for (const field in errors) {
                if (errors[field] && Array.isArray(errors[field])) {
                    errorMessages.push(...errors[field]);
                }
            }

            if (errorMessages.length > 0) {
                this.showToast(errorMessages.join('. '), 'error');
            }
        },

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            const bgColor = {
                success: 'bg-neutral-900 text-white',
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

            toast.className = `flex items-center p-4 rounded-2xl shadow-lg transform transition-all duration-300 translate-x-full border border-neutral-200 ${bgColor}`;

            toast.innerHTML = `
                <i class="fas fa-${icon} mr-3 text-lg"></i>
                <span class="font-medium">${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-4 hover:opacity-75 transition-opacity">
                    <i class="fas fa-times"></i>
                </button>
            `;

            document.getElementById('toast-container').appendChild(toast);

            setTimeout(() => toast.classList.remove('translate-x-full'), 100);

            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.parentElement?.removeChild(toast), 300);
            }, 5000);
        }
    }
}
</script>
@endsection
