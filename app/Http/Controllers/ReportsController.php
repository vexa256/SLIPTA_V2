<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SLIPTA Advanced Report Generation Controller
 *
 * Features:
 * - AI-powered narrative analysis
 * - International award-winning PDF formatting
 * - Strict RBAC enforcement
 * - Comprehensive audit insights
 *
 * @version 1.0.0
 */
class ReportsController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    // SLIPTA CONSTANTS
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

    private const SECTION_NAMES = [
        1 => 'Documents and Records',
        2 => 'Organisation and Leadership',
        3 => 'Personnel Management',
        4 => 'Customer Focus',
        5 => 'Equipment Management',
        6 => 'Assessments',
        7 => 'Supplier and Inventory Management',
        8 => 'Process Management',
        9 => 'Information Management',
        10 => 'Nonconforming Event Management',
        11 => 'Continual Improvement',
        12 => 'Facilities and Safety'
    ];

    // ══════════════════════════════════════════════════════════════════════
    // SELECTION VIEW
    // ══════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        try {
            $ctx = $this->getUserContext();

            // Get countries with audits based on user role
            $countries = $this->getAccessibleCountries($ctx);

            return view('reports.select', [
                'countries' => $countries,
                'userContext' => $ctx
            ]);

        } catch (Exception $e) {
            Log::error('Report selection failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load report selection: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // AJAX ENDPOINTS FOR DYNAMIC LOADING
    // ══════════════════════════════════════════════════════════════════════

    public function getLaboratories(Request $request, $countryId)
    {
        try {
            $ctx = $this->getUserContext();
            $countryId = (int) $countryId;

            if (!$this->canAccessCountry($ctx, $countryId)) {
                return response()->json(['success' => false, 'error' => 'Access denied'], 403);
            }

            $labs = DB::table('laboratories as l')
                ->join('audits as a', 'l.id', '=', 'a.laboratory_id')
                ->where('l.country_id', $countryId)
                ->where('l.is_active', 1)
                ->whereIn('a.status', ['in_progress', 'completed'])
                ->select('l.id', 'l.name', 'l.lab_number', 'l.lab_type')
                ->groupBy('l.id', 'l.name', 'l.lab_number', 'l.lab_type')
                ->orderBy('l.name')
                ->get();

            return response()->json(['success' => true, 'laboratories' => $labs]);

        } catch (Exception $e) {
            Log::error('Get laboratories failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getAudits(Request $request, $laboratoryId)
    {
        try {
            $ctx = $this->getUserContext();
            $laboratoryId = (int) $laboratoryId;

            if (!$this->canAccessLaboratory($ctx, $laboratoryId)) {
                return response()->json(['success' => false, 'error' => 'Access denied'], 403);
            }

            $audits = DB::table('audits as a')
                ->leftJoin('users as uc', 'a.created_by', '=', 'uc.id')
                ->where('a.laboratory_id', $laboratoryId)
                ->whereIn('a.status', ['in_progress', 'completed'])
                ->select(
                    'a.id',
                    'a.status',
                    'a.opened_on',
                    'a.closed_on',
                    'a.prior_official_status',
                    'uc.name as created_by_name',
                    DB::raw('(SELECT COUNT(*) FROM audit_responses WHERE audit_id = a.id) as response_count')
                )
                ->orderByDesc('a.opened_on')
                ->get();

            return response()->json(['success' => true, 'audits' => $audits]);

        } catch (Exception $e) {
            Log::error('Get audits failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // PDF GENERATION WITH AI NARRATIVE
    // ══════════════════════════════════════════════════════════════════════

  public function generate(Request $request, $auditId)
{
    try {
        $auditId = (int) $auditId;
        $ctx = $this->getUserContext();

        if (!$this->canAccessAudit($ctx, $auditId)) {
            return redirect()->route('reports.index')->with('error', 'Access denied');
        }

        $audit = $this->getAuditDetails($auditId);
        if (!$audit) {
            return redirect()->route('reports.index')->with('error', 'Audit not found');
        }

        if ($audit->status !== 'completed') {
            return back()->with('error', 'Reports can only be generated for completed audits');
        }

        $score = $this->calculateScore($auditId);
        $sections = $this->getSectionBreakdown($auditId);
        $findings = $this->getFindings($auditId);
        $team = $this->getTeamMembers($auditId);
        $evidence = $this->getEvidenceStats($auditId);

        // NEW: Get all detailed responses
        $allResponses = $this->getAllAuditResponses($auditId);

        $comparison = null;
        if ($audit->previous_audit_id) {
            $comparison = $this->compareAudits($auditId, $audit->previous_audit_id);
        }

        $narrative = $this->generateAINarrative($audit, $score, $sections, $findings, $comparison);

        return view('reports.report', compact(
            'audit', 'score', 'sections', 'findings', 'team',
            'evidence', 'comparison', 'narrative', 'allResponses'  // ← NEW
        ));

    } catch (Exception $e) {
        Log::error('Report generation failed', [
            'audit_id' => $auditId ?? 'unknown',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
    }
}


/**
 * Get all audit responses grouped by section
 *
 * @param int $auditId
 * @return array
 */
private function getAllAuditResponses($auditId)
{
    $responses = DB::table('audit_responses as r')
        ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
        ->join('slipta_sections as s', 'q.section_id', '=', 's.id')
        ->where('r.audit_id', $auditId)
        ->select([
            's.code as section_code',
            's.title as section_title',
            'q.q_code',
            'q.text as question_text',
            DB::raw("CASE WHEN q.weight='3' THEN 3 WHEN q.weight='2' THEN 2 ELSE 0 END as weight"),
            'r.answer',
            'r.comment',
            'r.na_justification'
        ])
        ->orderByRaw('CAST(s.code AS UNSIGNED)')
        ->orderByRaw("
            CAST(SUBSTRING_INDEX(q.q_code, '.', 1) AS UNSIGNED),
            CAST(SUBSTRING_INDEX(q.q_code, '.', -1) AS UNSIGNED)
        ")
        ->get()
        ->groupBy('section_code');

    return $responses->toArray();
}
    // ══════════════════════════════════════════════════════════════════════
    // AI NARRATIVE ENGINE
    // ══════════════════════════════════════════════════════════════════════

    private function generateAINarrative($audit, $score, $sections, $findings, $comparison)
    {
        $narrative = [
            'executive_summary' => '',
            'performance_analysis' => '',
            'strengths' => [],
            'weaknesses' => [],
            'critical_gaps' => [],
            'improvement_trajectory' => '',
            'recommendations' => [],
            'risk_assessment' => ''
        ];

        // EXECUTIVE SUMMARY
        $starLabel = $this->getStarLabel($score['stars']);
        $narrative['executive_summary'] = $this->buildExecutiveSummary($audit, $score, $starLabel);

        // PERFORMANCE ANALYSIS
        $narrative['performance_analysis'] = $this->buildPerformanceAnalysis($score, $sections, $comparison);

        // IDENTIFY STRENGTHS
        $narrative['strengths'] = $this->identifyStrengths($sections);

        // IDENTIFY WEAKNESSES
        $narrative['weaknesses'] = $this->identifyWeaknesses($sections);

        // CRITICAL GAPS
        $narrative['critical_gaps'] = $this->identifyCriticalGaps($findings);

        // IMPROVEMENT TRAJECTORY
        if ($comparison) {
            $narrative['improvement_trajectory'] = $this->buildImprovementTrajectory($comparison);
        }

        // STRATEGIC RECOMMENDATIONS
        $narrative['recommendations'] = $this->generateRecommendations($score, $sections, $findings);

        // RISK ASSESSMENT
        $narrative['risk_assessment'] = $this->buildRiskAssessment($findings, $sections);

        return $narrative;
    }

    private function buildExecutiveSummary($audit, $score, $starLabel)
    {
        $summary = "This comprehensive SLIPTA assessment evaluates {$audit->lab_name} ({$audit->lab_number}) against the WHO SLIPTA Version 3:2023 standard. ";

        $summary .= "The laboratory achieved a score of {$score['earned']} out of {$score['adjusted_denominator']} applicable points ";
        $summary .= "({$score['percentage']}%), earning {$starLabel}. ";

        if ($score['stars'] >= 4) {
            $summary .= "This exceptional performance demonstrates strong commitment to quality management systems and laboratory excellence. ";
        } elseif ($score['stars'] === 3) {
            $summary .= "This solid performance indicates substantial progress toward full compliance with international standards. ";
        } elseif ($score['stars'] >= 1) {
            $summary .= "While foundational systems are in place, significant gaps require immediate attention to ensure patient safety and result reliability. ";
        } else {
            $summary .= "Critical deficiencies in laboratory management systems pose substantial risks to patient care and require urgent remediation. ";
        }

        if ($score['na_points_excluded'] > 0) {
            $summary .= "Note: {$score['na_points_excluded']} points were excluded as not applicable to this laboratory's scope of service. ";
        }

        return $summary;
    }

    private function buildPerformanceAnalysis($score, $sections, $comparison)
    {
        $analysis = "Performance Distribution Analysis: ";

        $excellentSections = 0;
        $satisfactorySections = 0;
        $needsImprovementSections = 0;

        foreach ($sections as $section) {
            if ($section['percentage'] >= 85) {
                $excellentSections++;
            } elseif ($section['percentage'] >= 65) {
                $satisfactorySections++;
            } else {
                $needsImprovementSections++;
            }
        }

        $analysis .= "{$excellentSections} sections demonstrate excellent compliance (≥85%), ";
        $analysis .= "{$satisfactorySections} sections show satisfactory performance (65-84%), ";
        $analysis .= "and {$needsImprovementSections} sections require substantial improvement (<65%). ";

        if ($comparison) {
            $trend = $comparison['percentage_change'] >= 0 ? 'improvement' : 'decline';
            $analysis .= "Compared to the previous audit, the laboratory shows a {$comparison['percentage_change']}% {$trend} in overall performance. ";
        }

        return $analysis;
    }

    private function identifyStrengths($sections)
    {
        $strengths = [];

        foreach ($sections as $section) {
            if ($section['percentage'] >= 90) {
                $strengths[] = [
                    'section' => $section['title'],
                    'score' => $section['percentage'],
                    'insight' => "Outstanding performance in {$section['title']} ({$section['percentage']}%) demonstrates mature quality management practices and should serve as a model for other areas."
                ];
            }
        }

        return array_slice($strengths, 0, 3); // Top 3 strengths
    }

    private function identifyWeaknesses($sections)
    {
        $weaknesses = [];

        foreach ($sections as $section) {
            if ($section['percentage'] < 65) {
                $gap = 65 - $section['percentage'];
                $weaknesses[] = [
                    'section' => $section['title'],
                    'score' => $section['percentage'],
                    'gap' => $gap,
                    'insight' => "{$section['title']} requires immediate attention with a {$gap}% gap to minimum acceptable standards. Prioritize corrective actions in this area to ensure patient safety."
                ];
            }
        }

        usort($weaknesses, fn($a, $b) => $b['gap'] <=> $a['gap']);

        return array_slice($weaknesses, 0, 3); // Top 3 weaknesses
    }

    private function identifyCriticalGaps($findings)
    {
        $criticalGaps = [];

        $highSeverityCount = 0;
        $openActionPlans = 0;

        foreach ($findings as $sectionFindings) {
            foreach ($sectionFindings as $finding) {
                if ($finding->severity === 'high') {
                    $highSeverityCount++;
                }
                if (in_array($finding->plan_status, ['open', 'in_progress'])) {
                    $openActionPlans++;
                }
            }
        }

        if ($highSeverityCount > 0) {
            $criticalGaps[] = "{$highSeverityCount} high-severity findings require immediate corrective action to mitigate patient safety risks.";
        }

        if ($openActionPlans > 10) {
            $criticalGaps[] = "{$openActionPlans} open action plans indicate resource constraints or management oversight gaps.";
        }

        return $criticalGaps;
    }

    private function buildImprovementTrajectory($comparison)
    {
        $trajectory = "Longitudinal Analysis: ";

        if ($comparison['star_change'] > 0) {
            $trajectory .= "The laboratory has progressed {$comparison['star_change']} star level(s) since the previous audit, ";
            $trajectory .= "demonstrating sustained commitment to quality improvement. ";
        } elseif ($comparison['star_change'] < 0) {
            $trajectory .= "Concerning decline of {$comparison['star_change']} star level(s) requires urgent management intervention ";
            $trajectory .= "to restore quality standards. ";
        } else {
            $trajectory .= "The laboratory maintained its star level, though a {$comparison['percentage_change']}% score change indicates ";
            $trajectory .= ($comparison['percentage_change'] >= 0 ? "incremental improvements. " : "erosion of quality standards. ");
        }

        return $trajectory;
    }

    private function generateRecommendations($score, $sections, $findings)
    {
        $recommendations = [];

        // Recommendation 1: Priority sections
        $lowestSections = collect($sections)->sortBy('percentage')->take(2);
        $recommendations[] = [
            'priority' => 'HIGH',
            'area' => 'Quality Management System',
            'recommendation' => "Immediate focus required on " . $lowestSections->pluck('title')->implode(' and ') .
                               " to address foundational compliance gaps."
        ];

        // Recommendation 2: CAPA effectiveness
        $findingCount = collect($findings)->flatten(1)->count();
        if ($findingCount > 15) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'area' => 'Corrective Action',
                'recommendation' => "Strengthen root cause analysis and CAPA effectiveness monitoring. Current {$findingCount} findings suggest inadequate preventive measures."
            ];
        }

        // Recommendation 3: Star progression
        if ($score['stars'] < 5) {
            $gapToNextStar = 0;
            foreach (self::STAR_BANDS as $stars => $threshold) {
                if ($stars > $score['stars']) {
                    $gapToNextStar = $threshold - $score['percentage'];
                    break;
                }
            }

            $recommendations[] = [
                'priority' => 'MEDIUM',
                'area' => 'Strategic Planning',
                'recommendation' => "To achieve next star level, focus on gaining {$gapToNextStar}% additional compliance. Target low-hanging fruit in highest-weighted sections."
            ];
        }

        // Recommendation 4: Documentation
        $recommendations[] = [
            'priority' => 'MEDIUM',
            'area' => 'Documentation & Records',
            'recommendation' => "Establish robust document control and record retention systems. Implement regular management reviews to ensure sustainability."
        ];

        return $recommendations;
    }

    private function buildRiskAssessment($findings, $sections)
    {
        $riskLevel = 'LOW';
        $riskFactors = [];

        // Count high-severity findings
        $highSeverityCount = 0;
        foreach ($findings as $sectionFindings) {
            foreach ($sectionFindings as $finding) {
                if ($finding->severity === 'high') {
                    $highSeverityCount++;
                }
            }
        }

        if ($highSeverityCount > 5) {
            $riskLevel = 'CRITICAL';
            $riskFactors[] = "Multiple high-severity findings ({$highSeverityCount}) indicate systemic quality control failures";
        } elseif ($highSeverityCount > 0) {
            $riskLevel = 'HIGH';
            $riskFactors[] = "High-severity findings present immediate patient safety concerns";
        }

        // Check critical sections
        $section8Score = collect($sections)->firstWhere('code', 8)['percentage'] ?? 100;
        if ($section8Score < 65) {
            $riskLevel = 'CRITICAL';
            $riskFactors[] = "Process Management deficiencies directly impact result accuracy and reliability";
        }

        $assessment = "Overall Risk Level: {$riskLevel}. ";
        if (!empty($riskFactors)) {
            $assessment .= "Key risk factors: " . implode('; ', $riskFactors) . ". ";
        }
        $assessment .= "Immediate management intervention required to mitigate identified risks.";

        return $assessment;
    }

    // ══════════════════════════════════════════════════════════════════════
    // DATA RETRIEVAL METHODS
    // ══════════════════════════════════════════════════════════════════════

    private function getAuditDetails($auditId)
    {
        return DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->join('countries as c', 'l.country_id', '=', 'c.id')
            ->leftJoin('users as uc', 'a.created_by', '=', 'uc.id')
            ->where('a.id', $auditId)
            ->select(
                'a.*',
                'l.name as lab_name',
                'l.lab_number',
                'l.lab_type',
                'l.address',
                'l.city',
                'l.contact_person',
                'l.email',
                'l.phone',
                'c.name as country_name',
                'c.code as country_code',
                'uc.name as created_by_name',
                'uc.email as created_by_email'
            )
            ->first();
    }

    private function calculateScore($auditId)
    {
        $responses = DB::table('audit_responses as r')
            ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
            ->where('r.audit_id', $auditId)
            ->select([
                'r.answer',
                DB::raw("CASE WHEN q.weight='3' THEN 3 WHEN q.weight='2' THEN 2 ELSE 0 END as weight_value")
            ])
            ->get();

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

    private function getSectionBreakdown($auditId)
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
                DB::raw('SUM(CASE WHEN r.answer="Y" THEN 1 ELSE 0 END) as yes_count'),
                DB::raw('SUM(CASE WHEN r.answer="P" THEN 1 ELSE 0 END) as partial_count'),
                DB::raw('SUM(CASE WHEN r.answer="N" THEN 1 ELSE 0 END) as no_count'),
                DB::raw('SUM(CASE WHEN r.answer="NA" THEN 1 ELSE 0 END) as na_count'),
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
                'yes' => $section->yes_count,
                'partial' => $section->partial_count,
                'no' => $section->no_count,
                'na' => $section->na_count,
                'max_points' => $maxPoints,
                'earned_points' => $earned,
                'adjusted_denominator' => $adjusted,
                'percentage' => $percentage,
            ];
        }

        return $breakdown;
    }

    private function getFindings($auditId)
    {
        $findings = DB::table('audit_findings as f')
            ->leftJoin('slipta_sections as s', 'f.section_id', '=', 's.id')
            ->leftJoin('slipta_questions as q', 'f.question_id', '=', 'q.id')
            ->leftJoin('action_plans as ap', 'ap.finding_id', '=', 'f.id')
            ->leftJoin('users as u', 'ap.responsible_user_id', '=', 'u.id')
            ->where('f.audit_id', $auditId)
            ->select([
                'f.*',
                's.code as section_code',
                's.title as section_title',
                'q.q_code',
                'q.text as question_text',
                'ap.recommendation',
                'ap.status as plan_status',
                'ap.due_date',
                'ap.resolution_notes',
                'ap.closed_at',
                'u.name as responsible_person'
            ])
            ->orderBy('s.code')
            ->orderBy('f.severity', 'desc')
            ->get()
            ->groupBy('section_code');

        return $findings;
    }

  private function getTeamMembers($auditId)
{
    return DB::table('audit_team_members as atm')
        ->join('users as u', 'atm.user_id', '=', 'u.id')
        ->where('atm.audit_id', $auditId)
        ->select('u.name', 'u.email', 'u.organization', 'atm.role')
        ->orderByRaw("FIELD(atm.role, 'lead', 'member', 'observer')")
        ->get()
        ->toArray(); // ← CONVERT TO ARRAY FOR BLADE COMPATIBILITY
}

    private function getEvidenceStats($auditId)
    {
        $totalNC = DB::table('audit_responses')
            ->where('audit_id', $auditId)
            ->whereIn('answer', ['P', 'N'])
            ->count();

        $withEvidence = DB::table('audit_responses as r')
            ->join('audit_evidence as e', function ($join) use ($auditId) {
                $join->on('r.question_id', '=', 'e.question_id')
                     ->where('e.audit_id', $auditId);
            })
            ->where('r.audit_id', $auditId)
            ->whereIn('r.answer', ['P', 'N'])
            ->distinct()
            ->count('r.question_id');

        return [
            'total_nc' => $totalNC,
            'with_evidence' => $withEvidence,
            'without_evidence' => $totalNC - $withEvidence,
            'percentage_documented' => $totalNC > 0 ? round(($withEvidence / $totalNC) * 100, 1) : 0,
        ];
    }

    private function compareAudits($currentAuditId, $previousAuditId)
    {
        try {
            $current = $this->calculateScore($currentAuditId);
            $previous = $this->calculateScore($previousAuditId);

            return [
                'previous_percentage' => $previous['percentage'],
                'previous_stars' => $previous['stars'],
                'current_percentage' => $current['percentage'],
                'current_stars' => $current['stars'],
                'percentage_change' => round($current['percentage'] - $previous['percentage'], 2),
                'star_change' => $current['stars'] - $previous['stars'],
                'trend' => $current['percentage'] >= $previous['percentage'] ? 'improving' : 'declining',
            ];
        } catch (Exception $e) {
            Log::warning('Previous audit comparison failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // RBAC METHODS
    // ══════════════════════════════════════════════════════════════════════

    private function getUserContext()
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
            'country_ids' => $roles->pluck('country_id')->filter()->unique()->values()->all(),
            'laboratory_ids' => $roles->pluck('laboratory_id')->filter()->unique()->values()->all(),
        ];
    }

    private function getAccessibleCountries($context)
    {
        $query = DB::table('countries as c')
            ->join('laboratories as l', 'c.id', '=', 'l.country_id')
            ->join('audits as a', 'l.id', '=', 'a.laboratory_id')
            ->whereIn('a.status', ['in_progress', 'completed'])
            ->select('c.id', 'c.name', 'c.code')
            ->distinct();

        if (!$context['has_global_view']) {
            if (!empty($context['country_ids'])) {
                $query->whereIn('c.id', $context['country_ids']);
            } else {
                return collect();
            }
        }

        return $query->orderBy('c.name')->get();
    }

    private function canAccessCountry($context, $countryId)
    {
        if ($context['has_global_view']) {
            return true;
        }

        return in_array($countryId, $context['country_ids'], true);
    }

    private function canAccessLaboratory($context, $laboratoryId)
    {
        if ($context['has_global_view']) {
            return true;
        }

        $lab = DB::table('laboratories')->where('id', $laboratoryId)->first();
        if (!$lab) {
            return false;
        }

        return in_array($lab->country_id, $context['country_ids'], true) ||
               in_array($laboratoryId, $context['laboratory_ids'], true);
    }

    private function canAccessAudit($context, $auditId)
    {
        if ($context['has_global_view']) {
            return true;
        }

        $audit = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.id', $auditId)
            ->select('l.country_id', 'a.laboratory_id')
            ->first();

        if (!$audit) {
            return false;
        }

        return in_array($audit->country_id, $context['country_ids'], true) ||
               in_array($audit->laboratory_id, $context['laboratory_ids'], true);
    }

    private function getStarLabel($stars)
    {
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
}
