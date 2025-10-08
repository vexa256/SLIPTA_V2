<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * SLIPTA Laboratories Controller - Ultra Lean Version
 */
class LaboratoriesController extends Controller
{
    private const LAB_TYPES = [
        'national_reference', 'supranational_reference', 'regional_reference', 'provincial_reference',
        'district_reference', 'regional', 'provincial', 'district', 'sub_district', 'health_center',
        'hospital', 'clinical', 'public_health', 'surveillance', 'research', 'quality_assurance',
        'training', 'mobile', 'field', 'point_of_care', 'private_clinical', 'faith_based',
        'ngo', 'military', 'veterinary', 'food_safety', 'environmental', 'forensic', 'other'
    ];

    /**
     * Main laboratories interface - handles both view and API
     */
    public function index(Request $request)
    {
        try {
            $context = $this->getContext();
            $query = $this->buildQuery($context);

            // Apply filters
            $this->applyFilters($query, $request);

            // Paginate
            $page = max(1, (int)$request->input('page', 1));
            $perPage = max(1, min(100, (int)$request->input('per_page', 15)));
            $total = $query->count();

            $data = [
                'laboratories' => $query->offset(($page - 1) * $perPage)->limit($perPage)->get(),
                'countries' => $this->getCountries($context),
                'labTypes' => collect(self::LAB_TYPES)->map(fn($type) => [
                    'value' => $type, 'label' => ucfirst(str_replace('_', ' ', $type))
                ]),
                'context' => $context,
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => max(1, ceil($total / $perPage)),
                    'per_page' => $perPage,
                    'total' => $total,
                    'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
                    'to' => min($total, $page * $perPage)
                ]
            ];

            return $request->expectsJson()
                ? response()->json(['success' => true, 'data' => $data])
                : view('laboratories.index', $data);

        } catch (Exception $e) {
            return $this->errorResponse($request, $e->getMessage());
        }
    }

    /**
     * Store or update laboratory
     */
    public function store(Request $request)
    {
        try {
            $context = $this->getContext();
            $id = $request->input('id');

            if (!$this->canModify($context, $request->input('country_id'), $id)) {
                throw new Exception('Unauthorized access');
            }

            $validator = $this->validateLab($request->all(), $id, $context);
            if ($validator->fails()) {
                return $this->validationError($request, $validator->errors());
            }

            $data = $validator->validated();
            $data['updated_at'] = now();

            if ($id) {
                $existing = $this->buildQuery($context)->where('laboratories.id', $id)->first();
                if (!$existing) throw new Exception('Laboratory not found');

                DB::table('laboratories')->where('id', $id)->update($data);
                $action = 'updated';
            } else {
                $data['created_at'] = now();
                $id = DB::table('laboratories')->insertGetId($data);
                $action = 'created';
            }

            $laboratory = $this->buildQuery($context)->where('laboratories.id', $id)->first();
            $message = "Laboratory '{$data['name']}' {$action} successfully";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'data' => $laboratory, 'message' => $message])
                : redirect()->route('laboratories.index')->with('success', $message);

        } catch (Exception $e) {
            return $this->errorResponse($request, $e->getMessage());
        }
    }

    /**
     * Get single laboratory
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $context = $this->getContext();
            $laboratory = $this->buildQuery($context)->where('laboratories.id', $id)->first();

            if (!$laboratory) throw new Exception('Laboratory not found');

            return response()->json([
                'success' => true,
                'data' => [
                    'laboratory' => $laboratory,
                    'relatedData' => [
                        'audit_count' => DB::table('audits')->where('laboratory_id', $id)->count(),
                        'user_count' => DB::table('user_roles')->where('laboratory_id', $id)->count(),
                        'recent_audits' => DB::table('audits')
                            ->where('laboratory_id', $id)
                            ->orderBy('opened_on', 'desc')
                            ->limit(5)
                            ->select(['id', 'status', 'opened_on', 'closed_on'])
                            ->get()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete laboratory
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $context = $this->getContext();
            $laboratory = $this->buildQuery($context)->where('laboratories.id', $id)->first();

            if (!$laboratory) throw new Exception('Laboratory not found');
            if (!$this->canModify($context, $laboratory->country_id, $id)) {
                throw new Exception('Unauthorized access');
            }

            // Check dependencies
            $auditCount = DB::table('audits')->where('laboratory_id', $id)->count();
            $roleCount = DB::table('user_roles')->where('laboratory_id', $id)->count();

            if ($auditCount > 0 || $roleCount > 0) {
                throw new Exception("Cannot delete laboratory. {$auditCount} audits and {$roleCount} user roles depend on it.");
            }

            DB::table('laboratories')->where('id', $id)->delete();
            $message = "Laboratory '{$laboratory->name}' deleted successfully";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message])
                : redirect()->route('laboratories.index')->with('success', $message);

        } catch (Exception $e) {
            return $this->errorResponse($request, $e->getMessage());
        }
    }

    /**
     * Get user context with roles
     */
    private function getContext(): array
    {
        $user = auth()->user();
        $roles = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $user->id)
            ->where('user_roles.is_active', true)
            ->get();

        return [
            'user' => $user,
            'is_admin' => $roles->whereIn('name', ['system_admin', 'project_coordinator'])->count() > 0,
            'is_country_coordinator' => $roles->where('name', 'country_coordinator')->count() > 0,
            'country_ids' => $roles->whereNotNull('country_id')->pluck('country_id')->unique(),
            'lab_ids' => $roles->whereNotNull('laboratory_id')->pluck('laboratory_id')->unique()
        ];
    }

    /**
     * Build query with role-based filtering
     */
    private function buildQuery(array $context)
    {
        $query = DB::table('laboratories')
            ->join('countries', 'laboratories.country_id', '=', 'countries.id')
            ->select([
                'laboratories.id', 'laboratories.name', 'laboratories.lab_number',
                'laboratories.city', 'laboratories.contact_person', 'laboratories.email',
                'laboratories.phone', 'laboratories.lab_type', 'laboratories.is_active',
                'laboratories.country_id', 'laboratories.created_at', 'laboratories.updated_at',
                'countries.name as country_name'
            ])
            ->orderBy('laboratories.name');

        if ($context['is_admin']) return $query;

        if ($context['is_country_coordinator'] && $context['country_ids']->isNotEmpty()) {
            return $query->whereIn('laboratories.country_id', $context['country_ids']);
        }

        if ($context['lab_ids']->isNotEmpty()) {
            return $query->whereIn('laboratories.id', $context['lab_ids']);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply search and filters
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('laboratories.name', 'LIKE', "%{$search}%")
                  ->orWhere('laboratories.lab_number', 'LIKE', "%{$search}%")
                  ->orWhere('laboratories.city', 'LIKE', "%{$search}%")
                  ->orWhere('laboratories.contact_person', 'LIKE', "%{$search}%")
                  ->orWhere('countries.name', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('country')) {
            $query->where('laboratories.country_id', $request->input('country'));
        }

        if ($request->filled('lab_type')) {
            $query->where('laboratories.lab_type', $request->input('lab_type'));
        }

        if ($request->filled('status')) {
            $query->where('laboratories.is_active', $request->input('status') === 'active');
        }
    }

    /**
     * Get available countries
     */
    private function getCountries(array $context)
    {
        $query = DB::table('countries')
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->orderBy('name');

        if (!$context['is_admin'] && $context['country_ids']->isNotEmpty()) {
            $query->whereIn('id', $context['country_ids']);
        }

        return $query->get();
    }

    /**
     * Validate laboratory data
     */
    private function validateLab(array $data, $id = null, array $context = [])
    {
        return Validator::make($data, [
            'name' => [
                'required', 'string', 'max:191', 'min:2',
                function($attr, $value, $fail) use ($id) {
                    if (DB::table('laboratories')->where('name', $value)->when($id, fn($q) => $q->where('id', '!=', $id))->exists()) {
                        $fail('Laboratory name already exists.');
                    }
                }
            ],
            'lab_number' => [
                'nullable', 'string', 'max:100',
                function($attr, $value, $fail) use ($id) {
                    if ($value && DB::table('laboratories')->where('lab_number', $value)->when($id, fn($q) => $q->where('id', '!=', $id))->exists()) {
                        $fail('Laboratory number already exists.');
                    }
                }
            ],
            'country_id' => [
                'required', 'integer',
                function($attr, $value, $fail) use ($context) {
                    if (!DB::table('countries')->where('id', $value)->where('is_active', true)->exists()) {
                        $fail('Invalid country selected.');
                    }
                    if (!$context['is_admin'] && $context['country_ids']->isNotEmpty() && !$context['country_ids']->contains($value)) {
                        $fail('You cannot assign laboratories to this country.');
                    }
                }
            ],
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:191',
            'email' => 'nullable|email|max:191',
            'phone' => 'nullable|string|max:50',
            'lab_type' => 'required|in:' . implode(',', self::LAB_TYPES),
            'is_active' => 'boolean'
        ]);
    }

    /**
     * Check if user can modify laboratories
     */
    private function canModify(array $context, int $countryId = null, int $labId = null): bool
    {
        if ($context['is_admin']) return true;

        if ($context['is_country_coordinator'] && $countryId && $context['country_ids']->contains($countryId)) {
            return true;
        }

        return $labId && $context['lab_ids']->contains($labId);
    }

    /**
     * Return error response
     */
    private function errorResponse(Request $request, string $message, int $code = 500)
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message], $code)
            : redirect()->back()->with('error', $message)->withInput();
    }

    /**
     * Return validation error response
     */
    private function validationError(Request $request, $errors)
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'errors' => $errors], 422)
            : redirect()->back()->withErrors($errors)->withInput();
    }
}
