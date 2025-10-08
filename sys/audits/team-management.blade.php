@extends('layouts.app')

@section('title', 'Audit Team Management')
@section('breadcrumb', 'Team Management')

@section('content')
<div x-data="teamManagement()" x-init="init()" class="space-y-6">

    {{-- Flash Messages --}}
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-2xl p-4">
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
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-2xl p-4">
            <div class="flex items-start space-x-3">
                <i class="fas fa-check-circle text-green-600 text-lg mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-green-900">Success</p>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-2xl p-4">
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

    <!-- Premium Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-neutral-900 via-neutral-800 to-neutral-900 rounded-2xl p-8 shadow-xl">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>
        <div class="relative">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-12 h-12 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <i class="fas fa-users-cog text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-semibold text-white">Audit Team Management</h1>
                            <p class="text-sm text-neutral-300 mt-1">Build and manage audit teams with precision and control</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-xs text-neutral-400 uppercase tracking-wider">Total Teams</p>
                        <p class="text-2xl font-bold text-white" x-text="stats.total_members"></p>
                    </div>
                    <div class="w-px h-12 bg-white/20"></div>
                    <div class="text-right">
                        <p class="text-xs text-neutral-400 uppercase tracking-wider">Avg Size</p>
                        <p class="text-2xl font-bold text-white" x-text="stats.avg_team_size"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Wizard Container -->
    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm overflow-hidden">

        <!-- Wizard Navigation -->
        <div class="border-b border-neutral-200 bg-neutral-50">
            <nav class="flex space-x-1 px-6">
                <template x-for="(tab, key) in tabs" :key="key">
                    <button @click="changeTab(key)"
                            :class="activeTab === key ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-700'"
                            class="py-4 px-4 text-sm font-medium transition-colors relative">
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

        <!-- Wizard Content -->
        <div class="p-6">

            <!-- Step 1: Select Audit -->
            <div x-show="activeTab === 'select'" x-transition class="space-y-6">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-neutral-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clipboard-list text-neutral-600 text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-neutral-900 mb-2">Select Audit</h2>
                    <p class="text-sm text-neutral-600 max-w-2xl mx-auto">Choose the audit for which you want to manage the team. You can only manage teams for audits within your authorization scope.</p>
                </div>

                <div class="max-w-2xl mx-auto space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-2">Audit <span class="text-red-500">*</span></label>
                        <select x-model="selectedAuditId" @change="loadTeam()"
                                class="w-full px-4 py-3 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 transition-all">
                            <option value="">Select an audit...</option>
                            @foreach($audits as $audit)
                                <option value="{{ $audit->id }}">
                                    {{ $audit->laboratory_name }} - {{ date('M d, Y', strtotime($audit->opened_on)) }} ({{ ucfirst($audit->status) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="selectedAuditId" x-cloak class="bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-xl p-4">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-blue-900 mb-1">Authorization Required</p>
                                <p class="text-xs text-blue-800">You must be a Lead Auditor or have Country Coordinator privileges to manage this audit's team.</p>
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedAuditId" x-cloak class="flex items-center justify-end space-x-3 pt-4">
                        <button @click="changeTab('roles')"
                                class="inline-flex items-center px-6 py-3 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
                            Continue to Role Guide
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Understanding Roles -->
            <div x-show="activeTab === 'roles'" x-transition class="space-y-6">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-neutral-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-shield text-neutral-600 text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-neutral-900 mb-2">Understanding Team Roles</h2>
                    <p class="text-sm text-neutral-600 max-w-2xl mx-auto">Each role has specific responsibilities and access levels. Understanding these roles ensures proper team composition and audit workflow.</p>
                </div>

                <!-- Role Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- Lead Auditor Card -->
                    <div class="relative overflow-hidden bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-2xl p-6 hover:shadow-lg transition-all">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-green-100 rounded-full -mr-16 -mt-16 opacity-50"></div>
                        <div class="relative">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-4">
                                <i class="fas fa-user-check text-green-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Lead Auditor</h3>
                            <div class="inline-flex items-center px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-medium mb-4">
                                Required: Exactly 1
                            </div>

                            <div class="space-y-3 mb-4">
                                <p class="text-sm text-neutral-700 font-medium">Responsibilities:</p>
                                <ul class="space-y-2 text-xs text-neutral-600">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-0.5"></i>
                                        <span>Plan and coordinate audit activities</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-0.5"></i>
                                        <span>Manage team members and assignments</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-0.5"></i>
                                        <span>Review and approve audit findings</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-0.5"></i>
                                        <span>Sign off on final audit reports</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="space-y-2 pt-4 border-t border-green-200">
                                <p class="text-sm text-neutral-700 font-medium">Access Levels:</p>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Create Responses</span>
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Manage Team</span>
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Finalize Audit</span>
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Generate Reports</span>
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team Member Card -->
                    <div class="relative overflow-hidden bg-gradient-to-br from-blue-50 to-cyan-50 border-2 border-blue-200 rounded-2xl p-6 hover:shadow-lg transition-all">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-100 rounded-full -mr-16 -mt-16 opacity-50"></div>
                        <div class="relative">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4">
                                <i class="fas fa-user-friends text-blue-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Team Member</h3>
                            <div class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-medium mb-4">
                                Optional: 0 or more
                            </div>

                            <div class="space-y-3 mb-4">
                                <p class="text-sm text-neutral-700 font-medium">Responsibilities:</p>
                                <ul class="space-y-2 text-xs text-neutral-600">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-blue-600 mr-2 mt-0.5"></i>
                                        <span>Conduct audit assessments</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-blue-600 mr-2 mt-0.5"></i>
                                        <span>Enter examination responses</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-blue-600 mr-2 mt-0.5"></i>
                                        <span>Upload evidence and documentation</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-blue-600 mr-2 mt-0.5"></i>
                                        <span>Document findings and observations</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="space-y-2 pt-4 border-t border-blue-200">
                                <p class="text-sm text-neutral-700 font-medium">Access Levels:</p>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Create Responses</span>
                                    <i class="fas fa-check-circle text-blue-600"></i>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Manage Team</span>
                                    <i class="fas fa-times-circle text-neutral-300"></i>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Finalize Audit</span>
                                    <i class="fas fa-times-circle text-neutral-300"></i>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Generate Reports</span>
                                    <i class="fas fa-check-circle text-blue-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observer Card -->
                    <div class="relative overflow-hidden bg-gradient-to-br from-neutral-50 to-neutral-100 border-2 border-neutral-200 rounded-2xl p-6 hover:shadow-lg transition-all">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-neutral-100 rounded-full -mr-16 -mt-16 opacity-50"></div>
                        <div class="relative">
                            <div class="w-12 h-12 bg-neutral-100 rounded-xl flex items-center justify-center mb-4">
                                <i class="fas fa-eye text-neutral-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Observer</h3>
                            <div class="inline-flex items-center px-2 py-1 bg-neutral-100 text-neutral-700 rounded-lg text-xs font-medium mb-4">
                                Optional: 0 or more
                            </div>

                            <div class="space-y-3 mb-4">
                                <p class="text-sm text-neutral-700 font-medium">Responsibilities:</p>
                                <ul class="space-y-2 text-xs text-neutral-600">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-neutral-600 mr-2 mt-0.5"></i>
                                        <span>View audit progress and findings</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-neutral-600 mr-2 mt-0.5"></i>
                                        <span>Monitor audit activities (read-only)</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-neutral-600 mr-2 mt-0.5"></i>
                                        <span>Learn audit processes and standards</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-neutral-600 mr-2 mt-0.5"></i>
                                        <span>Provide informal feedback (no editing)</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="space-y-2 pt-4 border-t border-neutral-200">
                                <p class="text-sm text-neutral-700 font-medium">Access Levels:</p>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Create Responses</span>
                                    <i class="fas fa-times-circle text-neutral-300"></i>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Manage Team</span>
                                    <i class="fas fa-times-circle text-neutral-300"></i>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Finalize Audit</span>
                                    <i class="fas fa-times-circle text-neutral-300"></i>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-600">Generate Reports</span>
                                    <i class="fas fa-times-circle text-neutral-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Critical Rules -->
                <div class="mt-8 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-6">
                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-amber-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-neutral-900 mb-3">Critical Team Composition Rules</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-start space-x-2">
                                    <i class="fas fa-check-circle text-amber-600 mt-0.5"></i>
                                    <div>
                                        <p class="text-xs font-medium text-neutral-900">Exactly One Lead Required</p>
                                        <p class="text-xs text-neutral-600">Every audit must have precisely one Lead Auditor assigned</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <i class="fas fa-check-circle text-amber-600 mt-0.5"></i>
                                    <div>
                                        <p class="text-xs font-medium text-neutral-900">No Duplicate Members</p>
                                        <p class="text-xs text-neutral-600">Each person can only be assigned once per audit</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <i class="fas fa-check-circle text-amber-600 mt-0.5"></i>
                                    <div>
                                        <p class="text-xs font-medium text-neutral-900">Valid Credentials Required</p>
                                        <p class="text-xs text-neutral-600">All members must have appropriate auditor qualifications</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <i class="fas fa-check-circle text-amber-600 mt-0.5"></i>
                                    <div>
                                        <p class="text-xs font-medium text-neutral-900">Scope-Based Assignment</p>
                                        <p class="text-xs text-neutral-600">Members must be within your authorization scope</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <button @click="changeTab('select')"
                            class="inline-flex items-center px-6 py-3 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Selection
                    </button>
                    <button @click="changeTab('build')"
                            class="inline-flex items-center px-6 py-3 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
                        Build Team
                        <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Build Team -->
            <div x-show="activeTab === 'build'" x-transition class="space-y-6">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-neutral-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-neutral-600 text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-neutral-900 mb-2">Build Your Team</h2>
                    <p class="text-sm text-neutral-600 max-w-2xl mx-auto">Compose your audit team by selecting qualified auditors and assigning appropriate roles. Remember: exactly one Lead Auditor is required.</p>
                </div>

                <!-- Current Team Display -->
                <div x-show="!teamLoading" x-cloak class="bg-neutral-50 border border-neutral-200 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-neutral-900">Current Team Composition</h3>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-white border border-neutral-200">
                                <i class="fas fa-users text-neutral-600 mr-1.5"></i>
                                <span x-text="currentTeam.length"></span> Member(s)
                            </span>
                            <span x-show="hasLead()" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-green-100 text-green-700">
                                <i class="fas fa-check-circle mr-1.5"></i>
                                Lead Assigned
                            </span>
                            <span x-show="!hasLead()" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-700">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                Lead Required
                            </span>
                        </div>
                    </div>

                    <div x-show="currentTeam.length === 0" class="text-center py-12">
                        <div class="w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-users-slash text-neutral-400 text-2xl"></i>
                        </div>
                        <p class="text-sm font-medium text-neutral-900 mb-1">No Team Members Yet</p>
                        <p class="text-xs text-neutral-500">Add your first team member below to get started</p>
                    </div>

                    <div x-show="currentTeam.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template x-for="member in currentTeam" :key="member.id">
                            <div class="bg-white border border-neutral-200 rounded-xl p-4 hover:border-neutral-300 transition-all">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                             :class="getRoleBgClass(member.role)">
                                            <i :class="getRoleIcon(member.role) + ' text-sm'"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-neutral-900" x-text="member.name"></p>
                                            <p class="text-xs text-neutral-500" x-text="member.email"></p>
                                        </div>
                                    </div>
                                    <button @click="confirmRemoveMember(member)"
                                            :disabled="!canRemove(member)"
                                            :class="canRemove(member) ? 'hover:bg-red-50 text-red-600' : 'opacity-40 cursor-not-allowed text-neutral-400'"
                                            class="p-2 rounded-lg transition-colors"
                                            :title="canRemove(member) ? 'Remove member' : 'Cannot remove (required role)'">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium"
                                          :class="getRoleClass(member.role)"
                                          x-text="formatRole(member.role)"></span>
                                    <span x-show="member.organization" class="text-xs text-neutral-500" x-text="member.organization"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="teamLoading" x-cloak class="bg-neutral-50 border border-neutral-200 rounded-2xl p-6">
                    <div class="flex items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-2 border-neutral-900 border-t-transparent mr-3"></div>
                        <span class="text-sm text-neutral-600">Loading current team...</span>
                    </div>
                </div>

                <!-- Add Member Form -->
                <div class="bg-white border border-neutral-200 rounded-2xl p-6">
                    <h3 class="text-sm font-semibold text-neutral-900 mb-4">Add Team Member</h3>

                    <form @submit.prevent="addMember()" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-2">
                                    Select Auditor <span class="text-red-500">*</span>
                                </label>
                                <select x-model="newMember.user_id" required
                                        class="w-full px-4 py-3 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 transition-all">
                                    <option value="">Choose auditor...</option>
                                    @foreach($availableAuditors as $auditor)
                                        <option value="{{ $auditor->id }}">{{ $auditor->name }} - {{ ucfirst($auditor->role_name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-2">
                                    Assign Role <span class="text-red-500">*</span>
                                </label>
                                <select x-model="newMember.role" required
                                        class="w-full px-4 py-3 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 transition-all">
                                    <option value="">Choose role...</option>
                                    <option value="lead">Lead Auditor</option>
                                    <option value="member">Team Member</option>
                                    <option value="observer">Observer</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-2 border-t border-neutral-200">
                            <button type="button" @click="resetNewMember()"
                                    class="px-4 py-2 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                                Reset
                            </button>
                            <button type="submit" :disabled="loading"
                                    class="px-6 py-3 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center shadow-sm">
                                <span x-show="!loading"><i class="fas fa-user-plus mr-2"></i>Add to Team</span>
                                <span x-show="loading" class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>Adding...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <button @click="changeTab('roles')"
                            class="inline-flex items-center px-6 py-3 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Roles
                    </button>
                    <button @click="changeTab('review')"
                            :disabled="!hasLead()"
                            :class="hasLead() ? 'bg-neutral-900 hover:bg-neutral-800' : 'bg-neutral-300 cursor-not-allowed'"
                            class="inline-flex items-center px-6 py-3 text-white rounded-xl text-sm font-medium transition-colors shadow-sm">
                        Review & Confirm
                        <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 4: Review & Confirm -->
            <div x-show="activeTab === 'review'" x-transition class="space-y-6">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-neutral-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clipboard-check text-neutral-600 text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-neutral-900 mb-2">Review & Confirm</h2>
                    <p class="text-sm text-neutral-600 max-w-2xl mx-auto">Review your team composition and confirm that all assignments are correct before finalizing.</p>
                </div>

                <!-- Team Summary -->
                <div class="bg-gradient-to-br from-neutral-50 to-neutral-100 border border-neutral-200 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-neutral-900">Final Team Composition</h3>
                        <div class="flex items-center space-x-2">
                            <div class="inline-flex items-center px-3 py-1.5 bg-white border border-neutral-200 rounded-lg text-xs font-medium">
                                <i class="fas fa-users text-neutral-600 mr-2"></i>
                                <span x-text="currentTeam.length"></span> Total Members
                            </div>
                        </div>
                    </div>

                    <!-- Role Distribution -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-white rounded-xl p-4 border border-neutral-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-neutral-600">Lead Auditors</span>
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-user-check text-green-600 text-sm"></i>
                                </div>
                            </div>
                            <p class="text-2xl font-bold text-neutral-900" x-text="getRoleCount('lead')"></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-neutral-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-neutral-600">Team Members</span>
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-user-friends text-blue-600 text-sm"></i>
                                </div>
                            </div>
                            <p class="text-2xl font-bold text-neutral-900" x-text="getRoleCount('member')"></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-neutral-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-neutral-600">Observers</span>
                                <div class="w-8 h-8 bg-neutral-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-eye text-neutral-600 text-sm"></i>
                                </div>
                            </div>
                            <p class="text-2xl font-bold text-neutral-900" x-text="getRoleCount('observer')"></p>
                        </div>
                    </div>

                    <!-- Member List -->
                    <div class="space-y-3">
                        <template x-for="member in currentTeam" :key="member.id">
                            <div class="bg-white rounded-xl p-4 border border-neutral-200 flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center"
                                         :class="getRoleBgClass(member.role)">
                                        <i :class="getRoleIcon(member.role)"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-neutral-900" x-text="member.name"></p>
                                        <p class="text-xs text-neutral-500" x-text="member.email"></p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium"
                                      :class="getRoleClass(member.role)"
                                      x-text="formatRole(member.role)"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Validation Checks -->
                <div class="bg-white border border-neutral-200 rounded-2xl p-6">
                    <h3 class="text-sm font-semibold text-neutral-900 mb-4">Validation Checks</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-lg" :class="getRoleCount('lead') === 1 ? 'bg-green-50' : 'bg-red-50'">
                            <div class="flex items-center space-x-3">
                                <i :class="getRoleCount('lead') === 1 ? 'fas fa-check-circle text-green-600' : 'fas fa-times-circle text-red-600'"></i>
                                <span class="text-sm font-medium" :class="getRoleCount('lead') === 1 ? 'text-green-900' : 'text-red-900'">
                                    Exactly one Lead Auditor assigned
                                </span>
                            </div>
                            <span class="text-xs font-medium" :class="getRoleCount('lead') === 1 ? 'text-green-700' : 'text-red-700'" x-text="getRoleCount('lead') === 1 ? 'Valid' : 'Invalid'"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg" :class="currentTeam.length > 0 ? 'bg-green-50' : 'bg-neutral-50'">
                            <div class="flex items-center space-x-3">
                                <i :class="currentTeam.length > 0 ? 'fas fa-check-circle text-green-600' : 'fas fa-info-circle text-neutral-400'"></i>
                                <span class="text-sm font-medium" :class="currentTeam.length > 0 ? 'text-green-900' : 'text-neutral-600'">
                                    Team has members assigned
                                </span>
                            </div>
                            <span class="text-xs font-medium" :class="currentTeam.length > 0 ? 'text-green-700' : 'text-neutral-500'" x-text="currentTeam.length + ' member(s)'"></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <button @click="changeTab('build')"
                            class="inline-flex items-center px-6 py-3 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Build
                    </button>
                    <button @click="finalizeTeam()"
                            :disabled="loading || getRoleCount('lead') !== 1"
                            class="inline-flex items-center px-8 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium transition-colors shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!loading"><i class="fas fa-check-double mr-2"></i>Finalize Team</span>
                        <span x-show="loading" class="flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i>Finalizing...</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Remove Member Modal -->
    <div x-show="removeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-neutral-900 bg-opacity-75 backdrop-blur-sm transition-opacity" @click="removeModal = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.stop
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                <div class="p-6 text-center">
                    <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-user-minus text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-neutral-900 mb-2">Remove Team Member</h3>
                    <p class="text-sm text-neutral-600 mb-6" x-text="removeMessage"></p>
                    <div class="flex justify-center space-x-3">
                        <button @click="removeModal = false"
                                class="px-6 py-2.5 border border-neutral-200 hover:bg-neutral-50 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                            Cancel
                        </button>
                        <button @click="removeMember()" :disabled="loading"
                                class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-lg">
                            <span x-show="!loading">Remove</span>
                            <span x-show="loading" class="flex items-center justify-center">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Removing...
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
        'audits' => $audits,
        'availableAuditors' => $availableAuditors,
        'teamStats' => $teamStats,
        'userContext' => $userContext,
        'csrf' => csrf_token(),
    ];
@endphp

<script>
function teamManagement() {
    const data = @json($bootstrap);

    return {
        // State
        loading: false,
        teamLoading: false,
        selectedAuditId: '',
        currentTeam: [],
        removeModal: false,
        removeMessage: '',
        memberToRemove: null,

        // Wizard state
        activeTab: 'select',
        completed: {
            select: false,
            roles: false,
            build: false,
            review: false
        },

        tabs: {
            select: { label: 'Select Audit', icon: 'fas fa-clipboard-list' },
            roles: { label: 'Understand Roles', icon: 'fas fa-user-shield' },
            build: { label: 'Build Team', icon: 'fas fa-users' },
            review: { label: 'Review & Confirm', icon: 'fas fa-clipboard-check' }
        },

        // Stats
        stats: {
            total_members: data.teamStats?.total_members ?? 0,
            lead_count: data.teamStats?.lead_count ?? 0,
            member_count: data.teamStats?.member_count ?? 0,
            observer_count: data.teamStats?.observer_count ?? 0,
            avg_team_size: data.teamStats?.avg_team_size ?? 0
        },

        // New member form
        newMember: {
            user_id: '',
            role: ''
        },

        // Initialization
        init() {
            console.log('Team Management Wizard initialized');
        },

        // Wizard navigation
        changeTab(tab) {
            if (tab === 'build' && !this.selectedAuditId) {
                this.notify('error', 'Please select an audit first');
                return;
            }
            this.activeTab = tab;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // Load team
        async loadTeam() {
            if (!this.selectedAuditId) {
                this.currentTeam = [];
                return;
            }

            this.teamLoading = true;
            this.completed.select = true;

            try {
                const response = await fetch(`/audits/team/get?audit_id=${this.selectedAuditId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': data.csrf
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    this.currentTeam = result.team || [];
                } else {
                    this.notify('error', result.error || 'Failed to load team');
                }
            } catch (error) {
                console.error('Load team error:', error);
                this.notify('error', `Failed to load team: ${error.message}`);
            } finally {
                this.teamLoading = false;
            }
        },

        // Add member
        async addMember() {
            if (!this.newMember.user_id || !this.newMember.role) {
                this.notify('error', 'Please select both auditor and role');
                return;
            }

            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('_token', data.csrf);
                formData.append('audit_id', this.selectedAuditId);
                formData.append('user_id', this.newMember.user_id);
                formData.append('role', this.newMember.role);

                const response = await fetch('/audits/team/add-member', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const text = await response.text();

                if (response.ok) {
                    this.notify('success', 'Team member added successfully');
                    this.resetNewMember();
                    await this.loadTeam();
                    this.completed.build = true;
                } else {
                    this.notify('error', text || 'Failed to add member');
                }
            } catch (error) {
                console.error('Add member error:', error);
                this.notify('error', `Failed to add member: ${error.message}`);
            } finally {
                this.loading = false;
            }
        },

        // Confirm remove
        confirmRemoveMember(member) {
            this.memberToRemove = member;
            this.removeMessage = `Are you sure you want to remove "${member.name}" from the audit team?`;
            this.removeModal = true;
        },

        // Remove member
        async removeMember() {
            if (!this.memberToRemove) return;

            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('_token', data.csrf);
                formData.append('audit_id', this.selectedAuditId);
                formData.append('user_id', this.memberToRemove.user_id);

                const response = await fetch('/audits/team/remove-member', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const text = await response.text();

                if (response.ok) {
                    this.notify('success', 'Team member removed successfully');
                    this.removeModal = false;
                    this.memberToRemove = null;
                    await this.loadTeam();
                } else {
                    this.notify('error', text || 'Failed to remove member');
                }
            } catch (error) {
                console.error('Remove member error:', error);
                this.notify('error', `Failed to remove member: ${error.message}`);
            } finally {
                this.loading = false;
            }
        },

        // Finalize team
        async finalizeTeam() {
            if (this.getRoleCount('lead') !== 1) {
                this.notify('error', 'Exactly one Lead Auditor is required');
                return;
            }

            this.notify('success', 'Team composition confirmed and finalized!');
            this.completed.review = true;

            setTimeout(() => {
                this.changeTab('select');
            }, 2000);
        },

        // Helper methods
        hasLead() {
            return this.currentTeam.filter(m => m.role === 'lead').length > 0;
        },

        getRoleCount(role) {
            return this.currentTeam.filter(m => m.role === role).length;
        },

        canRemove(member) {
            const leadCount = this.currentTeam.filter(m => m.role === 'lead').length;
            if (member.role === 'lead' && leadCount <= 1) {
                return false;
            }
            return true;
        },

        resetNewMember() {
            this.newMember = { user_id: '', role: '' };
        },

        formatRole(role) {
            const roles = {
                'lead': 'Lead Auditor',
                'member': 'Team Member',
                'observer': 'Observer'
            };
            return roles[role] || role;
        },

        getRoleClass(role) {
            const classes = {
                'lead': 'bg-green-100 text-green-700',
                'member': 'bg-blue-100 text-blue-700',
                'observer': 'bg-neutral-100 text-neutral-600'
            };
            return classes[role] || 'bg-neutral-100 text-neutral-600';
        },

        getRoleBgClass(role) {
            const classes = {
                'lead': 'bg-green-100 text-green-600',
                'member': 'bg-blue-100 text-blue-600',
                'observer': 'bg-neutral-100 text-neutral-600'
            };
            return classes[role] || 'bg-neutral-100 text-neutral-600';
        },

        getRoleIcon(role) {
            const icons = {
                'lead': 'fas fa-user-check',
                'member': 'fas fa-user-friends',
                'observer': 'fas fa-eye'
            };
            return icons[role] || 'fas fa-user';
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
