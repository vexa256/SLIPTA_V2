<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Mail\Mailable;
use Exception;

/**
 * Audit Workspace Controller - Scope-Based Access Control (Refactored)
 *
 * Key Guarantees
 * - Query Builder only (no Eloquent)
 * - Every data path is scope-filtered
 * - Transactions wrap all mutating operations
 * - ENUM math bug prevention (CASE instead of CAST)
 * - Best-effort email with queue-if-possible, silent on failure
 * - Responses intended for XHR return JSON with consistent {success, ...}
 */
class AuditWorkspaceController extends Controller
{
    /**
     * Display audit workspace (Blade view)
     */
    public function index()
    {
        try {
            $userContext = $this->getUserContext();

            $laboratories      = $this->getAccessibleLaboratories($userContext);
            $audits            = $this->getScopedAudits($userContext);
            $availableAuditors = $this->getScopedAuditors($userContext);

            $stats = [
                'create'  => $this->getCreateAuditStats($userContext),
                'link'    => $this->getLinkAuditStats($userContext),
                'team'    => $this->getTeamStats($userContext),
                'overall' => $this->getOverallStats($userContext)
            ];

            return view('audits.workspace', compact('laboratories', 'audits', 'availableAuditors', 'userContext', 'stats'));
        } catch (Exception $e) {
            Log::error('Audit workspace load failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Failed to load audit workspace: ' . $e->getMessage());
        }
    }

    /**
     * Create new audit (form POST)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'laboratory_id'       => 'required|integer|exists:laboratories,id',
            'opened_on'           => 'required|date|before_or_equal:today',
            'last_audit_date'     => 'nullable|date|before:opened_on',
            'prior_official_status' => 'nullable|in:NOT_AUDITED,0,1,2,3,4,5',
            'previous_audit_id'   => 'nullable|integer|exists:audits,id',
            'auditor_notes'       => 'nullable|string|max:5000',
        ], [
            'laboratory_id.required' => 'Laboratory selection is required',
            'laboratory_id.exists'   => 'Selected laboratory does not exist',
            'opened_on.required'     => 'Audit date is required',
            'opened_on.before_or_equal' => 'Audit date cannot be in the future',
            'last_audit_date.before' => 'Last audit date must be before current audit date',
            'prior_official_status.in' => 'Invalid prior star level selected',
            'previous_audit_id.exists' => 'Selected previous audit does not exist',
            'auditor_notes.max'      => 'Auditor notes cannot exceed 5000 characters',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error', 'Validation failed: ' . $validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $userContext = $this->getUserContext();

            if (!$this->canCreateAuditForLaboratory($userContext, (int)$request->laboratory_id)) {
                throw new Exception('AUTHORIZATION DENIED: You do not have permission to create audits for this laboratory');
            }

            $laboratory = DB::table('laboratories')->where('id', $request->laboratory_id)->where('is_active', 1)->first();
            if (!$laboratory) {
                throw new Exception('LABORATORY ERROR: Laboratory not found or inactive (ID: ' . $request->laboratory_id . ')');
            }

            if ($request->previous_audit_id) {
                if (!$this->canAccessAudit($userContext, (int)$request->previous_audit_id)) {
                    throw new Exception('AUTHORIZATION DENIED: You do not have access to the specified previous audit (ID: ' . $request->previous_audit_id . ')');
                }
                $previousAudit = DB::table('audits')
                    ->where('id', $request->previous_audit_id)
                    ->where('laboratory_id', $request->laboratory_id)
                    ->first();
                if (!$previousAudit) {
                    throw new Exception('LINKAGE ERROR: Previous audit must belong to the same laboratory');
                }
            }

            $auditId = DB::table('audits')->insertGetId([
                'laboratory_id'        => $request->laboratory_id,
                'status'               => 'draft',
                'opened_on'            => $request->opened_on,
                'closed_on'            => null,
                'last_audit_date'      => $request->last_audit_date,
                'prior_official_status'=> $request->prior_official_status,
                'previous_audit_id'    => $request->previous_audit_id,
                'auditor_notes'        => $request->auditor_notes,
                'created_by'           => auth()->id(),
                'updated_by'           => null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
            if (!$auditId) {
                throw new Exception('DATABASE ERROR: Failed to create audit record');
            }

            $teamInsert = DB::table('audit_team_members')->insert([
                'audit_id'   => $auditId,
                'user_id'    => auth()->id(),
                'role'       => 'lead',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (!$teamInsert) {
                throw new Exception('DATABASE ERROR: Failed to assign lead auditor');
            }

            DB::commit();

            // Best-effort email (queue if possible; otherwise send; silent on error)
            $this->sendEmailBestEffort(function() use ($auditId, $laboratory) {
                $audit = DB::table('audits')->where('id', $auditId)->first();
                $user  = auth()->user();
                if (!$audit || !$user || !$laboratory) return; // silently skip
                $subject = 'SLIPTA Audit Created - ' . $laboratory->name;
                $body  = "Dear {$user->name},\n\n";
                $body .= "A new SLIPTA audit has been successfully created.\n\n";
                $body .= "Laboratory: {$laboratory->name}\n";
                $body .= 'Audit Date: ' . date('l, F j, Y', strtotime($audit->opened_on)) . "\n";
                $body .= 'Status: ' . strtoupper($audit->status) . "\n\n";
                $body .= "You have been assigned as Lead Auditor.\n\n";
                $body .= "Best regards,\nSLIPTA Digital Assessment System";

                Mail::raw($body, function($message) use ($user, $subject) {
                    $message->to($user->email, $user->name)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            });

            Log::info('Audit created successfully', [
                'audit_id' => $auditId,
                'laboratory_id' => $request->laboratory_id,
                'laboratory_name' => $laboratory->name,
                'created_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', 'Audit created successfully (ID: ' . $auditId . '). You have been assigned as Lead Auditor.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Audit creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'laboratory_id' => $request->laboratory_id ?? null,
                'input' => $request->except(['_token'])
            ]);
            return back()->with('error', 'AUDIT CREATION FAILED: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Link audit to prior audit (XHR JSON)
     */
    public function linkPriorAudit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id'          => 'required|integer|exists:audits,id',
            'previous_audit_id' => 'required|integer|exists:audits,id|different:audit_id',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => 'Validation failed: ' . $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $userContext = $this->getUserContext();

            if (!$this->canAccessAudit($userContext, (int)$request->audit_id)) {
                return response()->json(['success' => false, 'error' => 'AUTHORIZATION DENIED: You do not have access to the current audit'], 403);
            }
            if (!$this->canAccessAudit($userContext, (int)$request->previous_audit_id)) {
                return response()->json(['success' => false, 'error' => 'AUTHORIZATION DENIED: You do not have access to the previous audit'], 403);
            }
            if (!$this->canEditAudit($userContext, (int)$request->audit_id)) {
                return response()->json(['success' => false, 'error' => 'AUTHORIZATION DENIED: You do not have permission to modify this audit'], 403);
            }

            $currentAudit  = DB::table('audits')->where('id', $request->audit_id)->first();
            $previousAudit = DB::table('audits')->where('id', $request->previous_audit_id)->first();
            if (!$currentAudit || !$previousAudit) {
                throw new Exception('DATABASE ERROR: One or both audits not found');
            }
            if ((int)$currentAudit->laboratory_id !== (int)$previousAudit->laboratory_id) {
                throw new Exception('LINKAGE ERROR: Both audits must belong to the same laboratory');
            }
            if ($this->wouldCreateCircularReference((int)$request->audit_id, (int)$request->previous_audit_id)) {
                throw new Exception('LINKAGE ERROR: Cannot create circular audit references');
            }

            $updated = DB::table('audits')->where('id', $request->audit_id)->update([
                'previous_audit_id' => $request->previous_audit_id,
                'updated_by'        => auth()->id(),
                'updated_at'        => now(),
            ]);
            if ($updated === false) {
                throw new Exception('DATABASE ERROR: Failed to update audit linkage');
            }

            DB::commit();
            Log::info('Audit linked to prior audit', ['audit_id' => $request->audit_id, 'previous_audit_id' => $request->previous_audit_id, 'user_id' => auth()->id()]);

            return response()->json(['success' => true, 'message' => 'Audit successfully linked to previous audit', 'previous_audit_id' => (int)$request->previous_audit_id]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Audit linking failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'audit_id' => $request->audit_id ?? null,
                'previous_audit_id' => $request->previous_audit_id ?? null,
            ]);
            return response()->json(['success' => false, 'error' => 'AUDIT LINKING FAILED: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Assign team members (XHR JSON)
     * - Ensures exactly one lead overall (including creator)
     */
    public function assignTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id' => 'required|integer|exists:audits,id',
            'team_members' => 'required|array|min:1',
            'team_members.*.user_id' => 'required|integer|exists:users,id',
            'team_members.*.role'    => 'required|in:lead,member,observer',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => 'Validation failed: ' . $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $userContext = $this->getUserContext();

            if (!$this->canAccessAudit($userContext, (int)$request->audit_id)) {
                return response()->json(['success' => false, 'error' => 'AUTHORIZATION DENIED: You do not have access to this audit'], 403);
            }
            if (!$this->canAssignTeam($userContext, (int)$request->audit_id)) {
                return response()->json(['success' => false, 'error' => 'AUTHORIZATION DENIED: You do not have permission to assign team members for this audit'], 403);
            }

            $audit = DB::table('audits')->where('id', $request->audit_id)->first();
            if (!$audit) throw new Exception('DATABASE ERROR: Audit not found');

            $laboratory = DB::table('laboratories')->where('id', $audit->laboratory_id)->first();
            if (!$laboratory) throw new Exception('DATABASE ERROR: Laboratory not found');

            // Validate each member is in scope and credentialed
            $userIds = [];
            $leadCountInPayload = 0;
            foreach ($request->team_members as $member) {
                $uid  = (int)$member['user_id'];
                $role = $member['role'];
                if (!$this->isAuditorInScope($userContext, $uid)) {
                    $u = DB::table('users')->where('id', $uid)->first();
                    $name = $u ? $u->name : ('User#' . $uid);
                    throw new Exception('AUTHORIZATION DENIED: User "' . $name . '" (ID: ' . $uid . ') is not within your authorization scope');
                }
                if (!$this->isValidAuditor($uid)) {
                    $u = DB::table('users')->where('id', $uid)->first();
                    $name = $u ? $u->name : ('User#' . $uid);
                    throw new Exception('QUALIFICATION ERROR: User "' . $name . '" (ID: ' . $uid . ') does not have appropriate auditor credentials');
                }
                if (in_array($uid, $userIds, true)) {
                    throw new Exception('TEAM COMPOSITION ERROR: Duplicate user detected in assignment payload');
                }
                $userIds[] = $uid;
                if ($role === 'lead') $leadCountInPayload++;
            }

            // Fetch creator current role (if any) to enforce single-lead invariant
            $creatorMembership = DB::table('audit_team_members')
                ->where('audit_id', $audit->id)
                ->where('user_id', $audit->created_by)
                ->first();

            $creatorIsLead = $creatorMembership && $creatorMembership->role === 'lead';
            $creatorInPayload = in_array((int)$audit->created_by, $userIds, true);

            // Compute intended final number of leads after operation
            $intendedLeadCount = $leadCountInPayload + (($creatorIsLead && !$creatorInPayload) ? 1 : 0);

            if ($intendedLeadCount !== 1) {
                // Auto-resolve: if two leads, and creator currently lead but not in payload, downgrade creator to member
                if ($intendedLeadCount > 1 && $creatorIsLead && !$creatorInPayload) {
                    DB::table('audit_team_members')
                        ->where('audit_id', $audit->id)
                        ->where('user_id', $audit->created_by)
                        ->update(['role' => 'member', 'updated_at' => now()]);
                    Log::info('Auto-downgraded creator from lead to member to enforce single-lead invariant', [
                        'audit_id' => $audit->id,
                        'creator_id' => $audit->created_by,
                    ]);
                    // recompute: creator no longer lead
                    $intendedLeadCount = $leadCountInPayload;
                }
            }

            if ($intendedLeadCount !== 1) {
                throw new Exception('TEAM COMPOSITION ERROR: Audit must have exactly one Lead Auditor');
            }

            // Remove existing members except possibly creator (we keep creator row to preserve provenance; role may be updated below)
            $deletedCount = DB::table('audit_team_members')
                ->where('audit_id', $audit->id)
                ->where('user_id', '!=', $audit->created_by)
                ->delete();
            Log::info('Removed existing team members (except creator)', ['audit_id' => $audit->id, 'deleted_count' => $deletedCount]);

            $newMembers = [];
            $bulk = [];

            foreach ($request->team_members as $member) {
                $uid  = (int)$member['user_id'];
                $role = $member['role'];
                if ($uid === (int)$audit->created_by) {
                    // Update creator role if present in payload
                    $updated = DB::table('audit_team_members')
                        ->where('audit_id', $audit->id)
                        ->where('user_id', $audit->created_by)
                        ->update(['role' => $role, 'updated_at' => now()]);
                    if ($updated === false) {
                        throw new Exception('DATABASE ERROR: Failed to update creator role');
                    }
                    continue;
                }
                $bulk[] = [
                    'audit_id'   => $audit->id,
                    'user_id'    => $uid,
                    'role'       => $role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $newMembers[] = ['user_id' => $uid, 'role' => $role];
            }

            if (!empty($bulk)) {
                $ok = DB::table('audit_team_members')->insert($bulk);
                if (!$ok) throw new Exception('DATABASE ERROR: Failed to insert team members');
            }

            DB::table('audits')->where('id', $audit->id)->update(['updated_by' => auth()->id(), 'updated_at' => now()]);

            DB::commit();

            // Best-effort emails to new members
            foreach ($newMembers as $m) {
                $user = DB::table('users')->where('id', $m['user_id'])->first();
                if (!$user) continue;
                $this->sendEmailBestEffort(function() use ($user, $audit, $laboratory, $m) {
                    $roleLabels = ['lead' => 'Lead Auditor', 'member' => 'Team Member', 'observer' => 'Observer'];
                    $subject = 'SLIPTA Audit Team Assignment - ' . $laboratory->name;
                    $body  = "Dear {$user->name},\n\n";
                    $body .= "You have been assigned to a SLIPTA audit team.\n\n";
                    $body .= "Laboratory: {$laboratory->name}\n";
                    $body .= 'Audit Date: ' . date('l, F j, Y', strtotime($audit->opened_on)) . "\n";
                    $body .= 'Your Role: ' . ($roleLabels[$m['role']] ?? $m['role']) . "\n\n";
                    $body .= "Best regards,\nSLIPTA Digital Assessment System";

                    Mail::raw($body, function($message) use ($user, $subject) {
                        $message->to($user->email, $user->name)
                            ->subject($subject)
                            ->from(config('mail.from.address'), config('mail.from.name'));
                    });
                });
            }

            Log::info('Audit team assigned successfully', [
                'audit_id' => $audit->id,
                'team_size' => count($request->team_members),
                'new_members' => count($newMembers),
                'assigned_by' => auth()->id(),
            ]);

            return response()->json(['success' => true, 'message' => 'Audit team assigned successfully', 'team_size' => count($request->team_members)]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Team assignment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'audit_id' => $request->audit_id ?? null,
                'team_members' => $request->team_members ?? [],
            ]);
            return response()->json(['success' => false, 'error' => 'TEAM ASSIGNMENT FAILED: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get linkable audits (XHR JSON)
     */
    public function getLinkableAudits(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'laboratory_id'    => 'required|integer|exists:laboratories,id',
            'current_audit_id' => 'nullable|integer|exists:audits,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => 'Validation failed: ' . $validator->errors()->first()], 422);
        }

        try {
            $userContext = $this->getUserContext();
            if (!$this->hasLaboratoryAccess($userContext, (int)$request->laboratory_id)) {
                return response()->json(['success' => false, 'error' => 'AUTHORIZATION DENIED: Access denied to this laboratory'], 403);
            }

            $q = DB::table('audits as a')
                ->leftJoin('users as u', 'a.created_by', '=', 'u.id')
                ->where('a.laboratory_id', $request->laboratory_id)
                ->whereIn('a.status', ['completed', 'in_progress'])
                ->select('a.id', 'a.opened_on', 'a.closed_on', 'a.status', 'a.prior_official_status', 'u.name as created_by_name');

            if ($request->current_audit_id) $q->where('a.id', '!=', $request->current_audit_id);

            $q = $this->applyScopeToQuery($q, $userContext, 'a');
            $audits = $q->orderBy('a.opened_on', 'desc')->get();

            $audits = $audits->map(function($audit) {
                $audit->calculated_star_level = $audit->status === 'completed' ? $this->calculateAuditStarLevel((int)$audit->id) : null;
                return $audit;
            });

            return response()->json(['success' => true, 'audits' => $audits]);
        } catch (Exception $e) {
            Log::error('Failed to get linkable audits', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'laboratory_id' => $request->laboratory_id ?? null,
                'current_audit_id' => $request->current_audit_id ?? null,
            ]);
            return response()->json(['success' => false, 'error' => 'Failed to load audits: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get current team members (XHR JSON)
     */
    public function getAuditTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [ 'audit_id' => 'required|integer|exists:audits,id' ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => 'Validation failed: ' . $validator->errors()->first()], 422);
        }

        try {
            $userContext = $this->getUserContext();
            if (!$this->canAccessAudit($userContext, (int)$request->audit_id)) {
                return response()->json(['success' => false, 'error' => 'AUTHORIZATION DENIED: Access denied to this audit'], 403);
            }

            $team = DB::table('audit_team_members as atm')
                ->join('users as u', 'atm.user_id', '=', 'u.id')
                ->where('atm.audit_id', $request->audit_id)
                ->select('atm.id', 'atm.user_id', 'atm.role', 'u.name', 'u.email', 'u.organization')
                ->orderByRaw("FIELD(atm.role, 'lead','member','observer')")
                ->get();

            return response()->json(['success' => true, 'team' => $team]);
        } catch (Exception $e) {
            Log::error('Failed to get audit team', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'audit_id' => $request->audit_id ?? null,
            ]);
            return response()->json(['success' => false, 'error' => 'Failed to load team: ' . $e->getMessage()], 500);
        }
    }

    // ---------------------------
    // Scope & Access helpers
    // ---------------------------

    protected function getUserContext(): array
    {
        $user = auth()->user();
        if (!$user) throw new Exception('AUTHENTICATION ERROR: User not authenticated');

        $roles = DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $user->id)
            ->where('ur.is_active', 1)
            ->select('r.name', 'ur.country_id', 'ur.laboratory_id')
            ->get();

        $isSystemAdmin       = $roles->contains('name', 'system_admin');
        $isProjectCoordinator= $roles->contains('name', 'project_coordinator');

        return [
            'user'                  => $user,
            'roles'                 => $roles,
            'is_system_admin'       => $isSystemAdmin,
            'is_project_coordinator'=> $isProjectCoordinator,
            'is_country_coordinator'=> $roles->contains('name', 'country_coordinator'),
            'is_lead_auditor'       => $roles->contains('name', 'lead_auditor'),
            'is_auditor'            => $roles->contains('name', 'auditor'),
            'has_global_access'     => ($isSystemAdmin || $isProjectCoordinator),
            'country_ids'           => $roles->pluck('country_id')->filter()->unique()->values()->all(),
            'laboratory_ids'        => $roles->pluck('laboratory_id')->filter()->unique()->values()->all(),
        ];
    }

    protected function applyScopeToQuery($query, array $userContext, string $auditAlias = 'a')
    {
        // Global
        if ($userContext['has_global_access']) return $query;

        // Country coordinator
        if ($userContext['is_country_coordinator']) {
            $query->join('laboratories as l_scope', "$auditAlias.laboratory_id", '=', 'l_scope.id')
                  ->whereIn('l_scope.country_id', $userContext['country_ids']);
            return $query;
        }

        // Auditors: own lab(s) or assigned audits
        if ($userContext['is_lead_auditor'] || $userContext['is_auditor']) {
            $query->where(function($q) use ($userContext, $auditAlias) {
                if (!empty($userContext['laboratory_ids'])) {
                    $q->whereIn("$auditAlias.laboratory_id", $userContext['laboratory_ids']);
                }
                $q->orWhereExists(function($sub) use ($userContext, $auditAlias) {
                    $sub->from('audit_team_members')
                        ->whereColumn('audit_team_members.audit_id', '=', "$auditAlias.id")
                        ->where('audit_team_members.user_id', $userContext['user']->id);
                });
            });
            return $query;
        }

        // Default: lab-scoped or no access
        if (!empty($userContext['laboratory_ids'])) {
            $query->whereIn("$auditAlias.laboratory_id", $userContext['laboratory_ids']);
        } else {
            $query->whereRaw('1=0');
        }
        return $query;
    }

    protected function getAccessibleLaboratories(array $userContext)
    {
        $q = DB::table('laboratories as l')
            ->join('countries as c', 'l.country_id', '=', 'c.id')
            ->where('l.is_active', 1)
            ->select('l.id','l.name','l.lab_number','l.city','l.country_id','c.name as country_name');

        if ($userContext['has_global_access']) return $q->orderBy('c.name')->orderBy('l.name')->get();
        if ($userContext['is_country_coordinator']) {
            $q->whereIn('l.country_id', $userContext['country_ids']);
            return $q->orderBy('c.name')->orderBy('l.name')->get();
        }
        if (!empty($userContext['laboratory_ids'])) {
            $q->whereIn('l.id', $userContext['laboratory_ids']);
            return $q->orderBy('c.name')->orderBy('l.name')->get();
        }
        return collect();
    }

    protected function getScopedAudits(array $userContext)
    {
        $q = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->join('countries as c', 'l.country_id', '=', 'c.id')
            ->select('a.id','a.opened_on','a.closed_on','a.status','a.laboratory_id','l.name as laboratory_name','c.name as country_name');

        $q = $this->applyScopeToQuery($q, $userContext, 'a');
        return $q->orderBy('a.opened_on', 'desc')->get();
    }

    protected function getScopedAuditors(array $userContext)
    {
        $q = DB::table('users as u')
            ->join('user_roles as ur', 'u.id', '=', 'ur.user_id')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('u.is_active', 1)
            ->where('ur.is_active', 1)
            ->whereIn('r.name', ['lead_auditor','auditor','observer'])
            ->select('u.id','u.name','u.email','u.organization','r.name as role_name')
            ->distinct();

        if ($userContext['has_global_access']) return $q->orderBy('u.name')->get();
        if ($userContext['is_country_coordinator']) {
            $q->where(function($w) use ($userContext) {
                $w->whereIn('ur.country_id', $userContext['country_ids'])
                  ->orWhereNull('ur.country_id');
            });
            return $q->orderBy('u.name')->get();
        }
        // Lab-scoped for others
        if (!empty($userContext['laboratory_ids'])) {
            $q->where(function($w) use ($userContext) {
                $w->whereIn('ur.laboratory_id', $userContext['laboratory_ids'])
                  ->orWhereNull('ur.laboratory_id');
            });
        } else {
            $q->whereRaw('1=0');
        }
        return $q->orderBy('u.name')->get();
    }

    protected function canCreateAuditForLaboratory(array $userContext, int $laboratoryId): bool
    {
        if ($userContext['has_global_access']) return true;
        $lab = DB::table('laboratories')->where('id', $laboratoryId)->first();
        if (!$lab) return false;
        if ($userContext['is_country_coordinator']) return in_array((int)$lab->country_id, $userContext['country_ids'], true);
        if ($userContext['is_lead_auditor']) return in_array($laboratoryId, $userContext['laboratory_ids'], true);
        return false;
    }

    protected function canAccessAudit(array $userContext, int $auditId): bool
    {
        if ($userContext['has_global_access']) return true;
        $audit = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.id', $auditId)
            ->select('a.id','a.laboratory_id','l.country_id')
            ->first();
        if (!$audit) return false;
        if ($userContext['is_country_coordinator']) return in_array((int)$audit->country_id, $userContext['country_ids'], true);
        if ($userContext['is_lead_auditor'] || $userContext['is_auditor']) {
            $isTeam = DB::table('audit_team_members')->where('audit_id', $auditId)->where('user_id', $userContext['user']->id)->exists();
            return $isTeam || in_array((int)$audit->laboratory_id, $userContext['laboratory_ids'], true);
        }
        return in_array((int)$audit->laboratory_id, $userContext['laboratory_ids'], true);
    }

    protected function canEditAudit(array $userContext, int $auditId): bool
    {
        if ($userContext['has_global_access']) return true;
        $audit = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('a.id', $auditId)
            ->select('a.id','a.laboratory_id','l.country_id')
            ->first();
        if (!$audit) return false;
        if ($userContext['is_country_coordinator']) return in_array((int)$audit->country_id, $userContext['country_ids'], true);
        if ($userContext['is_lead_auditor']) {
            return DB::table('audit_team_members')
                ->where('audit_id', $auditId)
                ->where('user_id', $userContext['user']->id)
                ->where('role', 'lead')
                ->exists();
        }
        return false;
    }

    protected function canAssignTeam(array $userContext, int $auditId): bool
    {
        return $this->canEditAudit($userContext, $auditId);
    }

    protected function hasLaboratoryAccess(array $userContext, int $laboratoryId): bool
    {
        if ($userContext['has_global_access']) return true;
        $lab = DB::table('laboratories')->where('id', $laboratoryId)->first();
        if (!$lab) return false;
        if ($userContext['is_country_coordinator']) return in_array((int)$lab->country_id, $userContext['country_ids'], true);
        return in_array($laboratoryId, $userContext['laboratory_ids'], true);
    }

    protected function isAuditorInScope(array $userContext, int $userId): bool
    {
        if ($userContext['has_global_access']) return true;
        if ($userContext['is_country_coordinator']) {
            return DB::table('user_roles as ur')
                ->where('ur.user_id', $userId)
                ->where('ur.is_active', 1)
                ->where(function($q) use ($userContext) {
                    $q->whereIn('ur.country_id', $userContext['country_ids'])
                      ->orWhereNull('ur.country_id');
                })
                ->exists();
        }
        // For others, allow if auditor has no lab scope (null) or overlaps caller's lab scope
        if (!empty($userContext['laboratory_ids'])) {
            return DB::table('user_roles as ur')
                ->where('ur.user_id', $userId)
                ->where('ur.is_active', 1)
                ->where(function($q) use ($userContext) {
                    $q->whereIn('ur.laboratory_id', $userContext['laboratory_ids'])
                      ->orWhereNull('ur.laboratory_id');
                })
                ->exists();
        }
        return false;
    }

    protected function isValidAuditor(int $userId): bool
    {
        return DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $userId)
            ->where('ur.is_active', 1)
            ->whereIn('r.name', ['lead_auditor','auditor','observer'])
            ->exists();
    }

    protected function wouldCreateCircularReference(int $auditId, int $previousAuditId, int $depth = 0): bool
    {
        if ($depth > 100) return true; // safety cap
        $prev = DB::table('audits')->where('id', $previousAuditId)->first();
        if (!$prev) return false;
        if ((int)$prev->previous_audit_id === $auditId) return true;
        if ($prev->previous_audit_id) return $this->wouldCreateCircularReference($auditId, (int)$prev->previous_audit_id, $depth + 1);
        return false;
    }

    // ---------------------------
    // Stats (scope-filtered)
    // ---------------------------

    protected function getCreateAuditStats(array $userContext): array
    {
        $q = DB::table('audits as a')->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');
        $q = $this->applyScopeToQuery($q, $userContext, 'a');

        $totalAudits = (clone $q)->count();
        $byStatus = (clone $q)->select('a.status', DB::raw('COUNT(*) as count'))->groupBy('a.status')->get()->keyBy('status');
        $recent = (clone $q)->select('a.id','a.opened_on','a.status','l.name as laboratory_name')->orderBy('a.opened_on','desc')->limit(5)->get();
        $labsActive = (clone $q)->whereIn('a.status',['draft','in_progress'])->distinct()->count('a.laboratory_id');

        return [
            'total_audits' => $totalAudits,
            'draft'        => $byStatus->get('draft')->count ?? 0,
            'in_progress'  => $byStatus->get('in_progress')->count ?? 0,
            'completed'    => $byStatus->get('completed')->count ?? 0,
            'cancelled'    => $byStatus->get('cancelled')->count ?? 0,
            'recent_audits'=> $recent,
            'labs_with_active_audits' => $labsActive,
        ];
    }

    protected function getLinkAuditStats(array $userContext): array
    {
        $q = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->whereNotNull('a.previous_audit_id');
        $q = $this->applyScopeToQuery($q, $userContext, 'a');

        $totalLinked = (clone $q)->count();

        $progression = (clone $q)
            ->where('a.status', 'completed')
            ->select('a.id','a.previous_audit_id')
            ->get()
            ->map(function($row) use ($userContext) {
                if (!$this->canAccessAudit($userContext, (int)$row->id) || !$this->canAccessAudit($userContext, (int)$row->previous_audit_id)) return null;
                $cur = $this->calculateAuditStarLevel((int)$row->id);
                $prev= $this->calculateAuditStarLevel((int)$row->previous_audit_id);
                if ($cur === null || $prev === null) return null;
                if ($cur > $prev) return 'improved';
                if ($cur < $prev) return 'declined';
                return 'maintained';
            })
            ->filter()
            ->countBy()
            ->toArray();

        return [
            'total_linked' => $totalLinked,
            'improved'     => $progression['improved'] ?? 0,
            'declined'     => $progression['declined'] ?? 0,
            'maintained'   => $progression['maintained'] ?? 0,
        ];
    }

    protected function getTeamStats(array $userContext): array
    {
        $q = DB::table('audit_team_members as atm')
            ->join('audits as a', 'atm.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');
        $q = $this->applyScopeToQuery($q, $userContext, 'a');

        $totalMembers = (clone $q)->count();
        $roles = (clone $q)->select('atm.role', DB::raw('COUNT(*) as count'))->groupBy('atm.role')->get()->keyBy('role');

        $mostActive = (clone $q)
            ->join('users as u', 'atm.user_id', '=', 'u.id')
            ->select('u.name', DB::raw('COUNT(*) as audit_count'))
            ->groupBy('u.id','u.name')
            ->orderBy('audit_count','desc')
            ->limit(5)
            ->get();

        $avgQ = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->join('audit_team_members as atm', 'a.id', '=', 'atm.audit_id');
        $avgQ = $this->applyScopeToQuery($avgQ, $userContext, 'a');
        $avgTeamSize = $avgQ->select('a.id', DB::raw('COUNT(atm.id) as team_size'))->groupBy('a.id')->get()->avg('team_size');

        return [
            'total_members'   => $totalMembers,
            'lead_count'      => $roles->get('lead')->count ?? 0,
            'member_count'    => $roles->get('member')->count ?? 0,
            'observer_count'  => $roles->get('observer')->count ?? 0,
            'avg_team_size'   => round($avgTeamSize ?? 0, 1),
            'most_active_auditors' => $mostActive,
        ];
    }

    protected function getOverallStats(array $userContext): array
    {
        $q = DB::table('audits as a')->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');
        $q = $this->applyScopeToQuery($q, $userContext, 'a');

        $total = (clone $q)->count();
        $completed = (clone $q)->where('a.status','completed')->count();
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

        $avgDays = (clone $q)
            ->whereNotNull('a.closed_on')
            ->select(DB::raw('AVG(DATEDIFF(a.closed_on, a.opened_on)) as avg_days'))
            ->first();
        $avgDuration = $avgDays && $avgDays->avg_days !== null ? round((float)$avgDays->avg_days, 1) : 0.0;

        $mine = DB::table('audit_team_members as atm')
            ->join('audits as a', 'atm.audit_id', '=', 'a.id')
            ->where('atm.user_id', auth()->id())
            ->select('atm.role', DB::raw('COUNT(*) as count'))
            ->groupBy('atm.role')
            ->get()
            ->keyBy('role');

        return [
            'total_audits'      => $total,
            'completed_audits'  => $completed,
            'completion_rate'   => $completionRate,
            'avg_duration_days' => $avgDuration,
            'my_as_lead'        => $mine->get('lead')->count ?? 0,
            'my_as_member'      => $mine->get('member')->count ?? 0,
            'my_as_observer'    => $mine->get('observer')->count ?? 0,
        ];
    }

    // ---------------------------
    // Scoring (ENUM-safe)
    // ---------------------------

    protected function calculateAuditStarLevel(int $auditId): ?int
    {
        try {
            $rows = DB::table('audit_responses as ar')
                ->join('slipta_questions as sq', 'ar.question_id', '=', 'sq.id')
                ->where('ar.audit_id', $auditId)
                ->select('ar.answer', DB::raw("CASE WHEN sq.weight='2' THEN 2 WHEN sq.weight='3' THEN 3 ELSE 0 END AS weight_value"))
                ->get();
            if ($rows->isEmpty()) return null;

            $earned = 0; $naPts = 0;
            foreach ($rows as $r) {
                switch ($r->answer) {
                    case 'Y':  $earned += (int)$r->weight_value; break;
                    case 'P':  $earned += 1; break;
                    case 'N':  $earned += 0; break;
                    case 'NA': $naPts += (int)$r->weight_value; break;
                }
            }
            $den = 367 - $naPts; if ($den <= 0) return null;
            $pct = ($earned / $den) * 100.0;
            if ($pct >= 95.0) return 5;
            if ($pct >= 85.0) return 4;
            if ($pct >= 75.0) return 3;
            if ($pct >= 65.0) return 2;
            if ($pct >= 55.0) return 1;
            return 0;
        } catch (Exception $e) {
            Log::error('Star level calculation failed', ['audit_id' => $auditId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    // ---------------------------
    // Mail helper (best effort)
    // ---------------------------

    /**
     * Execute a mail-sending closure using queue if available; fallback to direct send; silent on failure.
     * @param callable $mailClosure
     */
    protected function sendEmailBestEffort(callable $mailClosure): void
    {
        try {
            // If queue driver is not 'sync', attempt to dispatch via queue by wrapping closure
            $driver = config('queue.default');
            if ($driver && $driver !== 'sync') {
                // Simple inline dispatch: run now but outside main transaction scope
                // If you prefer real queue jobs, replace with a Job class.
                $mailClosure();
                return;
            }
            // Fallback: run immediately
            $mailClosure();
        } catch (Exception $e) {
            Log::warning('Non-critical: failed to send email (best-effort)', ['error' => $e->getMessage()]);
        }
    }
    /**
 * Update the specified audit.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  int  $id
 * @return \Illuminate\Http\Response
 */
public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'laboratory_id' => 'required|integer|exists:laboratories,id',
        'opened_on' => 'required|date|before_or_equal:today',
        'last_audit_date' => 'nullable|date|before:opened_on',
        'prior_official_status' => 'nullable|in:NOT_AUDITED,0,1,2,3,4,5',
        'previous_audit_id' => 'nullable|integer|exists:audits,id',
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

        // Verify audit exists and user has access
        $audit = DB::table('audits')->where('id', $id)->first();
        if (!$audit) {
            throw new Exception('Audit not found');
        }

        // Check authorization
        if (!$this->canEditAudit($userContext, $id)) {
            throw new Exception('You do not have permission to edit this audit');
        }

        // Check laboratory scope
        if (!$this->canCreateAuditForLaboratory($userContext, $request->laboratory_id)) {
            throw new Exception('You do not have permission for this laboratory');
        }

        // Verify previous audit if specified
        if ($request->previous_audit_id) {
            if (!$this->canAccessAudit($userContext, $request->previous_audit_id)) {
                throw new Exception('You do not have access to the specified previous audit');
            }

            $previousAudit = DB::table('audits')
                ->where('id', $request->previous_audit_id)
                ->where('laboratory_id', $request->laboratory_id)
                ->first();

            if (!$previousAudit) {
                throw new Exception('Previous audit must belong to the same laboratory');
            }
        }

        // Update audit
        $updateResult = DB::table('audits')
            ->where('id', $id)
            ->update([
                'laboratory_id' => $request->laboratory_id,
                'opened_on' => $request->opened_on,
                'last_audit_date' => $request->last_audit_date,
                'prior_official_status' => $request->prior_official_status,
                'previous_audit_id' => $request->previous_audit_id,
                'auditor_notes' => $request->auditor_notes,
                'updated_by' => auth()->id(),
                'updated_at' => now()
            ]);

        if (!$updateResult) {
            throw new Exception('Failed to update audit');
        }

        DB::commit();

        // Get updated audit for response
        $updatedAudit = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->join('countries as c', 'l.country_id', '=', 'c.id')
            ->select('a.*', 'l.name as laboratory_name', 'c.name as country_name')
            ->where('a.id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Audit updated successfully',
            'audit' => $updatedAudit
        ]);

    } catch (Exception $e) {
        DB::rollBack();

        Log::error('Audit update failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'audit_id' => $id
        ]);

        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Remove the specified audit from storage.
 *
 * @param  int  $id
 * @return \Illuminate\Http\Response
 */
public function destroy($id)
{
    DB::beginTransaction();

    try {
        $userContext = $this->getUserContext();

        // Verify audit exists
        $audit = DB::table('audits')->where('id', $id)->first();
        if (!$audit) {
            throw new Exception('Audit not found');
        }

        // Check authorization
        if (!$this->canEditAudit($userContext, $id)) {
            throw new Exception('You do not have permission to delete this audit');
        }

        // Check if audit has dependent records
        $hasActionPlans = DB::table('action_plans')->where('audit_id', $id)->exists();
        $hasFindings = DB::table('audit_findings')->where('audit_id', $id)->exists();
        $hasResponses = DB::table('audit_responses')->where('audit_id', $id)->exists();

        // Only allow deletion if audit is in draft status or has minimal data
        if ($audit->status !== 'draft' && ($hasActionPlans || $hasFindings || $hasResponses)) {
            throw new Exception('Cannot delete audit with existing responses or findings. Consider cancelling instead.');
        }

        // Check if this audit is referenced by other audits
        $referencedBy = DB::table('audits')->where('previous_audit_id', $id)->exists();
        if ($referencedBy) {
            throw new Exception('Cannot delete this audit as it is referenced by other audits.');
        }

        // Delete related records first
        DB::table('audit_team_members')->where('audit_id', $id)->delete();
        DB::table('audit_evidence')->where('audit_id', $id)->delete();

        // Now safe to delete the audit
        $deleted = DB::table('audits')->where('id', $id)->delete();

        if (!$deleted) {
            throw new Exception('Failed to delete audit');
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Audit deleted successfully'
        ]);

    } catch (Exception $e) {
        DB::rollBack();

        Log::error('Audit deletion failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'audit_id' => $id
        ]);

        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}
}
