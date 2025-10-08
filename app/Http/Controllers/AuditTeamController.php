<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * Audit Team Management Controller - Scope-Enforced Team Operations
 *
 * SOLE RESPONSIBILITY: Manage audit team assignments with strict scope validation
 *
 * BUSINESS RULES ENFORCED:
 * 1. Exactly ONE lead auditor per audit (mandatory)
 * 2. No duplicate user assignments
 * 3. All team members must be within user's authorization scope
 * 4. Team members must hold valid auditor credentials
 * 5. Audit creator remains in team (role can change)
 * 6. All operations logged and audited
 */
class AuditTeamController extends Controller
{
    // ========================================
    // VIEW: DISPLAY TEAM MANAGEMENT INTERFACE
    // ========================================

    /**
     * Display team management interface for a specific audit
     */
    public function index(Request $request)
    {
        try {
            $userContext = $this->getUserContext();

            // Get accessible audits for team management
            $audits = $this->getAccessibleAudits($userContext);

            // Get available auditors within scope
            $availableAuditors = $this->getScopedAuditors($userContext);

            // Get team statistics
            $teamStats = $this->getTeamStatistics($userContext);

            return view('audits.team-management', [
                'audits' => $audits,
                'availableAuditors' => $availableAuditors,
                'teamStats' => $teamStats,
                'userContext' => $userContext,
                'csrf' => csrf_token()
            ]);

        } catch (Exception $e) {
            Log::error('Team management view load failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Failed to load team management: ' . $e->getMessage());
        }
    }

    // ========================================
    // CORE TEAM OPERATIONS
    // ========================================

    /**
     * Get current team members for an audit (API endpoint)
     */
    public function getTeam(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'audit_id' => 'required|integer|exists:audits,id'
            ], [
                'audit_id.required' => 'Audit ID is required',
                'audit_id.exists' => 'Audit does not exist'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            $userContext = $this->getUserContext();

            // SCOPE VERIFICATION
            if (!$this->canAccessAudit($userContext, $request->audit_id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'AUTHORIZATION DENIED: Access denied to this audit'
                ], 403);
            }

            // Fetch team with user details
            $team = DB::table('audit_team_members as atm')
                ->join('users as u', 'atm.user_id', '=', 'u.id')
                ->where('atm.audit_id', $request->audit_id)
                ->select(
                    'atm.id',
                    'atm.user_id',
                    'atm.role',
                    'atm.created_at',
                    'u.name',
                    'u.email',
                    'u.organization'
                )
                ->orderByRaw("FIELD(atm.role, 'lead', 'member', 'observer')")
                ->get();

            return response()->json([
                'success' => true,
                'team' => $team,
                'team_size' => $team->count(),
                'has_lead' => $team->where('role', 'lead')->count() > 0
            ]);

        } catch (Exception $e) {
            Log::error('Get team failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'audit_id' => $request->audit_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load team: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign complete team to audit (replaces existing team except creator)
     */
    public function assignTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id' => 'required|integer|exists:audits,id',
            'team_members' => 'required|array|min:1',
            'team_members.*.user_id' => 'required|integer|exists:users,id',
            'team_members.*.role' => 'required|in:lead,member,observer'
        ], [
            'audit_id.required' => 'Audit selection is required',
            'audit_id.exists' => 'Selected audit does not exist',
            'team_members.required' => 'At least one team member is required',
            'team_members.min' => 'At least one team member must be assigned',
            'team_members.*.user_id.required' => 'User ID is required for each team member',
            'team_members.*.user_id.exists' => 'One or more selected users do not exist',
            'team_members.*.role.required' => 'Role is required for each team member',
            'team_members.*.role.in' => 'Invalid role (must be: lead, member, or observer)'
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed: ' . $validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $userContext = $this->getUserContext();

            // SCOPE VERIFICATION
            if (!$this->canAccessAudit($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: You do not have access to this audit (ID: ' . $request->audit_id . ')');
            }

            if (!$this->canManageTeam($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: You do not have permission to manage team for this audit');
            }

            // BUSINESS RULE: EXACTLY ONE LEAD AUDITOR
            $leadCount = collect($request->team_members)->where('role', 'lead')->count();
            if ($leadCount !== 1) {
                throw new Exception('TEAM COMPOSITION ERROR: Audit must have exactly one Lead Auditor (found: ' . $leadCount . ')');
            }

            // BUSINESS RULE: NO DUPLICATE USERS
            $userIds = collect($request->team_members)->pluck('user_id');
            if ($userIds->count() !== $userIds->unique()->count()) {
                throw new Exception('TEAM COMPOSITION ERROR: Cannot assign the same user multiple times');
            }

            // SCOPE VERIFICATION FOR ALL TEAM MEMBERS
            foreach ($request->team_members as $index => $member) {
                if (!$this->isAuditorInScope($userContext, $member['user_id'])) {
                    $user = DB::table('users')->where('id', $member['user_id'])->first();
                    $userName = $user ? $user->name : 'Unknown User';
                    throw new Exception('AUTHORIZATION DENIED: User "' . $userName . '" (ID: ' . $member['user_id'] . ') is not within your authorization scope');
                }

                if (!$this->isValidAuditor($member['user_id'])) {
                    $user = DB::table('users')->where('id', $member['user_id'])->first();
                    $userName = $user ? $user->name : 'Unknown User';
                    throw new Exception('QUALIFICATION ERROR: User "' . $userName . '" (ID: ' . $member['user_id'] . ') does not have appropriate auditor credentials');
                }
            }

            // FETCH AUDIT AND LABORATORY
            $audit = DB::table('audits')->where('id', $request->audit_id)->first();
            if (!$audit) {
                throw new Exception('DATABASE ERROR: Audit not found (ID: ' . $request->audit_id . ')');
            }

            $laboratory = DB::table('laboratories')->where('id', $audit->laboratory_id)->first();
            if (!$laboratory) {
                throw new Exception('DATABASE ERROR: Laboratory not found (ID: ' . $audit->laboratory_id . ')');
            }

            // REMOVE EXISTING TEAM MEMBERS (EXCEPT CREATOR)
            $deletedCount = DB::table('audit_team_members')
                ->where('audit_id', $request->audit_id)
                ->where('user_id', '!=', $audit->created_by)
                ->delete();

            Log::info('Removed existing team members', [
                'audit_id' => $request->audit_id,
                'deleted_count' => $deletedCount
            ]);

            $newMembers = [];
            $teamInserts = [];

            // PROCESS TEAM ASSIGNMENTS
            foreach ($request->team_members as $member) {
                if ($member['user_id'] == $audit->created_by) {
                    // UPDATE CREATOR'S ROLE
                    $updateResult = DB::table('audit_team_members')
                        ->where('audit_id', $request->audit_id)
                        ->where('user_id', $audit->created_by)
                        ->update([
                            'role' => $member['role'],
                            'updated_at' => now()
                        ]);

                    if ($updateResult === false) {
                        throw new Exception('DATABASE ERROR: Failed to update creator role');
                    }

                    continue;
                }

                $teamInserts[] = [
                    'audit_id' => $request->audit_id,
                    'user_id' => $member['user_id'],
                    'role' => $member['role'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $newMembers[] = [
                    'user_id' => $member['user_id'],
                    'role' => $member['role']
                ];
            }

            // INSERT NEW TEAM MEMBERS
            if (!empty($teamInserts)) {
                $insertResult = DB::table('audit_team_members')->insert($teamInserts);
                if (!$insertResult) {
                    throw new Exception('DATABASE ERROR: Failed to insert team members');
                }
            }

            // UPDATE AUDIT METADATA
            $auditUpdateResult = DB::table('audits')
                ->where('id', $request->audit_id)
                ->update([
                    'updated_by' => auth()->id(),
                    'updated_at' => now()
                ]);

            if ($auditUpdateResult === false) {
                throw new Exception('DATABASE ERROR: Failed to update audit metadata');
            }

            DB::commit();

            // ASYNC MAIL NOTIFICATIONS (NON-BLOCKING)
            foreach ($newMembers as $memberData) {
                $user = DB::table('users')->where('id', $memberData['user_id'])->first();
                if ($user) {
                    $this->dispatchTeamAssignmentEmail($user, $audit, $laboratory, $memberData['role']);
                }
            }

            Log::info('Audit team assigned successfully', [
                'audit_id' => $request->audit_id,
                'team_size' => count($request->team_members),
                'new_members' => count($newMembers),
                'assigned_by' => auth()->id()
            ]);

            return back()->with('success', 'Audit team assigned successfully (' . count($request->team_members) . ' members)');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Team assignment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'audit_id' => $request->audit_id ?? null,
                'team_members' => $request->team_members ?? []
            ]);

            return back()
                ->with('error', 'TEAM ASSIGNMENT FAILED: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Add single team member to existing team
     */
    public function addMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id' => 'required|integer|exists:audits,id',
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|in:lead,member,observer'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', $validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $userContext = $this->getUserContext();

            // SCOPE CHECKS
            if (!$this->canAccessAudit($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: Access denied to this audit');
            }

            if (!$this->canManageTeam($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: Cannot manage team for this audit');
            }

            if (!$this->isAuditorInScope($userContext, $request->user_id)) {
                throw new Exception('AUTHORIZATION DENIED: User not in your scope');
            }

            if (!$this->isValidAuditor($request->user_id)) {
                throw new Exception('QUALIFICATION ERROR: User does not have auditor credentials');
            }

            // CHECK IF USER ALREADY IN TEAM
            $existingMember = DB::table('audit_team_members')
                ->where('audit_id', $request->audit_id)
                ->where('user_id', $request->user_id)
                ->first();

            if ($existingMember) {
                throw new Exception('DUPLICATE ERROR: User is already a team member');
            }

            // IF ADDING LEAD, CHECK CURRENT LEAD COUNT
            if ($request->role === 'lead') {
                $currentLeadCount = DB::table('audit_team_members')
                    ->where('audit_id', $request->audit_id)
                    ->where('role', 'lead')
                    ->count();

                if ($currentLeadCount > 0) {
                    throw new Exception('TEAM COMPOSITION ERROR: Audit already has a Lead Auditor. Remove existing lead first or assign as member/observer.');
                }
            }

            // INSERT NEW MEMBER
            $insertResult = DB::table('audit_team_members')->insert([
                'audit_id' => $request->audit_id,
                'user_id' => $request->user_id,
                'role' => $request->role,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            if (!$insertResult) {
                throw new Exception('DATABASE ERROR: Failed to add team member');
            }

            DB::commit();

            // ASYNC EMAIL NOTIFICATION
            $user = DB::table('users')->where('id', $request->user_id)->first();
            $audit = DB::table('audits')->where('id', $request->audit_id)->first();
            $laboratory = DB::table('laboratories')->where('id', $audit->laboratory_id)->first();

            if ($user && $audit && $laboratory) {
                $this->dispatchTeamAssignmentEmail($user, $audit, $laboratory, $request->role);
            }

            Log::info('Team member added', [
                'audit_id' => $request->audit_id,
                'user_id' => $request->user_id,
                'role' => $request->role,
                'added_by' => auth()->id()
            ]);

            return back()->with('success', 'Team member added successfully');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Add member failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'audit_id' => $request->audit_id ?? null
            ]);

            return back()->with('error', 'ADD MEMBER FAILED: ' . $e->getMessage());
        }
    }

    /**
     * Remove team member from audit
     */
    public function removeMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id' => 'required|integer|exists:audits,id',
            'user_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)
                ->with('error', $validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $userContext = $this->getUserContext();

            // SCOPE CHECKS
            if (!$this->canAccessAudit($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: Access denied to this audit');
            }

            if (!$this->canManageTeam($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: Cannot manage team for this audit');
            }

            // GET AUDIT CREATOR
            $audit = DB::table('audits')->where('id', $request->audit_id)->first();
            if (!$audit) {
                throw new Exception('DATABASE ERROR: Audit not found');
            }

            // PREVENT REMOVING AUDIT CREATOR
            if ($request->user_id == $audit->created_by) {
                throw new Exception('BUSINESS RULE VIOLATION: Cannot remove audit creator from team. You can change their role instead.');
            }

            // GET MEMBER TO REMOVE
            $member = DB::table('audit_team_members')
                ->where('audit_id', $request->audit_id)
                ->where('user_id', $request->user_id)
                ->first();

            if (!$member) {
                throw new Exception('NOT FOUND: User is not a member of this audit team');
            }

            // PREVENT REMOVING LAST LEAD AUDITOR
            if ($member->role === 'lead') {
                $leadCount = DB::table('audit_team_members')
                    ->where('audit_id', $request->audit_id)
                    ->where('role', 'lead')
                    ->count();

                if ($leadCount <= 1) {
                    throw new Exception('BUSINESS RULE VIOLATION: Cannot remove the only Lead Auditor. Assign another lead first.');
                }
            }

            // DELETE MEMBER
            $deleteResult = DB::table('audit_team_members')
                ->where('audit_id', $request->audit_id)
                ->where('user_id', $request->user_id)
                ->delete();

            if (!$deleteResult) {
                throw new Exception('DATABASE ERROR: Failed to remove team member');
            }

            DB::commit();

            Log::info('Team member removed', [
                'audit_id' => $request->audit_id,
                'user_id' => $request->user_id,
                'removed_by' => auth()->id()
            ]);

            return back()->with('success', 'Team member removed successfully');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Remove member failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'audit_id' => $request->audit_id ?? null
            ]);

            return back()->with('error', 'REMOVE MEMBER FAILED: ' . $e->getMessage());
        }
    }

    /**
     * Update team member role
     */
    public function updateMemberRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id' => 'required|integer|exists:audits,id',
            'user_id' => 'required|integer|exists:users,id',
            'new_role' => 'required|in:lead,member,observer'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)
                ->with('error', $validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $userContext = $this->getUserContext();

            // SCOPE CHECKS
            if (!$this->canAccessAudit($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: Access denied to this audit');
            }

            if (!$this->canManageTeam($userContext, $request->audit_id)) {
                throw new Exception('AUTHORIZATION DENIED: Cannot manage team for this audit');
            }

            // GET CURRENT MEMBER
            $member = DB::table('audit_team_members')
                ->where('audit_id', $request->audit_id)
                ->where('user_id', $request->user_id)
                ->first();

            if (!$member) {
                throw new Exception('NOT FOUND: User is not a member of this audit team');
            }

            // IF NO CHANGE, SKIP
            if ($member->role === $request->new_role) {
                return back()->with('info', 'No change: User already has this role');
            }

            // IF PROMOTING TO LEAD, CHECK IF LEAD EXISTS
            if ($request->new_role === 'lead') {
                $currentLeadCount = DB::table('audit_team_members')
                    ->where('audit_id', $request->audit_id)
                    ->where('role', 'lead')
                    ->where('user_id', '!=', $request->user_id)
                    ->count();

                if ($currentLeadCount > 0) {
                    throw new Exception('BUSINESS RULE VIOLATION: Audit already has a Lead Auditor. Remove existing lead role first.');
                }
            }

            // IF DEMOTING FROM LEAD, ENSURE ANOTHER LEAD EXISTS
            if ($member->role === 'lead' && $request->new_role !== 'lead') {
                $otherLeadCount = DB::table('audit_team_members')
                    ->where('audit_id', $request->audit_id)
                    ->where('role', 'lead')
                    ->where('user_id', '!=', $request->user_id)
                    ->count();

                if ($otherLeadCount === 0) {
                    throw new Exception('BUSINESS RULE VIOLATION: Cannot demote the only Lead Auditor. Assign another lead first.');
                }
            }

            // UPDATE ROLE
            $updateResult = DB::table('audit_team_members')
                ->where('audit_id', $request->audit_id)
                ->where('user_id', $request->user_id)
                ->update([
                    'role' => $request->new_role,
                    'updated_at' => now()
                ]);

            if ($updateResult === false) {
                throw new Exception('DATABASE ERROR: Failed to update role');
            }

            DB::commit();

            Log::info('Team member role updated', [
                'audit_id' => $request->audit_id,
                'user_id' => $request->user_id,
                'old_role' => $member->role,
                'new_role' => $request->new_role,
                'updated_by' => auth()->id()
            ]);

            return back()->with('success', 'Team member role updated successfully');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Update role failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'audit_id' => $request->audit_id ?? null
            ]);

            return back()->with('error', 'UPDATE ROLE FAILED: ' . $e->getMessage());
        }
    }

    // ========================================
    // SCOPE ENFORCEMENT & ACCESS CONTROL
    // ========================================

    /**
     * Get user context with roles and scope
     */
    protected function getUserContext(): array
    {
        try {
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
        } catch (Exception $e) {
            Log::error('Failed to get user context', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? 'not_authenticated'
            ]);
            throw $e;
        }
    }

    /**
     * Check if user can access audit
     */
    protected function canAccessAudit(array $userContext, int $auditId): bool
    {
        try {
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
        } catch (Exception $e) {
            Log::error('Failed to check audit access', [
                'error' => $e->getMessage(),
                'audit_id' => $auditId
            ]);
            return false;
        }
    }

    /**
     * Check if user can manage team for audit
     */
    protected function canManageTeam(array $userContext, int $auditId): bool
    {
        try {
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
        } catch (Exception $e) {
            Log::error('Failed to check team management permission', [
                'error' => $e->getMessage(),
                'audit_id' => $auditId
            ]);
            return false;
        }
    }

    /**
     * Check if auditor is within user's scope
     */
    protected function isAuditorInScope(array $userContext, int $userId): bool
    {
        try {
            if ($userContext['has_global_access']) {
                return true;
            }

            if ($userContext['is_country_coordinator']) {
                $auditorInScope = DB::table('user_roles as ur')
                    ->where('ur.user_id', $userId)
                    ->where('ur.is_active', 1)
                    ->where(function($q) use ($userContext) {
                        $q->whereIn('ur.country_id', $userContext['country_ids'])
                          ->orWhereNull('ur.country_id');
                    })
                    ->exists();

                return $auditorInScope;
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to check auditor scope', [
                'error' => $e->getMessage(),
                'auditor_user_id' => $userId
            ]);
            return false;
        }
    }

    /**
     * Check if user is valid auditor
     */
    protected function isValidAuditor(int $userId): bool
    {
        try {
            return DB::table('user_roles as ur')
                ->join('roles as r', 'ur.role_id', '=', 'r.id')
                ->where('ur.user_id', $userId)
                ->where('ur.is_active', 1)
                ->whereIn('r.name', ['lead_auditor', 'auditor', 'observer'])
                ->exists();
        } catch (Exception $e) {
            Log::error('Failed to validate auditor', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get accessible audits for team management
     */
    protected function getAccessibleAudits(array $userContext)
    {
        try {
            $query = DB::table('audits as a')
                ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
                ->join('countries as c', 'l.country_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.opened_on',
                    'a.status',
                    'a.laboratory_id',
                    'l.name as laboratory_name',
                    'c.name as country_name'
                );

            // Apply scope filtering
            if (!$userContext['has_global_access']) {
                if ($userContext['is_country_coordinator']) {
                    $query->whereIn('l.country_id', $userContext['country_ids']);
                } elseif ($userContext['is_lead_auditor'] || $userContext['is_auditor']) {
                    $query->where(function($q) use ($userContext) {
                        $q->whereIn('a.laboratory_id', $userContext['laboratory_ids'])
                          ->orWhereExists(function($subq) use ($userContext) {
                              $subq->from('audit_team_members')
                                   ->whereColumn('audit_team_members.audit_id', '=', 'a.id')
                                   ->where('audit_team_members.user_id', $userContext['user']->id);
                          });
                    });
                } else {
                    $query->whereIn('a.laboratory_id', $userContext['laboratory_ids']);
                }
            }

            return $query->orderBy('a.opened_on', 'desc')->get();
        } catch (Exception $e) {
            Log::error('Failed to get accessible audits', [
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get scoped auditors
     */
    protected function getScopedAuditors(array $userContext)
    {
        try {
            $query = DB::table('users as u')
                ->join('user_roles as ur', 'u.id', '=', 'ur.user_id')
                ->join('roles as r', 'ur.role_id', '=', 'r.id')
                ->where('u.is_active', 1)
                ->where('ur.is_active', 1)
                ->whereIn('r.name', ['lead_auditor', 'auditor', 'observer'])
                ->select('u.id', 'u.name', 'u.email', 'u.organization', 'r.name as role_name')
                ->distinct();

            if ($userContext['has_global_access']) {
                return $query->orderBy('u.name')->get();
            }

            if ($userContext['is_country_coordinator']) {
                $query->where(function($q) use ($userContext) {
                    $q->whereIn('ur.country_id', $userContext['country_ids'])
                      ->orWhereNull('ur.country_id');
                });
            }

            return $query->orderBy('u.name')->get();
        } catch (Exception $e) {
            Log::error('Failed to get scoped auditors', [
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get team statistics
     */
    protected function getTeamStatistics(array $userContext): array
    {
        try {
            $query = DB::table('audit_team_members as atm')
                ->join('audits as a', 'atm.audit_id', '=', 'a.id')
                ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

            // Apply scope
            if (!$userContext['has_global_access']) {
                if ($userContext['is_country_coordinator']) {
                    $query->whereIn('l.country_id', $userContext['country_ids']);
                } else {
                    $query->whereIn('a.laboratory_id', $userContext['laboratory_ids']);
                }
            }

            $totalMembers = (clone $query)->count();

            $roleDistribution = (clone $query)
                ->select('atm.role', DB::raw('COUNT(*) as count'))
                ->groupBy('atm.role')
                ->get()
                ->keyBy('role');

            $avgTeamSize = DB::table('audits as a')
                ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
                ->join('audit_team_members as atm', 'a.id', '=', 'atm.audit_id')
                ->when(!$userContext['has_global_access'], function($q) use ($userContext) {
                    if ($userContext['is_country_coordinator']) {
                        $q->whereIn('l.country_id', $userContext['country_ids']);
                    } else {
                        $q->whereIn('a.laboratory_id', $userContext['laboratory_ids']);
                    }
                })
                ->select('a.id', DB::raw('COUNT(atm.id) as team_size'))
                ->groupBy('a.id')
                ->get()
                ->avg('team_size');

            return [
                'total_members' => $totalMembers,
                'lead_count' => $roleDistribution->get('lead')->count ?? 0,
                'member_count' => $roleDistribution->get('member')->count ?? 0,
                'observer_count' => $roleDistribution->get('observer')->count ?? 0,
                'avg_team_size' => round($avgTeamSize ?? 0, 1)
            ];
        } catch (Exception $e) {
            Log::error('Failed to get team statistics', [
                'error' => $e->getMessage()
            ]);
            return [
                'total_members' => 0,
                'lead_count' => 0,
                'member_count' => 0,
                'observer_count' => 0,
                'avg_team_size' => 0
            ];
        }
    }

    // ========================================
    // EMAIL NOTIFICATIONS (ASYNC + SILENT)
    // ========================================

    /**
     * Dispatch team assignment email (non-blocking)
     */
    protected function dispatchTeamAssignmentEmail($user, $audit, $laboratory, $role)
    {
        try {
            if (!$user || !$audit || !$laboratory) {
                throw new Exception('Missing data for email notification');
            }

            $subject = 'SLIPTA Audit Team Assignment - ' . $laboratory->name;
            $roleLabels = [
                'lead' => 'Lead Auditor',
                'member' => 'Team Member',
                'observer' => 'Observer'
            ];

            $body = "Dear {$user->name},\n\n";
            $body .= "You have been assigned to a SLIPTA audit team.\n\n";
            $body .= "Laboratory: {$laboratory->name}\n";
            $body .= "Audit Date: " . date('l, F j, Y', strtotime($audit->opened_on)) . "\n";
            $body .= "Your Role: {$roleLabels[$role]}\n\n";
            $body .= "Best regards,\nSLIPTA Digital Assessment System";

            Mail::raw($body, function($message) use ($user, $subject) {
                $message->to($user->email, $user->name)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info('Team assignment email sent', [
                'audit_id' => $audit->id,
                'recipient' => $user->email,
                'role' => $role
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to send team assignment email (non-critical)', [
                'audit_id' => $audit->id ?? null,
                'error' => $e->getMessage(),
                'recipient' => $user->email ?? 'unknown'
            ]);
        }
    }
}
