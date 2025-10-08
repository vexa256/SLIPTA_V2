<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SLIPTA Dashboard Controller
 *
 * Provides role-based dashboard analytics with real-time scoring
 * NO stored scores - all calculations performed on-demand
 *
 * @version 1.0.0
 */
class DashboardController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    // SLIPTA CONSTANTS - NEVER CHANGE
    // ══════════════════════════════════════════════════════════════════════

    private const EXACT_QUESTION_COUNT = 151;
    private const EXACT_TOTAL_POINTS = 367;
    private const EXACT_SECTION_COUNT = 12;

    private const SECTION_MAX_POINTS = [
        1 => 22, 2 => 26, 3 => 34, 4 => 24, 5 => 38, 6 => 24,
        7 => 27, 8 => 71, 9 => 24, 10 => 13, 11 => 7, 12 => 57
    ];

    private const STAR_BANDS = [
        5 => 95.0, 4 => 85.0, 3 => 75.0, 2 => 65.0, 1 => 55.0, 0 => 0.0,
    ];

    // ══════════════════════════════════════════════════════════════════════
    // MAIN DASHBOARD VIEW
    // ══════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        try {
            $ctx = $this->getUserContext();

            // Executive KPI Cards
            $kpis = [
                'total_laboratories' => $this->getTotalLaboratories($ctx),
                'total_audits' => $this->getTotalAudits($ctx),
                'average_star_rating' => $this->getAverageStarRating($ctx),
            ];

            // Lab Performance Analytics
            $performance = [
                'star_distribution' => $this->getStarLevelDistribution($ctx),
                'score_trends' => $this->getAuditScoreTrends($ctx),
                'section_heatmap' => $this->getSectionPerformanceHeatmap($ctx),
                'top_bottom_sections' => $this->getTopBottomSections($ctx),
            ];

            // Audit Operational Metrics
            $operations = [
                'status_overview' => $this->getAuditStatusOverview($ctx),
                'recent_activity' => $this->getRecentAuditActivity($ctx),
                'completion_rate' => $this->getResponseCompletionRate($ctx),
                'na_response_rate' => $this->getNaResponseRate($ctx),
            ];

            // Quality & Compliance
            $quality = [
                'findings_severity' => $this->getFindingsSeverityDistribution($ctx),
                'evidence_rate' => $this->getEvidenceDocumentationRate($ctx),
                'critical_gaps' => $this->getCriticalGaps($ctx),
                'recurring_findings' => $this->getRecurringFindings($ctx),
            ];

            // Improvement Trajectory
            $improvement = [
                'improving_labs' => $this->getImprovingLabs($ctx),
                'declining_labs' => $this->getDecliningLabs($ctx),
            ];

            // Role-Specific Widgets
            $myAudits = $this->getMyAudits($ctx);

            $chartData = $this->prepareChartData($performance);

        return view('dashboard.dashboard', compact(
            'kpis',
            'performance',
            'operations',
            'quality',
            'improvement',
            'myAudits',
            'chartData', // Add this
            'ctx'
        ));


        } catch (Exception $e) {
            Log::error('Dashboard failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to load dashboard: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // EXECUTIVE KPI CARDS
    // ══════════════════════════════════════════════════════════════════════

    private function getTotalLaboratories(array $ctx): array
    {
        $query = DB::table('laboratories as l')
            ->join('countries as c', 'l.country_id', '=', 'c.id')
            ->where('l.is_active', 1);

        // RBAC Filtering
        if (!$ctx['has_global_view']) {
            if (!empty($ctx['country_ids'])) {
                $query->whereIn('l.country_id', $ctx['country_ids']);
            } elseif (!empty($ctx['laboratory_ids'])) {
                $query->whereIn('l.id', $ctx['laboratory_ids']);
            } else {
                $query->whereRaw('1=0'); // No access
            }
        }

        $total = $query->count();

        $byType = $query->select('l.lab_type', DB::raw('COUNT(*) as count'))
            ->groupBy('l.lab_type')
            ->pluck('count', 'lab_type')
            ->toArray();

        return [
            'total' => $total,
            'by_type' => $byType,
            'trend' => $this->calculateTrend('laboratories', $ctx),
        ];
    }

    private function getTotalAudits(array $ctx): array
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        // RBAC Filtering
        $this->applyRoleBasedFiltering($query, $ctx);

        $total = $query->count();

        $byStatus = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $this->applyRoleBasedFiltering($byStatus, $ctx);

        $statusBreakdown = $byStatus->select('a.status', DB::raw('COUNT(*) as count'))
            ->groupBy('a.status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $total,
            'draft' => $statusBreakdown['draft'] ?? 0,
            'in_progress' => $statusBreakdown['in_progress'] ?? 0,
            'completed' => $statusBreakdown['completed'] ?? 0,
            'cancelled' => $statusBreakdown['cancelled'] ?? 0,
            'trend' => $this->calculateTrend('audits', $ctx),
        ];
    }

    private function getAverageStarRating(array $ctx): array
    {
        $completedAudits = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.status', 'completed');

        $this->applyRoleBasedFiltering($completedAudits, $ctx);

        $auditIds = $completedAudits->pluck('a.id');

        if ($auditIds->isEmpty()) {
            return [
                'average_stars' => 0,
                'total_audits' => 0,
                'trend' => 0,
            ];
        }

        $totalStars = 0;
        $validAudits = 0;

        foreach ($auditIds as $auditId) {
            $score = $this->calculateAuditScore($auditId);
            if ($score) {
                $totalStars += $score['stars'];
                $validAudits++;
            }
        }

        $avgStars = $validAudits > 0 ? round($totalStars / $validAudits, 2) : 0;

        return [
            'average_stars' => $avgStars,
            'total_audits' => $validAudits,
            'trend' => $this->calculateTrend('average_stars', $ctx),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // LAB PERFORMANCE ANALYTICS
    // ══════════════════════════════════════════════════════════════════════

    private function getStarLevelDistribution(array $ctx): array
    {
        $completedAudits = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.status', 'completed');

        $this->applyRoleBasedFiltering($completedAudits, $ctx);

        $auditIds = $completedAudits->select('a.id', 'l.name as lab_name')->get();

        $distribution = [
            0 => ['count' => 0, 'labs' => []],
            1 => ['count' => 0, 'labs' => []],
            2 => ['count' => 0, 'labs' => []],
            3 => ['count' => 0, 'labs' => []],
            4 => ['count' => 0, 'labs' => []],
            5 => ['count' => 0, 'labs' => []],
        ];

        foreach ($auditIds as $audit) {
            $score = $this->calculateAuditScore($audit->id);
            if ($score) {
                $stars = $score['stars'];
                $distribution[$stars]['count']++;
                $distribution[$stars]['labs'][] = $audit->lab_name;
            }
        }

        return $distribution;
    }

    private function getAuditScoreTrends(array $ctx): array
    {
        $completedAudits = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.status', 'completed')
            ->orderBy('a.closed_on');

        $this->applyRoleBasedFiltering($completedAudits, $ctx);

        $audits = $completedAudits->select('a.id', 'a.closed_on', 'l.name as lab_name')->get();

        $trends = [];

        foreach ($audits as $audit) {
            $score = $this->calculateAuditScore($audit->id);
            if ($score && $audit->closed_on) {
                $trends[] = [
                    'date' => $audit->closed_on,
                    'lab' => $audit->lab_name,
                    'percentage' => $score['percentage'],
                    'stars' => $score['stars'],
                ];
            }
        }

        return $trends;
    }

    private function getSectionPerformanceHeatmap(array $ctx): array
    {
        $completedAudits = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.status', 'completed');

        $this->applyRoleBasedFiltering($completedAudits, $ctx);

        $auditIds = $completedAudits->pluck('a.id');

        if ($auditIds->isEmpty()) {
            return [];
        }

        $heatmap = [];

        foreach ($auditIds as $auditId) {
            $sections = $this->calculateSectionScores($auditId);

            foreach ($sections as $section) {
                $sectionCode = $section['code'];
                if (!isset($heatmap[$sectionCode])) {
                    $heatmap[$sectionCode] = [
                        'title' => $section['title'],
                        'scores' => [],
                    ];
                }
                $heatmap[$sectionCode]['scores'][] = $section['percentage'];
            }
        }

        // Calculate averages
        foreach ($heatmap as $code => &$data) {
            $data['average'] = round(array_sum($data['scores']) / count($data['scores']), 2);
            $data['color'] = $this->getHeatmapColor($data['average']);
        }

        return $heatmap;
    }

    private function getTopBottomSections(array $ctx): array
    {
        $heatmap = $this->getSectionPerformanceHeatmap($ctx);

        if (empty($heatmap)) {
            return ['top' => [], 'bottom' => []];
        }

        usort($heatmap, fn($a, $b) => $b['average'] <=> $a['average']);

        return [
            'top' => array_slice($heatmap, 0, 5),
            'bottom' => array_slice(array_reverse($heatmap), 0, 5),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // AUDIT OPERATIONAL METRICS
    // ══════════════════════════════════════════════════════════════════════

    private function getAuditStatusOverview(array $ctx): array
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $this->applyRoleBasedFiltering($query, $ctx);

        $statusCounts = $query->select('a.status', DB::raw('COUNT(*) as count'))
            ->groupBy('a.status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'draft' => $statusCounts['draft'] ?? 0,
            'in_progress' => $statusCounts['in_progress'] ?? 0,
            'completed' => $statusCounts['completed'] ?? 0,
            'cancelled' => $statusCounts['cancelled'] ?? 0,
        ];
    }

    private function getRecentAuditActivity(array $ctx): array
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->leftJoin('users as u', 'a.created_by', '=', 'u.id')
            ->orderByDesc('a.created_at')
            ->limit(10);

        $this->applyRoleBasedFiltering($query, $ctx);

        $audits = $query->select(
            'a.id',
            'l.name as lab_name',
            'a.status',
            'a.opened_on',
            'a.closed_on',
            'u.name as lead_auditor'
        )->get();

        $activity = [];

        foreach ($audits as $audit) {
            $score = null;
            if ($audit->status === 'completed') {
                $score = $this->calculateAuditScore($audit->id);
            }

            $activity[] = [
                'audit_id' => $audit->id,
                'lab_name' => $audit->lab_name,
                'status' => $audit->status,
                'opened_on' => $audit->opened_on,
                'closed_on' => $audit->closed_on,
                'lead_auditor' => $audit->lead_auditor,
                'stars' => $score ? $score['stars'] : null,
                'percentage' => $score ? $score['percentage'] : null,
            ];
        }

        return $activity;
    }

    private function getResponseCompletionRate(array $ctx): array
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $this->applyRoleBasedFiltering($query, $ctx);

        $audits = $query->select('a.id', 'a.status')->get();

        $rates = [];

        foreach ($audits as $audit) {
            $totalQuestions = self::EXACT_QUESTION_COUNT;
            $answeredQuestions = DB::table('audit_responses')
                ->where('audit_id', $audit->id)
                ->count();

            $completionRate = $totalQuestions > 0
                ? round(($answeredQuestions / $totalQuestions) * 100, 2)
                : 0;

            $rates[] = [
                'audit_id' => $audit->id,
                'status' => $audit->status,
                'total_questions' => $totalQuestions,
                'answered' => $answeredQuestions,
                'completion_rate' => $completionRate,
            ];
        }

        return $rates;
    }

    private function getNaResponseRate(array $ctx): array
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $this->applyRoleBasedFiltering($query, $ctx);

        $audits = $query->select('a.id')->get();

        $naRates = [];

        foreach ($audits as $audit) {
            $totalResponses = DB::table('audit_responses')
                ->where('audit_id', $audit->id)
                ->count();

            $naCount = DB::table('audit_responses')
                ->where('audit_id', $audit->id)
                ->where('answer', 'NA')
                ->count();

            $naRate = $totalResponses > 0
                ? round(($naCount / $totalResponses) * 100, 2)
                : 0;

            // Breakdown by section
            $bySection = DB::table('audit_responses as r')
                ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
                ->join('slipta_sections as s', 'q.section_id', '=', 's.id')
                ->where('r.audit_id', $audit->id)
                ->where('r.answer', 'NA')
                ->select('s.code', 's.title', DB::raw('COUNT(*) as na_count'))
                ->groupBy('s.code', 's.title')
                ->get();

            $naRates[] = [
                'audit_id' => $audit->id,
                'total_responses' => $totalResponses,
                'na_count' => $naCount,
                'na_rate' => $naRate,
                'by_section' => $bySection,
            ];
        }

        return $naRates;
    }

    // ══════════════════════════════════════════════════════════════════════
    // QUALITY & COMPLIANCE
    // ══════════════════════════════════════════════════════════════════════

    private function getFindingsSeverityDistribution(array $ctx): array
    {
        $query = DB::table('audit_findings as f')
            ->join('audits as a', 'f.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $this->applyRoleBasedFiltering($query, $ctx);

        $distribution = $query->select('f.severity', DB::raw('COUNT(*) as count'))
            ->groupBy('f.severity')
            ->pluck('count', 'severity')
            ->toArray();

        return [
            'high' => $distribution['high'] ?? 0,
            'medium' => $distribution['medium'] ?? 0,
            'low' => $distribution['low'] ?? 0,
        ];
    }

    private function getEvidenceDocumentationRate(array $ctx): array
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $this->applyRoleBasedFiltering($query, $ctx);

        $auditIds = $query->pluck('a.id');

        if ($auditIds->isEmpty()) {
            return [
                'total_nc' => 0,
                'with_evidence' => 0,
                'without_evidence' => 0,
                'percentage' => 0,
            ];
        }

        $totalNC = DB::table('audit_responses')
            ->whereIn('audit_id', $auditIds)
            ->whereIn('answer', ['P', 'N'])
            ->count();

        $withEvidence = DB::table('audit_responses as r')
            ->join('audit_evidence as e', function($join) use ($auditIds) {
                $join->on('r.question_id', '=', 'e.question_id')
                     ->whereIn('e.audit_id', $auditIds);
            })
            ->whereIn('r.audit_id', $auditIds)
            ->whereIn('r.answer', ['P', 'N'])
            ->distinct()
            ->count('r.question_id');

        $percentage = $totalNC > 0 ? round(($withEvidence / $totalNC) * 100, 2) : 0;

        return [
            'total_nc' => $totalNC,
            'with_evidence' => $withEvidence,
            'without_evidence' => $totalNC - $withEvidence,
            'percentage' => $percentage,
        ];
    }

    private function getCriticalGaps(array $ctx): array
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.status', 'completed');

        $this->applyRoleBasedFiltering($query, $ctx);

        $auditIds = $query->pluck('a.id');

        if ($auditIds->isEmpty()) {
            return [];
        }

        $criticalGaps = DB::table('audit_responses as r')
            ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
            ->join('slipta_sections as s', 'q.section_id', '=', 's.id')
            ->whereIn('r.audit_id', $auditIds)
            ->whereIn('r.answer', ['N', 'P'])
            ->select(
                'q.q_code',
                'q.text',
                's.code as section_code',
                's.title as section_title',
                DB::raw('COUNT(*) as failure_count'),
                DB::raw('COUNT(DISTINCT r.audit_id) as audit_count')
            )
            ->groupBy('q.id', 'q.q_code', 'q.text', 's.code', 's.title')
            ->orderByDesc('failure_count')
            ->limit(20)
            ->get();

        $totalAudits = $auditIds->count();

        return $criticalGaps->map(function($gap) use ($totalAudits) {
            return [
                'question_code' => $gap->q_code,
                'question_text' => substr($gap->text, 0, 150) . '...',
                'section' => $gap->section_code . '. ' . $gap->section_title,
                'failure_count' => $gap->failure_count,
                'audit_count' => $gap->audit_count,
                'failure_rate' => round(($gap->audit_count / $totalAudits) * 100, 2),
            ];
        })->toArray();
    }

    private function getRecurringFindings(array $ctx): array
    {
        $query = DB::table('audit_findings as f')
            ->join('audits as a', 'f.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $this->applyRoleBasedFiltering($query, $ctx);

        $recurring = $query->select(
            'f.title',
            'f.severity',
            DB::raw('COUNT(DISTINCT a.laboratory_id) as lab_count'),
            DB::raw('COUNT(*) as occurrence_count')
        )
        ->groupBy('f.title', 'f.severity')
        ->having('occurrence_count', '>', 1)
        ->orderByDesc('occurrence_count')
        ->limit(10)
        ->get();

        return $recurring->toArray();
    }

    // ══════════════════════════════════════════════════════════════════════
    // IMPROVEMENT TRAJECTORY
    // ══════════════════════════════════════════════════════════════════════

    private function getImprovingLabs(array $ctx): array
    {
        return $this->getLabTrends($ctx, 'improving');
    }

    private function getDecliningLabs(array $ctx): array
    {
        return $this->getLabTrends($ctx, 'declining');
    }

    private function getLabTrends(array $ctx, string $type): array
    {
        $query = DB::table('audits as a1')
            ->join('laboratories as l', 'a1.laboratory_id', '=', 'l.id')
            ->join('audits as a2', function($join) {
                $join->on('a1.laboratory_id', '=', 'a2.laboratory_id')
                     ->on('a1.id', '=', 'a2.previous_audit_id');
            })
            ->where('a1.status', 'completed')
            ->where('a2.status', 'completed');

        $this->applyRoleBasedFiltering($query, $ctx);

        $audits = $query->select(
            'l.id as lab_id',
            'l.name as lab_name',
            'a1.id as current_audit_id',
            'a2.id as previous_audit_id',
            'a1.closed_on as current_date',
            'a2.closed_on as previous_date'
        )->get();

        $trends = [];

        foreach ($audits as $audit) {
            $currentScore = $this->calculateAuditScore($audit->current_audit_id);
            $previousScore = $this->calculateAuditScore($audit->previous_audit_id);

            if (!$currentScore || !$previousScore) {
                continue;
            }

            $starChange = $currentScore['stars'] - $previousScore['stars'];
            $percentageChange = round($currentScore['percentage'] - $previousScore['percentage'], 2);

            if ($type === 'improving' && $percentageChange > 0) {
                $trends[] = [
                    'lab_name' => $audit->lab_name,
                    'previous_stars' => $previousScore['stars'],
                    'current_stars' => $currentScore['stars'],
                    'star_change' => $starChange,
                    'previous_percentage' => $previousScore['percentage'],
                    'current_percentage' => $currentScore['percentage'],
                    'percentage_change' => $percentageChange,
                ];
            } elseif ($type === 'declining' && $percentageChange < 0) {
                $trends[] = [
                    'lab_name' => $audit->lab_name,
                    'previous_stars' => $previousScore['stars'],
                    'current_stars' => $currentScore['stars'],
                    'star_change' => $starChange,
                    'previous_percentage' => $previousScore['percentage'],
                    'current_percentage' => $currentScore['percentage'],
                    'percentage_change' => $percentageChange,
                ];
            }
        }

        usort($trends, fn($a, $b) => abs($b['percentage_change']) <=> abs($a['percentage_change']));

        return array_slice($trends, 0, 10);
    }

    // Add this method to your DashboardController class
private function prepareChartData(array $performance): array
{
    // Star Distribution Chart Data
    $starDistributionData = [];
    foreach ($performance['star_distribution'] as $stars => $data) {
        $starDistributionData[] = [
            'star' => $stars . ' Stars',
            'count' => $data['count']
        ];
    }

    // Score Trends Chart Data
    $scoreTrendsData = [];
    foreach ($performance['score_trends'] as $trend) {
        $scoreTrendsData[] = [
            'date' => $trend['date'],
            'date_ts' => strtotime($trend['date']) * 1000, // Convert to milliseconds for JS
            'percentage' => $trend['percentage'],
            'lab' => $trend['lab']
        ];
    }

    return [
        'star_distribution' => $starDistributionData,
        'score_trends' => $scoreTrendsData,
    ];
}
    // ══════════════════════════════════════════════════════════════════════
    // ROLE-SPECIFIC WIDGETS
    // ══════════════════════════════════════════════════════════════════════

    private function getMyAudits(array $ctx): array
    {
        if ($ctx['is_lead_auditor'] || $ctx['is_auditor']) {
            return $this->getAuditorAudits($ctx);
        } elseif ($ctx['is_lab_manager']) {
            return $this->getLabManagerAudits($ctx);
        }

        return [];
    }

    private function getAuditorAudits(array $ctx): array
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->join('audit_team_members as atm', 'a.id', '=', 'atm.audit_id')
            ->where('atm.user_id', $ctx['user']->id)
            ->whereIn('a.status', ['draft', 'in_progress', 'completed']);

        $audits = $query->select(
            'a.id',
            'l.name as lab_name',
            'a.status',
            'a.opened_on',
            'a.closed_on',
            'atm.role as my_role'
        )->get();

        $myAudits = [];

        foreach ($audits as $audit) {
            $progress = $this->getAuditProgress($audit->id);

            $myAudits[] = [
                'audit_id' => $audit->id,
                'lab_name' => $audit->lab_name,
                'status' => $audit->status,
                'my_role' => $audit->my_role,
                'progress_percentage' => $progress['percentage'],
                'questions_answered' => $progress['answered'],
                'total_questions' => $progress['total'],
                'opened_on' => $audit->opened_on,
            ];
        }

        return $myAudits;
    }

    private function getLabManagerAudits(array $ctx): array
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->whereIn('a.laboratory_id', $ctx['laboratory_ids']);

        $audits = $query->select(
            'a.id',
            'l.name as lab_name',
            'a.status',
            'a.opened_on',
            'a.closed_on'
        )->get();

        $myAudits = [];

        foreach ($audits as $audit) {
            $score = null;
            if ($audit->status === 'completed') {
                $score = $this->calculateAuditScore($audit->id);
            }

            $findingsCount = DB::table('audit_findings')
                ->where('audit_id', $audit->id)
                ->count();

            $myAudits[] = [
                'audit_id' => $audit->id,
                'lab_name' => $audit->lab_name,
                'status' => $audit->status,
                'opened_on' => $audit->opened_on,
                'closed_on' => $audit->closed_on,
                'stars' => $score ? $score['stars'] : null,
                'percentage' => $score ? $score['percentage'] : null,
                'findings_count' => $findingsCount,
            ];
        }

        return $myAudits;
    }

    // ══════════════════════════════════════════════════════════════════════
    // SCORING CALCULATIONS (REAL-TIME)
    // ══════════════════════════════════════════════════════════════════════

    private function calculateAuditScore(int $auditId): ?array
    {
        $responses = DB::table('audit_responses as r')
            ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
            ->where('r.audit_id', $auditId)
            ->select([
                'r.answer',
                DB::raw("CASE WHEN q.weight='3' THEN 3 WHEN q.weight='2' THEN 2 ELSE 0 END as weight_value")
            ])
            ->get();

        if ($responses->isEmpty()) {
            return null;
        }

        $totalEarned = 0;
        $totalNaPoints = 0;

        foreach ($responses as $response) {
            switch ($response->answer) {
                case 'Y':
                    $totalEarned += (int) $response->weight_value;
                    break;
                case 'P':
                    $totalEarned += 1;
                    break;
                case 'NA':
                    $totalNaPoints += (int) $response->weight_value;
                    break;
            }
        }

        $adjustedDenominator = max(1, self::EXACT_TOTAL_POINTS - $totalNaPoints);
        $percentage = round(($totalEarned / $adjustedDenominator) * 100, 2);

        $starLevel = 0;
        foreach (self::STAR_BANDS as $stars => $minPercentage) {
            if ($percentage >= $minPercentage) {
                $starLevel = $stars;
                break;
            }
        }

        return [
            'earned' => $totalEarned,
            'total_possible' => self::EXACT_TOTAL_POINTS,
            'na_points_excluded' => $totalNaPoints,
            'adjusted_denominator' => $adjustedDenominator,
            'percentage' => $percentage,
            'stars' => $starLevel,
        ];
    }

    private function calculateSectionScores(int $auditId): array
    {
        $sections = DB::table('slipta_sections as s')
            ->leftJoin('slipta_questions as q', 's.id', '=', 'q.section_id')
            ->leftJoin('audit_responses as r', function ($join) use ($auditId) {
                $join->on('q.id', '=', 'r.question_id')
                     ->where('r.audit_id', $auditId);
            })
            ->select([
                's.id',
                's.code',
                's.title',
                DB::raw('COUNT(q.id) as total_questions'),
                DB::raw('COUNT(r.id) as answered_questions'),
                DB::raw('SUM(CASE WHEN r.answer="Y" THEN (CASE WHEN q.weight="3" THEN 3 WHEN q.weight="2" THEN 2 ELSE 0 END) ELSE 0 END) as y_points'),
                DB::raw('SUM(CASE WHEN r.answer="P" THEN 1 ELSE 0 END) as p_points'),
                DB::raw('SUM(CASE WHEN r.answer="NA" THEN (CASE WHEN q.weight="3" THEN 3 WHEN q.weight="2" THEN 2 ELSE 0 END) ELSE 0 END) as na_points'),
            ])
            ->groupBy('s.id', 's.code', 's.title')
            ->orderBy('s.code')
            ->get();

        $breakdown = [];

        foreach ($sections as $section) {
            $maxPoints = self::SECTION_MAX_POINTS[(int) $section->code] ?? 0;
            $earned = (int) $section->y_points + (int) $section->p_points;
            $adjusted = max(1, $maxPoints - (int) $section->na_points);
            $percentage = round(($earned / $adjusted) * 100, 2);

            $breakdown[] = [
                'code' => $section->code,
                'title' => $section->title,
                'total_questions' => $section->total_questions,
                'answered' => $section->answered_questions,
                'max_points' => $maxPoints,
                'earned_points' => $earned,
                'adjusted_denominator' => $adjusted,
                'percentage' => $percentage,
            ];
        }

        return $breakdown;
    }

    private function getAuditProgress(int $auditId): array
    {
        $totalQuestions = self::EXACT_QUESTION_COUNT;
        $answeredQuestions = DB::table('audit_responses')
            ->where('audit_id', $auditId)
            ->count();

        $percentage = $totalQuestions > 0
            ? round(($answeredQuestions / $totalQuestions) * 100, 2)
            : 0;

        return [
            'total' => $totalQuestions,
            'answered' => $answeredQuestions,
            'percentage' => $percentage,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // RBAC METHODS
    // ══════════════════════════════════════════════════════════════════════

    private function getUserContext(): array
    {
        $user = auth()->user();
        if (!$user) {
            throw new Exception('Unauthenticated');
        }

        $roles = DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $user->id)
            ->where('ur.is_active', 1)
            ->select('r.name', 'ur.country_id', 'ur.laboratory_id')
            ->get();

        $roleNames = $roles->pluck('name');

        return [
            'user' => $user,
            'roles' => $roles,
            'role_names' => $roleNames,
            'has_global_view' => $roleNames->contains('system_admin') || $roleNames->contains('project_coordinator'),
            'is_country_coordinator' => $roleNames->contains('country_coordinator'),
            'is_lead_auditor' => $roleNames->contains('lead_auditor'),
            'is_auditor' => $roleNames->contains('auditor'),
            'is_lab_manager' => $roleNames->contains('laboratory_manager'),
            'country_ids' => $roles->pluck('country_id')->filter()->unique()->values()->all(),
            'laboratory_ids' => $roles->pluck('laboratory_id')->filter()->unique()->values()->all(),
        ];
    }

    private function applyRoleBasedFiltering($query, array $context)
    {
        if (!$context['has_global_view']) {
            if ($context['is_country_coordinator']) {
                $query->whereIn('l.country_id', $context['country_ids']);
            } elseif ($context['is_auditor'] || $context['is_lead_auditor']) {
                $query->where(function($q) use ($context) {
                    $q->whereIn('l.id', $context['laboratory_ids'])
                      ->orWhereExists(function($subq) use ($context) {
                          $subq->from('audit_team_members')
                               ->whereColumn('audit_team_members.audit_id', '=', 'a.id')
                               ->where('audit_team_members.user_id', $context['user']->id);
                      });
                });
            } elseif ($context['is_lab_manager']) {
                $query->whereIn('l.id', $context['laboratory_ids']);
            } else {
                $query->whereRaw('1=0'); // No access
            }
        }

        return $query;
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ══════════════════════════════════════════════════════════════════════

    private function calculateTrend(string $metric, array $ctx): float
    {
        // Placeholder for trend calculation
        // Compare current period vs previous period
        return 0.0;
    }

    private function getHeatmapColor(float $percentage): string
    {
        if ($percentage >= 85) return 'green';
        if ($percentage >= 65) return 'yellow';
        return 'red';
    }
}
