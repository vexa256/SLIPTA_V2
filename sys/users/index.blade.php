@extends('layouts.app')

@section('title', 'User Management')

@section('breadcrumb', 'User Management')

@section('content')
<div x-data="userManagement()" x-init="init()" class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">User Management</h1>
            <p class="text-sm text-neutral-500 mt-1">Create and manage system users with role assignments</p>
        </div>
        <div class="flex items-center space-x-3">
            <button @click="openMyRolesModal()" class="inline-flex items-center px-4 py-2 bg-white border border-neutral-200 text-neutral-700 rounded-xl text-sm font-medium hover:bg-neutral-50 transition-colors">
                <i class="fas fa-id-badge mr-2 text-xs"></i>
                My Roles
            </button>
            <button @click="openCreateModal()" class="inline-flex items-center px-4 py-2 bg-neutral-900 text-white rounded-xl text-sm font-medium hover:bg-neutral-800 transition-colors">
                <i class="fas fa-plus mr-2 text-xs"></i>
                Create User
            </button>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm">
        <div class="p-4 border-b border-neutral-200">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-medium text-neutral-900">All Users</h2>
                <div class="flex items-center space-x-2">
                    <input type="text" x-model="searchQuery" placeholder="Search users..." class="px-3 py-2 text-sm border border-neutral-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Organization</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Roles</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <template x-for="user in filteredUsers" :key="user.id">
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-neutral-200 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-user text-neutral-600 text-xs"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-neutral-900" x-text="user.name"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-neutral-700" x-text="user.email"></p>
                                <p class="text-xs text-neutral-500" x-text="user.phone || 'N/A'"></p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-neutral-700" x-text="user.organization || 'N/A'"></p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-neutral-100 text-neutral-800" x-text="user.role_count + ' role(s)'"></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium" :class="user.is_active ? 'bg-green-50 text-green-700' : 'bg-neutral-100 text-neutral-600'" x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <button @click="viewUser(user.id)" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                                        <i class="fas fa-eye text-neutral-600 text-xs"></i>
                                    </button>
                                    <button @click="editUser(user)" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                                        <i class="fas fa-edit text-neutral-600 text-xs"></i>
                                    </button>
                                    <button @click="toggleStatus(user.id)" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                                        <i :class="user.is_active ? 'fas fa-ban text-neutral-600' : 'fas fa-check text-green-600'" class="text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Edit User Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="closeModal()" class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-y-auto" @click.away="closeModal()">
                <div class="sticky top-0 bg-white p-6 border-b border-neutral-200 z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-neutral-900" x-text="modalMode === 'create' ? 'Create New User' : 'Edit User'"></h3>
                            <p class="text-sm text-neutral-500 mt-0.5">Configure user details and assign roles with appropriate access levels</p>
                        </div>
                        <button @click="closeModal()" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                            <i class="fas fa-times text-neutral-600"></i>
                        </button>
                    </div>
                </div>

                <form :action="modalMode === 'create' ? '{{ route('users.store') }}' : '/users/' + editingUser.id" method="POST">
                    @csrf
                    <input type="hidden" name="_method" :value="modalMode === 'edit' ? 'PUT' : 'POST'">

                    <!-- Tab Navigation -->
                    <div class="border-b border-neutral-200 sticky top-[89px] bg-white z-10">
                        <nav class="flex space-x-4 px-6">
                            <button type="button" @click="activeTab = 'details'" :class="activeTab === 'details' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-700'" class="py-3 px-1 text-sm font-medium transition-colors">
                                User Details
                            </button>
                            <button type="button" @click="activeTab = 'roles'" :class="activeTab === 'roles' ? 'border-b-2 border-neutral-900 text-neutral-900' : 'text-neutral-500 hover:text-neutral-700'" class="py-3 px-1 text-sm font-medium transition-colors">
                                Role Assignments
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Tab 1: User Details -->
                        <div x-show="activeTab === 'details'" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1">Full Name</label>
                                    <input type="text" name="name" x-model="formData.name" required class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" placeholder="John Doe">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1">Email Address</label>
                                    <input type="email" name="email" x-model="formData.email" required class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" placeholder="john@example.com">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
                                    <input type="password" name="password" x-model="formData.password" :required="modalMode === 'create'" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" placeholder="••••••••">
                                    <p class="text-xs text-neutral-500 mt-1" x-show="modalMode === 'edit'">Leave blank to keep current password</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 mb-1">Phone</label>
                                    <input type="text" name="phone" x-model="formData.phone" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" placeholder="+1234567890">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Organization</label>
                                <input type="text" name="organization" x-model="formData.organization" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" placeholder="Ministry of Health">
                            </div>
                        </div>

                        <!-- Tab 2: Role Assignments -->
                        <div x-show="activeTab === 'roles'" class="space-y-6">

                            <!-- Role Information Panel -->
                            <div class="bg-gradient-to-br from-neutral-50 to-neutral-100/50 rounded-xl border border-neutral-200 p-5">
                                <div class="flex items-start space-x-3">
                                    <div class="w-10 h-10 bg-neutral-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-info text-white text-sm"></i>
                                    </div>
                                    <div class="flex-1 space-y-3">
                                        <div>
                                            <h4 class="text-sm font-semibold text-neutral-900 mb-1">Understanding SLIPTA Roles</h4>
                                            <p class="text-xs text-neutral-600 leading-relaxed">Each role grants specific permissions and access levels within the SLIPTA system. Select roles carefully based on the user's responsibilities.</p>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="bg-white rounded-lg p-3 border border-neutral-200">
                                                <div class="flex items-center space-x-2 mb-1.5">
                                                    <div class="w-6 h-6 bg-red-100 rounded flex items-center justify-center">
                                                        <i class="fas fa-crown text-red-600 text-xs"></i>
                                                    </div>
                                                    <h5 class="text-xs font-semibold text-neutral-900">System Admin</h5>
                                                </div>
                                                <p class="text-xs text-neutral-600 leading-relaxed">Platform root access. Manages infrastructure, schemas, and can assign any role including system_admin.</p>
                                            </div>

                                            <div class="bg-white rounded-lg p-3 border border-neutral-200">
                                                <div class="flex items-center space-x-2 mb-1.5">
                                                    <div class="w-6 h-6 bg-purple-100 rounded flex items-center justify-center">
                                                        <i class="fas fa-globe text-purple-600 text-xs"></i>
                                                    </div>
                                                    <h5 class="text-xs font-semibold text-neutral-900">Project Coordinator</h5>
                                                </div>
                                                <p class="text-xs text-neutral-600 leading-relaxed">Global program owner. Full application access across all countries, labs, and audits. Cannot manage infrastructure.</p>
                                            </div>

                                            <div class="bg-white rounded-lg p-3 border border-neutral-200">
                                                <div class="flex items-center space-x-2 mb-1.5">
                                                    <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center">
                                                        <i class="fas fa-flag text-blue-600 text-xs"></i>
                                                    </div>
                                                    <h5 class="text-xs font-semibold text-neutral-900">Country Coordinator</h5>
                                                </div>
                                                <p class="text-xs text-neutral-600 leading-relaxed">Country-bound authority. Manages all labs and audits within assigned country. Can assign in-country roles.</p>
                                            </div>

                                            <div class="bg-white rounded-lg p-3 border border-neutral-200">
                                                <div class="flex items-center space-x-2 mb-1.5">
                                                    <div class="w-6 h-6 bg-green-100 rounded flex items-center justify-center">
                                                        <i class="fas fa-user-tie text-green-600 text-xs"></i>
                                                    </div>
                                                    <h5 class="text-xs font-semibold text-neutral-900">Lead Auditor</h5>
                                                </div>
                                                <p class="text-xs text-neutral-600 leading-relaxed">Assigned audit owner. Plans audits, manages team, enters responses, and finalizes reports for assigned audits only.</p>
                                            </div>

                                            <div class="bg-white rounded-lg p-3 border border-neutral-200">
                                                <div class="flex items-center space-x-2 mb-1.5">
                                                    <div class="w-6 h-6 bg-amber-100 rounded flex items-center justify-center">
                                                        <i class="fas fa-clipboard-check text-amber-600 text-xs"></i>
                                                    </div>
                                                    <h5 class="text-xs font-semibold text-neutral-900">Auditor</h5>
                                                </div>
                                                <p class="text-xs text-neutral-600 leading-relaxed">Certified SLIPTA auditor. Enters Y/P/N/NA responses, uploads evidence, and responds to findings on assigned audits.</p>
                                            </div>

                                            <div class="bg-white rounded-lg p-3 border border-neutral-200">
                                                <div class="flex items-center space-x-2 mb-1.5">
                                                    <div class="w-6 h-6 bg-cyan-100 rounded flex items-center justify-center">
                                                        <i class="fas fa-building text-cyan-600 text-xs"></i>
                                                    </div>
                                                    <h5 class="text-xs font-semibold text-neutral-900">Laboratory Manager</h5>
                                                </div>
                                                <p class="text-xs text-neutral-600 leading-relaxed">Lab authority. Views findings, provides CAPA responses, uploads SOPs, and acknowledges results for own laboratory.</p>
                                            </div>

                                            <div class="bg-white rounded-lg p-3 border border-neutral-200">
                                                <div class="flex items-center space-x-2 mb-1.5">
                                                    <div class="w-6 h-6 bg-indigo-100 rounded flex items-center justify-center">
                                                        <i class="fas fa-microscope text-indigo-600 text-xs"></i>
                                                    </div>
                                                    <h5 class="text-xs font-semibold text-neutral-900">Quality Officer</h5>
                                                </div>
                                                <p class="text-xs text-neutral-600 leading-relaxed">Lab QA specialist. Manages CAPA artifacts, maintains IQC/EQA documentation, and uploads quality records.</p>
                                            </div>

                                            <div class="bg-white rounded-lg p-3 border border-neutral-200">
                                                <div class="flex items-center space-x-2 mb-1.5">
                                                    <div class="w-6 h-6 bg-neutral-100 rounded flex items-center justify-center">
                                                        <i class="fas fa-eye text-neutral-600 text-xs"></i>
                                                    </div>
                                                    <h5 class="text-xs font-semibold text-neutral-900">Observer</h5>
                                                </div>
                                                <p class="text-xs text-neutral-600 leading-relaxed">Read-only trainee. Can view assigned audit UI only. Cannot enter data, upload evidence, or export.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-neutral-900">Assign one or more roles to this user</p>
                                <button type="button" @click="addRole()" class="inline-flex items-center px-3 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-lg text-xs font-medium transition-colors">
                                    <i class="fas fa-plus mr-1.5"></i>
                                    Add Role
                                </button>
                            </div>

                            <template x-for="(role, index) in formData.roles" :key="index">
                                <div class="p-4 bg-white rounded-xl border-2 border-neutral-200 hover:border-neutral-300 transition-colors">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-8 h-8 bg-neutral-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-id-badge text-neutral-600 text-xs"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-neutral-900">Role Assignment <span x-text="index + 1"></span></h4>
                                                <p class="text-xs text-neutral-500">Configure role and access scope</p>
                                            </div>
                                        </div>
                                        <button type="button" @click="removeRole(index)" class="p-1.5 hover:bg-neutral-100 rounded-lg transition-colors">
                                            <i class="fas fa-times text-neutral-600 text-xs"></i>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-neutral-600 mb-1.5">Role</label>
                                            <select :name="'roles[' + index + '][role_id]'" x-model="role.role_id" @change="onRoleChange(index)" required class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                                <option value="">Select Role</option>
                                                @foreach($roles as $role)
                                                <option value="{{ $role->id }}">{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-neutral-600 mb-1.5">Country Scope</label>
                                            <select :name="'roles[' + index + '][country_id]'" x-model="role.country_id" @change="onCountryChange(index)" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                                <option value="">All Countries</option>
                                                @foreach($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-neutral-600 mb-1.5">Laboratory Scope</label>
                                            <select :name="'roles[' + index + '][laboratory_id]'" x-model="role.laboratory_id" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" :disabled="!role.country_id">
                                                <option value="">All Laboratories</option>
                                                <template x-for="lab in getFilteredLaboratories(role.country_id)" :key="lab.id">
                                                    <option :value="lab.id" x-text="lab.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <div x-show="getRoleInfo(role.role_id)" class="mt-3 p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                                        <p class="text-xs text-neutral-600" x-text="getRoleInfo(role.role_id)"></p>
                                    </div>
                                </div>
                            </template>

                            <div x-show="formData.roles.length === 0" class="text-center py-12 bg-neutral-50 rounded-xl border-2 border-dashed border-neutral-200">
                                <div class="w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-user-shield text-neutral-400 text-2xl"></i>
                                </div>
                                <p class="text-sm font-medium text-neutral-900 mb-1">No roles assigned yet</p>
                                <p class="text-xs text-neutral-500">Click "Add Role" above to assign roles to this user</p>
                            </div>
                        </div>
                    </div>

                    <div class="sticky bottom-0 bg-white p-6 border-t border-neutral-200 flex items-center justify-between">
                        <button type="button" @click="closeModal()" class="px-4 py-2 bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors">
                            <span x-text="modalMode === 'create' ? 'Create User' : 'Update User'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View User Details Modal -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="closeViewModal()" class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl">
                <div class="p-6 border-b border-neutral-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-neutral-900">User Details</h3>
                        <button @click="closeViewModal()" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                            <i class="fas fa-times text-neutral-600"></i>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-6" x-show="viewingUser">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-neutral-200 rounded-xl flex items-center justify-center">
                            <i class="fas fa-user text-neutral-600 text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-neutral-900" x-text="viewingUser?.name"></h4>
                            <p class="text-sm text-neutral-500" x-text="viewingUser?.email"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-neutral-500 mb-1">Phone</p>
                            <p class="text-sm text-neutral-900" x-text="viewingUser?.phone || 'N/A'"></p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-neutral-500 mb-1">Organization</p>
                            <p class="text-sm text-neutral-900" x-text="viewingUser?.organization || 'N/A'"></p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-neutral-500 mb-1">Status</p>
                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium" :class="viewingUser?.is_active ? 'bg-green-50 text-green-700' : 'bg-neutral-100 text-neutral-600'" x-text="viewingUser?.is_active ? 'Active' : 'Inactive'"></span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-neutral-500 mb-1">Created</p>
                            <p class="text-sm text-neutral-900" x-text="formatDate(viewingUser?.created_at)"></p>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-medium text-neutral-900">Role Assignments</h5>
                            <button @click="openAssignRoleModal()" class="inline-flex items-center px-3 py-1.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-900 rounded-lg text-xs font-medium transition-colors">
                                <i class="fas fa-plus mr-1.5"></i>
                                Assign Role
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="role in userRoles" :key="role.id">
                                <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-xl border border-neutral-200">
                                    <div>
                                        <p class="text-sm font-medium text-neutral-900" x-text="role.role_name"></p>
                                        <p class="text-xs text-neutral-500">
                                            <span x-show="role.country_name" x-text="'Country: ' + role.country_name"></span>
                                            <span x-show="role.country_name && role.laboratory_name"> • </span>
                                            <span x-show="role.laboratory_name" x-text="'Lab: ' + role.laboratory_name"></span>
                                            <span x-show="!role.country_name && !role.laboratory_name">Global Scope</span>
                                        </p>
                                    </div>
                                    <form :action="'/users/roles/' + role.id" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 hover:bg-neutral-200 rounded-lg transition-colors">
                                            <i class="fas fa-trash text-neutral-600 text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </template>
                            <div x-show="userRoles.length === 0" class="text-center py-4">
                                <p class="text-sm text-neutral-500">No roles assigned</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Role Modal -->
    <div x-show="showAssignRoleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="closeAssignRoleModal()" class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg">
                <div class="p-6 border-b border-neutral-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-neutral-900">Assign Role</h3>
                        <button @click="closeAssignRoleModal()" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                            <i class="fas fa-times text-neutral-600"></i>
                        </button>
                    </div>
                </div>

                <form action="{{ route('users.assign-role') }}" method="POST">
                    @csrf
                    <input type="hidden" name="user_id" :value="viewingUserId">

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Role</label>
                            <select name="role_id" x-model="assignRoleForm.role_id" required class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Country Scope</label>
                            <select name="country_id" x-model="assignRoleForm.country_id" @change="onAssignCountryChange()" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
                                <option value="">All Countries</option>
                                @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Laboratory Scope</label>
                            <select name="laboratory_id" x-model="assignRoleForm.laboratory_id" class="w-full px-3 py-2 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10" :disabled="!assignRoleForm.country_id">
                                <option value="">All Laboratories</option>
                                <template x-for="lab in getFilteredLaboratories(assignRoleForm.country_id)" :key="lab.id">
                                    <option :value="lab.id" x-text="lab.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="p-6 border-t border-neutral-200 flex items-center justify-between">
                        <button type="button" @click="closeAssignRoleModal()" class="px-4 py-2 bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 rounded-xl text-sm font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors">
                            Assign Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- My Roles Modal -->
    <div x-show="showMyRolesModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="closeMyRolesModal()" class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl">
                <div class="p-6 border-b border-neutral-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-neutral-900">My Role Assignments</h3>
                            <p class="text-sm text-neutral-500 mt-0.5">Your current roles and access levels in the system</p>
                        </div>
                        <button @click="closeMyRolesModal()" class="p-2 hover:bg-neutral-100 rounded-lg transition-colors">
                            <i class="fas fa-times text-neutral-600"></i>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center space-x-4 mb-6 pb-6 border-b border-neutral-200">
                        <div class="w-16 h-16 bg-gradient-to-br from-neutral-900 to-neutral-700 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-user text-white text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-neutral-900">{{ auth()->user()->name }}</h4>
                            <p class="text-sm text-neutral-500">{{ auth()->user()->email }}</p>
                            <p class="text-xs text-neutral-400 mt-1">{{ auth()->user()->organization ?? 'No organization' }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h5 class="text-sm font-medium text-neutral-900 mb-3">Active Role Assignments</h5>

                        @php
                            $currentUserRoles = DB::table('user_roles as ur')
                                ->join('roles as r', 'ur.role_id', '=', 'r.id')
                                ->leftJoin('countries as c', 'ur.country_id', '=', 'c.id')
                                ->leftJoin('laboratories as l', 'ur.laboratory_id', '=', 'l.id')
                                ->where('ur.user_id', auth()->id())
                                ->where('ur.is_active', 1)
                                ->select(
                                    'r.name as role_name',
                                    'c.name as country_name',
                                    'l.name as laboratory_name',
                                    'ur.assigned_at'
                                )
                                ->get();
                        @endphp

                        @if($currentUserRoles->count() > 0)
                            @foreach($currentUserRoles as $role)
                            <div class="p-4 bg-gradient-to-br from-neutral-50 to-neutral-100/50 rounded-xl border border-neutral-200">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-10 h-10 bg-neutral-900 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <i class="fas fa-shield-alt text-white text-sm"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h6 class="text-sm font-semibold text-neutral-900 mb-1">{{ ucwords(str_replace('_', ' ', $role->role_name)) }}</h6>
                                            <div class="space-y-1">
                                                @if($role->country_name || $role->laboratory_name)
                                                    <div class="flex items-center space-x-2 text-xs">
                                                        @if($role->country_name)
                                                            <span class="inline-flex items-center px-2 py-0.5 bg-white rounded-md border border-neutral-200">
                                                                <i class="fas fa-flag text-neutral-500 mr-1.5"></i>
                                                                <span class="text-neutral-700">{{ $role->country_name }}</span>
                                                            </span>
                                                        @endif
                                                        @if($role->laboratory_name)
                                                            <span class="inline-flex items-center px-2 py-0.5 bg-white rounded-md border border-neutral-200">
                                                                <i class="fas fa-building text-neutral-500 mr-1.5"></i>
                                                                <span class="text-neutral-700">{{ $role->laboratory_name }}</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 bg-white rounded-md border border-neutral-200 text-xs">
                                                        <i class="fas fa-globe text-neutral-500 mr-1.5"></i>
                                                        <span class="text-neutral-700">Global Scope</span>
                                                    </span>
                                                @endif
                                                <p class="text-xs text-neutral-500 mt-1.5">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Assigned {{ \Carbon\Carbon::parse($role->assigned_at)->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 bg-green-50 text-green-700 rounded-lg text-xs font-medium">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Active
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-user-slash text-neutral-400 text-2xl"></i>
                                </div>
                                <p class="text-sm font-medium text-neutral-900 mb-1">No Active Roles</p>
                                <p class="text-xs text-neutral-500">You don't have any active role assignments yet</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="p-6 border-t border-neutral-200 bg-neutral-50">
                    <button @click="closeMyRolesModal()" class="w-full px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-sm font-medium transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function userManagement() {
    return {
        users: @json($users),
        allLaboratories: @json($laboratories),
        searchQuery: '',
        showModal: false,
        showViewModal: false,
        showAssignRoleModal: false,
        showMyRolesModal: false,
        modalMode: 'create',
        activeTab: 'details',
        editingUser: null,
        viewingUser: null,
        viewingUserId: null,
        userRoles: [],
        formData: {
            name: '',
            email: '',
            password: '',
            phone: '',
            organization: '',
            roles: []
        },
        assignRoleForm: {
            role_id: '',
            country_id: '',
            laboratory_id: ''
        },
        roleDescriptions: {
            'system_admin': 'Platform root access with full infrastructure control',
            'project_coordinator': 'Global program owner with application-wide access',
            'country_coordinator': 'Country-bound authority managing all in-country operations',
            'lead_auditor': 'Audit owner responsible for planning and finalizing assessments',
            'auditor': 'Certified assessor entering responses and uploading evidence',
            'laboratory_manager': 'Laboratory authority managing findings and CAPA',
            'quality_officer': 'QA specialist maintaining quality documentation',
            'observer': 'Read-only trainee with view-only access'
        },

        init() {

        },

        get filteredUsers() {
            if (!this.searchQuery) return this.users;
            const query = this.searchQuery.toLowerCase();
            return this.users.filter(user =>
                user.name.toLowerCase().includes(query) ||
                user.email.toLowerCase().includes(query) ||
                (user.organization && user.organization.toLowerCase().includes(query))
            );
        },

        openCreateModal() {
            this.modalMode = 'create';
            this.activeTab = 'details';
            this.resetForm();
            this.showModal = true;
        },

        editUser(user) {
            this.modalMode = 'edit';
            this.activeTab = 'details';
            this.editingUser = user;
            this.formData = {
                name: user.name,
                email: user.email,
                password: '',
                phone: user.phone || '',
                organization: user.organization || '',
                roles: []
            };
            this.showModal = true;
        },

        async viewUser(userId) {
            try {
                const response = await fetch(`/users/${userId}`);
                const data = await response.json();

                if (data.success) {
                    this.viewingUser = data.user;
                    this.viewingUserId = userId;
                    this.userRoles = data.roles;
                    this.showViewModal = true;
                }
            } catch (error) {
                console.error('Failed to load user:', error);
            }
        },

        async toggleStatus(userId) {
            if (!confirm('Are you sure you want to change this user\'s status?')) return;

            try {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/users/${userId}/toggle-status`;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                form.appendChild(csrfInput);

                document.body.appendChild(form);
                form.submit();
            } catch (error) {
                console.error('Failed to toggle status:', error);
            }
        },

        closeModal() {
            this.showModal = false;
            this.resetForm();
        },

        closeViewModal() {
            this.showViewModal = false;
            this.viewingUser = null;
            this.viewingUserId = null;
            this.userRoles = [];
        },

        openAssignRoleModal() {
            this.assignRoleForm = {
                role_id: '',
                country_id: '',
                laboratory_id: ''
            };
            this.showAssignRoleModal = true;
        },

        closeAssignRoleModal() {
            this.showAssignRoleModal = false;
        },

        openMyRolesModal() {
            this.showMyRolesModal = true;
        },

        closeMyRolesModal() {
            this.showMyRolesModal = false;
        },

        addRole() {
            this.formData.roles.push({
                role_id: '',
                country_id: '',
                laboratory_id: ''
            });
        },

        removeRole(index) {
            this.formData.roles.splice(index, 1);
        },

        onRoleChange(index) {
            this.formData.roles[index].country_id = '';
            this.formData.roles[index].laboratory_id = '';
        },

        onCountryChange(index) {
            this.formData.roles[index].laboratory_id = '';
        },

        onAssignCountryChange() {
            this.assignRoleForm.laboratory_id = '';
        },

        getFilteredLaboratories(countryId) {
            if (!countryId) return [];
            return this.allLaboratories.filter(lab => lab.country_id == countryId);
        },

        getRoleInfo(roleId) {
            if (!roleId) return '';
            const roleData = @json($roles);
            const role = roleData.find(r => r.id == roleId);
            if (!role) return '';
            return this.roleDescriptions[role.name] || '';
        },

        resetForm() {
            this.formData = {
                name: '',
                email: '',
                password: '',
                phone: '',
                organization: '',
                roles: []
            };
            this.editingUser = null;
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
