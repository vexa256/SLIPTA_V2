@extends('layouts.app')

@section('title', 'SLIPTA Audit - Response Entry')

@section('breadcrumb', 'Audit Response Entry')

@section('content')
<div x-data="auditResponseApp()" x-init="init()" class="space-y-4">

    {{-- Statistics & Analytics Panel with Color Coding --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {{-- Overall Score Card --}}
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 opacity-10 -mr-8 -mt-8">
                <i class="fas fa-trophy text-8xl"
                   :class="{
                       'text-neutral-900': scores.percentage >= 75,
                       'text-amber-500': scores.percentage >= 55 && scores.percentage < 75,
                       'text-red-500': scores.percentage < 55
                   }"></i>
            </div>
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-neutral-600">Overall Score</h3>
                    <i class="fas fa-trophy text-neutral-400"></i>
                </div>
                <p class="text-3xl font-bold transition-colors"
                   :class="{
                       'text-neutral-900': scores.percentage >= 75,
                       'text-amber-600': scores.percentage >= 55 && scores.percentage < 75,
                       'text-red-600': scores.percentage < 55
                   }"
                   x-text="scores.percentage + '%'"></p>
                <p class="text-xs text-neutral-500 mt-1" x-text="scores.total_earned + ' / ' + scores.adjusted_denominator + ' pts'"></p>
                <div class="mt-3 flex items-center">
                    <template x-for="i in 5" :key="i">
                        <i class="fas fa-star text-sm transition-all duration-300"
                           :class="i <= scores.star_level ? 'text-amber-400' : 'text-neutral-200'"></i>
                    </template>
                    <span class="ml-2 text-xs font-semibold px-2 py-0.5 rounded-md"
                          :class="{
                              'bg-neutral-100 text-neutral-700': scores.star_level >= 4,
                              'bg-amber-100 text-amber-700': scores.star_level >= 2 && scores.star_level < 4,
                              'bg-red-100 text-red-700': scores.star_level < 2
                          }"
                          x-text="scores.star_level + ' Stars'"></span>
                </div>
            </div>
        </div>

        {{-- Progress Card --}}
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-600">Progress</h3>
                <i class="fas fa-chart-line"
                   :class="{
                       'text-neutral-900': progress.percentage_non_na >= 75,
                       'text-amber-500': progress.percentage_non_na >= 50 && progress.percentage_non_na < 75,
                       'text-red-500': progress.percentage_non_na < 50
                   }"></i>
            </div>
            <p class="text-3xl font-bold transition-colors"
               :class="{
                   'text-neutral-900': progress.percentage_non_na >= 75,
                   'text-amber-600': progress.percentage_non_na >= 50 && progress.percentage_non_na < 75,
                   'text-red-600': progress.percentage_non_na < 50
               }"
               x-text="progress.percentage_non_na + '%'"></p>
            <p class="text-xs text-neutral-500 mt-1" x-text="progress.answered_non_na + ' / ' + progress.answerable_questions"></p>
            <div class="mt-3 w-full bg-neutral-100 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all duration-500"
                     :class="{
                         'bg-neutral-900': progress.percentage_non_na >= 75,
                         'bg-amber-500': progress.percentage_non_na >= 50 && progress.percentage_non_na < 75,
                         'bg-red-500': progress.percentage_non_na < 50
                     }"
                     :style="`width: ${progress.percentage_non_na}%`"></div>
            </div>
        </div>

        {{-- NA Questions Card --}}
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-600">NA Questions</h3>
                <i class="fas fa-ban text-blue-500"></i>
            </div>
            <p class="text-3xl font-bold text-blue-600" x-text="progress.na_questions"></p>
            <p class="text-xs text-neutral-500 mt-1" x-text="scores.na_points_excluded + ' pts excluded'"></p>
            <div class="mt-3 px-3 py-1.5 bg-blue-50 rounded-lg">
                <p class="text-xs text-blue-700 font-medium">Adjusted from 367 pts</p>
            </div>
        </div>

        {{-- Unanswered Card --}}
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-neutral-600">Remaining</h3>
                <i class="fas fa-clipboard-list"
                   :class="{
                       'text-green-500': unansweredCount === 0,
                       'text-amber-500': unansweredCount > 0 && unansweredCount <= 20,
                       'text-red-500': unansweredCount > 20
                   }"></i>
            </div>
            <p class="text-3xl font-bold transition-colors"
               :class="{
                   'text-green-600': unansweredCount === 0,
                   'text-amber-600': unansweredCount > 0 && unansweredCount <= 20,
                   'text-red-600': unansweredCount > 20
               }"
               x-text="unansweredCount"></p>
            <div class="mt-3">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="showOnlyUnanswered" class="sr-only peer">
                    <div class="relative w-9 h-5 bg-neutral-200 rounded-full peer peer-checked:bg-indigo-600 transition-colors">
                        <div class="absolute top-0.5 left-0.5 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
                    </div>
                    <span class="ml-2 text-xs font-medium text-neutral-700">Show only</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Premium Mobile-Friendly Section Navigation --}}
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
        <h3 class="text-sm font-semibold text-neutral-900 mb-3 px-2">Section Performance</h3>
        <div class="relative">
            {{-- Scroll Left Button --}}
            <button @click="scrollSections('left')"
                    class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white border border-neutral-300 rounded-full shadow-lg flex items-center justify-center text-neutral-600 hover:bg-neutral-50 transition-colors"
                    :class="{ 'opacity-0 pointer-events-none': !canScrollLeft }">
                <i class="fas fa-chevron-left text-xs"></i>
            </button>

            {{-- Scrollable Container --}}
            <div x-ref="sectionsContainer"
                 @scroll="updateScrollButtons"
                 class="flex gap-3 overflow-x-auto pb-2 px-10 scrollbar-hide scroll-smooth">
                <template x-for="(sec, idx) in sectionsProgress" :key="idx">
                    <button @click="scrollToSection(idx)"
                            class="flex-shrink-0 px-4 py-3 rounded-xl border-2 transition-all hover:shadow-md min-w-[140px]"
                            :class="{
                                'bg-green-50 border-green-500': sec.percentage >= 75,
                                'bg-amber-50 border-amber-500': sec.percentage >= 50 && sec.percentage < 75,
                                'bg-red-50 border-red-500': sec.percentage < 50,
                                'bg-neutral-50 border-neutral-300': sec.total === 0
                            }">
                        <div class="text-xs font-semibold mb-1"
                             :class="{
                                 'text-green-700': sec.percentage >= 75,
                                 'text-amber-700': sec.percentage >= 50 && sec.percentage < 75,
                                 'text-red-700': sec.percentage < 50,
                                 'text-neutral-600': sec.total === 0
                             }"
                             x-text="`Section ${sec.code}`"></div>
                        <div class="text-lg font-bold mb-1"
                             :class="{
                                 'text-green-900': sec.percentage >= 75,
                                 'text-amber-900': sec.percentage >= 50 && sec.percentage < 75,
                                 'text-red-900': sec.percentage < 50,
                                 'text-neutral-900': sec.total === 0
                             }"
                             x-text="`${sec.answered}/${sec.total}`"></div>
                        <div class="w-full bg-white/50 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all"
                                 :class="{
                                     'bg-green-500': sec.percentage >= 75,
                                     'bg-amber-500': sec.percentage >= 50 && sec.percentage < 75,
                                     'bg-red-500': sec.percentage < 50,
                                     'bg-neutral-300': sec.total === 0
                                 }"
                                 :style="`width: ${sec.percentage}%`"></div>
                        </div>
                    </button>
                </template>
            </div>

            {{-- Scroll Right Button --}}
            <button @click="scrollSections('right')"
                    class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white border border-neutral-300 rounded-full shadow-lg flex items-center justify-center text-neutral-600 hover:bg-neutral-50 transition-colors"
                    :class="{ 'opacity-0 pointer-events-none': !canScrollRight }">
                <i class="fas fa-chevron-right text-xs"></i>
            </button>
        </div>
    </div>

    {{-- Audit Info Header --}}
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">{{ $audit->lab_name }}</h1>
                <p class="text-sm text-neutral-500 mt-1">{{ $audit->country_name }} • Lab #{{ $audit->lab_number }}</p>
                <p class="text-xs text-neutral-400 mt-1">Opened: {{ \Carbon\Carbon::parse($audit->opened_on)->format('M d, Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($readOnly)
                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-medium bg-neutral-100 text-neutral-700 border border-neutral-200">
                    <i class="fas fa-lock text-neutral-400 mr-2"></i> Read Only
                </span>
                @endif
                <div x-show="isSaving" class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                    <svg class="animate-spin h-3 w-3 mr-2" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                </div>
            </div>
        </div>
    </div>

    {{-- Confirmation Modal --}}
    <div x-show="confirmDialog.show"
         x-cloak
         @keydown.escape.window="confirmDialog.show = false"
         class="fixed inset-0 z-50 overflow-hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-neutral-900/75 backdrop-blur-sm" @click="confirmDialog.reject()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-amber-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-neutral-900" x-text="confirmDialog.title"></h3>
                </div>
                <p class="text-sm text-neutral-600 leading-relaxed mb-6" x-text="confirmDialog.message"></p>
                <div class="flex gap-3 justify-end">
                    <button @click="confirmDialog.reject()"
                            class="px-4 py-2 rounded-xl text-sm font-medium bg-neutral-100 text-neutral-700 hover:bg-neutral-200 transition-colors">
                        Cancel
                    </button>
                    <button @click="confirmDialog.resolve()"
                            class="px-4 py-2 rounded-xl text-sm font-medium bg-amber-600 text-white hover:bg-amber-700 transition-colors">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Evidence Preview Modal --}}
    <div x-show="evidenceModal.show"
         x-cloak
         @keydown.escape.window="evidenceModal.show = false"
         class="fixed inset-0 z-50 overflow-hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-neutral-900/95 backdrop-blur-sm" @click="evidenceModal.show = false"></div>
        <div class="absolute inset-4 md:inset-8 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white" x-text="evidenceModal.fileName"></h3>
                <button @click="evidenceModal.show = false"
                        class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-xl transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="flex-1 bg-neutral-800 rounded-2xl overflow-hidden flex items-center justify-center">
                <template x-if="evidenceModal.type === 'image'">
                    <img :src="evidenceModal.url"
                         :alt="evidenceModal.fileName"
                         class="max-w-full max-h-full object-contain">
                </template>
                <template x-if="evidenceModal.type === 'pdf'">
                    <iframe :src="evidenceModal.url"
                            class="w-full h-full border-0"></iframe>
                </template>
                <template x-if="evidenceModal.type === 'other'">
                    <div class="text-center p-8">
                        <i class="fas fa-file text-6xl text-neutral-600 mb-4"></i>
                        <p class="text-neutral-400 mb-4">Preview not available</p>
                        <a :href="evidenceModal.url"
                           download
                           class="inline-flex items-center px-4 py-2 bg-neutral-700 text-white rounded-xl hover:bg-neutral-600 transition-colors">
                            <i class="fas fa-download mr-2"></i> Download File
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Questions by Section --}}
    <div class="space-y-6">
        <template x-for="(sectionData, sIndex) in sectionsData" :key="sIndex">
            <div x-show="!showOnlyUnanswered || sectionData.hasUnanswered"
                 :id="`section-${sIndex}`"
                 class="bg-white border-2 rounded-2xl shadow-sm overflow-hidden transition-all"
                 :class="{
                     'border-green-200': sectionData.percentage >= 75,
                     'border-amber-200': sectionData.percentage >= 50 && sectionData.percentage < 75,
                     'border-red-200': sectionData.percentage < 50,
                     'border-neutral-200': sectionData.totalCount === 0
                 }">

                {{-- Section Header --}}
                <div class="px-6 py-4 border-b-2"
                     :class="{
                         'bg-green-50 border-green-200': sectionData.percentage >= 75,
                         'bg-amber-50 border-amber-200': sectionData.percentage >= 50 && sectionData.percentage < 75,
                         'bg-red-50 border-red-200': sectionData.percentage < 50,
                         'bg-neutral-50 border-neutral-200': sectionData.totalCount === 0
                     }">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold transition-colors"
                                :class="{
                                    'text-green-900': sectionData.percentage >= 75,
                                    'text-amber-900': sectionData.percentage >= 50 && sectionData.percentage < 75,
                                    'text-red-900': sectionData.percentage < 50,
                                    'text-neutral-900': sectionData.totalCount === 0
                                }"
                                x-text="`Section ${sectionData.section.code}: ${sectionData.section.title}`"></h2>
                            <p class="text-sm text-neutral-600 mt-1" x-text="`${sectionData.answeredCount} of ${sectionData.totalCount} answered`"></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-32 bg-white/50 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all"
                                     :class="{
                                         'bg-green-500': sectionData.percentage >= 75,
                                         'bg-amber-500': sectionData.percentage >= 50 && sectionData.percentage < 75,
                                         'bg-red-500': sectionData.percentage < 50
                                     }"
                                     :style="`width: ${sectionData.percentage}%`"></div>
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-lg"
                                  :class="{
                                      'bg-green-100 text-green-700': sectionData.percentage >= 75,
                                      'bg-amber-100 text-amber-700': sectionData.percentage >= 50 && sectionData.percentage < 75,
                                      'bg-red-100 text-red-700': sectionData.percentage < 50,
                                      'bg-neutral-100 text-neutral-600': sectionData.totalCount === 0
                                  }"
                                  x-text="`${sectionData.percentage}%`"></span>
                        </div>
                    </div>
                </div>

                {{-- Questions --}}
                <div class="divide-y divide-neutral-100">
                    <template x-for="(qData, qIndex) in sectionData.questions" :key="qIndex">
                        <div x-show="!showOnlyUnanswered || !qData.isAnswered"
                             class="p-6 hover:bg-neutral-50/50 transition-colors relative"
                             :class="{
                                 'border-l-4 border-amber-400 bg-amber-50/30': !qData.isAnswered,
                                 'bg-green-50/20': qData.response && qData.response.answer === 'Y',
                                 'bg-red-50/20': qData.response && qData.response.answer === 'N'
                             }">

                            {{-- Saving Indicator --}}
                            <div x-show="qData.saving" class="absolute top-4 right-4">
                                <div class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-blue-100 text-blue-700">
                                    <svg class="animate-spin h-3 w-3 mr-1" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving
                                </div>
                            </div>

                            {{-- Question Header --}}
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-16 h-16 rounded-xl flex items-center justify-center border-2 transition-all"
                                     :class="{
                                         'bg-green-100 border-green-500': qData.response && qData.response.answer === 'Y',
                                         'bg-amber-100 border-amber-500': qData.response && qData.response.answer === 'P',
                                         'bg-red-100 border-red-500': qData.response && qData.response.answer === 'N',
                                         'bg-blue-100 border-blue-500': qData.response && qData.response.answer === 'NA',
                                         'bg-neutral-100 border-neutral-300': !qData.response
                                     }">
                                    <span class="text-sm font-bold"
                                          :class="{
                                              'text-green-700': qData.response && qData.response.answer === 'Y',
                                              'text-amber-700': qData.response && qData.response.answer === 'P',
                                              'text-red-700': qData.response && qData.response.answer === 'N',
                                              'text-blue-700': qData.response && qData.response.answer === 'NA',
                                              'text-neutral-600': !qData.response
                                          }"
                                          x-text="qData.question.q_code"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4 mb-3">
                                        <div class="flex-1">
                                            <p class="text-sm text-neutral-900 leading-relaxed" x-text="qData.question.text"></p>
                                            <template x-if="qData.question.iso_reference">
                                                <p class="text-xs text-neutral-500 mt-1">
                                                    <i class="fas fa-book-open mr-1 text-indigo-500"></i>
                                                    <span x-text="qData.question.iso_reference"></span>
                                                </p>
                                            </template>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 text-white shadow-sm">
                                                <span x-text="qData.question.weight"></span> pts
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Sub-questions --}}
                                    <template x-if="qData.subquestions && qData.subquestions.length > 0">
                                        <div class="mb-4 p-4 bg-neutral-50 rounded-xl border-2 border-neutral-200">
                                            <p class="text-xs font-semibold text-neutral-700 mb-3 flex items-center">
                                                <i class="fas fa-list-ul mr-2 text-indigo-500"></i>
                                                Sub-questions
                                                <template x-if="qData.question.requires_all_subs_for_yes === 1">
                                                    <span class="ml-2 px-2 py-0.5 bg-amber-100 text-amber-700 rounded-md text-xs font-medium">
                                                        <i class="fas fa-exclamation-triangle text-xs mr-1"></i>
                                                        All must be Y/NA for parent Y
                                                    </span>
                                                </template>
                                            </p>
                                            <template x-for="(sub, subIdx) in qData.subquestions" :key="subIdx">
                                                <div class="flex items-start gap-3 py-2.5 border-b border-neutral-100 last:border-0">
                                                    <span class="text-xs font-semibold text-indigo-600 flex-shrink-0 mt-0.5 bg-indigo-50 px-2 py-1 rounded" x-text="sub.sub_code"></span>
                                                    <p class="text-xs text-neutral-700 flex-1 leading-relaxed" x-text="sub.text"></p>

                                                    @if(!$readOnly)
                                                    <div class="flex-shrink-0 flex gap-1.5">
                                                        <template x-for="opt in ['Y', 'P', 'N', 'NA']" :key="opt">
                                                            <button
                                                                @click="selectSubAnswer(sIndex, qIndex, sub.id, opt)"
                                                                class="w-8 h-8 rounded-lg text-xs font-bold transition-all border-2 shadow-sm hover:shadow-md"
                                                                :class="{
                                                                    'bg-green-500 text-white border-green-600': qData.sub_responses && qData.sub_responses[sub.id] && qData.sub_responses[sub.id].answer === opt && opt === 'Y',
                                                                    'bg-amber-500 text-white border-amber-600': qData.sub_responses && qData.sub_responses[sub.id] && qData.sub_responses[sub.id].answer === opt && opt === 'P',
                                                                    'bg-red-500 text-white border-red-600': qData.sub_responses && qData.sub_responses[sub.id] && qData.sub_responses[sub.id].answer === opt && opt === 'N',
                                                                    'bg-blue-500 text-white border-blue-600': qData.sub_responses && qData.sub_responses[sub.id] && qData.sub_responses[sub.id].answer === opt && opt === 'NA',
                                                                    'bg-white border-neutral-300 text-neutral-600 hover:bg-neutral-50': !qData.sub_responses || !qData.sub_responses[sub.id] || qData.sub_responses[sub.id].answer !== opt
                                                                }"
                                                                x-text="opt">
                                                            </button>
                                                        </template>
                                                    </div>
                                                    @else
                                                    <span class="flex-shrink-0 inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold"
                                                          x-show="qData.sub_responses && qData.sub_responses[sub.id]"
                                                          :class="{
                                                              'bg-green-100 text-green-800': qData.sub_responses && qData.sub_responses[sub.id] && qData.sub_responses[sub.id].answer === 'Y',
                                                              'bg-amber-100 text-amber-800': qData.sub_responses && qData.sub_responses[sub.id] && qData.sub_responses[sub.id].answer === 'P',
                                                              'bg-red-100 text-red-800': qData.sub_responses && qData.sub_responses[sub.id] && qData.sub_responses[sub.id].answer === 'N',
                                                              'bg-blue-100 text-blue-800': qData.sub_responses && qData.sub_responses[sub.id] && qData.sub_responses[sub.id].answer === 'NA'
                                                          }"
                                                          x-text="qData.sub_responses && qData.sub_responses[sub.id] ? qData.sub_responses[sub.id].answer : ''">
                                                    </span>
                                                    @endif
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Main Question Response --}}
                                    @if(!$readOnly)
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <template x-for="option in ['Y', 'P', 'N', 'NA']" :key="option">
                                                <button
                                                    @click="selectAnswer(sIndex, qIndex, option)"
                                                    :disabled="option === 'Y' && !canSelectYes(qData)"
                                                    class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all border-2 shadow-sm hover:shadow-md disabled:opacity-40 disabled:cursor-not-allowed"
                                                    :class="{
                                                        'bg-green-500 text-white border-green-600 ring-2 ring-green-200': qData.response && qData.response.answer === option && option === 'Y',
                                                        'bg-amber-500 text-white border-amber-600 ring-2 ring-amber-200': qData.response && qData.response.answer === option && option === 'P',
                                                        'bg-red-500 text-white border-red-600 ring-2 ring-red-200': qData.response && qData.response.answer === option && option === 'N',
                                                        'bg-blue-500 text-white border-blue-600 ring-2 ring-blue-200': qData.response && qData.response.answer === option && option === 'NA',
                                                        'bg-white border-neutral-300 text-neutral-700 hover:bg-neutral-50': !qData.response || qData.response.answer !== option
                                                    }"
                                                    x-text="option">
                                                </button>
                                            </template>
                                        </div>

                                        {{-- Required Comment/Justification with Validation --}}
                                        <template x-if="qData.response && ['P', 'N', 'NA'].includes(qData.response.answer)">
                                            <div class="space-y-2">
                                                <div class="relative">
                                                    <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                                        Comment <span class="text-red-500">*</span>
                                                        <span class="ml-1 text-red-500 text-xs" x-show="!qData.tempComment || qData.tempComment.trim() === ''">(Required)</span>
                                                    </label>
                                                    <textarea
                                                        x-model="qData.tempComment"
                                                        @input.debounce.500ms="autoSaveResponse(sIndex, qIndex)"
                                                        rows="3"
                                                        class="block w-full px-3 py-2 border-2 rounded-xl text-sm transition-all focus:ring-2 focus:ring-offset-1"
                                                        :class="{
                                                            'border-red-300 focus:border-red-500 focus:ring-red-200 bg-red-50': !qData.tempComment || qData.tempComment.trim() === '',
                                                            'border-neutral-300 focus:border-indigo-500 focus:ring-indigo-200': qData.tempComment && qData.tempComment.trim() !== ''
                                                        }"
                                                        placeholder="Enter comment explaining the response..."></textarea>
                                                </div>
                                                <template x-if="qData.response.answer === 'NA'">
                                                    <div class="relative">
                                                        <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                                            NA Justification <span class="text-red-500">*</span>
                                                            <span class="ml-1 text-red-500 text-xs" x-show="!qData.tempNaJustification || qData.tempNaJustification.trim() === ''">(Required)</span>
                                                        </label>
                                                        <textarea
                                                            x-model="qData.tempNaJustification"
                                                            @input.debounce.500ms="autoSaveResponse(sIndex, qIndex)"
                                                            rows="2"
                                                            class="block w-full px-3 py-2 border-2 rounded-xl text-sm transition-all focus:ring-2 focus:ring-offset-1"
                                                            :class="{
                                                                'border-red-300 focus:border-red-500 focus:ring-red-200 bg-red-50': !qData.tempNaJustification || qData.tempNaJustification.trim() === '',
                                                                'border-neutral-300 focus:border-indigo-500 focus:ring-indigo-200': qData.tempNaJustification && qData.tempNaJustification.trim() !== ''
                                                            }"
                                                            placeholder="Provide detailed justification for NA response..."></textarea>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Evidence Upload & Management --}}
                                        <div class="mt-4 space-y-3 p-4 bg-neutral-50 rounded-xl border border-neutral-200">
                                            <div class="flex items-center justify-between">
                                                <h4 class="text-sm font-semibold text-neutral-700 flex items-center">
                                                    <i class="fas fa-paperclip mr-2 text-indigo-500"></i> Evidence Files
                                                </h4>
                                                <label class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold cursor-pointer hover:bg-indigo-700 transition-colors shadow-sm">
                                                    <i class="fas fa-upload mr-1"></i> Upload
                                                    <input type="file"
                                                           @change="uploadEvidence($event, sIndex, qIndex)"
                                                           class="hidden"
                                                           accept="image/*,.pdf,.doc,.docx">
                                                </label>
                                            </div>

                                            <template x-if="qData.evidence && qData.evidence.length > 0">
                                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                                    <template x-for="(file, fileIdx) in qData.evidence" :key="fileIdx">
                                                        <div class="relative group bg-white border-2 border-neutral-200 rounded-xl overflow-hidden hover:shadow-lg transition-all">
                                                            <template x-if="file.type === 'image'">
                                                                <img :src="`/storage/${file.file_path}`"
                                                                     :alt="file.display_name"
                                                                     class="w-full h-24 object-cover">
                                                            </template>
                                                            <template x-if="file.type !== 'image'">
                                                                <div class="w-full h-24 flex items-center justify-center bg-gradient-to-br from-indigo-50 to-blue-50">
                                                                    <i class="fas fa-file-pdf text-4xl text-indigo-400"
                                                                       :class="{
                                                                           'fa-file-pdf text-red-400': file.mime_type === 'application/pdf',
                                                                           'fa-file-word text-blue-400': file.mime_type.includes('word'),
                                                                           'fa-file text-neutral-400': !file.mime_type.includes('pdf') && !file.mime_type.includes('word')
                                                                       }"></i>
                                                                </div>
                                                            </template>
                                                            <div class="p-2 bg-white">
                                                                <p class="text-xs text-neutral-900 truncate font-medium" x-text="file.display_name"></p>
                                                                <p class="text-xs text-neutral-500" x-text="formatFileSize(file.file_size)"></p>
                                                            </div>
                                                            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                <a :href="`/storage/${file.file_path}`"
                                                                   target="_blank"
                                                                   class="inline-flex items-center justify-center w-8 h-8 bg-white rounded-lg shadow-lg text-indigo-600 hover:text-indigo-800 border-2 border-indigo-200">
                                                                    <i class="fas fa-eye text-sm"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    @else
                                    {{-- Read-only Display --}}
                                    <div class="space-y-3">
                                        <template x-if="qData.response">
                                            <div>
                                                <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold shadow-sm"
                                                      :class="{
                                                          'bg-green-100 text-green-800 border-2 border-green-300': qData.response.answer === 'Y',
                                                          'bg-amber-100 text-amber-800 border-2 border-amber-300': qData.response.answer === 'P',
                                                          'bg-red-100 text-red-800 border-2 border-red-300': qData.response.answer === 'N',
                                                          'bg-blue-100 text-blue-800 border-2 border-blue-300': qData.response.answer === 'NA'
                                                      }"
                                                      x-text="qData.response.answer">
                                                </span>
                                                <template x-if="qData.response.comment">
                                                    <div class="mt-3 p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                                                        <p class="text-xs font-semibold text-neutral-600 mb-1">Comment:</p>
                                                        <p class="text-sm text-neutral-700" x-text="qData.response.comment"></p>
                                                    </div>
                                                </template>
                                                <template x-if="qData.response.na_justification">
                                                    <div class="mt-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                                        <p class="text-xs font-semibold text-blue-600 mb-1">NA Justification:</p>
                                                        <p class="text-sm text-blue-700" x-text="qData.response.na_justification"></p>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <template x-if="qData.evidence && qData.evidence.length > 0">
                                            <div class="mt-4 p-4 bg-neutral-50 rounded-xl border border-neutral-200">
                                                <h4 class="text-sm font-semibold text-neutral-700 mb-3 flex items-center">
                                                    <i class="fas fa-paperclip mr-2 text-indigo-500"></i> Evidence Files
                                                </h4>
                                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                                    <template x-for="(file, fileIdx) in qData.evidence" :key="fileIdx">
                                                        <a :href="`/storage/${file.file_path}`"
                                                           target="_blank"
                                                           class="bg-white border-2 border-neutral-200 rounded-xl overflow-hidden hover:shadow-lg transition-all hover:border-indigo-300">
                                                            <template x-if="file.type === 'image'">
                                                                <img :src="`/storage/${file.file_path}`"
                                                                     :alt="file.display_name"
                                                                     class="w-full h-24 object-cover">
                                                            </template>
                                                            <template x-if="file.type !== 'image'">
                                                                <div class="w-full h-24 flex items-center justify-center bg-gradient-to-br from-indigo-50 to-blue-50">
                                                                    <i class="fas fa-file-pdf text-4xl text-indigo-400"></i>
                                                                </div>
                                                            </template>
                                                            <div class="p-2">
                                                                <p class="text-xs text-neutral-900 truncate font-medium" x-text="file.display_name"></p>
                                                                <p class="text-xs text-neutral-500" x-text="formatFileSize(file.file_size)"></p>
                                                            </div>
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

</div>

<script>
function auditResponseApp() {
    return {
        showOnlyUnanswered: false,
        progress: {!! json_encode($progress) !!},
        scores: {!! json_encode($scores) !!},
        sectionsData: [
            @foreach($sections as $sectionData)
            {
                section: {!! json_encode($sectionData['section']) !!},
                questions: [
                    @foreach($sectionData['questions'] as $qData)
                    {
                        question: {!! json_encode($qData['question']) !!},
                        subquestions: {!! json_encode($qData['subquestions']) !!},
                        response: {!! json_encode($qData['response']) !!},
                        sub_responses: {!! json_encode($qData['sub_responses']) !!},
                        evidence: {!! json_encode($qData['evidence']) !!},
                        isAnswered: {{ $qData['response'] ? 'true' : 'false' }},
                        tempComment: {!! $qData['response'] && $qData['response']->comment ? json_encode($qData['response']->comment) : '""' !!},
                        tempNaJustification: {!! $qData['response'] && $qData['response']->na_justification ? json_encode($qData['response']->na_justification) : '""' !!},
                        saving: false
                    }{{ $loop->last ? '' : ',' }}
                    @endforeach
                ],
                totalCount: 0,
                answeredCount: 0,
                hasUnanswered: false,
                percentage: 0
            }{{ $loop->last ? '' : ',' }}
            @endforeach
        ],
        isSaving: false,
        canScrollLeft: false,
        canScrollRight: true,

        init() {
            this.updateProgress();
            this.updateScrollButtons();
        },

        get unansweredCount() {
            return this.sectionsData.reduce((sum, s) => sum + (s.totalCount - s.answeredCount), 0);
        },

        get sectionsProgress() {
            return this.sectionsData.map(s => ({
                code: s.section.code,
                total: s.totalCount,
                answered: s.answeredCount,
                unanswered: s.totalCount - s.answeredCount,
                percentage: s.percentage
            }));
        },

        updateProgress() {
            this.sectionsData.forEach(section => {
                section.totalCount = section.questions.length;
                section.answeredCount = section.questions.filter(q => q.isAnswered).length;
                section.hasUnanswered = section.answeredCount < section.totalCount;
                section.percentage = section.totalCount > 0
                    ? Math.round((section.answeredCount / section.totalCount) * 100)
                    : 0;
            });
        },

        canSelectYes(qData) {
            if (qData.question.requires_all_subs_for_yes !== 1) return true;
            if (!qData.subquestions || qData.subquestions.length === 0) return true;

            return qData.subquestions.every(sub => {
                const subResp = qData.sub_responses && qData.sub_responses[sub.id];
                return subResp && (subResp.answer === 'Y' || subResp.answer === 'NA');
            });
        },

        async selectAnswer(sIndex, qIndex, answer) {
            const qData = this.sectionsData[sIndex].questions[qIndex];

            if (answer === 'Y' && !this.canSelectYes(qData)) {
                this.showToast('All sub-questions must be Y or NA before selecting Y', 'warning');
                return;
            }

            if (!qData.response) {
                qData.response = {};
            }
            qData.response.answer = answer;

            if (answer === 'Y') {
                await this.autoSaveResponse(sIndex, qIndex);
            }
        },

        async selectSubAnswer(sIndex, qIndex, subId, answer) {
            const qData = this.sectionsData[sIndex].questions[qIndex];

            // Check if this change would invalidate parent Y answer
            if (qData.question.requires_all_subs_for_yes === 1 &&
                qData.response &&
                qData.response.answer === 'Y' &&
                ['P', 'N'].includes(answer)) {

                const confirmChange = await this.showConfirm(
                    'Warning: Parent Answer Conflict',
                    'This question is marked as Y (Yes), which requires ALL sub-questions to be Y or NA. ' +
                    'Changing this sub-question to ' + answer + ' will automatically change the parent answer to P (Partial). ' +
                    'Do you want to continue?'
                );

                if (!confirmChange) {
                    return; // User cancelled
                }
            }

            if (!qData.sub_responses) {
                qData.sub_responses = {};
            }
            if (!qData.sub_responses[subId]) {
                qData.sub_responses[subId] = {};
            }
            qData.sub_responses[subId].answer = answer;

            await this.saveSubResponse(sIndex, qIndex, subId);
        },

        async autoSaveResponse(sIndex, qIndex) {
            const qData = this.sectionsData[sIndex].questions[qIndex];
            const answer = qData.response.answer;

            if (['P', 'N', 'NA'].includes(answer)) {
                if (!qData.tempComment || qData.tempComment.trim() === '') {
                    return;
                }
                if (answer === 'NA' && (!qData.tempNaJustification || qData.tempNaJustification.trim() === '')) {
                    return;
                }
            }

            qData.saving = true;
            this.isSaving = true;

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('audit_id', '{{ $audit->id }}');
            formData.append('question_id', qData.question.id);
            formData.append('answer', answer);
            if (qData.tempComment) formData.append('comment', qData.tempComment);
            if (qData.tempNaJustification) formData.append('na_justification', qData.tempNaJustification);

            try {
                const response = await fetch('{{ route("audits.responses.store") }}', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    qData.isAnswered = true;
                    qData.response.comment = qData.tempComment;
                    qData.response.na_justification = qData.tempNaJustification;
                    this.progress = result.progress;
                    this.scores = result.scores;
                    this.updateProgress();
                    this.showToast('Response saved', 'success');
                } else {
                    this.showToast(result.error || 'Failed to save', 'error');
                }
            } catch (error) {
                console.error(error);
                this.showToast('Network error', 'error');
            } finally {
                qData.saving = false;
                this.isSaving = false;
            }
        },

        async saveSubResponse(sIndex, qIndex, subId) {
            const qData = this.sectionsData[sIndex].questions[qIndex];
            const subAnswer = qData.sub_responses[subId].answer;

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('audit_id', '{{ $audit->id }}');
            formData.append('subquestion_id', subId);
            formData.append('answer', subAnswer);
            formData.append('question_id', qData.question.id); // For parent validation

            try {
                const response = await fetch('{{ route("audits.subresponses.store") }}', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // If parent was invalidated, update the UI
                    if (result.parent_invalidated) {
                        qData.response.answer = result.new_parent_answer;
                        qData.tempComment = result.parent_comment || qData.tempComment;
                        this.showToast('Parent answer changed to ' + result.new_parent_answer + ' due to sub-question conflict', 'warning');
                    } else {
                        this.showToast('Sub-response saved', 'success');
                    }

                    // Update scores and progress if provided
                    if (result.progress) this.progress = result.progress;
                    if (result.scores) this.scores = result.scores;
                    this.updateProgress();
                } else {
                    this.showToast(result.error || 'Failed to save', 'error');
                }
            } catch (error) {
                console.error(error);
                this.showToast('Network error', 'error');
            }
        },

        showConfirm(title, message) {
            return new Promise((resolve) => {
                const result = confirm(title + '\n\n' + message);
                resolve(result);
            });
        },

        async uploadEvidence(event, sIndex, qIndex) {
            const qData = this.sectionsData[sIndex].questions[qIndex];
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('audit_id', '{{ $audit->id }}');
            formData.append('question_id', qData.question.id);
            formData.append('evidence_file', file);
            formData.append('display_name', file.name);

            this.isSaving = true;
            try {
                const response = await fetch('{{ route("audits.evidence.upload") }}', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (!qData.evidence) qData.evidence = [];
                    qData.evidence.push(result.evidence);
                    this.showToast('Evidence uploaded', 'success');
                } else {
                    this.showToast(result.error || 'Upload failed', 'error');
                }
            } catch (error) {
                console.error(error);
                this.showToast('Network error', 'error');
            } finally {
                this.isSaving = false;
            }

            event.target.value = '';
        },

        scrollToSection(index) {
            const element = document.getElementById(`section-${index}`);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        scrollSections(direction) {
            const container = this.$refs.sectionsContainer;
            const scrollAmount = 200;

            if (direction === 'left') {
                container.scrollLeft -= scrollAmount;
            } else {
                container.scrollLeft += scrollAmount;
            }

            setTimeout(() => this.updateScrollButtons(), 100);
        },

        updateScrollButtons() {
            const container = this.$refs.sectionsContainer;
            if (!container) return;

            this.canScrollLeft = container.scrollLeft > 0;
            this.canScrollRight = container.scrollLeft < (container.scrollWidth - container.clientWidth - 10);
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            const colors = {
                success: { bg: 'bg-green-600', icon: 'fa-check-circle', border: 'border-green-700' },
                error: { bg: 'bg-red-600', icon: 'fa-exclamation-circle', border: 'border-red-700' },
                warning: { bg: 'bg-amber-600', icon: 'fa-exclamation-triangle', border: 'border-amber-700' },
                info: { bg: 'bg-blue-600', icon: 'fa-info-circle', border: 'border-blue-700' }
            };

            const color = colors[type] || colors.info;

            toast.className = `fixed bottom-4 right-4 flex items-center p-4 rounded-xl shadow-2xl transform transition-all duration-300 translate-x-full z-50 ${color.bg} text-white border-2 ${color.border}`;
            toast.innerHTML = `
                <i class="fas ${color.icon} mr-3 text-xl"></i>
                <span class="font-semibold">${message}</span>
            `;

            document.body.appendChild(toast);
            setTimeout(() => toast.classList.remove('translate-x-full'), 100);
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endsection
