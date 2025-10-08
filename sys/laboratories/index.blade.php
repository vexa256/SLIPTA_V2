@extends('layouts.app')

@section('title', 'Laboratory Directory')
@section('breadcrumb', 'Laboratory Directory')

@section('content')
<div class="space-y-6">
    <!-- Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">Laboratory Directory</h1>
            <p class="text-sm text-neutral-500 mt-1">Manage laboratory network across regions</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('laboratories.index') }}"
               class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-200 rounded-xl hover:bg-neutral-50 transition duration-150">
                Reset Filters
            </a>
            @if($context['is_admin'] || $context['is_country_coordinator'])
                <button onclick="document.getElementById('add-modal').showModal()"
                        class="px-4 py-2 text-sm font-medium text-white bg-neutral-900 rounded-xl hover:bg-black transition duration-150">
                    Add Laboratory
                </button>
            @endif
        </div>
    </div>

    <!-- Stats Cards -->
    @php
        $stats = [
            'total' => count($laboratories),
            'active' => collect($laboratories)->where('is_active', true)->count(),
            'countries' => collect($laboratories)->pluck('country_id')->unique()->count(),
            'types' => collect($laboratories)->pluck('lab_type')->unique()->count()
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-500">Total Labs</p>
                    <p class="text-2xl font-semibold text-neutral-900">{{ $stats['total'] }}</p>
                </div>
                <div class="w-8 h-8 bg-neutral-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 3h10"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-500">Active Labs</p>
                    <p class="text-2xl font-semibold text-neutral-900">{{ $stats['active'] }}</p>
                </div>
                <div class="w-8 h-8 bg-neutral-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-500">Countries</p>
                    <p class="text-2xl font-semibold text-neutral-900">{{ $stats['countries'] }}</p>
                </div>
                <div class="w-8 h-8 bg-neutral-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-500">Lab Types</p>
                    <p class="text-2xl font-semibold text-neutral-900">{{ $stats['types'] }}</p>
                </div>
                <div class="w-8 h-8 bg-neutral-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Messages -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm font-medium text-green-900">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm font-medium text-red-900">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
        <form method="GET" action="{{ route('laboratories.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search laboratories..."
                   class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">

            <select name="country" class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                <option value="">All Countries</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" {{ request('country') == $country->id ? 'selected' : '' }}>
                        {{ $country->name }}
                    </option>
                @endforeach
            </select>

            <select name="lab_type" class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                <option value="">All Types</option>
                @foreach($labTypes as $type)
                    <option value="{{ $type['value'] }}" {{ request('lab_type') == $type['value'] ? 'selected' : '' }}>
                        {{ $type['label'] }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active Only</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive Only</option>
            </select>

            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-neutral-900 rounded-xl hover:bg-black transition duration-150">
                Filter
            </button>
        </form>
    </div>

    <!-- Labs Table -->
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 border-b border-neutral-200">
                    <tr>
                        <th class="text-left py-3 px-4 font-medium text-neutral-600">Laboratory</th>
                        <th class="text-left py-3 px-4 font-medium text-neutral-600">Country</th>
                        <th class="text-left py-3 px-4 font-medium text-neutral-600">Type</th>
                        <th class="text-left py-3 px-4 font-medium text-neutral-600">Contact</th>
                        <th class="text-left py-3 px-4 font-medium text-neutral-600">Status</th>
                        <th class="text-left py-3 px-4 font-medium text-neutral-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($laboratories as $lab)
                        <tr class="border-b border-neutral-100 hover:bg-neutral-50 transition duration-150">
                            <td class="py-3 px-4">
                                <div>
                                    <p class="font-medium text-neutral-900">{{ $lab->name }}</p>
                                    <p class="text-xs text-neutral-500">{{ $lab->lab_number ?: 'No number' }}</p>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-neutral-700">{{ $lab->country_name }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $formattedType = ucwords(str_replace('_', ' ', $lab->lab_type ?: 'unknown'));
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-neutral-100 text-neutral-700 rounded-lg">
                                    {{ $formattedType }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-xs">
                                    <p class="text-neutral-900">{{ $lab->contact_person ?: '-' }}</p>
                                    <p class="text-neutral-500">{{ $lab->email ?: $lab->phone ?: '-' }}</p>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-lg {{ $lab->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $lab->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <button onclick="showDetails({{ $lab->id }})"
                                            class="p-1.5 text-neutral-500 hover:text-neutral-700 hover:bg-neutral-100 rounded-lg transition duration-150">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>

                                    @if($context['is_admin'] || $context['is_country_coordinator'])
                                        <button onclick="editLab({{ json_encode($lab) }})"
                                                class="p-1.5 text-neutral-500 hover:text-neutral-700 hover:bg-neutral-100 rounded-lg transition duration-150">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    @endif

                                    @if($context['is_admin'])
                                        <form method="POST" action="{{ route('laboratories.destroy', $lab->id) }}" class="inline-block"
                                              onsubmit="return confirm('Delete {{ $lab->name }}? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition duration-150">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <svg class="w-12 h-12 text-neutral-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 3h10"></path>
                                </svg>
                                <p class="text-neutral-500">No laboratories found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($pagination['last_page'] > 1)
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-neutral-500">
                Showing {{ $pagination['from'] }} to {{ $pagination['to'] }} of {{ $pagination['total'] }} results
            </div>
            <div class="flex items-center gap-2">
                @if($pagination['current_page'] > 1)
                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}"
                       class="px-3 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-200 rounded-xl hover:bg-neutral-50 transition duration-150">
                        Previous
                    </a>
                @endif

                @for($page = max(1, $pagination['current_page'] - 2); $page <= min($pagination['last_page'], $pagination['current_page'] + 2); $page++)
                    @if($page == $pagination['current_page'])
                        <span class="px-3 py-2 text-sm font-medium bg-neutral-900 text-white border border-neutral-200 rounded-xl">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page]) }}"
                           class="px-3 py-2 text-sm font-medium bg-white text-neutral-700 hover:bg-neutral-50 border border-neutral-200 rounded-xl transition duration-150">
                            {{ $page }}
                        </a>
                    @endif
                @endfor

                @if($pagination['current_page'] < $pagination['last_page'])
                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}"
                       class="px-3 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-200 rounded-xl hover:bg-neutral-50 transition duration-150">
                        Next
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>

<!-- Add/Edit Modal -->
<dialog id="add-modal" class="bg-transparent p-0 max-w-lg w-full">
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 id="modal-title" class="text-lg font-semibold text-neutral-900">Add New Laboratory</h3>
            <button onclick="document.getElementById('add-modal').close()" class="p-1 text-neutral-400 hover:text-neutral-600 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="lab-form" method="POST" action="{{ route('laboratories.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" id="form-method" name="_method" value="POST">
            <input type="hidden" id="form-id" name="id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Laboratory Name *</label>
                    <input type="text" id="form-name" name="name" required
                           class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                    @error('name')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Lab Number</label>
                    <input type="text" id="form-lab_number" name="lab_number"
                           class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                    @error('lab_number')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Country *</label>
                    <select id="form-country_id" name="country_id" required
                            class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Laboratory Type *</label>
                    <select id="form-lab_type" name="lab_type" required
                            class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                        <option value="">Select Type</option>
                        @foreach($labTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                    @error('lab_type')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">City</label>
                    <input type="text" id="form-city" name="city"
                           class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Address</label>
                    <textarea id="form-address" name="address" rows="2"
                              class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Contact Person</label>
                    <input type="text" id="form-contact_person" name="contact_person"
                           class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Email</label>
                    <input type="email" id="form-email" name="email"
                           class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                    @error('email')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Phone</label>
                    <input type="text" id="form-phone" name="phone"
                           class="w-full px-3 py-2 text-sm bg-white border border-neutral-200 rounded-xl focus:ring-2 focus:ring-neutral-900/10 focus:border-neutral-900">
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" id="form-is_active" name="is_active" value="1" checked class="sr-only">
                        <div class="relative">
                            <div id="toggle-bg" class="w-10 h-6 bg-neutral-900 rounded-full shadow-inner"></div>
                            <div id="toggle-dot" class="absolute w-4 h-4 bg-white rounded-full shadow transition-transform top-1 translate-x-5"></div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-neutral-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="document.getElementById('add-modal').close()"
                        class="flex-1 px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-200 rounded-xl hover:bg-neutral-50 transition duration-150">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-neutral-900 rounded-xl hover:bg-black transition duration-150">
                    Save Laboratory
                </button>
            </div>
        </form>
    </div>
</dialog>

<!-- Details Modal -->
<dialog id="details-modal" class="bg-transparent p-0 max-w-2xl w-full">
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-neutral-900">Laboratory Details</h3>
            <button onclick="document.getElementById('details-modal').close()" class="p-1 text-neutral-400 hover:text-neutral-600 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div id="details-content" class="space-y-6">
            <!-- Content loaded via JavaScript -->
        </div>
    </div>
</dialog>

<script>
// Initialize charts on page load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof am5 !== 'undefined') {
        initializeCharts();
    }
});

function initializeCharts() {
    createLabTypesChart();
    createCountriesChart();
}

function createLabTypesChart() {
    const root = am5.Root.new("labTypesChart");
    root.setThemes([am5themes_Animated.new(root)]);

    const chart = root.container.children.push(am5xy.XYChart.new(root, {
        panX: false, panY: false, layout: root.verticalLayout
    }));

    const xRenderer = am5xy.AxisRendererX.new(root, { minGridDistance: 30 });
    xRenderer.labels.template.setAll({
        rotation: -45, centerY: am5.p50, centerX: am5.p100,
        paddingRight: 15, fontSize: 10, fill: am5.color("#262626")
    });

    const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "type", renderer: xRenderer
    }));

    const yRenderer = am5xy.AxisRendererY.new(root, { strokeOpacity: 0.1 });
    yRenderer.labels.template.setAll({ fontSize: 10, fill: am5.color("#262626") });

    const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
        renderer: yRenderer
    }));

    const series = chart.series.push(am5xy.ColumnSeries.new(root, {
        name: "Count", xAxis: xAxis, yAxis: yAxis,
        valueYField: "count", categoryXField: "type"
    }));

    series.columns.template.setAll({
        cornerRadiusTL: 4, cornerRadiusTR: 4,
        strokeOpacity: 0, fill: am5.color("#0a0a0a")
    });

    // Process PHP data for chart
    const laboratories = @json($laboratories);
    const typeData = {};
    laboratories.forEach(lab => {
        const type = lab.lab_type ? lab.lab_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown';
        typeData[type] = (typeData[type] || 0) + 1;
    });

    const chartData = Object.entries(typeData).map(([type, count]) => ({ type, count }));
    xAxis.data.setAll(chartData);
    series.data.setAll(chartData);
}

function createCountriesChart() {
    const root = am5.Root.new("countriesChart");
    root.setThemes([am5themes_Animated.new(root)]);

    const chart = root.container.children.push(am5xy.XYChart.new(root, {
        panX: false, panY: false, layout: root.verticalLayout
    }));

    const xRenderer = am5xy.AxisRendererX.new(root, { minGridDistance: 30 });
    xRenderer.labels.template.setAll({
        rotation: -45, centerY: am5.p50, centerX: am5.p100,
        paddingRight: 15, fontSize: 10, fill: am5.color("#262626")
    });

    const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "country", renderer: xRenderer
    }));

    const yRenderer = am5xy.AxisRendererY.new(root, { strokeOpacity: 0.1 });
    yRenderer.labels.template.setAll({ fontSize: 10, fill: am5.color("#262626") });

    const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
        renderer: yRenderer
    }));

    const series = chart.series.push(am5xy.ColumnSeries.new(root, {
        name: "Labs", xAxis: xAxis, yAxis: yAxis,
        valueYField: "count", categoryXField: "country"
    }));

    series.columns.template.setAll({
        cornerRadiusTL: 4, cornerRadiusTR: 4,
        strokeOpacity: 0, fill: am5.color("#a3a3a3")
    });

    // Process PHP data for chart
    const laboratories = @json($laboratories);
    const countryData = {};
    laboratories.forEach(lab => {
        const country = lab.country_name || 'Unknown';
        countryData[country] = (countryData[country] || 0) + 1;
    });

    const chartData = Object.entries(countryData)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 8)
        .map(([country, count]) => ({
            country: country.length > 12 ? country.substring(0, 12) + '...' : country,
            count
        }));

    xAxis.data.setAll(chartData);
    series.data.setAll(chartData);
}

// Minimal JavaScript for modal interactions and toggle
function editLab(lab) {
    document.getElementById('modal-title').textContent = 'Edit Laboratory';
    document.getElementById('lab-form').action = `/laboratories/${lab.id}`;
    document.getElementById('form-method').value = 'PUT';
    document.getElementById('form-id').value = lab.id;

    // Populate form fields
    document.getElementById('form-name').value = lab.name || '';
    document.getElementById('form-lab_number').value = lab.lab_number || '';
    document.getElementById('form-country_id').value = lab.country_id || '';
    document.getElementById('form-lab_type').value = lab.lab_type || '';
    document.getElementById('form-city').value = lab.city || '';
    document.getElementById('form-address').value = lab.address || '';
    document.getElementById('form-contact_person').value = lab.contact_person || '';
    document.getElementById('form-email').value = lab.email || '';
    document.getElementById('form-phone').value = lab.phone || '';

    // Set toggle
    const isActive = lab.is_active;
    const checkbox = document.getElementById('form-is_active');
    const toggleBg = document.getElementById('toggle-bg');
    const toggleDot = document.getElementById('toggle-dot');

    checkbox.checked = isActive;
    toggleBg.className = `w-10 h-6 rounded-full shadow-inner ${isActive ? 'bg-neutral-900' : 'bg-neutral-200'}`;
    toggleDot.className = `absolute w-4 h-4 bg-white rounded-full shadow transition-transform top-1 ${isActive ? 'translate-x-5' : 'translate-x-1'}`;

    document.getElementById('add-modal').showModal();
}

function showDetails(labId) {
    fetch(`/laboratories/${labId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const lab = data.data.laboratory;
            const related = data.data.relatedData;

            document.getElementById('details-content').innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-neutral-900 mb-2">Basic Information</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-neutral-500">Name:</span><span>${lab.name}</span></div>
                                <div class="flex justify-between"><span class="text-neutral-500">Number:</span><span>${lab.lab_number || '-'}</span></div>
                                <div class="flex justify-between"><span class="text-neutral-500">Country:</span><span>${lab.country_name}</span></div>
                                <div class="flex justify-between"><span class="text-neutral-500">Type:</span><span>${lab.lab_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-neutral-900 mb-2">Activity Summary</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-neutral-500">Total Audits:</span><span>${related.audit_count || 0}</span></div>
                                <div class="flex justify-between"><span class="text-neutral-500">Assigned Users:</span><span>${related.user_count || 0}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('details-modal').showModal();
        }
    })
    .catch(error => {
        console.error('Error loading details:', error);
    });
}

// Toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('form-is_active').addEventListener('change', function() {
        const toggleBg = document.getElementById('toggle-bg');
        const toggleDot = document.getElementById('toggle-dot');
        const isChecked = this.checked;

        toggleBg.className = `w-10 h-6 rounded-full shadow-inner ${isChecked ? 'bg-neutral-900' : 'bg-neutral-200'}`;
        toggleDot.className = `absolute w-4 h-4 bg-white rounded-full shadow transition-transform top-1 ${isChecked ? 'translate-x-5' : 'translate-x-1'}`;
    });

    // Reset form when opening add modal
    const addButton = document.querySelector('button[onclick="document.getElementById(\'add-modal\').showModal()"]');
    if (addButton) {
        addButton.addEventListener('click', function() {
            document.getElementById('modal-title').textContent = 'Add New Laboratory';
            document.getElementById('lab-form').action = '{{ route("laboratories.store") }}';
            document.getElementById('form-method').value = 'POST';
            document.getElementById('form-id').value = '';
            document.getElementById('lab-form').reset();

            // Reset toggle to active
            const checkbox = document.getElementById('form-is_active');
            const toggleBg = document.getElementById('toggle-bg');
            const toggleDot = document.getElementById('toggle-dot');

            checkbox.checked = true;
            toggleBg.className = 'w-10 h-6 bg-neutral-900 rounded-full shadow-inner';
            toggleDot.className = 'absolute w-4 h-4 bg-white rounded-full shadow transition-transform top-1 translate-x-5';
        });
    }
});
</script>

@endsection
