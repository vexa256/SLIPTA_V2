<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- <meta name="csrf-token" content="csrf-token-placeholder"> --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

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
                            <a href="{{ route('dashboard_main') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
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

                            <a href="{{ route('countries.index') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-globe-africa mr-3 h-4 w-4 text-gray-400"></i>
                                Countries
                            </a>

                            <a href="{{ route('laboratories.index') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-building mr-3 h-4 w-4 text-gray-400"></i>
                                Laboratory

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
                                    <a href="{{ route('slipta.sections.index') }}" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">SLIPTA Sections</a>
                                    <a href="{{ route('slipta.questions.index') }}" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Questions</a>
                                    {{-- <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Sub-questions</a> --}}
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
                                        Audit Settings
                                    </div>
                                    <i class="fas fa-chevron-right h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="ml-6 space-y-1 mt-1">
                                     <a href="{{ route('users.index') }}" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">User settings</a>
                                    <a href="{{ route('audits.index') }}" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Manage Audits</a>


                                      <a href="{{ route('audits.team.index') }}" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100"> Audit Teams</a>

                                       <a href="{{ route('audits.linking.index') }}" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100"> Audit Linking</a>

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
                                    <a href="{{ route('audits.select') }}" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Conduct Audit</a>
                                    {{-- <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Sub-question Responses</a> --}}
                                </div>
                            </div>

                            {{-- <a href="{{ route('findings.index') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-paperclip mr-3 h-4 w-4 text-gray-400"></i>
                                        Findings
                                     </a> --}}

                            <div x-data="{ open: false }">
                                <button @click="open = !open" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle mr-3 h-4 w-4 text-gray-400"></i>
                                        SLIPTA  Report
                                    </div>
                                    <i class="fas fa-chevron-right h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="ml-6 space-y-1 mt-1">
                                    {{-- <a href="#" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Findings Log</a> --}}

                                     <a href="{{ route('findings.index') }}" class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-paperclip mr-3 h-4 w-4 text-gray-400"></i>
                                        Findings
                                     </a>
                                    <a href="{{ route('reports.index') }}" class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">  <i class="fa-solid fa-file-pdf mr-3 text-gray-400 text-lg"></i>
SLIPTA Report</a>


                                </div>
                            </div>




                            {{-- Logout link (keeps your original styling; only adds JS + hidden form) --}}
<a href="{{ route('logout') }}"
   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
   class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
    <i class="fas fa-sign-out-alt mr-3 h-4 w-4 text-gray-400"></i>
    Logout
</a>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>

                        </div>
                    </div>

                    <!-- Views & Reports Section -->
                    {{-- <div class="mb-6">
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
                    </div> --}}

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
                {{-- <div class="flex items-center space-x-4">
                    <button class="flex items-center space-x-2 px-3 py-2 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                        <i class="fas fa-calendar text-gray-400"></i>
                        <span>Pick a date</span>
                    </button>
                    <button class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                        Download
                    </button>
                </div> --}}
            </header>

            <!-- Page Content -->
           <main class="flex-1 overflow-auto bg-gray-50 p-6">
    @yield('content')
</main>
        </div>
    </div>

    {{-- Add this snippet before closing </body> tag in layouts.app --}}

@if(session('error') || session('success') || $errors->any())
<div x-data="{
    show: true,
    type: '{{ session('error') ? 'error' : (session('success') ? 'success' : 'error') }}',
    message: `{{ session('error') ?? session('success') ?? $errors->first() }}`,
    details: @json($errors->all()),
    showDetails: false,
    init() {
        document.body.style.overflow = 'hidden';
    },
    close() {
        this.show = false;
        document.body.style.overflow = '';
    }
}"
x-show="show"
@keydown.escape.window="close()"
x-cloak
class="fixed inset-0 z-50 overflow-y-auto">

    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">

        <div x-show="show"
             @click="close()"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity bg-neutral-900 bg-opacity-75 backdrop-blur-sm">
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div x-show="show"
             @click.away="close()"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl">

            <div class="relative">
                <div class="absolute top-0 left-0 w-full h-1"
                     :class="{
                         'bg-red-500': type === 'error',
                         'bg-green-500': type === 'success'
                     }">
                </div>

                <div class="px-6 pt-8 pb-6">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center"
                             :class="{
                                 'bg-red-100': type === 'error',
                                 'bg-green-100': type === 'success'
                             }">
                            <i class="fas text-xl"
                               :class="{
                                   'fa-exclamation-triangle text-red-600': type === 'error',
                                   'fa-check-circle text-green-600': type === 'success'
                               }"></i>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-neutral-900"
                                x-text="type === 'error' ? 'Error Occurred' : 'Operation Successful'">
                            </h3>
                            <p class="text-sm text-neutral-600 mt-1">
                                <span x-text="type === 'error' ? 'The following error was encountered:' : 'Your action completed successfully.'"></span>
                            </p>
                        </div>

                        <button @click="close()"
                                class="flex-shrink-0 p-2 text-neutral-400 hover:text-neutral-600 rounded-lg hover:bg-neutral-100 transition-colors">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <div class="mt-6 bg-neutral-50 border border-neutral-200 rounded-xl p-4">
                        <p class="text-sm text-neutral-900 font-medium break-words" x-text="message"></p>
                    </div>

                    @if($errors->count() > 1)
                    <div class="mt-4">
                        <button @click="showDetails = !showDetails"
                                class="flex items-center space-x-2 text-sm font-medium text-neutral-700 hover:text-neutral-900 transition-colors">
                            <i class="fas fa-chevron-right text-xs transition-transform duration-200"
                               :class="{ 'rotate-90': showDetails }"></i>
                            <span>View all errors ({{ $errors->count() }})</span>
                        </button>

                        <div x-show="showDetails"
                             x-collapse
                             class="mt-3 bg-red-50 border border-red-200 rounded-xl p-4 max-h-64 overflow-y-auto">
                            <ul class="space-y-2">
                                @foreach($errors->all() as $index => $error)
                                <li class="flex items-start space-x-2 text-sm text-red-800">
                                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-red-200 text-red-700 text-xs flex items-center justify-center font-semibold mt-0.5">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="flex-1">{{ $error }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    @if(config('app.debug') && session('error'))
                    <div class="mt-4">
                        <button @click="showDetails = !showDetails"
                                class="flex items-center space-x-2 text-xs font-medium text-neutral-600 hover:text-neutral-900 transition-colors">
                            <i class="fas fa-code text-xs"></i>
                            <span>Developer Details</span>
                        </button>

                        <div x-show="showDetails"
                             x-collapse
                             class="mt-3 bg-neutral-900 border border-neutral-700 rounded-xl p-4 max-h-96 overflow-auto">
                            <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap">{{ session('error') }}</pre>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="px-6 py-4 bg-neutral-50 border-t border-neutral-200 flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-xs text-neutral-600">
                        <i class="fas fa-clock"></i>
                        <span x-text="new Date().toLocaleTimeString()"></span>
                    </div>

                    <div class="flex items-center space-x-3">
                        @if(session('error'))
                        <button @click="navigator.clipboard.writeText(message); $dispatch('notification', {message: 'Error copied to clipboard', type: 'info'})"
                                class="px-3 py-2 text-sm font-medium text-neutral-700 hover:text-neutral-900 rounded-lg hover:bg-neutral-200 transition-colors">
                            <i class="fas fa-copy mr-2"></i>Copy Error
                        </button>
                        @endif

                        <button @click="close()"
                                class="px-4 py-2 text-sm font-medium text-white rounded-xl transition-colors"
                                :class="{
                                    'bg-red-600 hover:bg-red-700': type === 'error',
                                    'bg-green-600 hover:bg-green-700': type === 'success'
                                }">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
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
