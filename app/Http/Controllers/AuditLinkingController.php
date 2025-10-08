<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * Audit Linking Controller - Ultra-Precise Scope-Based Implementation
 *
 * PURPOSE: Link current audits to their prior audits for progression tracking
 * SCOPE: Enforces user permissions at every operation
 * VALIDATION: Zero-tolerance for data integrity violations
 */
class AuditLinkingController extends Controller
{
    /**
     * Display audit linking interface with scope-filtered data
     */
    public function index()
    {
        try {
            $userContext = $this->getUserContext();

            // Get audits accessible to user for linking operations
            $currentAudits = $this->getAccessibleAudits($userContext);

            // Calculate stats for dashboard display
            $stats = $this->calculateLinkingStats($userContext);

            return view('audits.linking', [
                'currentAudits' => $currentAudits,
                'userContext' => $userContext,
                'stats' => $stats,
                'csrf' => csrf_token()
            ]);

        } catch (Exception $e) {
            Log::error('Audit linking page load failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'SYSTEM ERROR: Failed to load audit linking interface - ' . $e->getMessage());
        }
    }

    /**
     * Get linkable audits for a specific laboratory (API endpoint)
     */
    public function getLinkableAudits(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'laboratory_id' => 'required|integer|exists:laboratories,id',
            'current_audit_id' => 'nullable|integer|exists:audits,id'
        ], [
            'laboratory_id.required' => 'Laboratory ID is required',
            'laboratory_id.exists' => 'Laboratory does not exist',
            'current_audit_id.exists' => 'Current audit does not exist'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'VALIDATION ERROR: ' . $validator->errors()->first()
            ], 422);
        }

        try {
            $userContext = $this->getUserContext();

            // CRITICAL: Verify user has access to this laboratory
            if (!$this->hasLaboratoryAccess($userContext, $request->laboratory_id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'AUTHORIZATION DENIED: You do not have access to this laboratory'
                ], 403);
            }

            $query = DB::table('audits as a')
                ->leftJoin('users as u', 'a.created_by', '=', 'u.id')
                ->where('a.laboratory_id', $request->laboratory_id)
                ->whereIn('a.status', ['completed', 'in_progress'])
                ->select(
                    'a.id',
                    'a.opened_on',
                    'a.closed_on',
                    'a.status',
                    'a.prior_official_status',
                    'u.name as created_by_name'
                );

            // Exclude current audit if specified
            if ($request->current_audit_id) {
                $query->where('a.id', '!=', $request->current_audit_id);
            }

            // Apply scope filtering
            $query = $this->applyScopeToQuery($query, $userContext, 'a');

            $audits = $query->orderBy('a.opened_on', 'desc')->get();

            // Calculate star levels for completed audits
            $audits = $audits->map(function($audit) {
                if ($audit->status === 'completed') {
                    $audit->calculated_star_level = $this->calculateAuditStarLevel($audit->id);
                } else {
                    $audit->calculated_star_level = null;
                }
                return $audit;
            });

            return response()->json([
                'success' => true,
                'audits' => $audits
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get linkable audits', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'laboratory_id' => $request->laboratory_id ?? null,
                'current_audit_id' => $request->current_audit_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'SYSTEM ERROR: Failed to load audits - ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Link audit to prior audit - CORE BUSINESS LOGIC
     */
    public function linkAudit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id' => 'required|integer|exists:audits,id',
            'previous_audit_id' => 'required|integer|exists:audits,id|different:audit_id'
        ], [
            'audit_id.required' => 'Current audit selection is required',
            'audit_id.exists' => 'Current audit does not exist',
            'previous_audit_id.required' => 'Previous audit selection is required',
            'previous_audit_id.exists' => 'Previous audit does not exist',
            'previous_audit_id.different' => 'Current and previous audit cannot be the same'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'error' => 'VALIDATION FAILED: ' . $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $userContext = $this->getUserContext();

            // SCOPE VERIFICATION #1: Current audit access
            if (!$this->canAccessAudit($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: You do not have access to the current audit (ID: ' . $request->audit_id . ')');
            }

            // SCOPE VERIFICATION #2: Previous audit access
            if (!$this->canAccessAudit($userContext, $request->previous_audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: You do not have access to the previous audit (ID: ' . $request->previous_audit_id . ')');
            }

            // PERMISSION CHECK: Edit authorization
            if (!$this->canEditAudit($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: You do not have permission to modify this audit');
            }

            // FETCH AUDIT RECORDS
            $currentAudit = DB::table('audits')->where('id', $request->audit_id)->first();
            $previousAudit = DB::table('audits')->where('id', $request->previous_audit_id)->first();

            if (!$currentAudit || !$previousAudit) {
                throw new Exception('DATABASE ERROR: One or both audits not found');
            }

            // BUSINESS RULE #1: Same laboratory requirement
            if ($currentAudit->laboratory_id !== $previousAudit->laboratory_id) {
                throw new Exception('BUSINESS RULE VIOLATION: Both audits must belong to the same laboratory (Current: Lab ' . $currentAudit->laboratory_id . ', Previous: Lab ' . $previousAudit->laboratory_id . ')');
            }

            // BUSINESS RULE #2: No circular references
            if ($this->wouldCreateCircularReference($request->audit_id, $request->previous_audit_id)) {
                throw new Exception('BUSINESS RULE VIOLATION: Cannot create circular audit references (this would create an infinite loop)');
            }

            // UPDATE AUDIT LINKAGE
            $updateResult = DB::table('audits')
                ->where('id', $request->audit_id)
                ->update([
                    'previous_audit_id' => $request->previous_audit_id,
                    'updated_by' => auth()->id(),
                    'updated_at' => now()
                ]);

            if ($updateResult === false) {
                throw new Exception('DATABASE ERROR: Failed to update audit linkage');
            }

            DB::commit();

            // Calculate star levels for progression analysis
            $currentStarLevel = $this->calculateAuditStarLevel($request->audit_id);
            $previousStarLevel = $this->calculateAuditStarLevel($request->previous_audit_id);

            $progression = 'maintained';
            if ($currentStarLevel > $previousStarLevel) {
                $progression = 'improved';
            } elseif ($currentStarLevel < $previousStarLevel) {
                $progression = 'declined';
            }

            Log::info('Audit linked to prior audit successfully', [
                'audit_id' => $request->audit_id,
                'previous_audit_id' => $request->previous_audit_id,
                'current_star_level' => $currentStarLevel,
                'previous_star_level' => $previousStarLevel,
                'progression' => $progression,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Audit successfully linked to previous audit',
                'data' => [
                    'audit_id' => $request->audit_id,
                    'previous_audit_id' => $request->previous_audit_id,
                    'current_star_level' => $currentStarLevel,
                    'previous_star_level' => $previousStarLevel,
                    'progression' => $progression
                ]
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Audit linking failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'audit_id' => $request->audit_id ?? null,
                'previous_audit_id' => $request->previous_audit_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Unlink audit from prior audit
     */
    public function unlinkAudit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id' => 'required|integer|exists:audits,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'VALIDATION FAILED: ' . $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $userContext = $this->getUserContext();

            // SCOPE AND PERMISSION CHECKS
            if (!$this->canAccessAudit($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: You do not have access to this audit');
            }

            if (!$this->canEditAudit($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: You do not have permission to modify this audit');
            }

            $updateResult = DB::table('audits')
                ->where('id', $request->audit_id)
                ->update([
                    'previous_audit_id' => null,
                    'updated_by' => auth()->id(),
                    'updated_at' => now()
                ]);

            if ($updateResult === false) {
                throw new Exception('DATABASE ERROR: Failed to unlink audit');
            }

            DB::commit();

            Log::info('Audit unlinked from prior audit', [
                'audit_id' => $request->audit_id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Audit successfully unlinked from previous audit'
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Audit unlinking failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'audit_id' => $request->audit_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // ========================================
    // SCOPE ENFORCEMENT METHODS
    // ========================================

    /**
     * Get user context with roles and scope
     */
    protected function getUserContext(): array
    {
        $user = auth()->user();

        if (!$user) {
            throw new Exception('AUTHENTICATION ERROR: User not authenticated');
        }

        $roles = DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $user->id)
            ->where('ur.is_active', 1)
            ->select('r.name', 'ur.country_id', 'ur.laboratory_id')
            ->get();

        $isSystemAdmin = $roles->contains('name', 'system_admin');
        $isProjectCoordinator = $roles->contains('name', 'project_coordinator');

        return [
            'user' => $user,
            'roles' => $roles,
            'is_system_admin' => $isSystemAdmin,
            'is_project_coordinator' => $isProjectCoordinator,
            'is_country_coordinator' => $roles->contains('name', 'country_coordinator'),
            'is_lead_auditor' => $roles->contains('name', 'lead_auditor'),
            'is_auditor' => $roles->contains('name', 'auditor'),
            'has_global_access' => $isSystemAdmin || $isProjectCoordinator,
            'country_ids' => $roles->pluck('country_id')->filter()->unique()->values()->all(),
            'laboratory_ids' => $roles->pluck('laboratory_id')->filter()->unique()->values()->all()
        ];
    }

    /**
     * Apply scope filtering to query
     */
    protected function applyScopeToQuery($query, array $userContext, string $auditTableAlias = 'a')
    {
        // Global access sees everything
        if ($userContext['has_global_access']) {
            return $query;
        }

        // Country coordinators see only their country's data
        if ($userContext['is_country_coordinator']) {
            $query->join('laboratories as l_scope', "{$auditTableAlias}.laboratory_id", '=', 'l_scope.id')
                ->whereIn('l_scope.country_id', $userContext['country_ids']);
            return $query;
        }

        // Lead auditors and auditors see assigned audits + their lab's audits
        if ($userContext['is_lead_auditor'] || $userContext['is_auditor']) {
            $query->where(function($q) use ($userContext, $auditTableAlias) {
                // Their laboratory's audits
                if (!empty($userContext['laboratory_ids'])) {
                    $q->whereIn("{$auditTableAlias}.laboratory_id", $userContext['laboratory_ids']);
                }
                // OR audits they're assigned to
                $q->orWhereExists(function($subq) use ($userContext, $auditTableAlias) {
                    $subq->from('audit_team_members')
                        ->whereColumn('audit_team_members.audit_id', '=', "{$auditTableAlias}.id")
                        ->where('audit_team_members.user_id', $userContext['user']->id);
                });
            });
            return $query;
        }

        // Default: only their laboratory's data
        if (!empty($userContext['laboratory_ids'])) {
            $query->whereIn("{$auditTableAlias}.laboratory_id", $userContext['laboratory_ids']);
        } else {
            // No scope = no access
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Check if user can access audit
     */
    protected function canAccessAudit(array $userContext, int $auditId): bool
    {
        if ($userContext['has_global_access']) {
            return true;
        }

        $audit = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.id', $auditId)
            ->select('a.id', 'a.laboratory_id', 'l.country_id')
            ->first();

        if (!$audit) {
            return false;
        }

        if ($userContext['is_country_coordinator']) {
            return in_array($audit->country_id, $userContext['country_ids']);
        }

        if ($userContext['is_lead_auditor'] || $userContext['is_auditor']) {
            $isTeamMember = DB::table('audit_team_members')
                ->where('audit_id', $auditId)
                ->where('user_id', $userContext['user']->id)
                ->exists();

            return $isTeamMember || in_array($audit->laboratory_id, $userContext['laboratory_ids']);
        }

        return in_array($audit->laboratory_id, $userContext['laboratory_ids']);
    }

    /**
     * Check if user can edit audit
     */
    protected function canEditAudit(array $userContext, int $auditId): bool
    {
        if ($userContext['has_global_access']) {
            return true;
        }

        $audit = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.id', $auditId)
            ->select('a.id', 'a.laboratory_id', 'l.country_id')
            ->first();

        if (!$audit) {
            return false;
        }

        if ($userContext['is_country_coordinator']) {
            return in_array($audit->country_id, $userContext['country_ids']);
        }

        if ($userContext['is_lead_auditor']) {
            $isLead = DB::table('audit_team_members')
                ->where('audit_id', $auditId)
                ->where('user_id', $userContext['user']->id)
                ->where('role', 'lead')
                ->exists();

            return $isLead;
        }

        return false;
    }

    /**
     * Check if user has laboratory access
     */
    protected function hasLaboratoryAccess(array $userContext, int $laboratoryId): bool
    {
        if ($userContext['has_global_access']) {
            return true;
        }

        $laboratory = DB::table('laboratories')->where('id', $laboratoryId)->first();
        if (!$laboratory) {
            return false;
        }

        if ($userContext['is_country_coordinator']) {
            return in_array($laboratory->country_id, $userContext['country_ids']);
        }

        return in_array($laboratoryId, $userContext['laboratory_ids']);
    }

    /**
     * Check for circular reference - CRITICAL BUSINESS RULE
     */
    protected function wouldCreateCircularReference(int $auditId, int $previousAuditId, int $depth = 0): bool
    {
        if ($depth > 100) {
            return true; // Safety limit
        }

        $previousAudit = DB::table('audits')->where('id', $previousAuditId)->first();

        if (!$previousAudit) {
            return false;
        }

        if ($previousAudit->previous_audit_id === $auditId) {
            return true; // Direct circular reference
        }

        if ($previousAudit->previous_audit_id) {
            return $this->wouldCreateCircularReference($auditId, $previousAudit->previous_audit_id, $depth + 1);
        }

        return false;
    }

    // ========================================
    // DATA RETRIEVAL METHODS
    // ========================================

    /**
     * Get audits accessible to user
     */
    protected function getAccessibleAudits(array $userContext)
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->join('countries as c', 'l.country_id', '=', 'c.id')
            ->leftJoin('audits as prev', 'a.previous_audit_id', '=', 'prev.id')
            ->select(
                'a.id',
                'a.laboratory_id',
                'a.opened_on',
                'a.closed_on',
                'a.status',
                'a.previous_audit_id',
                'l.name as laboratory_name',
                'c.name as country_name',
                'prev.opened_on as previous_audit_date'
            );

        $query = $this->applyScopeToQuery($query, $userContext, 'a');

        return $query->orderBy('a.opened_on', 'desc')->get();
    }

    /**
     * Calculate linking statistics
     */
    protected function calculateLinkingStats(array $userContext): array
    {
        $query = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $query = $this->applyScopeToQuery($query, $userContext, 'a');

        $totalAudits = (clone $query)->count();
        $linkedAudits = (clone $query)->whereNotNull('a.previous_audit_id')->count();
        $unlinkedAudits = $totalAudits - $linkedAudits;

        // Calculate progression for linked audits
        $progressionData = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->whereNotNull('a.previous_audit_id')
            ->where('a.status', 'completed')
            ->select('a.id', 'a.previous_audit_id');

        $progressionData = $this->applyScopeToQuery($progressionData, $userContext, 'a');
        $progressionData = $progressionData->get();

        $improved = 0;
        $declined = 0;
        $maintained = 0;

        foreach ($progressionData as $audit) {
            $currentStar = $this->calculateAuditStarLevel($audit->id);
            $previousStar = $this->calculateAuditStarLevel($audit->previous_audit_id);

            if ($currentStar > $previousStar) {
                $improved++;
            } elseif ($currentStar < $previousStar) {
                $declined++;
            } else {
                $maintained++;
            }
        }

        return [
            'total_audits' => $totalAudits,
            'linked_audits' => $linkedAudits,
            'unlinked_audits' => $unlinkedAudits,
            'improved' => $improved,
            'declined' => $declined,
            'maintained' => $maintained,
            'linking_rate' => $totalAudits > 0 ? round(($linkedAudits / $totalAudits) * 100, 1) : 0
        ];
    }

    /**
     * Calculate audit star level - EXACT WHO SLIPTA FORMULA
     */
    protected function calculateAuditStarLevel(int $auditId): ?int
    {
        try {
            // Use CASE statement to prevent MySQL ENUM index bug
            $responses = DB::table('audit_responses as ar')
                ->join('slipta_questions as sq', 'ar.question_id', '=', 'sq.id')
                ->where('ar.audit_id', $auditId)
                ->select(
                    'ar.answer',
                    DB::raw("CASE WHEN sq.weight = '2' THEN 2 WHEN sq.weight = '3' THEN 3 ELSE 0 END as weight_value")
                )
                ->get();

            if ($responses->isEmpty()) {
                return null;
            }

            $totalEarned = 0;
            $totalNaPoints = 0;

            foreach ($responses as $response) {
                switch ($response->answer) {
                    case 'Y':
                        $totalEarned += $response->weight_value;
                        break;
                    case 'P':
                        $totalEarned += 1; // Always 1 point
                        break;
                    case 'N':
                        $totalEarned += 0;
                        break;
                    case 'NA':
                        $totalEarned += 0;
                        $totalNaPoints += $response->weight_value;
                        break;
                }
            }

            $adjustedDenominator = 367 - $totalNaPoints;

            if ($adjustedDenominator <= 0) {
                return null;
            }

            $percentage = ($totalEarned / $adjustedDenominator) * 100;

            // WHO SLIPTA EXACT BOUNDARIES
            if ($percentage >= 95.0) return 5;
            if ($percentage >= 85.0) return 4;
            if ($percentage >= 75.0) return 3;
            if ($percentage >= 65.0) return 2;
            if ($percentage >= 55.0) return 1;
            return 0;

        } catch (Exception $e) {
            Log::error('Star level calculation failed', [
                'audit_id' => $auditId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
