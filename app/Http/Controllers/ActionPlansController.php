<?php
namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActionPlansController extends Controller
{
    // ════════════════════════════════════════════════════════════════════
    // DUAL RESPONSE HANDLER (AJAX + NATIVE)
    // ════════════════════════════════════════════════════════════════════

    private function respond($success, $data = [], $redirectRoute = null, $redirectId = null)
    {
        if (request()->ajax() || request()->wantsJson()) {
            $status = $success ? 200 : (isset($data['code']) && is_int($data['code']) ? $data['code'] : 400);
            return response()->json($success ? ['success' => true] + $data : ['success' => false] + $data, $status);
        }

        if ($success) {
            $route = $redirectRoute ?? 'action-plans.index';
            $url   = $redirectId ? route($route, $redirectId) : route($route);
            return redirect($url)->with('success', $data['message'] ?? 'Operation completed successfully');
        } else {
            return back()->withErrors(['error' => $data['error'] ?? 'Operation failed'])->withInput();
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // RBAC & VALIDATION HELPERS
    // ════════════════════════════════════════════════════════════════════

    private function getUserContext(): array
    {
        $userId = auth()->id();
        if (! $userId) {
            abort(401);
        }

        $roles = DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $userId)
            ->where('ur.is_active', 1)
            ->select('r.name as role_name', 'ur.country_id', 'ur.laboratory_id')
            ->get();

        $roleNames = $roles->pluck('role_name')->unique()->toArray();

        return [
            'user_id'          => $userId,
            'roles'            => $roleNames,
            'is_admin'         => in_array('system_admin', $roleNames) || in_array('project_coordinator', $roleNames),
            'is_country_coord' => in_array('country_coordinator', $roleNames),
            'is_auditor'       => in_array('lead_auditor', $roleNames) || in_array('auditor', $roleNames),
            'is_lab_role'      => in_array('laboratory_manager', $roleNames) || in_array('quality_officer', $roleNames),
            'country_ids'      => $roles->pluck('country_id')->filter()->unique()->toArray(),
            'lab_ids'          => $roles->pluck('laboratory_id')->filter()->unique()->toArray(),
        ];
    }

    private function applyScopeFilter($query, array $ctx, string $auditAlias = 'a', string $labAlias = 'l')
    {
        if ($ctx['is_admin']) {
            return $query;
        }

        if ($ctx['is_country_coord']) {
            return $query->whereIn("$labAlias.country_id", $ctx['country_ids']);
        }

        if ($ctx['is_auditor']) {
            return $query->where(function ($q) use ($ctx, $auditAlias, $labAlias) {
                $q->whereIn("$auditAlias.laboratory_id", $ctx['lab_ids'])
                    ->orWhereExists(function ($sub) use ($ctx, $auditAlias) {
                        $sub->from('audit_team_members')
                            ->whereColumn('audit_team_members.audit_id', "$auditAlias.id")
                            ->where('audit_team_members.user_id', $ctx['user_id']);
                    });
            });
        }

        if ($ctx['is_lab_role']) {
            return $query->whereIn("$auditAlias.laboratory_id", $ctx['lab_ids']);
        }

        return $query->whereRaw('1=0');
    }

    private function assertAuditAccess(int $auditId, array $ctx): void
    {
        $row = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->select(
                'l.country_id',
                'a.laboratory_id',
                'a.status as audit_status'
            )
            ->addSelect(DB::raw('EXISTS(
                SELECT 1 FROM audit_team_members atm
                WHERE atm.audit_id = a.id AND atm.user_id = ?
            ) as on_team'), [$ctx['user_id']])
            ->where('a.id', $auditId)
            ->first();

        if (! $row) {
            abort(404, 'Audit not found');
        }

        if ($row->audit_status === 'cancelled') {
            abort(422, 'Cannot create action plans for cancelled audits');
        }

        $allowed =
            $ctx['is_admin'] ||
            ($ctx['is_country_coord'] && in_array($row->country_id, $ctx['country_ids'])) ||
            ($ctx['is_auditor'] && ((int) $row->on_team === 1 || in_array($row->laboratory_id, $ctx['lab_ids']))) ||
            ($ctx['is_lab_role'] && in_array($row->laboratory_id, $ctx['lab_ids']));

        if (! $allowed) {
            abort(403, 'Forbidden: Insufficient audit access');
        }

    }

    private function canEditPlan(object $plan, array $ctx): bool
    {
        if ($ctx['is_admin']) {
            return true;
        }

        if ($ctx['is_country_coord'] && in_array($plan->country_id, $ctx['country_ids'])) {
            return true;
        }

        if ($ctx['is_auditor']) {
            $onTeam = DB::table('audit_team_members')->where('audit_id', $plan->audit_id)
                ->where('user_id', $ctx['user_id'])->exists();
            if ($onTeam || in_array($plan->laboratory_id, $ctx['lab_ids'])) {
                return true;
            }

        }

        if ($ctx['is_lab_role'] && in_array($plan->laboratory_id, $ctx['lab_ids'])) {
            return true;
        }

        return false;
    }

    private function validateStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $validTransitions = [
            'open'        => ['in_progress', 'deferred'],
            'in_progress' => ['closed', 'deferred', 'open'],
            'closed'      => [], // Only admin/coordinator can reopen
            'deferred'    => ['open', 'in_progress'],
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }

    // ════════════════════════════════════════════════════════════════════
    // VIEW: INDEX
    // ════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $ctx = $this->getUserContext();

        $base = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $base = $this->applyScopeFilter($base, $ctx, 'a', 'l');

        $query = clone $base;
        $query->leftJoin('users as u', 'ap.responsible_user_id', '=', 'u.id')
            ->leftJoin('slipta_sections as s', 'ap.section_id', '=', 's.id')
            ->leftJoin('audit_findings as f', 'ap.finding_id', '=', 'f.id')
            ->leftJoin('countries as c', 'l.country_id', '=', 'c.id');

        // Country filter for admins
        if ($ctx['is_admin'] && $request->filled('country_id')) {
            $query->where('l.country_id', (int) $request->country_id);
        }

        // Tab filter (my_plans, overdue)
        if ($request->get('tab') === 'my_plans') {
            $query->where('ap.responsible_user_id', $ctx['user_id']);
        } elseif ($request->get('tab') === 'overdue') {
            $query->whereDate('ap.due_date', '<', today())
                ->whereNotIn('ap.status', ['closed', 'deferred']);
        }

        // Standard filters
        if ($request->filled('audit_id')) {
            $query->where('ap.audit_id', (int) $request->audit_id);
        }

        if ($request->filled('status')) {
            $query->where('ap.status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('ap.type', $request->type);
        }

        if ($request->filled('responsible_user_id')) {
            $query->where('ap.responsible_user_id', (int) $request->responsible_user_id);
        }

        if ($request->filled('section_id')) {
            $query->where('ap.section_id', (int) $request->section_id);
        }

        $plans = $query->select([
            'ap.*', 'l.name as lab_name', 'c.name as country_name', 'u.name as responsible_name',
            's.title as section_title', 's.code as section_code', 'f.title as finding_title', 'a.opened_on',
            DB::raw('DATEDIFF(ap.due_date, CURDATE()) as days_until_due'),
        ])->orderBy('ap.due_date')->paginate(50);

        $meta = [
            'total'       => (clone $base)->count(),
            'open'        => (clone $base)->where('ap.status', 'open')->count(),
            'in_progress' => (clone $base)->where('ap.status', 'in_progress')->count(),
            'closed'      => (clone $base)->where('ap.status', 'closed')->count(),
            'deferred'    => (clone $base)->where('ap.status', 'deferred')->count(),
            'overdue'     => (clone $base)->whereDate('ap.due_date', '<', today())->whereNotIn('ap.status', ['closed', 'deferred'])->count(),
            'my_plans'    => (clone $base)->where('ap.responsible_user_id', $ctx['user_id'])->count(),
        ];

        $countries = $ctx['is_admin'] ? DB::table('countries')->orderBy('name')->get() : collect();
        $sections  = DB::table('slipta_sections')->orderBy('code')->get();
        $users     = DB::table('users')->where('is_active', 1)->orderBy('name')->get();

        if (request()->wantsJson()) {
            return response()->json(compact('plans', 'meta'));
        }

        return view('action_plans.index', compact('plans', 'meta', 'ctx', 'countries', 'sections', 'users'));
    }

    // ════════════════════════════════════════════════════════════════════
    // VIEW: SHOW
    // ════════════════════════════════════════════════════════════════════

    public function show($id)
    {
        $ctx = $this->getUserContext();
        $id  = (int) $id;

        $query = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->leftJoin('users as u', 'ap.responsible_user_id', '=', 'u.id')
            ->leftJoin('slipta_sections as s', 'ap.section_id', '=', 's.id')
            ->leftJoin('slipta_questions as q', 'ap.question_id', '=', 'q.id')
            ->leftJoin('audit_findings as f', 'ap.finding_id', '=', 'f.id')
            ->leftJoin('countries as c', 'l.country_id', '=', 'c.id')
            ->where('ap.id', $id);

        $query = $this->applyScopeFilter($query, $ctx, 'a', 'l');

        $plan = $query->select(
            'ap.*', 'a.id as audit_id', 'a.opened_on', 'a.status as audit_status',
            'l.id as laboratory_id', 'l.name as lab_name', 'l.country_id',
            'c.name as country_name', 'u.name as responsible_name', 'u.email as responsible_email',
            's.title as section_title', 's.code as section_code',
            'q.q_code', 'q.text as question_text',
            'f.id as finding_id', 'f.title as finding_title', 'f.description as finding_description',
            DB::raw('DATEDIFF(ap.due_date, CURDATE()) as days_until_due')
        )->first();

        if (! $plan) {
            abort(404);
        }

        $canEdit   = $this->canEditPlan($plan, $ctx);
        $canClose  = $canEdit && $plan->status === 'in_progress';
        $canReopen = ($ctx['is_admin'] || $ctx['is_country_coord']) && $plan->status === 'closed';

        if (request()->wantsJson()) {
            return response()->json(compact('plan', 'canEdit', 'canClose', 'canReopen'));
        }

        return view('action_plans.show', compact('plan', 'ctx', 'canEdit', 'canClose', 'canReopen'));
    }

    // ════════════════════════════════════════════════════════════════════
    // VIEW: CREATE
    // ════════════════════════════════════════════════════════════════════

    public function create(Request $request)
    {
        $ctx = $this->getUserContext();

        if (! ($ctx['is_admin'] || $ctx['is_country_coord'] || $ctx['is_auditor'])) {
            abort(403);
        }

        $auditsQuery = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->whereIn('a.status', ['draft', 'in_progress', 'completed']);

        $auditsQuery = $this->applyScopeFilter($auditsQuery, $ctx, 'a', 'l');

        $audits = $auditsQuery->select('a.id', 'l.name as lab_name', 'a.opened_on', 'a.status')
            ->orderBy('a.opened_on', 'desc')
            ->get();

        $sections = DB::table('slipta_sections')->orderBy('code')->get();
        $users    = DB::table('users')->where('is_active', 1)->orderBy('name')->get();

        // Pre-fill from query params
        $prefilledAuditId   = $request->get('audit_id');
        $prefilledFindingId = $request->get('finding_id');

        return view('action_plans.create', compact('audits', 'sections', 'users', 'prefilledAuditId', 'prefilledFindingId', 'ctx'));
    }

    // ════════════════════════════════════════════════════════════════════
    // ACTION: STORE
    // ════════════════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $ctx = $this->getUserContext();

        if (! ($ctx['is_admin'] || $ctx['is_country_coord'] || $ctx['is_auditor'])) {
            return $this->respond(false, ['error' => 'Forbidden', 'code' => 403]);
        }

        $validated = $request->validate([
            'audit_id'            => 'required|integer|exists:audits,id',
            'finding_id'          => 'nullable|integer|exists:audit_findings,id',
            'section_id'          => 'nullable|integer|exists:slipta_sections,id',
            'question_id'         => 'nullable|integer|exists:slipta_questions,id',
            'type'                => 'required|in:finding,risk_opportunity,other',
            'recommendation'      => 'required|string|min:20|max:5000',
            'responsible_user_id' => 'nullable|integer|exists:users,id',
            'due_date'            => 'required|date|after:today',
        ]);

        try {
            $this->assertAuditAccess($validated['audit_id'], $ctx);
        } catch (Exception $e) {
            return $this->respond(false, ['error' => $e->getMessage(), 'code' => $e->getCode() ?: 403]);
        }

        // Edge case: Validate finding belongs to audit
        if ($validated['finding_id']) {
            $findingAuditId = DB::table('audit_findings')->where('id', $validated['finding_id'])->value('audit_id');
            if ($findingAuditId != $validated['audit_id']) {
                return $this->respond(false, ['error' => 'Finding does not belong to selected audit', 'code' => 422]);
            }
        }

        // Edge case: Check for duplicate action plan
        $duplicate = DB::table('action_plans')
            ->where('audit_id', $validated['audit_id'])
            ->where('finding_id', $validated['finding_id'])
            ->whereIn('status', ['open', 'in_progress'])
            ->exists();

        if ($duplicate && $validated['finding_id']) {
            return $this->respond(false, ['error' => 'Active action plan already exists for this finding', 'code' => 422]);
        }

        DB::beginTransaction();
        try {
            $validated['status']     = 'open';
            $validated['created_at'] = now();
            $validated['updated_at'] = now();

            $id = DB::table('action_plans')->insertGetId($validated);

            Log::info('Action plan created', ['id' => $id, 'audit_id' => $validated['audit_id'], 'by' => $ctx['user_id']]);

            DB::commit();
            return $this->respond(true, ['id' => $id, 'message' => 'Action plan created successfully'], 'action-plans.show', $id);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Action plan creation failed', ['error' => $e->getMessage()]);
            return $this->respond(false, ['error' => $e->getMessage()]);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // VIEW: EDIT
    // ════════════════════════════════════════════════════════════════════

    public function edit($id)
    {
        $ctx = $this->getUserContext();
        $id  = (int) $id;

        $plan = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('ap.id', $id)
            ->select('ap.*', 'a.id as audit_id', 'l.id as laboratory_id', 'l.country_id', 'l.name as lab_name')
            ->first();

        if (! $plan) {
            abort(404);
        }

        if (! $this->canEditPlan($plan, $ctx)) {
            abort(403);
        }

        $sections = DB::table('slipta_sections')->orderBy('code')->get();
        $users    = DB::table('users')->where('is_active', 1)->orderBy('name')->get();

        return view('action_plans.edit', compact('plan', 'sections', 'users', 'ctx'));
    }

    // ════════════════════════════════════════════════════════════════════
    // ACTION: UPDATE
    // ════════════════════════════════════════════════════════════════════

    public function update(Request $request, $id)
    {
        $ctx = $this->getUserContext();
        $id  = (int) $id;

        $plan = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('ap.id', $id)
            ->select('ap.*', 'a.id as audit_id', 'l.id as laboratory_id', 'l.country_id')
            ->first();

        if (! $plan) {
            return $this->respond(false, ['error' => 'Not found', 'code' => 404]);
        }

        if (! $this->canEditPlan($plan, $ctx)) {
            return $this->respond(false, ['error' => 'Forbidden', 'code' => 403]);
        }

        $validated = $request->validate([
            'recommendation'      => 'sometimes|string|min:20|max:5000',
            'responsible_user_id' => 'sometimes|nullable|integer|exists:users,id',
            'due_date'            => 'sometimes|date',
            'status'              => 'sometimes|in:open,in_progress,closed,deferred',
            'resolution_notes'    => 'sometimes|nullable|string|max:5000',
        ]);

        // Edge case: Validate status transition
        if (isset($validated['status']) && $validated['status'] !== $plan->status) {
            if (! $this->validateStatusTransition($plan->status, $validated['status'])) {
                return $this->respond(false, ['error' => "Invalid status transition from {$plan->status} to {$validated['status']}", 'code' => 422]);
            }
        }

        // Edge case: Closing requires resolution notes
        if (isset($validated['status']) && $validated['status'] === 'closed') {
            if (empty($validated['resolution_notes'])) {
                return $this->respond(false, ['error' => 'resolution_notes required for closed status', 'code' => 422]);
            }
            $validated['closed_at'] = now();
        }

        // Edge case: Due date cannot be in past
        if (isset($validated['due_date']) && strtotime($validated['due_date']) < strtotime('today')) {
            return $this->respond(false, ['error' => 'Due date cannot be in the past', 'code' => 422]);
        }

        $validated['updated_at'] = now();
        DB::table('action_plans')->where('id', $id)->update($validated);

        Log::info('Action plan updated', ['id' => $id, 'audit_id' => $plan->audit_id, 'by' => $ctx['user_id'], 'changes' => array_keys($validated)]);

        return $this->respond(true, ['message' => 'Action plan updated successfully'], 'action-plans.show', $id);
    }

    // ════════════════════════════════════════════════════════════════════
    // ACTION: DESTROY
    // ════════════════════════════════════════════════════════════════════

    public function destroy(Request $request, $id)
    {
        $ctx = $this->getUserContext();
        if (! $ctx['is_admin']) {
            return $this->respond(false, ['error' => 'Forbidden', 'code' => 403]);
        }

        $request->validate(['justification' => 'required|string|min:50|max:1000']);

        $plan = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->where('ap.id', (int) $id)
            ->select('ap.*', 'a.id as audit_id')
            ->first();

        if (! $plan) {
            return $this->respond(false, ['error' => 'Not found', 'code' => 404]);
        }

        // Edge case: Cannot delete closed action plans (data integrity)
        if ($plan->status === 'closed') {
            return $this->respond(false, ['error' => 'Cannot delete closed action plans. Use reopen instead.', 'code' => 422]);
        }

        DB::table('action_plans')->where('id', (int) $id)->delete();

        Log::warning('Action plan deleted', ['id' => $id, 'audit_id' => $plan->audit_id, 'by' => $ctx['user_id'], 'justification' => $request->justification]);

        return $this->respond(true, ['message' => 'Action plan deleted']);
    }

    // ════════════════════════════════════════════════════════════════════
    // VIEW: DASHBOARD
    // ════════════════════════════════════════════════════════════════════

    public function dashboard(Request $request)
    {
        $ctx = $this->getUserContext();

        $base = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $base = $this->applyScopeFilter($base, $ctx, 'a', 'l');

        $stats = [
            'total'         => (clone $base)->count(),
            'open'          => (clone $base)->where('ap.status', 'open')->count(),
            'in_progress'   => (clone $base)->where('ap.status', 'in_progress')->count(),
            'closed'        => (clone $base)->where('ap.status', 'closed')->count(),
            'deferred'      => (clone $base)->where('ap.status', 'deferred')->count(),
            'overdue'       => (clone $base)->whereDate('ap.due_date', '<', today())->whereNotIn('ap.status', ['closed', 'deferred'])->count(),
            'due_this_week' => (clone $base)->whereBetween('ap.due_date', [today(), today()->addDays(7)])->whereNotIn('ap.status', ['closed'])->count(),
            'my_plans'      => (clone $base)->where('ap.responsible_user_id', $ctx['user_id'])->whereNotIn('ap.status', ['closed'])->count(),
        ];

        $byType = (clone $base)->select('ap.type', DB::raw('count(*) as count'))->groupBy('ap.type')->get();

        $bySection = (clone $base)
            ->leftJoin('slipta_sections as s', 'ap.section_id', '=', 's.id')
            ->select('s.title as section', 's.code as section_code', DB::raw('count(*) as count'))
            ->groupBy('s.title', 's.code')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $recentlyClosed = (clone $base)
            ->leftJoin('users as u', 'ap.responsible_user_id', '=', 'u.id')
            ->where('ap.status', 'closed')
            ->orderBy('ap.closed_at', 'desc')
            ->limit(10)
            ->select('ap.id', 'ap.recommendation', 'ap.closed_at', 'u.name as responsible_name', 'l.name as lab_name')
            ->get();

        $overduePlans = (clone $base)
            ->leftJoin('users as u', 'ap.responsible_user_id', '=', 'u.id')
            ->whereDate('ap.due_date', '<', today())
            ->whereNotIn('ap.status', ['closed', 'deferred'])
            ->orderBy('ap.due_date')
            ->limit(20)
            ->select('ap.id', 'ap.recommendation', 'ap.due_date', 'ap.status', 'u.name as responsible_name', 'l.name as lab_name', DB::raw('DATEDIFF(CURDATE(), ap.due_date) as days_overdue'))
            ->get();

        if (request()->wantsJson()) {
            return response()->json(compact('stats', 'byType', 'bySection', 'recentlyClosed', 'overduePlans'));
        }

        return view('action_plans.dashboard', compact('stats', 'byType', 'bySection', 'recentlyClosed', 'overduePlans', 'ctx'));
    }

    // ════════════════════════════════════════════════════════════════════
    // VIEW: BULK OPERATIONS
    // ════════════════════════════════════════════════════════════════════

    public function bulkOperations(Request $request)
    {
        $ctx = $this->getUserContext();

        if (! ($ctx['is_admin'] || $ctx['is_country_coord'])) {
            abort(403);
        }

        $query = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->leftJoin('users as u', 'ap.responsible_user_id', '=', 'u.id')
            ->leftJoin('slipta_sections as s', 'ap.section_id', '=', 's.id');

        $query = $this->applyScopeFilter($query, $ctx, 'a', 'l');

        // Only show non-closed plans for bulk operations
        $query->whereNotIn('ap.status', ['closed']);

        $plans = $query->select('ap.*', 'l.name as lab_name', 'u.name as responsible_name', 's.title as section_title')
            ->orderBy('ap.due_date')
            ->paginate(100);

        $users = DB::table('users')->where('is_active', 1)->orderBy('name')->get();

        return view('action_plans.bulk', compact('plans', 'users', 'ctx'));
    }

    // ════════════════════════════════════════════════════════════════════
    // AJAX: BULK CREATE
    // ════════════════════════════════════════════════════════════════════

    public function bulkCreate(Request $request)
    {
        $ctx = $this->getUserContext();
        if (! ($ctx['is_admin'] || $ctx['is_country_coord'] || $ctx['is_auditor'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'action_plans'                  => 'required|array|min:1|max:100',
            'action_plans.*.audit_id'       => 'required|integer|exists:audits,id',
            'action_plans.*.recommendation' => 'required|string|min:20|max:5000',
            'action_plans.*.type'           => 'required|in:finding,risk_opportunity,other',
            'action_plans.*.due_date'       => 'required|date|after:today',
        ]);

        DB::beginTransaction();
        try {
            $created = 0;
            $errors  = [];

            foreach ($validated['action_plans'] as $index => $item) {
                try {
                    $this->assertAuditAccess($item['audit_id'], $ctx);

                    $item['status']     = 'open';
                    $item['created_at'] = now();
                    $item['updated_at'] = now();
                    DB::table('action_plans')->insert($item);
                    $created++;
                } catch (Exception $e) {
                    $errors[] = "Item {$index}: " . $e->getMessage();
                }
            }

            if (! empty($errors)) {
                DB::rollBack();
                return response()->json(['error' => 'Bulk creation failed', 'details' => $errors], 422);
            }

            DB::commit();
            return response()->json(['success' => true, 'created' => $created]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // AJAX: BULK UPDATE
    // ════════════════════════════════════════════════════════════════════

    public function bulkUpdate(Request $request)
    {
        $ctx = $this->getUserContext();
        if (! ($ctx['is_admin'] || $ctx['is_country_coord'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'ids'                         => 'required|array|min:1',
            'ids.*'                       => 'integer',
            'updates'                     => 'required|array',
            'updates.status'              => 'sometimes|in:open,in_progress,closed,deferred',
            'updates.responsible_user_id' => 'sometimes|nullable|integer|exists:users,id',
            'updates.due_date'            => 'sometimes|date',
            'justification'               => 'required|string|min:50|max:2000',
        ]);

        // Edge case: Validate all plans exist and user has access
        $plans = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->whereIn('ap.id', $validated['ids'])
            ->select('ap.*', 'l.country_id', 'a.id as audit_id', 'l.id as laboratory_id')
            ->get();

        if ($plans->count() !== count($validated['ids'])) {
            return response()->json(['error' => 'One or more action plans not found'], 404);
        }

        foreach ($plans as $plan) {
            if (! $this->canEditPlan($plan, $ctx)) {
                return response()->json(['error' => "Forbidden: insufficient access to action plan #{$plan->id}"], 403);
            }
        }

        // Edge case: Cannot bulk close (must be done individually)
        if (isset($validated['updates']['status']) && $validated['updates']['status'] === 'closed') {
            return response()->json(['error' => 'Cannot bulk close action plans. Close individually with resolution notes.'], 422);
        }

        // Edge case: Due date cannot be in past
        if (isset($validated['updates']['due_date']) && strtotime($validated['updates']['due_date']) < strtotime('today')) {
            return response()->json(['error' => 'Due date cannot be in the past'], 422);
        }

        $validated['updates']['updated_at'] = now();
        DB::table('action_plans')->whereIn('id', $validated['ids'])->update($validated['updates']);

        Log::info('Bulk update', ['count' => count($validated['ids']), 'ids' => $validated['ids'], 'by' => $ctx['user_id'], 'justification' => $validated['justification']]);

        return response()->json(['success' => true, 'updated' => count($validated['ids'])]);
    }

    // ════════════════════════════════════════════════════════════════════
    // AJAX: AUTO-GENERATE FROM FINDINGS
    // ════════════════════════════════════════════════════════════════════

    public function autoGenerate($auditId)
    {
        $ctx = $this->getUserContext();
        if (! ($ctx['is_admin'] || $ctx['is_country_coord'] || $ctx['is_auditor'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        try {
            $this->assertAuditAccess((int) $auditId, $ctx);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 403);
        }

        DB::beginTransaction();
        try {
            $findingsWithoutPlans = DB::table('audit_findings as f')
                ->leftJoin('action_plans as ap', 'f.id', '=', 'ap.finding_id')
                ->where('f.audit_id', (int) $auditId)
                ->whereNull('ap.id')
                ->select('f.*')
                ->get();

            if ($findingsWithoutPlans->isEmpty()) {
                return response()->json(['success' => true, 'created' => 0, 'message' => 'No findings without action plans']);
            }

            $created = 0;
            foreach ($findingsWithoutPlans as $finding) {
                DB::table('action_plans')->insert([
                    'audit_id'            => $auditId,
                    'finding_id'          => $finding->id,
                    'section_id'          => $finding->section_id,
                    'question_id'         => $finding->question_id,
                    'type'                => 'finding',
                    'recommendation'      => 'CAPA: ' . substr($finding->title, 0, 200),
                    'responsible_user_id' => $ctx['user_id'],
                    'due_date'            => now()->addDays(45)->format('Y-m-d'),
                    'status'              => 'open',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
                $created++;
            }

            Log::info('Action plans auto-generated', ['audit_id' => $auditId, 'created' => $created, 'by' => $ctx['user_id']]);

            DB::commit();
            return response()->json(['success' => true, 'created' => $created, 'message' => "Generated {$created} action plans"]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // AJAX: WORKFLOW ACTIONS
    // ════════════════════════════════════════════════════════════════════

    public function close(Request $request, $id)
    {
        $ctx = $this->getUserContext();
        $id  = (int) $id;

        $validated = $request->validate([
            'resolution_notes' => 'required|string|min:50|max:5000',
            'effectiveness_evaluation' => 'required|in:effective,partially_effective,ineffective',
        ]);

        $plan = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('ap.id', $id)
            ->select('ap.*', 'a.id as audit_id', 'l.id as laboratory_id', 'l.country_id')
            ->first();

        if (! $plan) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if (! $this->canEditPlan($plan, $ctx)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($plan->status !== 'in_progress') {
            return response()->json(['error' => 'Must be in_progress before closing. Current status: ' . $plan->status], 422);
        }

        DB::table('action_plans')->where('id', $id)->update([
            'status'                   => 'closed',
            'resolution_notes'         => $validated['resolution_notes'],
            'effectiveness_evaluation' => $validated['effectiveness_evaluation'],
            'closed_at'                => now(),
            'updated_at'               => now(),
        ]);

        Log::info('Action plan closed', ['id' => $id, 'audit_id' => $plan->audit_id, 'evaluation' => $validated['effectiveness_evaluation'], 'by' => $ctx['user_id']]);

        return response()->json(['success' => true, 'message' => 'Action plan closed']);
    }

    public function reopen(Request $request, $id)
    {
        $ctx = $this->getUserContext();
        if (! ($ctx['is_admin'] || $ctx['is_country_coord'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate(['justification' => 'required|string|min:50|max:2000']);

        $plan = DB::table('action_plans')->where('id', (int) $id)->first();
        if (! $plan) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($plan->status !== 'closed') {
            return response()->json(['error' => 'Only closed action plans can be reopened'], 422);
        }

        DB::table('action_plans')->where('id', (int) $id)->update([
            'status'           => 'open',
            'closed_at'        => null,
            'resolution_notes' => null,
            'updated_at'       => now(),
        ]);

        Log::warning('Action plan reopened', ['id' => $id, 'audit_id' => $plan->audit_id, 'by' => $ctx['user_id'], 'justification' => $validated['justification']]);

        return response()->json(['success' => true, 'message' => 'Reopened']);
    }

    public function defer(Request $request, $id)
    {
        $ctx = $this->getUserContext();
        $id  = (int) $id;

        $validated = $request->validate([
            'justification' => 'required|string|min:50|max:2000',
            'new_due_date'  => 'nullable|date|after:today',
        ]);

        $plan = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('ap.id', $id)
            ->select('ap.*', 'a.id as audit_id', 'l.id as laboratory_id', 'l.country_id')
            ->first();

        if (! $plan) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if (! $this->canEditPlan($plan, $ctx)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($plan->status === 'closed') {
            return response()->json(['error' => 'Cannot defer closed action plans'], 422);
        }

        $updates = ['status' => 'deferred', 'updated_at' => now()];
        if (isset($validated['new_due_date'])) {
            $updates['due_date'] = $validated['new_due_date'];
        }

        DB::table('action_plans')->where('id', $id)->update($updates);

        Log::info('Action plan deferred', ['id' => $id, 'audit_id' => $plan->audit_id, 'by' => $ctx['user_id'], 'justification' => $validated['justification']]);

        return response()->json(['success' => true, 'message' => 'Deferred']);
    }

    public function extendDueDate(Request $request, $id)
    {
        $ctx = $this->getUserContext();
        $id  = (int) $id;

        $validated = $request->validate([
            'new_due_date'  => 'required|date|after:today',
            'justification' => 'required|string|min:50|max:1000',
        ]);

        $plan = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('ap.id', $id)
            ->select('ap.*', 'a.id as audit_id', 'l.id as laboratory_id', 'l.country_id')
            ->first();

        if (! $plan) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if (! $this->canEditPlan($plan, $ctx)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Edge case: Cannot extend if already closed
        if ($plan->status === 'closed') {
            return response()->json(['error' => 'Cannot extend closed action plans'], 422);
        }

        // Edge case: New date must be after current due date
        if (strtotime($validated['new_due_date']) <= strtotime($plan->due_date)) {
            return response()->json(['error' => 'New due date must be after current due date'], 422);
        }

        DB::table('action_plans')->where('id', $id)->update([
            'due_date'   => $validated['new_due_date'],
            'updated_at' => now(),
        ]);

        Log::info('Due date extended', ['id' => $id, 'audit_id' => $plan->audit_id, 'old_date' => $plan->due_date, 'new_date' => $validated['new_due_date'], 'by' => $ctx['user_id']]);

        return response()->json(['success' => true, 'message' => 'Due date extended']);
    }

    public function assign(Request $request, $id)
    {
        $ctx = $this->getUserContext();
        $id  = (int) $id;

        $validated = $request->validate(['responsible_user_id' => 'required|integer|exists:users,id']);

        $plan = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->where('ap.id', $id)
            ->select('ap.*', 'a.id as audit_id', 'l.id as laboratory_id', 'l.country_id')
            ->first();

        if (! $plan) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if (! $this->canEditPlan($plan, $ctx)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Edge case: Check user is active
        $userActive = DB::table('users')->where('id', $validated['responsible_user_id'])->value('is_active');
        if (! $userActive) {
            return response()->json(['error' => 'Cannot assign to inactive user'], 422);
        }

        DB::table('action_plans')->where('id', $id)->update([
            'responsible_user_id' => $validated['responsible_user_id'],
            'updated_at'          => now(),
        ]);

        Log::info('Action plan assigned', ['id' => $id, 'audit_id' => $plan->audit_id, 'from' => $plan->responsible_user_id, 'to' => $validated['responsible_user_id'], 'by' => $ctx['user_id']]);

        return response()->json(['success' => true, 'message' => 'Assigned']);
    }

    // ════════════════════════════════════════════════════════════════════
    // AJAX: SUMMARY & EXPORT
    // ════════════════════════════════════════════════════════════════════

    public function summary(Request $request)
    {
        $ctx = $this->getUserContext();

        $base = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id');

        $base = $this->applyScopeFilter($base, $ctx, 'a', 'l');

        $summary = [
            'total'     => (clone $base)->count(),
            'by_status' => (clone $base)->select('ap.status', DB::raw('count(*) as count'))->groupBy('ap.status')->get(),
            'by_type'   => (clone $base)->select('ap.type', DB::raw('count(*) as count'))->groupBy('ap.type')->get(),
            'overdue'   => (clone $base)->whereDate('ap.due_date', '<', today())->whereNotIn('ap.status', ['closed', 'deferred'])->count(),
        ];

        return response()->json($summary);
    }

    public function export(Request $request)
    {
        $ctx = $this->getUserContext();

        $query = DB::table('action_plans as ap')
            ->join('audits as a', 'ap.audit_id', '=', 'a.id')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->leftJoin('users as u', 'ap.responsible_user_id', '=', 'u.id')
            ->leftJoin('slipta_sections as s', 'ap.section_id', '=', 's.id')
            ->leftJoin('countries as c', 'l.country_id', '=', 'c.id');

        $query = $this->applyScopeFilter($query, $ctx, 'a', 'l');

        $data = $query->select(
            'ap.id',
            'ap.audit_id',
            'ap.section_id',
            'ap.question_id',
            'ap.finding_id',
            'ap.type',
            'ap.status',
            'ap.recommendation',
            'ap.responsible_user_id',
            'ap.due_date',
            'ap.closed_at',
            'ap.created_at',
            'ap.updated_at',
            'l.name as lab_name',
            'c.name as country_name',
            'u.name as responsible_name',
            's.title as section_title',
            DB::raw('DATEDIFF(ap.due_date, CURDATE()) as days_until_due')
        )->get();

        return response()->json(['data' => $data, 'count' => $data->count(), 'format' => 'json']);
    }
}
