@extends('layouts.app')

@section('breadcrumb', 'Findings Management')

@section('content')
<div class="space-y-6" x-data="findingsSelectApp()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">Findings Management</h1>
            <p class="text-sm text-neutral-600 mt-1">Select an audit to manage findings and AI-powered gap detection</p>
        </div>
    </div>

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start space-x-3">
        <i class="fas fa-exclamation-circle text-red-600 mt-0.5"></i>
        <div class="flex-1">
            <p class="text-sm font-medium text-red-900">Error</p>
            <p class="text-sm text-red-700 mt-1">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-start space-x-3">
        <i class="fas fa-check-circle text-green-600 mt-0.5"></i>
        <div class="flex-1">
            <p class="text-sm font-medium text-green-900">Success</p>
            <p class="text-sm text-green-700 mt-1">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-neutral-900">Audits with Responses</h2>
                <span class="text-xs text-neutral-600">{{ $audits->count() }} audits</span>
            </div>
        </div>

        @if($audits->isEmpty())
        <div class="p-12 text-center">
            <i class="fas fa-clipboard-list text-neutral-300 text-4xl mb-4"></i>
            <p class="text-sm font-medium text-neutral-900 mb-1">No audits with responses found</p>
            <p class="text-sm text-neutral-600">Audits must have at least one response to appear here</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50 border-b border-neutral-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-600 uppercase tracking-wider">Laboratory</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-600 uppercase tracking-wider">Responses</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-600 uppercase tracking-wider">P/N Count</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-600 uppercase tracking-wider">Findings</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-600 uppercase tracking-wider">Dates</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-600 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @foreach($audits as $audit)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-neutral-900">{{ $audit->lab_name }}</span>
                                <span class="text-xs text-neutral-600">{{ $audit->lab_number ?? 'N/A' }}</span>
                                <span class="text-xs text-neutral-500">{{ $audit->country_name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'draft' => 'bg-neutral-100 text-neutral-800',
                                    'in_progress' => 'bg-blue-100 text-blue-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$audit->status] ?? 'bg-neutral-100 text-neutral-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $audit->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex flex-col items-center">
                                <span class="text-sm font-semibold text-neutral-900">{{ $audit->response_count }}</span>
                                <span class="text-xs text-neutral-500">of 151</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg {{ $audit->non_compliant_count > 0 ? 'bg-orange-50 text-orange-700' : 'bg-green-50 text-green-700' }} font-semibold text-sm">
                                {{ $audit->non_compliant_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex flex-col items-center">
                                <span class="text-sm font-semibold text-neutral-900">{{ $audit->finding_count }}</span>
                                @php
                                    $gap = $audit->non_compliant_count - $audit->finding_count;
                                @endphp
                                @if($gap > 0)
                                <span class="text-xs text-red-600 font-medium">{{ $gap }} gap</span>
                                @else
                                <span class="text-xs text-green-600">complete</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-xs text-neutral-600">Opened: {{ \Carbon\Carbon::parse($audit->opened_on)->format('d M Y') }}</span>
                                @if($audit->closed_on)
                                <span class="text-xs text-neutral-600">Closed: {{ \Carbon\Carbon::parse($audit->closed_on)->format('d M Y') }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('findings.show', $audit->id) }}" class="inline-flex items-center px-3 py-2 bg-neutral-900 text-white text-sm font-medium rounded-xl hover:bg-neutral-800 transition-colors">
                                Manage
                                <i class="fas fa-arrow-right ml-2 text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="flex items-start space-x-3">
            <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
            <div class="flex-1">
                <p class="text-sm font-medium text-blue-900">About Findings Management</p>
                <p class="text-sm text-blue-800 mt-1">This module provides intelligent gap detection, AI-powered suggestions, and SLIPTA-compliant finding documentation. All P/N responses require documented findings with evidence.</p>
            </div>
        </div>
    </div>
</div>

<script>
function findingsSelectApp() {
    return {
        init() {
            console.log('Findings select initialized');
        }
    }
}
</script>
@endsection
