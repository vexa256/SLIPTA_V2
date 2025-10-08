<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * SLIPTA Countries CRUD Controller - Ultra Minimal
 * Single view, maximum functionality, zero bloat
 */
class CountriesController extends Controller
{
    private const ISO_CODES = [
        'AFG', 'ALB', 'DZA', 'AND', 'AGO', 'ATG', 'ARG', 'ARM', 'AUS', 'AUT',
        'AZE', 'BHS', 'BHR', 'BGD', 'BRB', 'BLR', 'BEL', 'BLZ', 'BEN', 'BTN',
        'BOL', 'BIH', 'BWA', 'BRA', 'BRN', 'BGR', 'BFA', 'BDI', 'CPV', 'KHM',
        'CMR', 'CAN', 'CAF', 'TCD', 'CHL', 'CHN', 'COL', 'COM', 'COG', 'COD',
        'CRI', 'CIV', 'HRV', 'CUB', 'CYP', 'CZE', 'DNK', 'DJI', 'DMA', 'DOM',
        'ECU', 'EGY', 'SLV', 'GNQ', 'ERI', 'EST', 'SWZ', 'ETH', 'FJI', 'FIN',
        'FRA', 'GAB', 'GMB', 'GEO', 'DEU', 'GHA', 'GRC', 'GRD', 'GTM', 'GIN',
        'GNB', 'GUY', 'HTI', 'HND', 'HUN', 'ISL', 'IND', 'IDN', 'IRN', 'IRQ',
        'IRL', 'ISR', 'ITA', 'JAM', 'JPN', 'JOR', 'KAZ', 'KEN', 'KIR', 'PRK',
        'KOR', 'KWT', 'KGZ', 'LAO', 'LVA', 'LBN', 'LSO', 'LBR', 'LBY', 'LIE',
        'LTU', 'LUX', 'MDG', 'MWI', 'MYS', 'MDV', 'MLI', 'MLT', 'MHL', 'MRT',
        'MUS', 'MEX', 'FSM', 'MDA', 'MCO', 'MNG', 'MNE', 'MAR', 'MOZ', 'MMR',
        'NAM', 'NRU', 'NPL', 'NLD', 'NZL', 'NIC', 'NER', 'NGA', 'MKD', 'NOR',
        'OMN', 'PAK', 'PLW', 'PAN', 'PNG', 'PRY', 'PER', 'PHL', 'POL', 'PRT',
        'QAT', 'ROU', 'RUS', 'RWA', 'KNA', 'LCA', 'VCT', 'WSM', 'SMR', 'STP',
        'SAU', 'SEN', 'SRB', 'SYC', 'SLE', 'SGP', 'SVK', 'SVN', 'SLB', 'SOM',
        'ZAF', 'SSD', 'ESP', 'LKA', 'SDN', 'SUR', 'SWE', 'CHE', 'SYR', 'TJK',
        'TZA', 'THA', 'TLS', 'TGO', 'TON', 'TTO', 'TUN', 'TUR', 'TKM', 'TUV',
        'UGA', 'UKR', 'ARE', 'GBR', 'USA', 'URY', 'UZB', 'VUT', 'VEN', 'VNM',
        'YEM', 'ZMB', 'ZWE'
    ];

    /**
     * Unified countries management interface
     */
    public function index(Request $request): View|JsonResponse
    {
        try {
            $context = $this->getContext();
            $query = $this->buildQuery($context);

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%")
                      ->orWhere('region', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('region')) {
                $query->where('region', $request->input('region'));
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->input('status') === 'active');
            }

            // Pagination
            $page = max(1, (int)$request->input('page', 1));
            $perPage = max(1, min(100, (int)$request->input('per_page', 15)));
            $total = $query->count();

            $countries = $query
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            $regions = DB::table('countries')
                ->select('region')
                ->whereNotNull('region')
                ->where('region', '!=', '')
                ->distinct()
                ->orderBy('region')
                ->pluck('region');

            $data = compact('countries', 'regions', 'context') + [
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
                : view('countries.index', $data);

        } catch (Exception $e) {
            Log::error('Countries Index Error', ['error' => $e->getMessage(), 'user' => auth()->id()]);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Failed to load countries'], 500)
                : redirect()->back()->with('error', 'Failed to load countries');
        }
    }

    /**
     * Store or update country
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $context = $this->getContext();

            if (!$this->canModify($context)) {
                return $this->unauthorized($request);
            }

            $id = $request->input('id');
            $validator = $this->validate($request->all(), $id);

            if ($validator->fails()) {
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'errors' => $validator->errors()], 422)
                    : redirect()->back()->withErrors($validator)->withInput();
            }

            $data = $validator->validated();
            $data['code'] = strtoupper($data['code']);
            $data['updated_at'] = now();

            if ($id) {
                // Update
                $country = $this->buildQuery($context)->where('id', $id)->first();
                if (!$country) {
                    return $this->notFound($request);
                }

                DB::table('countries')->where('id', $id)->update($data);
                $action = 'updated';
            } else {
                // Create
                $data['created_at'] = now();
                $id = DB::table('countries')->insertGetId($data);
                $action = 'created';
            }

            $country = DB::table('countries')->where('id', $id)->first();

            Log::info("Country {$action}", [
                'id' => $id,
                'name' => $data['name'],
                'user' => auth()->id()
            ]);

            $message = "Country '{$data['name']}' {$action} successfully";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'data' => $country, 'message' => $message])
                : redirect()->route('countries.index')->with('success', $message);

        } catch (Exception $e) {
            Log::error('Countries Store Error', ['error' => $e->getMessage(), 'user' => auth()->id()]);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Failed to save country'], 500)
                : redirect()->back()->with('error', 'Failed to save country')->withInput();
        }
    }

    /**
     * Get single country
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $context = $this->getContext();
            $country = $this->buildQuery($context)->where('id', $id)->first();

            if (!$country) {
                return $this->notFound($request);
            }

            $relatedData = [
                'laboratory_count' => DB::table('laboratories')->where('country_id', $id)->count(),
                'audit_count' => DB::table('audits')
                    ->join('laboratories', 'audits.laboratory_id', '=', 'laboratories.id')
                    ->where('laboratories.country_id', $id)
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => compact('country', 'relatedData')
            ]);

        } catch (Exception $e) {
            Log::error('Countries Show Error', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json(['success' => false, 'message' => 'Failed to load country'], 500);
        }
    }

    /**
     * Delete country
     */
    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            $context = $this->getContext();

            if (!$this->canModify($context)) {
                return $this->unauthorized($request);
            }

            $country = $this->buildQuery($context)->where('id', $id)->first();
            if (!$country) {
                return $this->notFound($request);
            }

            // Check dependencies
            $labCount = DB::table('laboratories')->where('country_id', $id)->count();
            $roleCount = DB::table('user_roles')->where('country_id', $id)->count();

            if ($labCount > 0 || $roleCount > 0) {
                $message = "Cannot delete country. {$labCount} laboratories and {$roleCount} user roles depend on it.";

                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => $message], 422)
                    : redirect()->back()->with('error', $message);
            }

            DB::table('countries')->where('id', $id)->delete();

            Log::warning('Country Deleted', [
                'id' => $id,
                'name' => $country->name,
                'user' => auth()->id()
            ]);

            $message = "Country '{$country->name}' deleted successfully";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message])
                : redirect()->route('countries.index')->with('success', $message);

        } catch (Exception $e) {
            Log::error('Countries Delete Error', ['error' => $e->getMessage(), 'id' => $id]);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Failed to delete country'], 500)
                : redirect()->back()->with('error', 'Failed to delete country');
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
            'is_admin' => $roles->contains('name', 'system_admin') ||
                         $roles->contains('name', 'project_coordinator'),
            'country_ids' => $roles->whereNotNull('country_id')->pluck('country_id')->unique(),
            'lab_ids' => $roles->whereNotNull('laboratory_id')->pluck('laboratory_id')->unique()
        ];
    }

    /**
     * Build query with role-based filtering
     */
    private function buildQuery(array $context)
    {
        $query = DB::table('countries')
            ->select(['id', 'name', 'code', 'region', 'is_active', 'created_at', 'updated_at'])
            ->orderBy('name');

        if ($context['is_admin']) {
            return $query;
        }

        if ($context['country_ids']->isNotEmpty()) {
            return $query->whereIn('id', $context['country_ids']);
        }

        if ($context['lab_ids']->isNotEmpty()) {
            return $query->whereExists(function($q) use ($context) {
                $q->from('laboratories')
                  ->whereColumn('laboratories.country_id', 'countries.id')
                  ->whereIn('laboratories.id', $context['lab_ids']);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Validate country data
     */
    private function validate(array $data, $id = null)
    {
        return Validator::make($data, [
            'name' => [
                'required', 'string', 'max:120', 'min:2',
                function($attr, $value, $fail) use ($id) {
                    $exists = DB::table('countries')
                        ->where('name', $value)
                        ->when($id, fn($q) => $q->where('id', '!=', $id))
                        ->exists();
                    if ($exists) $fail('Country name already exists.');
                }
            ],
            'code' => [
                'required', 'string', 'size:3', 'alpha', 'uppercase',
                function($attr, $value, $fail) use ($id) {
                    if (!in_array($value, self::ISO_CODES)) {
                        $fail('Invalid ISO 3166-1 alpha-3 country code.');
                    }
                    $exists = DB::table('countries')
                        ->where('code', $value)
                        ->when($id, fn($q) => $q->where('id', '!=', $id))
                        ->exists();
                    if ($exists) $fail('Country code already exists.');
                }
            ],
            'region' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);
    }

    /**
     * Check if user can modify countries
     */
    private function canModify(array $context): bool
    {
        return $context['is_admin'];
    }

    /**
     * Return unauthorized response
     */
    private function unauthorized(Request $request)
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => 'Unauthorized'], 403)
            : redirect()->back()->with('error', 'Unauthorized');
    }

    /**
     * Return not found response
     */
    private function notFound(Request $request)
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => 'Country not found'], 404)
            : redirect()->route('countries.index')->with('error', 'Country not found');
    }
}
