<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLIPTA Audit Report - {{ $audit->lab_name ?? 'Unknown Laboratory' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .page-break { page-break-after: always; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body class="bg-neutral-50 p-2 sm:p-4 lg:p-6">

@php
// ═══════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════════

function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return 'N/A';
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

function safeOutput($value, $default = 'N/A') {
    return !empty($value) ? htmlspecialchars($value) : $default;
}

function getAnswerBadgeClass($answer) {
    switch(strtoupper($answer)) {
        case 'Y': return 'bg-emerald-100 text-emerald-800';
        case 'P': return 'bg-amber-100 text-amber-800';
        case 'N': return 'bg-red-100 text-red-800';
        case 'NA': return 'bg-neutral-100 text-neutral-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getSeverityBadgeClass($severity) {
    switch(strtolower($severity)) {
        case 'high': return 'bg-red-600 text-white';
        case 'medium': return 'bg-amber-600 text-white';
        case 'low': return 'bg-blue-600 text-white';
        default: return 'bg-gray-600 text-white';
    }
}

function getStarLabel($stars) {
    $labels = [
        0 => 'No Stars (<55%)',
        1 => '1 Star (55-64.99%)',
        2 => '2 Stars (65-74.99%)',
        3 => '3 Stars (75-84.99%)',
        4 => '4 Stars (85-94.99%)',
        5 => '5 Stars (≥95%)',
    ];
    return $labels[$stars] ?? 'Unknown';
}

// ═══════════════════════════════════════════════════════════════════
// DATA VALIDATION & INTEGRITY CHECKS
// ═══════════════════════════════════════════════════════════════════

$validationErrors = [];
$validationWarnings = [];

if (!isset($audit) || !is_object($audit)) {
    $validationErrors[] = "CRITICAL: Audit data structure is invalid or missing";
}

if (!isset($sections) || !is_array($sections)) {
    $validationErrors[] = "CRITICAL: Sections data is invalid or missing";
} else {
    if (count($sections) !== 12) {
        $validationWarnings[] = "WARNING: Expected 12 SLIPTA sections, found " . count($sections);
    }
}

if (!isset($score) || !is_array($score)) {
    $validationErrors[] = "CRITICAL: Score data is invalid or missing";
}

// Sort sections by code
if (isset($sections) && is_array($sections)) {
    usort($sections, function($a, $b) {
        $codeA = is_array($a) ? ($a['code'] ?? 0) : ($a->code ?? 0);
        $codeB = is_array($b) ? ($b['code'] ?? 0) : ($b->code ?? 0);
        return $codeA <=> $codeB;
    });
}

// Calculate dynamic totals from sections
$calculatedTotals = [
    'yes' => 0,
    'partial' => 0,
    'no' => 0,
    'na' => 0,
    'earned_points' => 0,
    'max_points' => 0,
    'total_questions' => 0,
    'answered_questions' => 0
];

if (isset($sections) && is_array($sections)) {
    foreach ($sections as $section) {
        $sectionData = is_array($section) ? $section : (array)$section;
        $calculatedTotals['yes'] += $sectionData['yes'] ?? 0;
        $calculatedTotals['partial'] += $sectionData['partial'] ?? 0;
        $calculatedTotals['no'] += $sectionData['no'] ?? 0;
        $calculatedTotals['na'] += $sectionData['na'] ?? 0;
        $calculatedTotals['earned_points'] += $sectionData['earned_points'] ?? 0;
        $calculatedTotals['max_points'] += $sectionData['max_points'] ?? 0;
        $calculatedTotals['total_questions'] += $sectionData['total_questions'] ?? 0;
        $calculatedTotals['answered_questions'] += $sectionData['answered'] ?? 0;
    }
}

// Validate totals integrity
if (isset($score['earned'])) {
    $diff = abs($calculatedTotals['earned_points'] - $score['earned']);
    if ($diff > 0.01) {
        $validationErrors[] = "DATA INTEGRITY ERROR: Section totals ({$calculatedTotals['earned_points']} pts) ≠ Overall score ({$score['earned']} pts). Difference: {$diff} points";
    }
}

// Validate WHO SLIPTA standard: 367 total points
if ($calculatedTotals['max_points'] > 0 && $calculatedTotals['max_points'] !== 367) {
    $validationWarnings[] = "WARNING: Total max points is {$calculatedTotals['max_points']}, expected 367 per WHO SLIPTA v3:2023";
}

// Validate star level matches percentage
if (isset($score['percentage']) && isset($score['stars'])) {
    $pct = $score['percentage'];
    $expectedStars = 0;
    if ($pct >= 95.0) $expectedStars = 5;
    elseif ($pct >= 85.0) $expectedStars = 4;
    elseif ($pct >= 75.0) $expectedStars = 3;
    elseif ($pct >= 65.0) $expectedStars = 2;
    elseif ($pct >= 55.0) $expectedStars = 1;

    if ($expectedStars !== $score['stars']) {
        $validationErrors[] = "STAR LEVEL ERROR: {$pct}% = {$expectedStars} stars per WHO SLIPTA, but {$score['stars']} stars assigned";
    }
}

// Validate percentage calculation
if (isset($score['earned'], $score['adjusted_denominator']) && $score['adjusted_denominator'] > 0) {
    $expectedPct = round(($score['earned'] / $score['adjusted_denominator']) * 100, 2);
    $actualPct = $score['percentage'];
    if (abs($expectedPct - $actualPct) > 0.01) {
        $validationErrors[] = "PERCENTAGE ERROR: Should be {$expectedPct}% but stored as {$actualPct}%";
    }
}

// Check team members - FIXED
if (!isset($team) || !is_array($team) || count($team) === 0) {
    $validationWarnings[] = "WARNING: No audit team members recorded";
}

// Check audit completion status
if (isset($audit->status) && $audit->status !== 'completed') {
    $validationWarnings[] = "WARNING: Audit status is '{$audit->status}'. Reports should only be generated for completed audits";
}

// Validate response count
$totalAnswered = $calculatedTotals['yes'] + $calculatedTotals['partial'] + $calculatedTotals['no'] + $calculatedTotals['na'];
if ($calculatedTotals['answered_questions'] > 0 && $totalAnswered !== $calculatedTotals['answered_questions']) {
    $validationWarnings[] = "WARNING: Response count mismatch. Y+P+N+NA={$totalAnswered}, but answered_questions={$calculatedTotals['answered_questions']}";
}
@endphp

<!-- Validation Alerts -->
@if (!empty($validationErrors))
<div class="no-print bg-red-100 border-2 border-red-600 text-red-900 px-4 py-3 rounded-2xl mb-4 shadow-sm">
    <h3 class="font-bold text-lg mb-2">⚠️ CRITICAL DATA INTEGRITY ERRORS</h3>
    <ul class="list-disc list-inside space-y-1">
        @foreach ($validationErrors as $error)
        <li class="text-sm">{{ $error }}</li>
        @endforeach
    </ul>
    <p class="text-xs mt-2 font-semibold">This report should NOT be used until these errors are resolved.</p>
</div>
@endif

@if (!empty($validationWarnings))
<div class="no-print bg-amber-100 border-2 border-amber-600 text-amber-900 px-4 py-3 rounded-2xl mb-4 shadow-sm">
    <h3 class="font-bold mb-2">⚡ Data Quality Warnings</h3>
    <ul class="list-disc list-inside space-y-1">
        @foreach ($validationWarnings as $warning)
        <li class="text-sm">{{ $warning }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Print Button -->
<div class="no-print fixed top-4 right-4 z-50">
    <button onclick="window.print()" class="bg-neutral-900 text-white px-4 py-3 rounded-xl shadow-lg hover:bg-neutral-700 transition-all duration-200 text-sm font-bold">
        📄 PRINT PDF
    </button>
</div>

<!-- Report Container -->
<div class="max-w-5xl mx-auto space-y-4">

    <!-- HEADER -->
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-sm">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th colspan="4" class="p-4 text-left rounded-t-2xl">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">🔬</span>
                        <div>
                            <h1 class="text-xl font-bold">SLIPTA AUDIT REPORT</h1>
                            <p class="text-xs text-neutral-300">Stepwise Laboratory Quality Improvement Process Towards Accreditation</p>
                        </div>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-neutral-200">
                <td class="p-3 font-semibold text-neutral-700 bg-neutral-50 w-1/4">Laboratory</td>
                <td class="p-3 font-bold text-neutral-900">{{ safeOutput($audit->lab_name ?? null) }}</td>
                <td class="p-3 font-semibold text-neutral-700 bg-neutral-50 w-1/6">Lab Number</td>
                <td class="p-3 text-neutral-900">{{ safeOutput($audit->lab_number ?? null) }}</td>
            </tr>
            <tr class="border-b border-neutral-200">
                <td class="p-3 font-semibold text-neutral-700 bg-neutral-50">Country</td>
                <td class="p-3 text-neutral-900">{{ safeOutput($audit->country_name ?? null) }}</td>
                <td class="p-3 font-semibold text-neutral-700 bg-neutral-50">Audit Date</td>
                <td class="p-3 text-neutral-900">{{ formatDate($audit->opened_on ?? null) }}</td>
            </tr>
            <tr class="border-b border-neutral-200">
                <td class="p-3 font-semibold text-neutral-700 bg-neutral-50">Status</td>
                <td class="p-3">
                    <span class="inline-block px-2 py-1 bg-emerald-100 text-emerald-800 rounded-xl text-xs font-bold uppercase">
                        {{ safeOutput($audit->status ?? null) }}
                    </span>
                </td>
                <td class="p-3 font-semibold text-neutral-700 bg-neutral-50">Closed Date</td>
                <td class="p-3 text-neutral-900">{{ formatDate($audit->closed_on ?? null) }}</td>
            </tr>
            <tr>
                <td class="p-3 font-semibold text-neutral-700 bg-neutral-50">Lead Auditor</td>
                <td colspan="3" class="p-3 text-neutral-900">{{ safeOutput($audit->created_by_name ?? null) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- OVERALL PERFORMANCE -->
    <div class="bg-gradient-to-br from-neutral-900 to-neutral-700 text-white rounded-2xl shadow-lg p-6 text-center">
        <div class="text-sm opacity-90 mb-2">OVERALL PERFORMANCE</div>
        <div class="text-6xl font-bold mb-2">
            {{ $score['stars'] ?? 0 }}
            <span class="text-yellow-400">⭐</span>
        </div>
        <div class="text-2xl font-bold mb-2">
            {{ number_format($score['percentage'] ?? 0, 1) }}% COMPLIANCE
        </div>
        <div class="text-lg">
            {{ $score['earned'] ?? 0 }} /
            {{ $score['adjusted_denominator'] ?? 367 }} POINTS
        </div>
        <div class="text-xs opacity-75 mt-2">
            {{ getStarLabel($score['stars'] ?? 0) }}
        </div>
    </div>

    <!-- SCORE SUMMARY -->
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-sm">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th colspan="4" class="p-3 text-left font-bold rounded-t-2xl">📊 SCORE SUMMARY</th>
            </tr>
            <tr class="bg-neutral-800 text-xs">
                <th class="p-2 text-left font-semibold">Metric</th>
                <th class="p-2 text-right font-semibold">Value</th>
                <th class="p-2 text-left font-semibold">Metric</th>
                <th class="p-2 text-right font-semibold">Value</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-neutral-100">
                <td class="p-2 text-neutral-700">Points Earned</td>
                <td class="p-2 text-right font-bold text-neutral-900">{{ $score['earned'] ?? 0 }}</td>
                <td class="p-2 text-neutral-700">Total Possible</td>
                <td class="p-2 text-right font-bold text-neutral-900">{{ $score['total_possible'] ?? 367 }}</td>
            </tr>
            <tr class="border-b border-neutral-100">
                <td class="p-2 text-neutral-700">NA Exclusions</td>
                <td class="p-2 text-right font-bold text-neutral-900">{{ $score['na_points_excluded'] ?? 0 }}</td>
                <td class="p-2 text-neutral-700">Adjusted Total</td>
                <td class="p-2 text-right font-bold text-neutral-900">{{ $score['adjusted_denominator'] ?? 367 }}</td>
            </tr>
            <tr class="border-b border-neutral-100">
                <td class="p-2 text-neutral-700">Total Questions</td>
                <td class="p-2 text-right font-bold text-neutral-900">{{ $calculatedTotals['total_questions'] }}</td>
                <td class="p-2 text-neutral-700">Questions Answered</td>
                <td class="p-2 text-right font-bold text-neutral-900">{{ $calculatedTotals['answered_questions'] }}</td>
            </tr>
            <tr class="bg-neutral-900 text-white rounded-b-2xl">
                <td class="p-3 font-bold">FINAL SCORE</td>
                <td colspan="3" class="p-3 text-right text-xl font-bold">
                    {{ $score['earned'] ?? 0 }} / {{ $score['adjusted_denominator'] ?? 367 }}
                    ({{ number_format($score['percentage'] ?? 0, 2) }}%)
                </td>
            </tr>
        </tbody>
    </table>

    <!-- EXECUTIVE SUMMARY -->
    @if (isset($narrative['executive_summary']) && !empty($narrative['executive_summary']))
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-sm">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th class="p-3 text-left font-bold rounded-t-2xl">📋 EXECUTIVE SUMMARY</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="p-4 leading-relaxed text-neutral-700">
                    {{ $narrative['executive_summary'] }}
                </td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- SECTION PERFORMANCE BREAKDOWN -->
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-xs">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th colspan="9" class="p-3 text-left font-bold text-sm rounded-t-2xl">📊 SECTION PERFORMANCE BREAKDOWN</th>
            </tr>
            <tr class="bg-neutral-800">
                <th class="p-2 text-left">Section</th>
                <th class="p-2 text-center">Y</th>
                <th class="p-2 text-center">P</th>
                <th class="p-2 text-center">N</th>
                <th class="p-2 text-center">NA</th>
                <th class="p-2 text-right">Pts</th>
                <th class="p-2 text-right">Max</th>
                <th class="p-2 text-right">%</th>
                <th class="p-2 text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($sections) && is_array($sections) && count($sections) > 0)
                @foreach ($sections as $section)
                @php
                    $sectionData = is_array($section) ? $section : (array)$section;
                    $pct = $sectionData['percentage'] ?? 0;
                    $rowBg = $pct < 65 ? 'bg-red-50' : ($pct >= 85 ? 'bg-emerald-50' : '');
                @endphp
                <tr class="border-b border-neutral-100 hover:bg-neutral-50 {{ $rowBg }}">
                    <td class="p-2 font-medium text-neutral-900">
                        <span class="font-bold">{{ str_pad($sectionData['code'] ?? 0, 2, '0', STR_PAD_LEFT) }}.</span>
                        {{ $sectionData['title'] ?? 'Unknown Section' }}
                    </td>
                    <td class="p-2 text-center text-emerald-700 font-semibold">{{ $sectionData['yes'] ?? 0 }}</td>
                    <td class="p-2 text-center text-amber-700 font-semibold">{{ $sectionData['partial'] ?? 0 }}</td>
                    <td class="p-2 text-center text-red-700 font-semibold">{{ $sectionData['no'] ?? 0 }}</td>
                    <td class="p-2 text-center text-neutral-500">{{ $sectionData['na'] ?? 0 }}</td>
                    <td class="p-2 text-right font-bold text-neutral-900">{{ $sectionData['earned_points'] ?? 0 }}</td>
                    <td class="p-2 text-right text-neutral-600">{{ $sectionData['max_points'] ?? 0 }}</td>
                    <td class="p-2 text-right font-bold text-neutral-900">{{ number_format($pct, 1) }}%</td>
                    <td class="p-2 text-center">
                        @if ($pct >= 85)
                            <span class="inline-block px-1.5 py-0.5 bg-emerald-100 text-emerald-800 rounded-xl text-xs font-bold">✓</span>
                        @elseif ($pct >= 65)
                            <span class="inline-block px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded-xl text-xs font-bold">○</span>
                        @else
                            <span class="inline-block px-1.5 py-0.5 bg-red-100 text-red-800 rounded-xl text-xs font-bold">✗</span>
                        @endif
                    </td>
                </tr>
                @endforeach

                <!-- DYNAMIC TOTALS ROW -->
                <tr class="bg-neutral-900 text-white font-bold text-sm">
                    <td class="p-3 rounded-bl-2xl">TOTAL</td>
                    <td class="p-3 text-center">{{ $calculatedTotals['yes'] }}</td>
                    <td class="p-3 text-center">{{ $calculatedTotals['partial'] }}</td>
                    <td class="p-3 text-center">{{ $calculatedTotals['no'] }}</td>
                    <td class="p-3 text-center">{{ $calculatedTotals['na'] }}</td>
                    <td class="p-3 text-right">{{ $calculatedTotals['earned_points'] }}</td>
                    <td class="p-3 text-right">{{ $calculatedTotals['max_points'] }}</td>
                    <td class="p-3 text-right">
                        @php
                        $overallPct = $calculatedTotals['max_points'] > 0
                            ? round(($calculatedTotals['earned_points'] / $calculatedTotals['max_points']) * 100, 1)
                            : 0;
                        @endphp
                        {{ number_format($overallPct, 1) }}%
                    </td>
                    <td class="p-3 text-center text-yellow-400 text-base rounded-br-2xl">
                        {{ str_repeat('★', $score['stars'] ?? 0) }}
                    </td>
                </tr>
            @else
                <tr>
                    <td colspan="9" class="p-4 text-center text-neutral-500">No section data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- PERFORMANCE ANALYSIS -->
    @if ((isset($narrative['strengths']) && count($narrative['strengths']) > 0) || (isset($narrative['weaknesses']) && count($narrative['weaknesses']) > 0))
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-xs">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th colspan="4" class="p-3 text-left font-bold text-sm rounded-t-2xl">📊 PERFORMANCE ANALYSIS</th>
            </tr>
            <tr class="bg-neutral-800">
                <th class="p-2 text-left w-10">Type</th>
                <th class="p-2 text-left">Section</th>
                <th class="p-2 text-right w-16">Score</th>
                <th class="p-2 text-left">Insight</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($narrative['strengths']) && is_array($narrative['strengths']))
                @foreach ($narrative['strengths'] as $strength)
                <tr class="border-b border-neutral-100 bg-emerald-50 hover:bg-emerald-100">
                    <td class="p-2 text-center">
                        <span class="inline-flex items-center justify-center w-5 h-5 bg-emerald-600 text-white rounded-full text-xs font-bold">✓</span>
                    </td>
                    <td class="p-2 font-bold text-emerald-900">{{ $strength['section'] ?? '' }}</td>
                    <td class="p-2 text-right font-bold text-emerald-900">{{ number_format($strength['score'] ?? 0, 1) }}%</td>
                    <td class="p-2 text-neutral-700">{{ $strength['insight'] ?? '' }}</td>
                </tr>
                @endforeach
            @endif

            @if (isset($narrative['weaknesses']) && is_array($narrative['weaknesses']))
                @foreach ($narrative['weaknesses'] as $weakness)
                <tr class="border-b border-neutral-100 bg-red-50 hover:bg-red-100">
                    <td class="p-2 text-center">
                        <span class="inline-flex items-center justify-center w-5 h-5 bg-red-600 text-white rounded-full text-xs font-bold">!</span>
                    </td>
                    <td class="p-2 font-bold text-red-900">
                        {{ $weakness['section'] ?? '' }}
                        @if (isset($weakness['gap']) && $weakness['gap'] > 0)
                        <span class="text-red-600 text-xs ml-1">▼{{ number_format($weakness['gap'], 1) }}%</span>
                        @endif
                    </td>
                    <td class="p-2 text-right font-bold text-red-900">{{ number_format($weakness['score'] ?? 0, 1) }}%</td>
                    <td class="p-2 text-neutral-700">{{ $weakness['insight'] ?? '' }}</td>
                </tr>
                @endforeach
            @endif
        </tbody>
    </table>
    @endif

    <!-- STRATEGIC RECOMMENDATIONS -->
    @if (isset($narrative['recommendations']) && is_array($narrative['recommendations']) && count($narrative['recommendations']) > 0)
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-xs">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th class="p-3 text-left font-bold text-sm rounded-t-2xl">🎯 STRATEGIC RECOMMENDATIONS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($narrative['recommendations'] as $index => $rec)
            <tr class="border-b border-neutral-100">
                <td class="p-3">
                    <div class="flex items-start space-x-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ ($rec['priority'] ?? '') === 'HIGH' ? 'bg-red-600' : 'bg-blue-600' }} text-white font-bold text-xs flex-shrink-0">
                            {{ $index + 1 }}
                        </span>
                        <div class="flex-1">
                            <div class="font-bold text-neutral-900 mb-1">
                                <span class="inline-block px-2 py-0.5 rounded-xl text-xs font-bold {{ ($rec['priority'] ?? '') === 'HIGH' ? 'bg-red-600 text-white' : 'bg-blue-600 text-white' }}">
                                    {{ strtoupper($rec['priority'] ?? 'MEDIUM') }}
                                </span>
                                <span class="ml-2">{{ $rec['area'] ?? '' }}</span>
                            </div>
                            <div class="text-neutral-700 text-xs">{{ $rec['recommendation'] ?? '' }}</div>
                        </div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- PAGE BREAK FOR PRINT -->
    <div class="page-break"></div>

    <!-- DETAILED AUDIT RESPONSES BY SECTION -->
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-xs">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th colspan="6" class="p-3 text-left font-bold text-sm rounded-t-2xl">📋 COMPLETE AUDIT RESPONSES (SLIPTA Order)</th>
            </tr>
            <tr class="bg-neutral-800">
                <th class="p-2 text-left w-12">Sec</th>
                <th class="p-2 text-left w-16">Q.Code</th>
                <th class="p-2 text-left">Question Text</th>
                <th class="p-2 text-center w-12">Ans</th>
                <th class="p-2 text-center w-12">Wt</th>
                <th class="p-2 text-left">Auditor Comment</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($allResponses) && is_array($allResponses) && count($allResponses) > 0)
                @foreach ($allResponses as $sectionCode => $sectionResponses)
                    @php
                    $sectionInfo = collect($sections)->firstWhere('code', $sectionCode);
                    $sectionTitle = $sectionInfo['title'] ?? 'Unknown Section';
                    @endphp

                    <!-- Section Header -->
                    <tr class="bg-neutral-900 text-white">
                        <td colspan="6" class="p-2 font-bold">
                            SECTION {{ str_pad($sectionCode, 2, '0', STR_PAD_LEFT) }}: {{ strtoupper($sectionTitle) }}
                        </td>
                    </tr>

                    <!-- Section Responses -->
                    @if (!empty($sectionResponses) && is_array($sectionResponses))
                        @foreach ($sectionResponses as $response)
                        @php
                            $responseData = is_array($response) ? $response : (array)$response;
                        @endphp
                        <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                            <td class="p-2 text-center text-neutral-500">{{ str_pad($sectionCode, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="p-2 font-semibold text-neutral-900">{{ $responseData['q_code'] ?? '' }}</td>
                            <td class="p-2 text-neutral-700">{{ substr($responseData['question_text'] ?? '', 0, 150) }}{{ strlen($responseData['question_text'] ?? '') > 150 ? '...' : '' }}</td>
                            <td class="p-2 text-center">
                                <span class="inline-block px-2 py-1 rounded-xl font-bold {{ getAnswerBadgeClass($responseData['answer'] ?? '') }}">
                                    {{ strtoupper($responseData['answer'] ?? '') }}
                                </span>
                            </td>
                            <td class="p-2 text-center font-bold text-neutral-900">{{ $responseData['weight'] ?? '' }}</td>
                            <td class="p-2 text-neutral-600">
                                @php
                                $comment = $responseData['comment'] ?? $responseData['na_justification'] ?? '';
                                @endphp
                                {{ substr($comment, 0, 100) }}{{ strlen($comment) > 100 ? '...' : '' }}
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="p-2 text-center text-neutral-500 italic">No responses recorded for this section</td>
                        </tr>
                    @endif
                @endforeach
            @else
                <tr>
                    <td colspan="6" class="p-4 text-center text-neutral-500">No response data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- FINDINGS & ACTION PLANS -->
    @if (isset($findings) && is_array($findings) && count($findings) > 0)
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-xs">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th colspan="7" class="p-3 text-left font-bold text-sm rounded-t-2xl">🔍 AUDIT FINDINGS & ACTION PLANS</th>
            </tr>
            <tr class="bg-neutral-800">
                <th class="p-2 text-left w-12">Sec</th>
                <th class="p-2 text-left">Finding</th>
                <th class="p-2 text-center w-12">Sev</th>
                <th class="p-2 text-left">Related Question</th>
                <th class="p-2 text-left">Action Plan</th>
                <th class="p-2 text-left w-24">Responsible</th>
                <th class="p-2 text-center w-16">Due Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($findings as $sectionCode => $sectionFindings)
                @foreach ($sectionFindings as $finding)
                @php
                    $findingData = is_array($finding) ? $finding : (array)$finding;
                @endphp
                <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                    <td class="p-2 text-center font-bold text-neutral-900">{{ str_pad($sectionCode, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="p-2">
                        <div class="font-semibold text-neutral-900">{{ $findingData['title'] ?? '' }}</div>
                        <div class="text-neutral-600 text-xs mt-1">{{ substr($findingData['description'] ?? '', 0, 100) }}</div>
                    </td>
                    <td class="p-2 text-center">
                        <span class="inline-block px-1.5 py-0.5 rounded-xl text-xs font-bold uppercase {{ getSeverityBadgeClass($findingData['severity'] ?? '') }}">
                            {{ substr(strtoupper($findingData['severity'] ?? 'N/A'), 0, 1) }}
                        </span>
                    </td>
                    <td class="p-2 text-neutral-700">{{ $findingData['q_code'] ?? 'N/A' }}</td>
                    <td class="p-2 text-neutral-700">{{ substr($findingData['recommendation'] ?? 'No action plan', 0, 80) }}</td>
                    <td class="p-2 text-neutral-600">{{ $findingData['responsible_person'] ?? 'Not assigned' }}</td>
                    <td class="p-2 text-center text-neutral-600">{{ formatDate($findingData['due_date'] ?? null, 'M d') }}</td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- AUDIT TEAM - FIXED -->
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-sm">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th colspan="3" class="p-3 text-left font-bold rounded-t-2xl">👥 AUDIT TEAM</th>
            </tr>
            <tr class="bg-neutral-800 text-xs">
                <th class="p-2 text-left">Name</th>
                <th class="p-2 text-left">Role</th>
                <th class="p-2 text-left">Organization</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($team) && is_array($team) && count($team) > 0)
                @foreach ($team as $member)
                @php
                    $memberData = is_array($member) ? $member : (array)$member;
                @endphp
                <tr class="border-b border-neutral-100 hover:bg-neutral-50">
                    <td class="p-2 font-semibold text-neutral-900">{{ safeOutput($memberData['name'] ?? '') }}</td>
                    <td class="p-2 text-neutral-700 capitalize">{{ safeOutput($memberData['role'] ?? '') }}</td>
                    <td class="p-2 text-neutral-600">{{ safeOutput($memberData['organization'] ?? $memberData['email'] ?? 'N/A') }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="3" class="p-3 text-center text-neutral-500">No team members recorded</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- EVIDENCE STATISTICS -->
    @if (isset($evidence) && is_array($evidence))
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-sm">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th colspan="2" class="p-3 text-left font-bold rounded-t-2xl">📎 EVIDENCE DOCUMENTATION</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-neutral-100">
                <td class="p-2 text-neutral-700">Total Non-Conformances (P/N)</td>
                <td class="p-2 text-right font-bold text-neutral-900">{{ $evidence['total_nc'] ?? 0 }}</td>
            </tr>
            <tr class="border-b border-neutral-100">
                <td class="p-2 text-neutral-700">With Evidence Attached</td>
                <td class="p-2 text-right font-bold text-emerald-600">{{ $evidence['with_evidence'] ?? 0 }}</td>
            </tr>
            <tr class="border-b border-neutral-100">
                <td class="p-2 text-neutral-700">Without Evidence</td>
                <td class="p-2 text-right font-bold text-red-600">{{ $evidence['without_evidence'] ?? 0 }}</td>
            </tr>
            <tr class="bg-neutral-100 rounded-b-2xl">
                <td class="p-2 font-semibold text-neutral-900">Documentation Rate</td>
                <td class="p-2 text-right font-bold text-neutral-900">{{ number_format($evidence['percentage_documented'] ?? 0, 1) }}%</td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- COMPARISON WITH PREVIOUS AUDIT -->
    @if (isset($comparison) && is_array($comparison))
    <table class="w-full bg-white rounded-2xl shadow-sm border border-neutral-200 text-sm">
        <thead class="bg-neutral-900 text-white">
            <tr>
                <th colspan="3" class="p-3 text-left font-bold rounded-t-2xl">📈 COMPARISON WITH PREVIOUS AUDIT</th>
            </tr>
            <tr class="bg-neutral-800 text-xs">
                <th class="p-2 text-left">Metric</th>
                <th class="p-2 text-center">Previous</th>
                <th class="p-2 text-center">Current</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-neutral-100">
                <td class="p-2 text-neutral-700">Score Percentage</td>
                <td class="p-2 text-center text-neutral-900">{{ number_format($comparison['previous_percentage'] ?? 0, 1) }}%</td>
                <td class="p-2 text-center font-bold text-neutral-900">{{ number_format($comparison['current_percentage'] ?? 0, 1) }}%</td>
            </tr>
            <tr class="border-b border-neutral-100">
                <td class="p-2 text-neutral-700">Star Level</td>
                <td class="p-2 text-center text-yellow-600">{{ str_repeat('★', $comparison['previous_stars'] ?? 0) }}</td>
                <td class="p-2 text-center font-bold text-yellow-600">{{ str_repeat('★', $comparison['current_stars'] ?? 0) }}</td>
            </tr>
            <tr class="bg-neutral-100 rounded-b-2xl">
                <td class="p-2 font-semibold text-neutral-900">Change</td>
                <td colspan="2" class="p-2 text-center">
                    <span class="font-bold text-lg {{ ($comparison['percentage_change'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ ($comparison['percentage_change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($comparison['percentage_change'] ?? 0, 2) }}%
                    </span>
                    <span class="ml-2 text-neutral-700 capitalize text-sm">({{ $comparison['trend'] ?? 'stable' }})</span>
                </td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- FOOTER -->
    <table class="w-full bg-neutral-900 text-white rounded-2xl shadow-sm text-sm">
        <tbody>
            <tr>
                <td class="p-4 rounded-2xl">
                    <div class="text-center">
                        <div class="font-bold text-lg mb-2">WHO AFRO SLIPTA Programme</div>
                        <div class="text-neutral-300 text-sm">Stepwise Laboratory Quality Improvement Process Towards Accreditation</div>
                        <div class="text-neutral-400 text-xs mt-2">ISO 15189:2022 • SLIPTA Version 3:2023</div>
                        <div class="text-neutral-500 text-xs mt-3">
                            Confidential Report • Generated {{ date('Y-m-d H:i') }} • Audit ID: {{ $audit->id ?? 'N/A' }}
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

</div>

</body>
</html>
