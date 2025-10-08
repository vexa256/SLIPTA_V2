<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="csrf-token-placeholder">
    <title>SLIPTA Digital Assessment System - Dashboard</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.3);
            border-radius: 2px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.5);
        }
    </style>
</head>
<body class="antialiased bg-gray-50 text-gray-900 overflow-hidden" x-data="sliptaApp()" x-cloak>
    <!-- Main Container -->
    <div class="h-screen flex">

        <!-- Mobile Header -->
        <div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 text-gray-600 hover:text-gray-900">
                <i class="fas fa-bars text-lg"></i>
            </button>
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 bg-black rounded flex items-center justify-center">
                    <i class="fas fa-microscope text-white text-xs"></i>
                </div>
                <span class="text-sm font-medium">SLIPTA</span>
            </div>
            <div class="w-8"></div>
        </div>

        <!-- Sidebar - Exact Shadcn Style -->
        <aside class="fixed lg:static inset-y-0 left-0 z-40 w-56 bg-white border-r border-gray-200 transform lg:transform-none transition-transform duration-300"
               :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">

            <!-- Sidebar Container -->
            <div class="h-full flex flex-col">

                <!-- Header -->
                <div class="flex-shrink-0 p-6">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 bg-black rounded flex items-center justify-center">
                            <i class="fas fa-microscope text-white text-xs"></i>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-900">SLIPTA Digital Assessment</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex-1 overflow-y-auto custom-scrollbar px-3 pb-6">

                    <!-- DASHBOARDS Section -->
                    <div class="mb-6">
                        <h3 class="mb-2 px-3 text-xs font-medium text-gray-500 uppercase tracking-wider">DASHBOARDS</h3>
                        <div class="space-y-1">
                            <a href="#" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-home mr-3 h-4 w-4 text-gray-400"></i>
                                Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Foundation Section -->
                    <div class="mb-6">
                        <h3 class="mb-2 px-3 text-xs font-medium text-gray-500 uppercase tracking-wider">FOUNDATION</h3>
                        <div class="space-y-1">
                            <div x-show="userRole === 'system_admin' || userRole === 'project_coordinator'" x-data="{ open: false }">
                                <button @click="open = !open" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-users-cog mr-3 h-4 w-4 text-gray-400"></i>
                                        User Management
                                    </div>
                                    <i class="fas fa-chevron-right h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="ml-6 space-y-1 mt-1">
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Users</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Roles</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Role Assignments</a>
                                </div>
                            </div>

                            <a href="#" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-globe-africa mr-3 h-4 w-4 text-gray-400"></i>
                                Countries
                            </a>

                            <a href="#" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-building mr-3 h-4 w-4 text-gray-400"></i>
                                Laboratory Directory
                            </a>
                        </div>
                    </div>

                    <!-- SLIPTA Catalogue Section -->
                    <div class="mb-6">
                        <h3 class="mb-2 px-3 text-xs font-medium text-gray-500 uppercase tracking-wider">SLIPTA CATALOGUE</h3>
                        <div class="space-y-1">
                            <div x-data="{ open: false }">
                                <button @click="open = !open" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-list-alt mr-3 h-4 w-4 text-gray-400"></i>
                                        SLIPTA Structure
                                    </div>
                                    <i class="fas fa-chevron-right h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="ml-6 space-y-1 mt-1">
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">12 SLIPTA Sections</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">145 Questions</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Sub-questions</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Lifecycle Section -->
                    <div class="mb-6">
                        <h3 class="mb-2 px-3 text-xs font-medium text-gray-500 uppercase tracking-wider">AUDIT LIFECYCLE</h3>
                        <div class="space-y-1">
                            <div x-data="{ open: false }">
                                <button @click="open = !open" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-plus-circle mr-3 h-4 w-4 text-gray-400"></i>
                                        Audit Workspace
                                    </div>
                                    <i class="fas fa-chevron-right h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="ml-6 space-y-1 mt-1">
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Create Audit</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Link to Prior Audit</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Assign Team</a>
                                </div>
                            </div>

                            <div x-data="{ open: false }">
                                <button @click="open = !open" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-edit mr-3 h-4 w-4 text-gray-400"></i>
                                        Response Entry
                                    </div>
                                    <i class="fas fa-chevron-right h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="ml-6 space-y-1 mt-1">
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Question Responses (Y/P/N/NA)</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Sub-question Responses</a>
                                </div>
                            </div>

                            <a href="#" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-paperclip mr-3 h-4 w-4 text-gray-400"></i>
                                Evidence Management
                            </a>

                            <div x-data="{ open: false }">
                                <button @click="open = !open" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle mr-3 h-4 w-4 text-gray-400"></i>
                                        Findings & Actions
                                    </div>
                                    <i class="fas fa-chevron-right h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="ml-6 space-y-1 mt-1">
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Findings Log</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Action Plans</a>
                                </div>
                            </div>

                            <a href="#" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-flag-checkered mr-3 h-4 w-4 text-gray-400"></i>
                                Close Audit
                            </a>
                        </div>
                    </div>

                    <!-- Views & Reports Section -->
                    <div class="mb-6">
                        <h3 class="mb-2 px-3 text-xs font-medium text-gray-500 uppercase tracking-wider">VIEWS & REPORTS</h3>
                        <div class="space-y-1">
                            <a href="#" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-calculator mr-3 h-4 w-4 text-gray-400"></i>
                                Real-time Scoring
                            </a>

                            <div x-data="{ open: false }">
                                <button @click="open = !open" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-clipboard-list mr-3 h-4 w-4 text-gray-400"></i>
                                        My Audits
                                    </div>
                                    <i class="fas fa-chevron-right h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="ml-6 space-y-1 mt-1">
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Lead Auditor View</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Team Member View</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Observer View</a>
                                </div>
                            </div>

                            <a href="#" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100" x-show="userRole === 'country_coordinator'">
                                <i class="fas fa-flag mr-3 h-4 w-4 text-gray-400"></i>
                                Country Dashboard
                            </a>

                            <a href="#" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100" x-show="userRole === 'system_admin' || userRole === 'project_coordinator'">
                                <i class="fas fa-globe mr-3 h-4 w-4 text-gray-400"></i>
                                System Dashboard
                            </a>

                            <div x-data="{ open: false }">
                                <button @click="open = !open" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-chart-bar mr-3 h-4 w-4 text-gray-400"></i>
                                        Reports & Analytics
                                    </div>
                                    <i class="fas fa-chevron-right h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="ml-6 space-y-1 mt-1">
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Audit Summary Report</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Section Scorecards</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Team Activity</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Export Center (PDF/CSV)</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Administration Section -->
                    <div class="mb-6" x-show="userRole === 'system_admin' || userRole === 'project_coordinator'">
                        <h3 class="mb-2 px-3 text-xs font-medium text-gray-500 uppercase tracking-wider">ADMINISTRATION</h3>
                        <div class="space-y-1">
                            <a href="#" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-shield-alt mr-3 h-4 w-4 text-gray-400"></i>
                                Catalogue Governance
                            </a>

                            <div x-data="{ open: false }">
                                <button @click="open = !open" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-heartbeat mr-3 h-4 w-4 text-gray-400"></i>
                                        System Health
                                    </div>
                                    <i class="fas fa-chevron-right h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="ml-6 space-y-1 mt-1">
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Active Sessions</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">System Settings</a>
                                    <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Global Counters</a>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Bottom CTA -->

                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-50 lg:hidden z-30 transition-opacity"></div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden" :class="{ 'pt-16 lg:pt-0': true }">
            <!-- Desktop Header -->
            <header class="hidden lg:flex bg-white border-b border-gray-200 px-6 py-4 items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="flex items-center space-x-2 px-3 py-2 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                        <i class="fas fa-calendar text-gray-400"></i>
                        <span>Pick a date</span>
                    </button>
                    <button class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                        Download
                    </button>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-auto bg-gray-50 p-6">
                <!-- Welcome Section -->
                <div class="mb-8">
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-semibold text-gray-900">Welcome back, <span x-text="userName"></span></h1>
                                <p class="text-gray-600 mt-1">Here's what's happening with your laboratory assessments today.</p>
                            </div>
                            <div class="flex items-center space-x-2 bg-gray-50 px-4 py-2 rounded-lg border border-gray-200">
                                <i class="fas fa-calendar text-gray-400"></i>
                                <span class="text-sm font-medium text-gray-700" x-text="currentDate"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Audits -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Audits</p>
                                <p class="text-3xl font-bold text-gray-900">24</p>
                            </div>
                            <div class="flex items-center text-sm text-green-600">
                                <i class="fas fa-arrow-up w-4 h-4 mr-1"></i>
                                +12.5%
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">vs last month</p>
                    </div>

                    <!-- Active Audits -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Audits</p>
                                <p class="text-3xl font-bold text-gray-900">8</p>
                            </div>
                            <div class="flex items-center text-sm text-blue-600">
                                <i class="fas fa-circle w-4 h-4 mr-1"></i>
                                In Progress
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Across 6 laboratories</p>
                    </div>

                    <!-- Average Star Rating -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Avg Star Rating</p>
                                <p class="text-3xl font-bold text-gray-900">3.2</p>
                            </div>
                            <div class="flex items-center text-sm text-yellow-600">
                                <i class="fas fa-star w-4 h-4 mr-1"></i>
                                Stars
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Network performance</p>
                    </div>

                    <!-- Completion Rate -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Completion Rate</p>
                                <p class="text-3xl font-bold text-gray-900">94%</p>
                            </div>
                            <div class="flex items-center text-sm text-green-600">
                                <i class="fas fa-check-circle w-4 h-4 mr-1"></i>
                                On Track
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">This quarter</p>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Audit Progress Chart -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Audit Progress</h3>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">
                                    This Month
                                </button>
                            </div>
                        </div>
                        <div class="h-64 w-full flex items-center justify-center text-gray-400">
                            <i class="fas fa-chart-line text-4xl mb-2"></i>
                            <span class="ml-2">Chart would render here</span>
                        </div>
                    </div>

                    <!-- Star Distribution -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Star Distribution</h3>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">
                                    All Labs
                                </button>
                            </div>
                        </div>
                        <div class="h-64 w-full flex items-center justify-center text-gray-400">
                            <i class="fas fa-chart-pie text-4xl mb-2"></i>
                            <span class="ml-2">Chart would render here</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Recent Activities</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laboratory</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">General Hospital Lab</div>
                                        <div class="text-sm text-gray-500">Dar es Salaam</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            In Progress
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-gray-900 h-2 rounded-full" style="width: 67%"></div>
                                            </div>
                                            <span class="text-sm text-gray-600">67%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        2 hours ago
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <button class="text-gray-900 hover:text-gray-700 font-medium">View</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function sliptaApp() {
            return {
                sidebarOpen: window.innerWidth >= 1024,
                loading: false,
                userName: 'Dr. Sarah Johnson',
                userEmail: 'sarah.johnson@slipta.org',
                userRole: 'lead_auditor',
                currentStarLevel: 3,
                auditProgress: 67,
                currentDate: new Date().toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }),

                init() {
                    this.checkScreenSize();
                    window.addEventListener('resize', () => this.checkScreenSize());
                },

                checkScreenSize() {
                    if (window.innerWidth < 1024) {
                        this.sidebarOpen = false;
                    } else {
                        this.sidebarOpen = true;
                    }
                }
            }
        }
    </script>
</body>
</html>
