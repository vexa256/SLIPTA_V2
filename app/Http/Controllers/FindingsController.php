<?php
namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * FindingsController - SLIPTA v3:2023 Findings Management
 *
 * CRITICAL FEATURES:
 * 1. Automatic synchronization when situations change
 * 2. Audit status lifecycle management (open → close → reopen)
 * 3. Comprehensive professor-grade reports
 * 4. Evidence gaps as flags only (never blockers)
 * 5. Real-time cascade updates
 *
 * @version 3.0.0 - PRODUCTION READY
 * @standard SLIPTA v3:2023
 * @standard ISO 15189:2022
 */
class FindingsController extends Controller
{
    // ——————————————————————————————————————————————————————————————————————
    // SLIPTA v3:2023 IMMUTABLE CONSTANTS (WHO PDF)
    // ——————————————————————————————————————————————————————————————————————

    private const EXACT_QUESTION_COUNT = 151; //
    private const EXACT_TOTAL_POINTS   = 367; // ✅ Already correct
    private const EXACT_SECTION_COUNT  = 12;  // ✅ Already correct

   private const SECTION_MAX_POINTS = [
    1=>22, 2=>26, 3=>34, 4=>24, 5=>38, 6=>24,
    7=>27, 8=>71, 9=>24, 10=>13, 11=>7, 12=>57
]   ;

    private const STAR_BANDS = [
        5 => 95.0, 4 => 85.0, 3 => 75.0, 2 => 65.0, 1 => 55.0, 0 => 0.0,
    ];

    private const VALID_ANSWERS         = ['Y', 'P', 'N', 'NA'];
    private const VALID_WEIGHTS         = ['2', '3'];
    private const VALID_SEVERITIES      = ['low', 'medium', 'high'];
    private const VALID_ACTION_TYPES    = ['finding', 'risk_opportunity', 'other'];
    private const VALID_ACTION_STATUSES = ['open', 'in_progress', 'closed', 'deferred'];
    private const VALID_AUDIT_STATUSES  = ['draft', 'in_progress', 'completed', 'cancelled'];

    // ══════════════════════════════════════════════════════════════════════
    // EXISTING METHODS (INDEX, SHOW, STORE, UPDATE, DESTROY)
    // ══════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        try {
            $this->validateSystemIntegrity();
            $ctx = $this->getUserContext();

            $audits = $this->getScopedAudits($ctx)
                ->leftJoin('audit_responses as ar', 'a.id', '=', 'ar.audit_id')
                ->leftJoin(DB::raw('(SELECT audit_id, COUNT(*) as finding_count FROM audit_findings GROUP BY audit_id) f'), 'a.id', '=', 'f.audit_id')
                ->select([
                    'a.id', 'a.status', 'a.laboratory_id', 'a.opened_on', 'a.closed_on',
                    'a.last_audit_date', 'a.prior_official_status', 'a.previous_audit_id',
                    'a.created_by', 'a.updated_by', 'a.created_at', 'a.updated_at',
                    'l.name as lab_name', 'l.lab_number', 'c.name as country_name', 'l.country_id',
                    DB::raw('COUNT(DISTINCT ar.id) as response_count'),
                    DB::raw('COALESCE(f.finding_count,0) as finding_count'),
                    DB::raw('SUM(CASE WHEN ar.answer IN ("P","N") THEN 1 ELSE 0 END) as non_compliant_count'),
                    DB::raw('SUM(CASE WHEN ar.answer="Y" THEN 1 ELSE 0 END) as compliant_count'),
                ])
                ->groupBy([
                    'a.id', 'a.status', 'a.laboratory_id', 'a.opened_on', 'a.closed_on',
                    'a.last_audit_date', 'a.prior_official_status', 'a.previous_audit_id',
                    'a.created_by', 'a.updated_by', 'a.created_at', 'a.updated_at',
                    'l.name', 'l.lab_number', 'c.name', 'l.country_id', 'f.audit_id', 'f.finding_count',
                ])
                ->having('response_count', '>', 0)
                ->orderByDesc('a.updated_at')
                ->get();

            return view('findings.select', ['audits' => $audits, 'userContext' => $ctx]);

        } catch (Exception $e) {
            Log::error('Findings index failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to load audits: ' . $e->getMessage());
        }
    }

    public function show($auditId)
    {
        try {
            $this->validateSystemIntegrity();
            $ctx = $this->getUserContext();

            $auditId = (int) $auditId;
            if ($auditId <= 0) {
                return redirect()->route('findings.index')->with('error', 'Invalid audit ID');
            }

            if (! $this->canAccessAudit($ctx, $auditId)) {
                return redirect()->route('findings.index')->with('error', 'Access denied');
            }

            $audit = DB::table('audits as a')
                ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
                ->join('countries as c', 'l.country_id', '=', 'c.id')
                ->where('a.id', $auditId)
                ->select('a.*', 'l.name as lab_name', 'l.lab_number', 'c.name as country_name', 'l.country_id')
                ->first();

            if (! $audit) {
                return redirect()->route('findings.index')->with('error', 'Audit not found');
            }

            $sections = DB::table('slipta_sections')->orderBy('code')->get();
            if ($sections->isEmpty()) {
                throw new Exception('CRITICAL: No SLIPTA sections found');
            }

            $findings = DB::table('audit_findings as af')
                ->leftJoin('slipta_sections as s', 'af.section_id', '=', 's.id')
                ->leftJoin('slipta_questions as q', 'af.question_id', '=', 'q.id')
                ->where('af.audit_id', $auditId)
                ->select('af.*', 's.code as section_code', 's.title as section_title', 'q.q_code', 'q.text as question_text')
                ->orderBy('s.code')
                ->orderByRaw('CAST(SUBSTRING_INDEX(q.q_code,".",1) AS UNSIGNED)')
                ->get()
                ->groupBy('section_id');

            $diagnostics = $this->diagnoseAudit($auditId);
            $closure     = $this->closureGate($auditId, $diagnostics);
            $score       = $this->scoreAudit($auditId);

            return view('findings.index', [
                'audit'             => $audit,
                'sections'          => $sections,
                'findings'          => $findings,
                'diagnostics'       => $diagnostics,
                'score'             => $score,
                'closureValidation' => $closure,
                'userContext'       => $ctx,
                'readOnly'          => ! $this->canEditAudit($ctx, $auditId),
            ]);

        } catch (Exception $e) {
            Log::error('Findings show failed', ['audit_id' => $auditId ?? 'unknown', 'error' => $e->getMessage()]);
            return redirect()->route('findings.index')->with('error', 'Failed to load findings: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id'    => 'required|integer|min:1|exists:audits,id',
            'section_id'  => 'nullable|integer|min:1|exists:slipta_sections,id',
            'question_id' => 'nullable|integer|min:1|exists:slipta_questions,id',
            'title'       => 'required|string|max:191|min:5',
            'description' => 'required|string|max:5000|min:10',
            'severity'    => 'nullable|in:' . implode(',', self::VALID_SEVERITIES),
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $ctx     = $this->getUserContext();
            $auditId = (int) $request->audit_id;

            if (! $this->canEditAudit($ctx, $auditId)) {
                return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
            }

            $inProgress = $this->getScopedAudits($ctx)
                ->where('a.id', $auditId)
                ->where('a.status', 'in_progress')
                ->exists();

            if (! $inProgress) {
                return response()->json(['success' => false, 'error' => 'Audit is not in progress'], 403);
            }

            $severity = $request->severity;
            if (! $severity && $request->question_id) {
                $severity = $this->determineSeverity($auditId, (int) $request->question_id);
            }
            $severity = $severity ?: 'medium';

            $findingId = DB::table('audit_findings')->insertGetId([
                'audit_id'    => $auditId,
                'section_id'  => $request->section_id ? (int) $request->section_id : null,
                'question_id' => $request->question_id ? (int) $request->question_id : null,
                'title'       => trim($request->title),
                'description' => trim($request->description),
                'severity'    => $severity,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('audits')->where('id', $auditId)->update([
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            DB::commit();

            $finding = DB::table('audit_findings as af')
                ->leftJoin('slipta_sections as s', 'af.section_id', '=', 's.id')
                ->leftJoin('slipta_questions as q', 'af.question_id', '=', 'q.id')
                ->where('af.id', $findingId)
                ->select('af.*', 's.code as section_code', 's.title as section_title', 'q.q_code', 'q.text as question_text')
                ->first();

            return response()->json(['success' => true, 'message' => 'Finding created', 'finding' => $finding]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Finding creation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $findingId)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:191|min:5',
            'description' => 'required|string|max:5000|min:10',
            'severity'    => 'nullable|in:' . implode(',', self::VALID_SEVERITIES),
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $findingId = (int) $findingId;
            if ($findingId <= 0) {
                return response()->json(['success' => false, 'error' => 'Invalid finding ID'], 400);
            }

            $finding = DB::table('audit_findings')->where('id', $findingId)->first();
            if (! $finding) {
                return response()->json(['success' => false, 'error' => 'Finding not found'], 404);
            }

            $ctx = $this->getUserContext();
            if (! $this->canEditAudit($ctx, (int) $finding->audit_id)) {
                return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
            }

            DB::table('audit_findings')->where('id', $findingId)->update([
                'title'       => trim($request->title),
                'description' => trim($request->description),
                'severity'    => $request->severity ?: 'medium',
                'updated_at'  => now(),
            ]);

            DB::table('audits')->where('id', (int) $finding->audit_id)->update([
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            DB::commit();

            $updated = DB::table('audit_findings as af')
                ->leftJoin('slipta_sections as s', 'af.section_id', '=', 's.id')
                ->leftJoin('slipta_questions as q', 'af.question_id', '=', 'q.id')
                ->where('af.id', $findingId)
                ->select('af.*', 's.code as section_code', 's.title as section_title', 'q.q_code', 'q.text as question_text')
                ->first();

            return response()->json(['success' => true, 'message' => 'Finding updated', 'finding' => $updated]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Finding update failed', ['finding_id' => $findingId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($findingId)
    {
        DB::beginTransaction();
        try {
            $findingId = (int) $findingId;
            if ($findingId <= 0) {
                return response()->json(['success' => false, 'error' => 'Invalid finding ID'], 400);
            }

            $finding = DB::table('audit_findings')->where('id', $findingId)->first();
            if (! $finding) {
                return response()->json(['success' => false, 'error' => 'Finding not found'], 404);
            }

            $ctx = $this->getUserContext();
            if (! $this->canEditAudit($ctx, (int) $finding->audit_id)) {
                return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
            }

            $hasPlans = DB::table('action_plans')->where('finding_id', $findingId)->exists();
            if ($hasPlans) {
                return response()->json(['success' => false, 'error' => 'Cannot delete: Action plans exist'], 400);
            }

            DB::table('audit_findings')->where('id', $findingId)->delete();

            DB::table('audits')->where('id', (int) $finding->audit_id)->update([
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Finding deleted']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Finding deletion failed', ['finding_id' => $findingId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // NEW: AUTOMATIC SYNCHRONIZATION ENGINE
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Automatically sync findings and action plans when responses change
     * Triggered after response updates
     */
    public function autoSyncFindings(Request $request, $auditId)
    {
        DB::beginTransaction();
        try {
            $auditId = (int) $auditId;
            if (! DB::table('audits')->where('id', $auditId)->exists()) {
                return response()->json(['success' => false, 'error' => 'Audit not found'], 404);
            }

            $ctx = $this->getUserContext();
            if (! $this->canEditAudit($ctx, $auditId)) {
                return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
            }

            $syncReport = [
                'findings_created'     => 0,
                'findings_removed'     => 0,
                'action_plans_created' => 0,
                'action_plans_updated' => 0,
            ];

            // STEP 1: Create findings for new P/N responses
            $newNC = DB::table('audit_responses as r')
                ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
                ->leftJoin('audit_findings as f', function ($join) use ($auditId) {
                    $join->on('r.question_id', '=', 'f.question_id')
                        ->where('f.audit_id', $auditId);
                })
                ->where('r.audit_id', $auditId)
                ->whereIn('r.answer', ['P', 'N'])
                ->whereNull('f.id')
                ->select('r.question_id', 'r.answer', 'r.comment', 'q.section_id', 'q.q_code', 'q.text',
                    DB::raw("CASE WHEN q.weight='3' THEN 3 WHEN q.weight='2' THEN 2 ELSE 0 END as weight_value"))
                ->get();

            foreach ($newNC as $nc) {
                $severity = $nc->answer === 'N' ? 'high' : ($nc->weight_value == 3 ? 'medium' : 'low');

                $findingId = DB::table('audit_findings')->insertGetId([
                    'audit_id'    => $auditId,
                    'section_id'  => $nc->section_id,
                    'question_id' => $nc->question_id,
                    'title'       => "[{$nc->q_code}] Auto-created: " . ($nc->answer === 'N' ? 'Not Implemented' : 'Partially Implemented'),
                    'description' => trim($nc->comment ?: 'Non-conformance detected. ' . substr($nc->text, 0, 200)),
                    'severity'    => $severity,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $syncReport['findings_created']++;

                // Auto-create action plan
                if (! DB::table('action_plans')->where('finding_id', $findingId)->exists()) {
                    DB::table('action_plans')->insert([
                        'audit_id'            => $auditId,
                        'finding_id'          => $findingId,
                        'section_id'          => $nc->section_id,
                        'question_id'         => $nc->question_id,
                        'type'                => 'finding',
                        'recommendation'      => 'CAPA required: ' . $nc->q_code,
                        'responsible_user_id' => auth()->id(),
                        'due_date'            => now()->addDays(45)->format('Y-m-d'),
                        'status'              => 'open',
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                    $syncReport['action_plans_created']++;
                }
            }

            // STEP 2: Remove findings for responses changed from P/N to Y
            $resolvedNC = DB::table('audit_findings as f')
                ->join('audit_responses as r', function ($join) use ($auditId) {
                    $join->on('f.question_id', '=', 'r.question_id')
                        ->where('r.audit_id', $auditId);
                })
                ->where('f.audit_id', $auditId)
                ->where('r.answer', 'Y')
                ->whereNotNull('f.question_id')
                ->pluck('f.id');

            if ($resolvedNC->isNotEmpty()) {
                // Check for linked action plans before deletion
                $safeToDelete = [];
                foreach ($resolvedNC as $fid) {
                    $hasActivePlans = DB::table('action_plans')
                        ->where('finding_id', $fid)
                        ->whereIn('status', ['open', 'in_progress'])
                        ->exists();

                    if (! $hasActivePlans) {
                        $safeToDelete[] = $fid;
                    }
                }

                if (! empty($safeToDelete)) {
                    DB::table('audit_findings')->whereIn('id', $safeToDelete)->delete();
                    $syncReport['findings_removed'] = count($safeToDelete);
                }
            }

            // STEP 3: Update action plan statuses based on resolution
            $actionPlans = DB::table('action_plans as ap')
                ->leftJoin('audit_responses as r', function ($join) use ($auditId) {
                    $join->on('ap.question_id', '=', 'r.question_id')
                        ->where('r.audit_id', $auditId);
                })
                ->where('ap.audit_id', $auditId)
                ->whereIn('ap.status', ['open', 'in_progress'])
                ->where('r.answer', 'Y')
                ->select('ap.id')
                ->get();

            if ($actionPlans->isNotEmpty()) {
                DB::table('action_plans')
                    ->whereIn('id', $actionPlans->pluck('id'))
                    ->update([
                        'status'           => 'closed',
                        'resolution_notes' => 'Auto-closed: Response changed to Y (fully compliant)',
                        'closed_at'        => now(),
                        'updated_at'       => now(),
                    ]);
                $syncReport['action_plans_updated'] = $actionPlans->count();
            }

            DB::table('audits')->where('id', $auditId)->update([
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Findings and action plans synchronized',
                'report'  => $syncReport,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Auto-sync failed', ['audit_id' => $auditId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // NEW: AUDIT STATUS MANAGEMENT (OPEN → CLOSE → REOPEN)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Close an audit (with validation)
     */
    public function closeAudit(Request $request, $auditId)
    {
        DB::beginTransaction();
        try {
            $auditId = (int) $auditId;

            $audit = DB::table('audits')->where('id', $auditId)->first();
            if (! $audit) {
                return response()->json(['success' => false, 'error' => 'Audit not found'], 404);
            }

            $ctx = $this->getUserContext();
            if (! $this->canEditAudit($ctx, $auditId)) {
                return response()->json(['success' => false, 'error' => 'Forbidden: You cannot close this audit'], 403);
            }

            if ($audit->status === 'completed') {
                return response()->json(['success' => false, 'error' => 'Audit is already closed'], 400);
            }

            if ($audit->status !== 'in_progress') {
                return response()->json(['success' => false, 'error' => 'Only in-progress audits can be closed'], 400);
            }

            // Validate closure readiness
            $diagnostics = $this->diagnoseAudit($auditId);
            $closure     = $this->closureGate($auditId, $diagnostics);

            if (! $closure['can_close']) {
                return response()->json([
                    'success'       => false,
                    'error'         => 'Audit cannot be closed due to validation errors',
                    'blockers'      => $closure['blockers'],
                    'blocker_count' => $closure['blocker_count'],
                ], 422);
            }

            // Calculate final score
            $finalScore = $this->scoreAudit($auditId);

            // Close audit
            DB::table('audits')->where('id', $auditId)->update([
                'status'     => 'completed',
                'closed_on'  => now()->format('Y-m-d'),
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            // Log closure event
            Log::info('Audit closed', [
                'audit_id'       => $auditId,
                'closed_by'      => auth()->id(),
                'final_score'    => $finalScore['percentage'],
                'stars'          => $finalScore['stars'],
                'evidence_flags' => $closure['evidence_flag_count'] ?? 0,
            ]);

            DB::commit();

            return response()->json([
                'success'           => true,
                'message'           => 'Audit closed successfully',
                'final_score'       => $finalScore,
                'evidence_warnings' => $closure['evidence_flag_message'] ?? null,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Audit closure failed', ['audit_id' => $auditId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reopen a closed audit (with justification)
     */
    public function reopenAudit(Request $request, $auditId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:20|max:1000',
        ], [
            'reason.required' => 'Justification is required to reopen an audit',
            'reason.min'      => 'Justification must be at least 20 characters',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $auditId = (int) $auditId;

            $audit = DB::table('audits')->where('id', $auditId)->first();
            if (! $audit) {
                return response()->json(['success' => false, 'error' => 'Audit not found'], 404);
            }

            $ctx = $this->getUserContext();

            // Only system_admin and project_coordinator can reopen
            if (! $ctx['has_global_view']) {
                return response()->json(['success' => false, 'error' => 'Forbidden: Only system administrators can reopen audits'], 403);
            }

            if ($audit->status !== 'completed') {
                return response()->json(['success' => false, 'error' => 'Only completed audits can be reopened'], 400);
            }

            // Reopen audit
            DB::table('audits')->where('id', $auditId)->update([
                'status'     => 'in_progress',
                'closed_on'  => null,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            // Log reopening with justification
            Log::warning('Audit reopened', [
                'audit_id'    => $auditId,
                'reopened_by' => auth()->id(),
                'reason'      => $request->reason,
                'timestamp'   => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Audit reopened successfully. All findings and responses can now be edited.',
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Audit reopening failed', ['audit_id' => $auditId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // EXISTING METHOD: ACTION PLAN GENERATION (ALREADY FIXED)
    // ══════════════════════════════════════════════════════════════════════

    public function generateActionPlans(Request $request, $auditId)
    {
        DB::beginTransaction();
        try {
            $auditId = (int) $auditId;
            if (! DB::table('audits')->where('id', $auditId)->exists()) {
                return response()->json(['success' => false, 'error' => 'Audit not found'], 404);
            }

            $ctx = $this->getUserContext();
            if (! $this->canEditAudit($ctx, $auditId)) {
                return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
            }

            $ncResponses = DB::table('audit_responses as r')
                ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
                ->leftJoin('audit_findings as f', function ($join) use ($auditId) {
                    $join->on('f.question_id', '=', 'r.question_id')
                        ->where('f.audit_id', $auditId);
                })
                ->leftJoin('action_plans as ap', 'ap.finding_id', '=', 'f.id')
                ->where('r.audit_id', $auditId)
                ->whereIn('r.answer', ['P', 'N'])
                ->whereNull('ap.id')
                ->select([
                    'r.question_id', 'r.answer', 'r.comment', 'q.section_id', 'q.q_code', 'q.text as question_text',
                    DB::raw("CASE WHEN q.weight='3' THEN 3 WHEN q.weight='2' THEN 2 ELSE 0 END as weight_value"),
                ])
                ->get();

            $createdCount = 0;

            foreach ($ncResponses as $row) {
                $findingId = DB::table('audit_findings')
                    ->where('audit_id', $auditId)
                    ->where('question_id', (int) $row->question_id)
                    ->value('id');

                if (! $findingId) {
                    $severity = $row->answer === 'N' ? 'high' : ($row->weight_value == 3 ? 'medium' : 'low');

                    $findingId = DB::table('audit_findings')->insertGetId([
                        'audit_id'    => $auditId,
                        'section_id'  => (int) $row->section_id,
                        'question_id' => (int) $row->question_id,
                        'title'       => "[{$row->q_code}] NC: " . ($row->answer === 'N' ? 'Not Implemented' : 'Partially Implemented'),
                        'description' => trim($row->comment ?: 'Non-conformance. Q: ' . substr($row->question_text, 0, 200)),
                        'severity'    => $severity,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                    $createdCount++;
                }

                if (! DB::table('action_plans')->where('finding_id', $findingId)->exists()) {
                    DB::table('action_plans')->insert([
                        'audit_id'            => $auditId,
                        'finding_id'          => $findingId,
                        'section_id'          => (int) $row->section_id,
                        'question_id'         => (int) $row->question_id,
                        'type'                => 'finding',
                        'recommendation'      => 'CAPA: ' . $row->q_code . '. ' . trim($row->comment ?: 'Address NC'),
                        'responsible_user_id' => auth()->id(),
                        'due_date'            => now()->addDays(45)->format('Y-m-d'),
                        'status'              => 'open',
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                    $createdCount++;
                }
            }

            DB::table('audits')->where('id', $auditId)->update([
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Generated {$createdCount} action plans",
                'created_count' => $createdCount,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Action plan generation failed', ['audit_id' => $auditId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // COMPREHENSIVE REPORT GENERATION (NOW FULLY IMPLEMENTED)
    // ══════════════════════════════════════════════════════════════════════

    public function downloadReport(Request $request, $auditId)
    {
        try {
            $this->validateSystemIntegrity();

            $auditId = (int) $auditId;
            $ctx     = $this->getUserContext();

            if (! $this->canAccessAudit($ctx, $auditId)) {
                return redirect()->route('findings.index')->with('error', 'Access denied');
            }

            // Load comprehensive data
            $audit = DB::table('audits as a')
                ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
                ->join('countries as c', 'l.country_id', '=', 'c.id')
                ->where('a.id', $auditId)
                ->select('a.*', 'l.name as lab_name', 'l.lab_number', 'l.lab_type', 'l.address', 'l.city', 'c.name as country_name')
                ->first();

            if (! $audit) {
                return redirect()->route('findings.index')->with('error', 'Audit not found');
            }

            $score       = $this->scoreAudit($auditId);
            $diagnostics = $this->diagnoseAudit($auditId);
            $closure     = $this->closureGate($auditId, $diagnostics);

            // Section-by-section breakdown
            $sectionBreakdown = $this->getSectionBreakdown($auditId);

            // Findings with action plans
            $findings = DB::table('audit_findings as f')
                ->leftJoin('slipta_sections as s', 'f.section_id', '=', 's.id')
                ->leftJoin('slipta_questions as q', 'f.question_id', '=', 'q.id')
                ->leftJoin('action_plans as ap', 'ap.finding_id', '=', 'f.id')
                ->where('f.audit_id', $auditId)
                ->select([
                    'f.*', 's.code as section_code', 's.title as section_title',
                    'q.q_code', 'q.text as question_text',
                    'ap.recommendation', 'ap.status as plan_status', 'ap.due_date', 'ap.resolution_notes',
                ])
                ->orderBy('s.code')
                ->get()
                ->groupBy('section_code');

            // Evidence status
            $evidenceStats = $this->getEvidenceStatistics($auditId);

            // Team members
            $teamMembers = DB::table('audit_team_members as atm')
                ->join('users as u', 'atm.user_id', '=', 'u.id')
                ->where('atm.audit_id', $auditId)
                ->select('u.name', 'u.email', 'atm.role')
                ->get();

            // Previous audit comparison (if exists)
            $comparison = null;
            if ($audit->previous_audit_id) {
                $comparison = $this->compareWithPreviousAudit($auditId, $audit->previous_audit_id);
            }

            // Pass everything to view
            $html = view('findings.report', compact(
                'audit', 'score', 'diagnostics', 'closure', 'sectionBreakdown',
                'findings', 'evidenceStats', 'teamMembers', 'comparison'
            ))->render();

            // Generate PDF if available
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                    ->setPaper('A4', 'portrait')
                    ->setOption('enable-local-file-access', true);

                return $pdf->download('SLIPTA_Findings_Report_' . $audit->lab_number . '_' . now()->format('Y-m-d') . '.pdf');
            }

            return response($html, 200, [
                'Content-Type'        => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="SLIPTA_Report_' . $audit->lab_number . '.html"',
            ]);

        } catch (Exception $e) {
            Log::error('Report generation failed', ['audit_id' => $auditId, 'error' => $e->getMessage()]);
            return redirect()->route('findings.index')->with('error', 'Failed to generate report');
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPER METHODS FOR COMPREHENSIVE REPORTING
    // ══════════════════════════════════════════════════════════════════════

    protected function getSectionBreakdown(int $auditId): array
    {
        $sections = DB::table('slipta_sections as s')
            ->leftJoin('slipta_questions as q', 's.id', '=', 'q.section_id')
            ->leftJoin('audit_responses as r', function ($join) use ($auditId) {
                $join->on('q.id', '=', 'r.question_id')
                    ->where('r.audit_id', $auditId);
            })
            ->select([
                's.id', 's.code', 's.title',
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
            $maxPoints  = self::SECTION_MAX_POINTS[(int) $section->code] ?? 0;
            $earned     = (int) $section->y_points + (int) $section->p_points;
            $adjusted   = max(1, $maxPoints - (int) $section->na_points);
            $percentage = round(($earned / $adjusted) * 100, 2);

            $breakdown[] = [
                'code'                 => $section->code,
                'title'                => $section->title,
                'total_questions'      => $section->total_questions,
                'answered'             => $section->answered_questions,
                'yes'                  => $section->yes_count,
                'partial'              => $section->partial_count,
                'no'                   => $section->no_count,
                'na'                   => $section->na_count,
                'max_points'           => $maxPoints,
                'earned_points'        => $earned,
                'adjusted_denominator' => $adjusted,
                'percentage'           => $percentage,
            ];
        }

        return $breakdown;
    }

    protected function getEvidenceStatistics(int $auditId): array
    {
        $total = DB::table('audit_responses')
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
            'total_nc'              => $total,
            'with_evidence'         => $withEvidence,
            'without_evidence'      => $total - $withEvidence,
            'percentage_documented' => $total > 0 ? round(($withEvidence / $total) * 100, 1) : 0,
        ];
    }

    protected function compareWithPreviousAudit(int $currentAuditId, int $previousAuditId): ?array
    {
        try {
            $current  = $this->scoreAudit($currentAuditId);
            $previous = $this->scoreAudit($previousAuditId);

            return [
                'previous_percentage' => $previous['percentage'],
                'previous_stars'      => $previous['stars'],
                'current_percentage'  => $current['percentage'],
                'current_stars'       => $current['stars'],
                'percentage_change'   => round($current['percentage'] - $previous['percentage'], 2),
                'star_change'         => $current['stars'] - $previous['stars'],
                'trend'               => $current['percentage'] >= $previous['percentage'] ? 'improving' : 'declining',
            ];
        } catch (Exception $e) {
            Log::warning('Previous audit comparison failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // EXISTING CORE METHODS (DIAGNOSTICS, SCORING, RBAC, ETC.)
    // ══════════════════════════════════════════════════════════════════════

    protected function diagnoseAudit(int $auditId): array
    {
        $gaps = [
            'unanswered'               => collect(),
            'nc_without_finding'       => collect(),
            'evidence_missing'         => collect(),
            'na_without_justification' => collect(),
            'missing_comments'         => collect(),
            'composite_violations'     => collect(),
            'contradictions'           => collect(),
        ];

        $answeredCount = (int) DB::table('audit_responses')->where('audit_id', $auditId)->count();

        if ($answeredCount < self::EXACT_QUESTION_COUNT) {
            $gaps['unanswered'] = DB::table('slipta_questions as q')
                ->leftJoin('audit_responses as r', function ($join) use ($auditId) {
                    $join->on('q.id', '=', 'r.question_id')->where('r.audit_id', $auditId);
                })
                ->join('slipta_sections as s', 'q.section_id', '=', 's.id')
                ->whereNull('r.id')
                ->select('q.id', 'q.q_code', 'q.text', 's.code as section_code', 's.title as section_title', 's.id as section_id')
                ->orderBy('s.code')
                ->get();
        }

        $gaps['nc_without_finding'] = DB::table('audit_responses as r')
            ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
            ->join('slipta_sections as s', 'q.section_id', '=', 's.id')
            ->leftJoin('audit_findings as f', function ($join) use ($auditId) {
                $join->on('r.question_id', '=', 'f.question_id')->where('f.audit_id', $auditId);
            })
            ->where('r.audit_id', $auditId)
            ->whereIn('r.answer', ['P', 'N'])
            ->whereNull('f.id')
            ->select('q.id', 'q.q_code', 'q.text', 'r.answer', 'r.comment', 's.code as section_code', 's.title as section_title')
            ->get();

        $gaps['evidence_missing'] = DB::table('audit_responses as r')
            ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
            ->join('slipta_sections as s', 'q.section_id', '=', 's.id')
            ->leftJoin('audit_evidence as e', function ($join) use ($auditId) {
                $join->on('r.question_id', '=', 'e.question_id')->where('e.audit_id', $auditId);
            })
            ->where('r.audit_id', $auditId)
            ->whereIn('r.answer', ['P', 'N'])
            ->whereNull('e.id')
            ->select('q.id', 'q.q_code', 'q.text', 'r.answer', 's.code as section_code')
            ->get();

        $gaps['na_without_justification'] = DB::table('audit_responses as r')
            ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
            ->join('slipta_sections as s', 'q.section_id', '=', 's.id')
            ->where('r.audit_id', $auditId)
            ->where('r.answer', 'NA')
            ->where(function ($query) {
                $query->whereNull('r.na_justification')->orWhere('r.na_justification', '');
            })
            ->select('q.id', 'q.q_code', 'q.text', 's.code as section_code')
            ->get();

        $gaps['missing_comments'] = DB::table('audit_responses as r')
            ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
            ->join('slipta_sections as s', 'q.section_id', '=', 's.id')
            ->where('r.audit_id', $auditId)
            ->whereIn('r.answer', ['P', 'N', 'NA'])
            ->where(function ($query) {
                $query->whereNull('r.comment')->orWhere('r.comment', '');
            })
            ->select('q.id', 'q.q_code', 'q.text', 'r.answer', 's.code as section_code')
            ->get();

        $compositeQuestions = DB::table('slipta_questions as q')
            ->join('audit_responses as r', 'q.id', '=', 'r.question_id')
            ->join('slipta_sections as s', 'q.section_id', '=', 's.id')
            ->where('r.audit_id', $auditId)
            ->where('q.requires_all_subs_for_yes', 1)
            ->where('r.answer', 'Y')
            ->select('q.id', 'q.q_code', 'q.text', 's.code as section_code')
            ->get();

        $gaps['composite_violations'] = $compositeQuestions->filter(function ($question) use ($auditId) {
            $subIds = DB::table('slipta_subquestions')->where('question_id', $question->id)->pluck('id');
            if ($subIds->isEmpty()) {
                return false;
            }

            $subResponses = DB::table('audit_subquestion_responses')
                ->where('audit_id', $auditId)->whereIn('subquestion_id', $subIds)->get();
            if ($subResponses->count() !== $subIds->count()) {
                return true;
            }

            return $subResponses->first(fn($r) => ! in_array($r->answer, ['Y', 'NA'])) !== null;
        })->values();

        return $gaps;
    }

    protected function closureGate(int $auditId, array $diagnostics): array
    {
        $blockers = [];

        if ($diagnostics['unanswered']->count() > 0) {
            $blockers[] = [
                'type'     => 'unanswered',
                'count'    => $diagnostics['unanswered']->count(),
                'message'  => $diagnostics['unanswered']->count() . ' questions unanswered',
                'severity' => 'critical',
            ];
        }

        if ($diagnostics['nc_without_finding']->count() > 0) {
            $blockers[] = [
                'type'     => 'undocumented_nc',
                'count'    => $diagnostics['nc_without_finding']->count(),
                'message'  => $diagnostics['nc_without_finding']->count() . ' NC lack findings',
                'severity' => 'high',
            ];
        }

        if ($diagnostics['na_without_justification']->count() > 0) {
            $blockers[] = [
                'type'     => 'na_justification',
                'count'    => $diagnostics['na_without_justification']->count(),
                'message'  => $diagnostics['na_without_justification']->count() . ' NA lack justification',
                'severity' => 'critical',
            ];
        }

        if ($diagnostics['missing_comments']->count() > 0) {
            $blockers[] = [
                'type'     => 'missing_comments',
                'count'    => $diagnostics['missing_comments']->count(),
                'message'  => $diagnostics['missing_comments']->count() . ' P/N/NA lack comments',
                'severity' => 'critical',
            ];
        }

        if ($diagnostics['composite_violations']->count() > 0) {
            $blockers[] = [
                'type'     => 'composite_violation',
                'count'    => $diagnostics['composite_violations']->count(),
                'message'  => $diagnostics['composite_violations']->count() . ' composite rule violations',
                'severity' => 'critical',
            ];
        }

        return [
            'can_close'             => empty($blockers),
            'blockers'              => $blockers,
            'blocker_count'         => count($blockers),
            'evidence_flag_count'   => $diagnostics['evidence_missing']->count(),
            'evidence_flag_message' => $diagnostics['evidence_missing']->count() > 0
                ? "⚠️ WARNING: {$diagnostics['evidence_missing']->count()} NC lack evidence. While not blocking closure, documentation strengthens audit credibility."
                : null,
        ];
    }

    protected function scoreAudit(int $auditId): array
    {
        $responses = DB::table('audit_responses as r')
            ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
            ->where('r.audit_id', $auditId)
            ->select([
                'r.answer',
                DB::raw("CASE WHEN q.weight='3' THEN 3 WHEN q.weight='2' THEN 2 ELSE 0 END as weight_value"),
            ])
            ->get();

        $totalEarned   = 0;
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
        $percentage          = round(($totalEarned / $adjustedDenominator) * 100, 2);

        $starLevel = 0;
        foreach (self::STAR_BANDS as $stars => $minPercentage) {
            if ($percentage >= $minPercentage) {
                $starLevel = $stars;
                break;
            }
        }

        return [
            'earned'               => $totalEarned,
            'total_possible'       => self::EXACT_TOTAL_POINTS,
            'na_points_excluded'   => $totalNaPoints,
            'adjusted_denominator' => $adjustedDenominator,
            'percentage'           => $percentage,
            'stars'                => $starLevel,
            'star_label'           => $this->getStarLabel($starLevel),
        ];
    }

    protected function determineSeverity(int $auditId, int $questionId): string
    {
        $response = DB::table('audit_responses as r')
            ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
            ->where('r.audit_id', $auditId)
            ->where('r.question_id', $questionId)
            ->select(['r.answer', DB::raw("CASE WHEN q.weight='3' THEN 3 WHEN q.weight='2' THEN 2 ELSE 0 END as weight_value")])
            ->first();

        if (! $response) {
            return 'medium';
        }

        if ($response->answer === 'N') {
            return 'high';
        }

        if ($response->answer === 'P') {
            return ((int) $response->weight_value === 3) ? 'medium' : 'low';
        }

        return 'medium';
    }

    protected function validateSystemIntegrity(): void
    {
        $questionCount = (int) DB::table('slipta_questions')->count();
        if ($questionCount !== self::EXACT_QUESTION_COUNT) {
            throw new Exception("INTEGRITY: Expected 151 questions, found {$questionCount}");
        }

        $invalidWeights = (int) DB::table('slipta_questions')->whereNotIn('weight', self::VALID_WEIGHTS)->count();
        if ($invalidWeights > 0) {
            throw new Exception("INTEGRITY: {$invalidWeights} invalid weights");
        }

        $actualTotal = (int) DB::table('slipta_questions')
            ->selectRaw("COALESCE(SUM(CASE WHEN weight='3' THEN 3 WHEN weight='2' THEN 2 ELSE 0 END), 0) as total")
            ->value('total');

        if ($actualTotal !== self::EXACT_TOTAL_POINTS) {
            throw new Exception("INTEGRITY: Total points = {$actualTotal}, expected 367");
        }

        $sectionCount = (int) DB::table('slipta_sections')->count();
        if ($sectionCount !== self::EXACT_SECTION_COUNT) {
            throw new Exception("INTEGRITY: Expected 12 sections, found {$sectionCount}");
        }

        $sectionPoints = DB::table('slipta_sections as s')
            ->leftJoin('slipta_questions as q', 's.id', '=', 'q.section_id')
            ->select('s.code', DB::raw("SUM(CASE WHEN q.weight='3' THEN 3 WHEN q.weight='2' THEN 2 ELSE 0 END) as actual_points"))
            ->groupBy('s.id', 's.code')
            ->get();

        foreach ($sectionPoints as $section) {
            $expected = self::SECTION_MAX_POINTS[(int) $section->code] ?? 0;
            $actual   = (int) $section->actual_points;
            if ($actual !== $expected) {
                throw new Exception("INTEGRITY: Section {$section->code} has {$actual} points, expected {$expected}");
            }
        }
    }

    protected function getUserContext(): array
    {
        $user = auth()->user();
        if (! $user) {
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
            'user'             => $user,
            'roles'            => $roles,
            'role_names'       => $roleNames,
            'has_global_view'  => $roleNames->contains('system_admin') || $roleNames->contains('project_coordinator'),
            'country_ids'      => $roles->pluck('country_id')->filter()->unique()->values()->all(),
            'laboratory_ids'   => $roles->pluck('laboratory_id')->filter()->unique()->values()->all(),
            'is_country_coord' => $roleNames->contains('country_coordinator'),
            'is_lab_manager'   => $roleNames->contains('laboratory_manager') || $roleNames->contains('quality_officer'),
            'is_observer'      => $roleNames->contains('observer'),
        ];
    }

    protected function canAccessAudit(array $context, int $auditId): bool
    {
        $audit = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->select('a.id', 'a.laboratory_id', 'l.country_id')
            ->where('a.id', $auditId)
            ->first();

        if (! $audit) {
            return false;
        }

        if ($context['has_global_view']) {
            return true;
        }

        if ($context['is_country_coord'] && in_array($audit->country_id, $context['country_ids'], true)) {
            return true;
        }

        if ($context['is_lab_manager'] && in_array($audit->laboratory_id, $context['laboratory_ids'], true)) {
            return true;
        }

        $isTeamMember = DB::table('audit_team_members')
            ->where('audit_id', $auditId)
            ->where('user_id', $context['user']->id)
            ->exists();

        return $isTeamMember || in_array($audit->laboratory_id, $context['laboratory_ids'], true);
    }

    protected function canEditAudit(array $context, int $auditId): bool
    {
        if ($context['is_observer']) {
            return false;
        }

        if ($context['has_global_view'] || $context['is_country_coord']) {
            return true;
        }

        if ($context['is_lab_manager']) {
            return false;
        }

        return DB::table('audit_team_members')
            ->where('audit_id', $auditId)
            ->where('user_id', $context['user']->id)
            ->whereIn('role', ['lead', 'member'])
            ->exists();
    }

    protected function getScopedAudits(array $context)
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->join('countries as c', 'l.country_id', '=', 'c.id')
            ->leftJoin('audit_team_members as atm', function ($join) use ($context) {
                $join->on('atm.audit_id', '=', 'a.id')->where('atm.user_id', $context['user']->id);
            })
            ->select(
                'a.id', 'a.status', 'a.laboratory_id', 'a.opened_on', 'a.closed_on',
                'a.last_audit_date', 'a.prior_official_status', 'a.previous_audit_id',
                'a.created_by', 'a.updated_by', 'a.created_at', 'a.updated_at',
                'l.name as lab_name', 'l.lab_number', 'l.country_id', 'c.name as country_name'
            );

        if ($context['has_global_view']) {
            return $query;
        }

        if ($context['is_country_coord'] && ! empty($context['country_ids'])) {
            $query->whereIn('l.country_id', $context['country_ids']);
        } else {
            $query->where(function ($where) use ($context) {
                $where->whereNotNull('atm.user_id');
                if (! empty($context['laboratory_ids'])) {
                    $where->orWhereIn('a.laboratory_id', $context['laboratory_ids']);
                }
            });
        }

        return $query;
    }

    protected function getStarLabel(int $stars): string
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
