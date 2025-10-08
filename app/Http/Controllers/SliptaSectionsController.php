<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SliptaSectionsController extends Controller
{
    /**
     * WHO SLIPTA v3:2023 Section Point Distribution (Page 5) - IMMUTABLE
     */
    private const SECTION_CONFIG = [
        1 => ['title' => 'Documents and Records', 'points' => 22],
        2 => ['title' => 'Organisation and Leadership', 'points' => 26],
        3 => ['title' => 'Personnel Management', 'points' => 34],
        4 => ['title' => 'Customer Focus', 'points' => 24],
        5 => ['title' => 'Equipment Management', 'points' => 38],
        6 => ['title' => 'Assessments', 'points' => 24],
        7 => ['title' => 'Supplier and Inventory Management', 'points' => 27],
        8 => ['title' => 'Process Management', 'points' => 71],
        9 => ['title' => 'Information Management', 'points' => 24],
        10 => ['title' => 'Nonconforming Event Management', 'points' => 13],
        11 => ['title' => 'Continual Improvement', 'points' => 7],
        12 => ['title' => 'Facilities and Safety', 'points' => 57]
    ];

    private const TOTAL_POINTS = 367; // WHO SLIPTA specification

    /**
     * Single view handling all CRUD operations
     */
    public function index()
    {
        $this->validateSystemIntegrity();

        $sections = $this->getSectionsWithStats();
        $systemTotals = $this->calculateSystemTotals($sections);
        $availableSections = $this->getAvailableSections();

        return view('slipta-sections.index', compact('sections', 'systemTotals', 'availableSections'));
    }

    /**
     * Store new section (AJAX/Native)
     */
    public function store(Request $request)
    {
        try {
            $this->validateSectionInput($request);

            $sectionCode = (int)$request->code;
            $sectionConfig = self::SECTION_CONFIG[$sectionCode];

            $sectionId = DB::table('slipta_sections')->insertGetId([
                'code' => $sectionCode,
                'title' => $sectionConfig['title'],
                'description' => $request->description ?? "WHO SLIPTA Section {$sectionCode}: {$sectionConfig['title']} - Max Points: {$sectionConfig['points']}",
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $sectionData = $this->getSectionWithStats($sectionId);
            $message = "Section {$sectionCode}: {$sectionConfig['title']} created successfully.";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message, 'section' => $sectionData])
                : back()->with('success', $message);

        } catch (ValidationException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'errors' => $e->errors()])
                : back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()])
                : back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update section (AJAX/Native)
     */
    public function update(Request $request, $id)
    {
        try {
            $section = $this->findSectionOrFail($id);
            $this->validateSectionUpdateInput($request, $section);

            // Check if audit responses exist
            if ($this->hasAuditResponses($id) && $request->title !== $section->title) {
                throw ValidationException::withMessages([
                    'title' => 'Cannot change section title - audit responses exist for this section.'
                ]);
            }

            DB::table('slipta_sections')
                ->where('id', $id)
                ->update([
                    'title' => $request->title,
                    'description' => $request->description,
                    'updated_at' => now()
                ]);

            $sectionData = $this->getSectionWithStats($id);
            $message = "Section {$section->code} updated successfully.";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message, 'section' => $sectionData])
                : back()->with('success', $message);

        } catch (ValidationException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'errors' => $e->errors()])
                : back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()])
                : back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete section (AJAX/Native)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $section = $this->findSectionOrFail($id);

            // Safety checks
            $questionCount = DB::table('slipta_questions')->where('section_id', $id)->count();
            if ($questionCount > 0) {
                throw new \Exception("Cannot delete section. {$questionCount} questions exist in this section.");
            }

            if ($this->hasAuditResponses($id)) {
                throw new \Exception('Cannot delete section. Audit responses exist for this section.');
            }

            DB::table('slipta_sections')->where('id', $id)->delete();
            $message = "Section {$section->code}: {$section->title} deleted successfully.";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message])
                : redirect()->route('slipta-sections.index')->with('success', $message);

        } catch (\Exception $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()])
                : back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show single section details (AJAX/Native)
     */
    public function show(Request $request, $id)
    {
        try {
            $sectionData = $this->getSectionWithStats($id);
            $questions = $this->getSectionQuestions($id);
            $auditStats = $this->getSectionAuditStats($id);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'section' => $sectionData,
                    'questions' => $questions,
                    'audit_stats' => $auditStats
                ]);
            }

            // For non-AJAX, return the same view with specific section data
            $sections = [$sectionData];
            $systemTotals = $this->calculateSystemTotals($sections);
            $availableSections = $this->getAvailableSections();

            return view('slipta-sections.index', compact(
                'sections', 'systemTotals', 'availableSections', 'sectionData', 'questions', 'auditStats'
            ));

        } catch (\Exception $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()])
                : back()->with('error', $e->getMessage());
        }
    }

    /**
     * Initialize all 12 WHO SLIPTA sections
     */
    public function initializeAll(Request $request)
    {
        try {
            if (DB::table('slipta_sections')->count() > 0) {
                throw new \Exception('SLIPTA sections already exist. Cannot reinitialize.');
            }

            DB::beginTransaction();

            $sectionsCreated = [];
            foreach (self::SECTION_CONFIG as $code => $config) {
                $sectionId = DB::table('slipta_sections')->insertGetId([
                    'code' => $code,
                    'title' => $config['title'],
                    'description' => "WHO SLIPTA Section {$code}: {$config['title']} - Max Points: {$config['points']}",
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $sectionsCreated[] = [
                    'id' => $sectionId,
                    'code' => $code,
                    'title' => $config['title'],
                    'max_points' => $config['points']
                ];
            }

            DB::commit();

            $message = 'All 12 WHO SLIPTA sections initialized successfully.';
            $data = [
                'sections_created' => count($sectionsCreated),
                'total_points' => self::TOTAL_POINTS,
                'sections' => $sectionsCreated
            ];

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message, 'data' => $data])
                : redirect()->route('slipta-sections.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()])
                : back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get all sections data (AJAX endpoint)
     */
    public function getData()
    {
        try {
            $sections = $this->getSectionsWithStats();
            $systemTotals = $this->calculateSystemTotals($sections);
            $availableSections = $this->getAvailableSections();

            return response()->json([
                'success' => true,
                'sections' => $sections,
                'system_totals' => $systemTotals,
                'available_sections' => $availableSections
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    private function getSectionsWithStats()
    {
        $sections = DB::table('slipta_sections')
            ->select('id', 'code', 'title', 'description', 'created_at', 'updated_at')
            ->orderBy('code')
            ->get();

        return $sections->map(function ($section) {
            return $this->addSectionStats($section);
        });
    }

    private function getSectionWithStats($id)
    {
        $section = $this->findSectionOrFail($id);
        return $this->addSectionStats($section);
    }

    private function addSectionStats($section)
    {
        $questionCount = DB::table('slipta_questions')->where('section_id', $section->id)->count();

        // CRITICAL: ENUM CAST bug fix - use explicit CASE instead of CAST
        // MySQL ENUM columns store internal indexes (1,2,3...), not string values
        // CAST(weight AS UNSIGNED) returns index, not the enum value itself
        $totalActualPoints = DB::table('slipta_questions')
            ->where('section_id', $section->id)
            ->sum(DB::raw("CASE WHEN weight = '2' THEN 2 WHEN weight = '3' THEN 3 ELSE 0 END"));

        $expectedMaxPoints = self::SECTION_CONFIG[$section->code]['points'];

        return [
            'id' => $section->id,
            'code' => $section->code,
            'title' => $section->title,
            'description' => $section->description,
            'question_count' => $questionCount,
            'total_points' => (int)$totalActualPoints,
            'max_points_expected' => $expectedMaxPoints,
            'points_match_slipta' => ((int)$totalActualPoints === $expectedMaxPoints),
            'created_at' => $section->created_at,
            'updated_at' => $section->updated_at
        ];
    }

    private function getSectionQuestions($sectionId)
    {
        $questions = DB::table('slipta_questions')
            ->where('section_id', $sectionId)
            ->select('id', 'q_code', 'text', 'weight', 'requires_all_subs_for_yes', 'iso_reference', 'created_at')
            ->orderBy('q_code')
            ->get();

        return $questions->map(function ($question) {
            $subQuestionCount = DB::table('slipta_subquestions')->where('question_id', $question->id)->count();
            return [
                'id' => $question->id,
                'q_code' => $question->q_code,
                'text' => $question->text,
                'weight' => (int)$question->weight,
                'requires_all_subs_for_yes' => (bool)$question->requires_all_subs_for_yes,
                'iso_reference' => $question->iso_reference,
                'sub_question_count' => $subQuestionCount,
                'created_at' => $question->created_at
            ];
        });
    }

    private function getSectionAuditStats($sectionId)
    {
        return DB::table('audit_responses')
            ->join('slipta_questions', 'audit_responses.question_id', '=', 'slipta_questions.id')
            ->where('slipta_questions.section_id', $sectionId)
            ->selectRaw('
                COUNT(*) as total_responses,
                SUM(CASE WHEN answer = "Y" THEN 1 ELSE 0 END) as y_responses,
                SUM(CASE WHEN answer = "P" THEN 1 ELSE 0 END) as p_responses,
                SUM(CASE WHEN answer = "N" THEN 1 ELSE 0 END) as n_responses,
                SUM(CASE WHEN answer = "NA" THEN 1 ELSE 0 END) as na_responses
            ')
            ->first() ?: (object)[
                'total_responses' => 0,
                'y_responses' => 0,
                'p_responses' => 0,
                'n_responses' => 0,
                'na_responses' => 0
            ];
    }

    private function calculateSystemTotals($sections)
    {
        $sectionsCollection = collect($sections);

        return [
            'total_sections' => $sectionsCollection->count(),
            'total_questions' => DB::table('slipta_questions')->count(),
            'total_points_actual' => $sectionsCollection->sum('total_points'),
            'total_points_expected' => self::TOTAL_POINTS,
            'system_integrity_valid' => (
                $sectionsCollection->sum('total_points') === self::TOTAL_POINTS &&
                $sectionsCollection->count() === 12
            )
        ];
    }

    private function getAvailableSections()
    {
        $existingCodes = DB::table('slipta_sections')->pluck('code')->toArray();
        $available = [];

        foreach (self::SECTION_CONFIG as $code => $config) {
            if (!in_array($code, $existingCodes)) {
                $available[$code] = [
                    'code' => $code,
                    'title' => $config['title'],
                    'max_points' => $config['points']
                ];
            }
        }

        return $available;
    }

    private function findSectionOrFail($id)
    {
        $section = DB::table('slipta_sections')->where('id', $id)->first();
        if (!$section) {
            throw new \Exception('SLIPTA section not found.');
        }
        return $section;
    }

    private function hasAuditResponses($sectionId)
    {
        return DB::table('audit_responses')
            ->join('slipta_questions', 'audit_responses.question_id', '=', 'slipta_questions.id')
            ->where('slipta_questions.section_id', $sectionId)
            ->exists();
    }

    private function validateSectionInput($request)
    {
        $request->validate([
            'code' => [
                'required',
                'integer',
                'min:1',
                'max:12',
                'unique:slipta_sections,code',
                function ($attribute, $value, $fail) {
                    if (!array_key_exists((int)$value, self::SECTION_CONFIG)) {
                        $fail('Invalid section code. Must be 1-12 per WHO SLIPTA specification.');
                    }
                }
            ],
            'description' => 'nullable|string'
        ]);

        if (DB::table('slipta_sections')->count() >= 12) {
            throw ValidationException::withMessages([
                'code' => 'Cannot create more than 12 sections per WHO SLIPTA specification.'
            ]);
        }
    }

    private function validateSectionUpdateInput($request, $section)
    {
        $expectedTitle = self::SECTION_CONFIG[$section->code]['title'];

        $request->validate([
            'title' => [
                'required',
                'string',
                'max:191',
                function ($attribute, $value, $fail) use ($expectedTitle) {
                    if ($value !== $expectedTitle) {
                        $fail("Title must be exactly: {$expectedTitle} per WHO SLIPTA specification.");
                    }
                }
            ],
            'description' => 'nullable|string'
        ]);
    }

    private function validateSystemIntegrity()
    {
        if (array_sum(array_column(self::SECTION_CONFIG, 'points')) !== self::TOTAL_POINTS) {
            throw new \Exception('System integrity error: Section points do not sum to ' . self::TOTAL_POINTS);
        }

        if (count(self::SECTION_CONFIG) !== 12) {
            throw new \Exception('System integrity error: Expected 12 sections, found ' . count(self::SECTION_CONFIG));
        }
    }
}
