<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * User Management Controller
 *
 * Handles complete user lifecycle:
 * - Create users with role assignments
 * - Update user details and roles
 * - Activate/deactivate users
 * - Manage role assignments with country/lab scoping
 *
 * CRITICAL: Query Builder only, no Eloquent
 */
class UserManagementController extends Controller
{
    /**
     * Display user management interface
     */
    public function index()
    {
        try {
            $userContext = $this->getUserContext();

            // Only system_admin and project_coordinator can access
            if (!$userContext['is_system_admin'] && !$userContext['is_project_coordinator']) {
                abort(403, 'Unauthorized access to user management');
            }

            $users = $this->getUsers($userContext);
            $roles = $this->getRoles();
            $countries = $this->getCountries();
            $laboratories = $this->getLaboratories($userContext);

            return view('users.index', [
                'users' => $users,
                'roles' => $roles,
                'countries' => $countries,
                'laboratories' => $laboratories,
                'userContext' => $userContext
            ]);

        } catch (Exception $e) {
            Log::error('User management page error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            return back()->with('error', 'Failed to load user management');
        }
    }

    /**
     * Create new user with roles
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
            'organization' => 'nullable|string|max:191',
            'roles' => 'required|array|min:1',
            'roles.*.role_id' => 'required|exists:roles,id',
            'roles.*.country_id' => 'nullable|exists:countries,id',
            'roles.*.laboratory_id' => 'nullable|exists:laboratories,id'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $userContext = $this->getUserContext();

            // Validate permissions
            if (!$this->canCreateUser($userContext, $request->roles)) {
                throw new Exception('You do not have permission to assign these roles');
            }

            // Create user
            $userId = DB::table('users')->insertGetId([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'organization' => $request->organization,
                'is_active' => 1,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Assign roles
            foreach ($request->roles as $roleData) {
                DB::table('user_roles')->insert([
                    'user_id' => $userId,
                    'role_id' => $roleData['role_id'],
                    'country_id' => $roleData['country_id'] ?? null,
                    'laboratory_id' => $roleData['laboratory_id'] ?? null,
                    'is_active' => 1,
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            Log::info('User created', [
                'user_id' => $userId,
                'created_by' => auth()->id()
            ]);

            return back()->with('success', 'User created successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Update existing user
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:50',
            'organization' => 'nullable|string|max:191'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $userContext = $this->getUserContext();

            if (!$this->canEditUser($userContext, $id)) {
                throw new Exception('You do not have permission to edit this user');
            }

            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'organization' => $request->organization,
                'updated_at' => now()
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            DB::table('users')->where('id', $id)->update($updateData);

            DB::commit();

            Log::info('User updated', [
                'user_id' => $id,
                'updated_by' => auth()->id()
            ]);

            return back()->with('success', 'User updated successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('User update failed', [
                'error' => $e->getMessage(),
                'user_id' => $id
            ]);
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus($id)
    {
        try {
            DB::beginTransaction();

            $userContext = $this->getUserContext();

            if (!$this->canEditUser($userContext, $id)) {
                throw new Exception('You do not have permission to change user status');
            }

            $user = DB::table('users')->where('id', $id)->first();

            if (!$user) {
                throw new Exception('User not found');
            }

            $newStatus = $user->is_active ? 0 : 1;

            DB::table('users')
                ->where('id', $id)
                ->update([
                    'is_active' => $newStatus,
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('User status toggled', [
                'user_id' => $id,
                'new_status' => $newStatus,
                'changed_by' => auth()->id()
            ]);

            $status = $newStatus ? 'activated' : 'deactivated';
            return back()->with('success', "User {$status} successfully");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('User status toggle failed', [
                'error' => $e->getMessage(),
                'user_id' => $id
            ]);
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'country_id' => 'nullable|exists:countries,id',
            'laboratory_id' => 'nullable|exists:laboratories,id'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $userContext = $this->getUserContext();

            if (!$this->canAssignRole($userContext, $request->role_id, $request->country_id, $request->laboratory_id)) {
                throw new Exception('You do not have permission to assign this role');
            }

            // Check if role already assigned
            $exists = DB::table('user_roles')
                ->where('user_id', $request->user_id)
                ->where('role_id', $request->role_id)
                ->where('country_id', $request->country_id)
                ->where('laboratory_id', $request->laboratory_id)
                ->exists();

            if ($exists) {
                throw new Exception('This role assignment already exists');
            }

            DB::table('user_roles')->insert([
                'user_id' => $request->user_id,
                'role_id' => $request->role_id,
                'country_id' => $request->country_id,
                'laboratory_id' => $request->laboratory_id,
                'is_active' => 1,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('Role assigned', [
                'user_id' => $request->user_id,
                'role_id' => $request->role_id,
                'assigned_by' => auth()->id()
            ]);

            return back()->with('success', 'Role assigned successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Role assignment failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id ?? null
            ]);
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Revoke role from user
     */
    public function revokeRole($userRoleId)
    {
        try {
            DB::beginTransaction();

            $userContext = $this->getUserContext();

            $userRole = DB::table('user_roles')->where('id', $userRoleId)->first();

            if (!$userRole) {
                throw new Exception('Role assignment not found');
            }

            if (!$this->canRevokeRole($userContext, $userRole)) {
                throw new Exception('You do not have permission to revoke this role');
            }

            DB::table('user_roles')->where('id', $userRoleId)->delete();

            DB::commit();

            Log::info('Role revoked', [
                'user_role_id' => $userRoleId,
                'user_id' => $userRole->user_id,
                'revoked_by' => auth()->id()
            ]);

            return back()->with('success', 'Role revoked successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Role revocation failed', [
                'error' => $e->getMessage(),
                'user_role_id' => $userRoleId
            ]);
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get user details with roles
     */
    public function show($id)
    {
        try {
            $userContext = $this->getUserContext();

            if (!$this->canViewUser($userContext, $id)) {
                abort(403, 'Unauthorized');
            }

            $user = DB::table('users')->where('id', $id)->first();

            if (!$user) {
                abort(404, 'User not found');
            }

            $userRoles = DB::table('user_roles as ur')
                ->join('roles as r', 'ur.role_id', '=', 'r.id')
                ->leftJoin('countries as c', 'ur.country_id', '=', 'c.id')
                ->leftJoin('laboratories as l', 'ur.laboratory_id', '=', 'l.id')
                ->where('ur.user_id', $id)
                ->select(
                    'ur.id',
                    'ur.is_active',
                    'ur.assigned_at',
                    'r.name as role_name',
                    'c.name as country_name',
                    'l.name as laboratory_name'
                )
                ->get();

            return response()->json([
                'success' => true,
                'user' => $user,
                'roles' => $userRoles
            ]);

        } catch (Exception $e) {
            Log::error('User show failed', [
                'error' => $e->getMessage(),
                'user_id' => $id
            ]);
            return response()->json(['error' => 'Failed to load user'], 500);
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get user context with roles
     */
    protected function getUserContext(): array
    {
        $user = auth()->user();

        $roles = DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $user->id)
            ->where('ur.is_active', 1)
            ->select('r.name', 'ur.country_id', 'ur.laboratory_id')
            ->get();

        return [
            'user' => $user,
            'roles' => $roles,
            'is_system_admin' => $roles->contains('name', 'system_admin'),
            'is_project_coordinator' => $roles->contains('name', 'project_coordinator'),
            'country_ids' => $roles->pluck('country_id')->filter()->unique()->values()->all()
        ];
    }

    /**
     * Get all users based on scope
     */
    protected function getUsers(array $userContext)
    {
        $query = DB::table('users as u')
            ->select(
                'u.id',
                'u.name',
                'u.email',
                'u.phone',
                'u.organization',
                'u.is_active',
                'u.created_at',
                DB::raw('COUNT(ur.id) as role_count')
            )
            ->leftJoin('user_roles as ur', 'u.id', '=', 'ur.user_id')
            ->groupBy('u.id', 'u.name', 'u.email', 'u.phone', 'u.organization', 'u.is_active', 'u.created_at')
            ->orderBy('u.name');

        return $query->get();
    }

    /**
     * Get all active roles
     */
    protected function getRoles()
    {
        return DB::table('roles')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all active countries
     */
    protected function getCountries()
    {
        return DB::table('countries')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get laboratories based on scope
     */
    protected function getLaboratories(array $userContext)
    {
        $query = DB::table('laboratories as l')
            ->join('countries as c', 'l.country_id', '=', 'c.id')
            ->where('l.is_active', 1)
            ->select('l.id', 'l.name', 'l.country_id', 'c.name as country_name')
            ->orderBy('l.name');

        return $query->get();
    }

    /**
     * Check if user can create users with given roles
     */
    protected function canCreateUser(array $userContext, array $roles): bool
    {
        if ($userContext['is_system_admin']) {
            return true;
        }

        if ($userContext['is_project_coordinator']) {
            // Cannot assign system_admin role
            foreach ($roles as $roleData) {
                $roleName = DB::table('roles')->where('id', $roleData['role_id'])->value('name');
                if ($roleName === 'system_admin') {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Check if user can edit another user
     */
    protected function canEditUser(array $userContext, int $userId): bool
    {
        if ($userContext['is_system_admin']) {
            return true;
        }

        if ($userContext['is_project_coordinator']) {
            // Cannot edit system_admin users
            $hasSystemAdmin = DB::table('user_roles as ur')
                ->join('roles as r', 'ur.role_id', '=', 'r.id')
                ->where('ur.user_id', $userId)
                ->where('r.name', 'system_admin')
                ->exists();

            return !$hasSystemAdmin;
        }

        return false;
    }

    /**
     * Check if user can view another user
     */
    protected function canViewUser(array $userContext, int $userId): bool
    {
        return $userContext['is_system_admin'] || $userContext['is_project_coordinator'];
    }

    /**
     * Check if user can assign a role
     */
    protected function canAssignRole(array $userContext, int $roleId, $countryId, $laboratoryId): bool
    {
        if ($userContext['is_system_admin']) {
            return true;
        }

        if ($userContext['is_project_coordinator']) {
            // Cannot assign system_admin role
            $roleName = DB::table('roles')->where('id', $roleId)->value('name');
            return $roleName !== 'system_admin';
        }

        return false;
    }

    /**
     * Check if user can revoke a role
     */
    protected function canRevokeRole(array $userContext, $userRole): bool
    {
        if ($userContext['is_system_admin']) {
            return true;
        }

        if ($userContext['is_project_coordinator']) {
            // Cannot revoke system_admin role
            $roleName = DB::table('roles')
                ->where('id', $userRole->role_id)
                ->value('name');

            return $roleName !== 'system_admin';
        }

        return false;
    }
}
