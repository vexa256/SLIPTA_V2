<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * Audit CRUD Controller - Single View Management
 */
class AuditController extends Controller
{
    /**
     * Display audit management interface
     */
    public function index()
    {
        try {
            $userContext = $this->getUserContext();

            // Get audits with scope filtering
            $query = DB::table('audits as a')
                ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
                ->join('countries as c', 'l.country_id', '=', 'c.id')
                ->leftJoin('users as creator', 'a.created_by', '=', 'creator.id')
                ->select(
                    'a.id',
                    'a.laboratory_id',
                    'a.status',
                    'a.opened_on',
                    'a.closed_on',
                    'a.last_audit_date',
                    'a.prior_official_status',
                    'a.previous_audit_id',
                    'a.auditor_notes',
                    'a.created_at',
                    'l.name as laboratory_name',
                    'l.lab_number',
                    'c.name as country_name',
                    'creator.name as created_by_name'
                );

            $query = $this->applyScopeFilter($query, $userContext, 'a');
            $audits = $query->orderBy('a.opened_on', 'desc')->get();

            // Enhance audits with additional data
            $audits = $audits->map(function($audit) {
                // Check if audit has data
                $hasResponses = DB::table('audit_responses')
                    ->where('audit_id', $audit->id)
                    ->exists();

                $hasFindings = DB::table('audit_findings')
                    ->where('audit_id', $audit->id)
                    ->exists();

                $hasEvidence = DB::table('audit_evidence')
                    ->where('audit_id', $audit->id)
                    ->exists();

                $audit->has_data = $hasResponses || $hasFindings || $hasEvidence;
                $audit->can_delete = !$audit->has_data && $audit->status === 'draft';

                // Calculate score if completed
                if ($audit->status === 'completed') {
                    $audit->score = $this->calculateAuditScore($audit->id);
                }

                return $audit;
            });

            // Get accessible laboratories
            $laboratories = $this->getAccessibleLaboratories($userContext);

            // Calculate stats
            $stats = [
                'total' => $audits->count(),
                'draft' => $audits->where('status', 'draft')->count(),
                'in_progress' => $audits->where('status', 'in_progress')->count(),
                'completed' => $audits->where('status', 'completed')->count(),
                'cancelled' => $audits->where('status', 'cancelled')->count(),
                'my_audits' => $audits->where('created_by', auth()->id())->count(),
                'completion_rate' => $audits->count() > 0
                    ? round(($audits->where('status', 'completed')->count() / $audits->count()) * 100, 1)
                    : 0
            ];

            return view('audits.manage', [
                'audits' => $audits,
                'laboratories' => $laboratories,
                'stats' => $stats,
                'userContext' => $userContext,
                'csrf' => csrf_token()
            ]);

        } catch (Exception $e) {
            Log::error('Audit management load failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Failed to load audits: ' . $e->getMessage());
        }
    }

    /**
     * Store new audit
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'laboratory_id' => 'required|integer|exists:laboratories,id',
            'opened_on' => 'required|date|before_or_equal:today',
            'last_audit_date' => 'nullable|date|before:opened_on',
            'prior_official_status' => 'nullable|in:NOT_AUDITED,0,1,2,3,4,5',
            'auditor_notes' => 'nullable|string|max:5000'
        ], [
            'laboratory_id.required' => 'Laboratory is required',
            'laboratory_id.exists' => 'Selected laboratory does not exist',
            'opened_on.required' => 'Audit date is required',
            'opened_on.before_or_equal' => 'Audit date cannot be in the future',
            'last_audit_date.before' => 'Last audit date must be before current audit date',
            'auditor_notes.max' => 'Notes cannot exceed 5000 characters'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $userContext = $this->getUserContext();

            if (!$this->canCreateAudit($userContext, $request->laboratory_id)) {
                throw new Exception('AUTHORIZATION DENIED: You cannot create audits for this laboratory');
            }

            $laboratory = DB::table('laboratories')
                ->where('id', $request->laboratory_id)
                ->where('is_active', 1)
                ->first();

            if (!$laboratory) {
                throw new Exception('Laboratory not found or inactive');
            }

            $auditId = DB::table('audits')->insertGetId([
                'laboratory_id' => $request->laboratory_id,
                'status' => 'draft',
                'opened_on' => $request->opened_on,
                'closed_on' => null,
                'last_audit_date' => $request->last_audit_date,
                'prior_official_status' => $request->prior_official_status,
                'previous_audit_id' => null,
                'auditor_notes' => $request->auditor_notes,
                'created_by' => auth()->id(),
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('audit_team_members')->insert([
                'audit_id' => $auditId,
                'user_id' => auth()->id(),
                'role' => 'lead',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('Audit created', [
                'audit_id' => $auditId,
                'laboratory_id' => $request->laboratory_id,
                'created_by' => auth()->id()
            ]);

            // Get created audit with details
            $audit = DB::table('audits as a')
                ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
                ->join('countries as c', 'l.country_id', '=', 'c.id')
                ->where('a.id', $auditId)
                ->select('a.*', 'l.name as laboratory_name', 'c.name as country_name')
                ->first();

            $audit->has_data = false;
            $audit->can_delete = true;

            return response()->json([
                'success' => true,
                'message' => 'Audit created successfully',
                'audit' => $audit
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Audit creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update audit
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'laboratory_id' => 'required|integer|exists:laboratories,id',
            'status' => 'required|in:draft,in_progress,completed,cancelled',
            'opened_on' => 'required|date|before_or_equal:today',
            'closed_on' => 'nullable|date|after_or_equal:opened_on',
            'last_audit_date' => 'nullable|date|before:opened_on',
            'prior_official_status' => 'nullable|in:NOT_AUDITED,0,1,2,3,4,5',
            'auditor_notes' => 'nullable|string|max:5000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $userContext = $this->getUserContext();

            if (!$this->canEditAudit($userContext, $id)) {
                throw new Exception('AUTHORIZATION DENIED: You cannot edit this audit');
            }

            $currentAudit = DB::table('audits')->where('id', $id)->first();
            if (!$currentAudit) {
                throw new Exception('Audit not found');
            }

            // Status transition validation
            $this->validateStatusTransition($currentAudit->status, $request->status, $id);

            // Auto-set closed_on when completing
            $closedOn = $request->closed_on;
            if ($request->status === 'completed' && !$closedOn) {
                $closedOn = now()->format('Y-m-d');
            }
            if ($request->status !== 'completed') {
                $closedOn = null;
            }

            DB::table('audits')
                ->where('id', $id)
                ->update([
                    'laboratory_id' => $request->laboratory_id,
                    'status' => $request->status,
                    'opened_on' => $request->opened_on,
                    'closed_on' => $closedOn,
                    'last_audit_date' => $request->last_audit_date,
                    'prior_official_status' => $request->prior_official_status,
                    'auditor_notes' => $request->auditor_notes,
                    'updated_by' => auth()->id(),
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('Audit updated', [
                'audit_id' => $id,
                'updated_by' => auth()->id(),
                'new_status' => $request->status
            ]);

            // Get updated audit
            $audit = DB::table('audits as a')
                ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
                ->join('countries as c', 'l.country_id', '=', 'c.id')
                ->where('a.id', $id)
                ->select('a.*', 'l.name as laboratory_name', 'c.name as country_name')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Audit updated successfully',
                'audit' => $audit
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Audit update failed', [
                'error' => $e->getMessage(),
                'audit_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete audit
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $userContext = $this->getUserContext();

            if (!$this->canDeleteAudit($userContext, $id)) {
                throw new Exception('AUTHORIZATION DENIED: You cannot delete this audit');
            }

            $audit = DB::table('audits')->where('id', $id)->first();
            if (!$audit) {
                throw new Exception('Audit not found');
            }

            // Only allow deletion of drafts
            if ($audit->status !== 'draft') {
                throw new Exception('Only draft audits can be deleted. Current status: ' . $audit->status);
            }

            // Check for data
            $hasResponses = DB::table('audit_responses')->where('audit_id', $id)->exists();
            $hasFindings = DB::table('audit_findings')->where('audit_id', $id)->exists();
            $hasEvidence = DB::table('audit_evidence')->where('audit_id', $id)->exists();

            if ($hasResponses || $hasFindings || $hasEvidence) {
                throw new Exception('Cannot delete audit with recorded data (responses, findings, or evidence)');
            }

            DB::table('audits')->where('id', $id)->delete();

            DB::commit();

            Log::info('Audit deleted', [
                'audit_id' => $id,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Audit deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Audit deletion failed', [
                'error' => $e->getMessage(),
                'audit_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate status transitions
     */
    protected function validateStatusTransition($currentStatus, $newStatus, $auditId)
    {
        // Define allowed transitions
        $allowedTransitions = [
            'draft' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [], // Cannot change from completed
            'cancelled' => [] // Cannot change from cancelled
        ];

        if ($currentStatus === $newStatus) {
            return; // Same status is always allowed
        }

        if (!isset($allowedTransitions[$currentStatus])) {
            throw new Exception("Invalid current status: {$currentStatus}");
        }

        if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
            throw new Exception("Cannot transition from '{$currentStatus}' to '{$newStatus}'");
        }

        // Additional validation for completion
        if ($newStatus === 'completed') {
            $responseCount = DB::table('audit_responses')->where('audit_id', $auditId)->count();
            if ($responseCount === 0) {
                throw new Exception('Cannot complete audit without any responses');
            }
        }
    }

    /**
     * Calculate audit score
     */
    protected function calculateAuditScore($auditId)
    {
        try {
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
                    case 'Y': $totalEarned += $response->weight_value; break;
                    case 'P': $totalEarned += 1; break;
                    case 'NA': $totalNaPoints += $response->weight_value; break;
                }
            }

            $adjustedDenominator = 367 - $totalNaPoints;
            $percentage = ($totalEarned / $adjustedDenominator) * 100;

            if ($percentage >= 95.0) $starLevel = 5;
            elseif ($percentage >= 85.0) $starLevel = 4;
            elseif ($percentage >= 75.0) $starLevel = 3;
            elseif ($percentage >= 65.0) $starLevel = 2;
            elseif ($percentage >= 55.0) $starLevel = 1;
            else $starLevel = 0;

            return [
                'percentage' => round($percentage, 2),
                'star_level' => $starLevel,
                'total_earned' => $totalEarned,
                'adjusted_denominator' => $adjustedDenominator
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    // Scope methods remain the same
    protected function getUserContext(): array
    {
        $user = auth()->user();
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
            'is_system_admin' => $isSystemAdmin,
            'is_project_coordinator' => $isProjectCoordinator,
            'is_country_coordinator' => $roles->contains('name', 'country_coordinator'),
            'is_lead_auditor' => $roles->contains('name', 'lead_auditor'),
            'has_global_access' => $isSystemAdmin || $isProjectCoordinator,
            'country_ids' => $roles->pluck('country_id')->filter()->unique()->values()->all(),
            'laboratory_ids' => $roles->pluck('laboratory_id')->filter()->unique()->values()->all()
        ];
    }

    protected function applyScopeFilter($query, array $userContext, string $alias = 'a')
    {
        if ($userContext['has_global_access']) return $query;

        if ($userContext['is_country_coordinator']) {
            return $query->whereIn('l.country_id', $userContext['country_ids']);
        }

        if ($userContext['is_lead_auditor']) {
            return $query->where(function($q) use ($userContext, $alias) {
                if (!empty($userContext['laboratory_ids'])) {
                    $q->whereIn("{$alias}.laboratory_id", $userContext['laboratory_ids']);
                }
                $q->orWhereExists(function($sub) use ($userContext, $alias) {
                    $sub->from('audit_team_members')
                        ->whereColumn('audit_team_members.audit_id', '=', "{$alias}.id")
                        ->where('audit_team_members.user_id', $userContext['user']->id);
                });
            });
        }

        if (!empty($userContext['laboratory_ids'])) {
            return $query->whereIn("{$alias}.laboratory_id", $userContext['laboratory_ids']);
        }

        return $query->whereRaw('1 = 0');
    }

    protected function getAccessibleLaboratories(array $userContext)
    {
        $query = DB::table('laboratories as l')
            ->join('countries as c', 'l.country_id', '=', 'c.id')
            ->where('l.is_active', 1)
            ->select('l.id', 'l.name', 'l.lab_number', 'c.name as country_name');

        if ($userContext['has_global_access']) {
            return $query->orderBy('c.name')->orderBy('l.name')->get();
        }

        if ($userContext['is_country_coordinator']) {
            return $query->whereIn('l.country_id', $userContext['country_ids'])
                ->orderBy('c.name')->orderBy('l.name')->get();
        }

        if (!empty($userContext['laboratory_ids'])) {
            return $query->whereIn('l.id', $userContext['laboratory_ids'])
                ->orderBy('c.name')->orderBy('l.name')->get();
        }

        return collect();
    }

    protected function canCreateAudit(array $userContext, int $labId): bool
    {
        if ($userContext['has_global_access']) return true;

        $lab = DB::table('laboratories')->where('id', $labId)->first();
        if (!$lab) return false;

        if ($userContext['is_country_coordinator']) {
            return in_array($lab->country_id, $userContext['country_ids']);
        }

        return $userContext['is_lead_auditor'] && in_array($labId, $userContext['laboratory_ids']);
    }

    protected function canEditAudit(array $userContext, int $auditId): bool
    {
        if ($userContext['has_global_access']) return true;

        $audit = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.id', $auditId)
            ->first();

        if (!$audit) return false;

        if ($userContext['is_country_coordinator']) {
            return in_array($audit->country_id, $userContext['country_ids']);
        }

        if ($userContext['is_lead_auditor']) {
            return DB::table('audit_team_members')
                ->where('audit_id', $auditId)
                ->where('user_id', $userContext['user']->id)
                ->where('role', 'lead')
                ->exists();
        }

        return false;
    }

    protected function canDeleteAudit(array $userContext, int $auditId): bool
    {
        if ($userContext['has_global_access']) return true;

        if ($userContext['is_country_coordinator']) {
            $audit = DB::table('audits as a')
                ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
                ->where('a.id', $auditId)
                ->first();
            return $audit && in_array($audit->country_id, $userContext['country_ids']);
        }

        return false;
    }
}
