<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class SliptaQuestionsController extends Controller
{
    // ========================================
    // WHO SLIPTA v3:2023 IMMUTABLE CONSTANTS
    // ========================================

    private const ALLOWED_WEIGHTS = [2, 3];
    private const TOTAL_POSSIBLE_POINTS = 367;
    private const EXACT_SECTION_COUNT = 12;

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

    // ========================================
    // MAIN ENTRY POINT
    // ========================================

    public function index(Request $request)
    {
        try {
            $this->validateSystemIntegrity();

            $sections = $this->getAvailableSections();
            $systemTotals = $this->calculateSystemTotals();

            $selectedSection = null;
            $selectedQuestion = null;
            $questions = collect();
            $subQuestions = collect();
            $auditStats = null;

            // Handle section selection (page load or AJAX)
            if ($request->filled('section_id')) {
                $selectedSection = $this->getSectionDetails($request->section_id);
                $questions = $this->getQuestionsForSection($request->section_id, $request->get('search'));

                // Handle deep-link to specific question
                if ($request->filled('question_id')) {
                    $selectedQuestion = $this->getQuestionWithStats($request->question_id);
                    $subQuestions = $this->getSubQuestions($request->question_id);
                    $auditStats = $this->getQuestionAuditStats($request->question_id);
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'sections' => $sections,
                    'selected_section' => $selectedSection,
                    'questions' => $questions,
                    'selected_question' => $selectedQuestion,
                    'sub_questions' => $subQuestions,
                    'audit_stats' => $auditStats,
                    'system_totals' => $systemTotals
                ]);
            }

            return view('slipta-questions.index', compact(
                'sections',
                'selectedSection',
                'questions',
                'selectedQuestion',
                'subQuestions',
                'auditStats',
                'systemTotals'
            ));

        } catch (Exception $e) {
            return $this->handleException($e, $request);
        }
    }

    // ========================================
    // CRUD OPERATIONS
    // ========================================

    public function store(Request $request)
    {
        if (!$request->filled('section_id')) {
            throw ValidationException::withMessages([
                'section_id' => 'Section selection required before creating question.'
            ]);
        }

        try {
            $this->validateQuestionInput($request);
            $this->validateSectionPointLimits($request->section_id, (int)$request->weight);

            DB::beginTransaction();

            $questionId = DB::table('slipta_questions')->insertGetId([
                'section_id' => $request->section_id,
                'q_code' => $request->q_code,
                'weight' => (int)$request->weight,
                'requires_all_subs_for_yes' => $request->boolean('requires_all_subs_for_yes'),
                'text' => $request->text,
                'iso_reference' => $request->iso_reference,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->storeSubQuestions($questionId, $request->sub_questions ?? []);

            DB::commit();

            return $this->successResponse(
                "Question {$request->q_code} created successfully.",
                [
                    'questions' => $this->getQuestionsForSection($request->section_id),
                    'system_totals' => $this->calculateSystemTotals(),
                    'created_question_id' => $questionId
                ],
                $request
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $question = $this->findQuestionOrFail($id);
            $this->validateQuestionUpdateInput($request, $question);

            $hasResponses = $this->hasAuditResponses($id);

            if ($hasResponses) {
                if ($request->weight != $question->weight || $request->q_code !== $question->q_code) {
                    throw ValidationException::withMessages([
                        'weight' => 'Cannot change weight - audit responses exist.',
                        'q_code' => 'Cannot change code - audit responses exist.'
                    ]);
                }
                $updates = ['text' => $request->text, 'iso_reference' => $request->iso_reference];
            } else {
                if ($request->weight != $question->weight) {
                    $this->validateSectionPointLimits($question->section_id, (int)$request->weight, $question->id);
                }
                $updates = [
                    'q_code' => $request->q_code,
                    'weight' => (int)$request->weight,
                    'requires_all_subs_for_yes' => $request->boolean('requires_all_subs_for_yes'),
                    'text' => $request->text,
                    'iso_reference' => $request->iso_reference,
                ];
            }

            DB::beginTransaction();

            DB::table('slipta_questions')
                ->where('id', $id)
                ->update(array_merge($updates, ['updated_at' => now()]));

            if (!$hasResponses && $request->has('sub_questions')) {
                DB::table('slipta_subquestions')->where('question_id', $id)->delete();
                $this->storeSubQuestions($id, $request->sub_questions ?? []);
            }

            DB::commit();

            return $this->successResponse(
                "Question {$question->q_code} updated successfully.",
                [
                    'selected_question' => $this->getQuestionWithStats($id),
                    'sub_questions' => $this->getSubQuestions($id),
                    'questions' => $this->getQuestionsForSection($question->section_id),
                    'system_totals' => $this->calculateSystemTotals()
                ],
                $request
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $question = $this->findQuestionOrFail($id);

            if ($this->hasAuditResponses($id)) {
                throw new Exception('Cannot delete question - audit responses exist.');
            }

            DB::beginTransaction();

            DB::table('slipta_subquestions')->where('question_id', $id)->delete();
            DB::table('slipta_questions')->where('id', $id)->delete();

            DB::commit();

            return $this->successResponse(
                "Question {$question->q_code} deleted successfully.",
                [
                    'questions' => $this->getQuestionsForSection($question->section_id),
                    'system_totals' => $this->calculateSystemTotals(),
                    'deleted_question_id' => $id
                ],
                $request
            );

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ========================================
    // AJAX ENDPOINTS
    // ========================================

    public function getSectionQuestions(Request $request, $sectionId)
    {
        try {
            $questions = $this->getQuestionsForSection($sectionId, $request->get('search'));
            $sectionDetails = $this->getSectionDetails($sectionId);

            return response()->json([
                'success' => true,
                'section' => $sectionDetails,
                'questions' => $questions
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getQuestionDetails(Request $request, $questionId)
    {
        try {
            return response()->json([
                'success' => true,
                'selected_question' => $this->getQuestionWithStats($questionId),
                'sub_questions' => $this->getSubQuestions($questionId),
                'audit_stats' => $this->getQuestionAuditStats($questionId)
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkImport(Request $request)
    {
        if (!$request->filled('section_id')) {
            throw ValidationException::withMessages([
                'section_id' => 'Section selection required before import.'
            ]);
        }

        $request->validate([
            'section_id' => 'required|exists:slipta_sections,id',
            'questions' => 'required|array|min:1',
            'questions.*.q_code' => 'required|string|max:20',
            'questions.*.weight' => 'required|in:2,3',
            'questions.*.text' => 'required|string',
            'questions.*.iso_reference' => 'nullable|string|max:100',
            'questions.*.requires_all_subs_for_yes' => 'boolean',
            'questions.*.sub_questions' => 'nullable|array',
            'questions.*.sub_questions.*.sub_code' => 'required_with:questions.*.sub_questions.*.text|string|max:20',
            'questions.*.sub_questions.*.text' => 'required_with:questions.*.sub_questions.*.sub_code|string',
        ]);

        try {
            DB::beginTransaction();

            $createdCount = 0;
            foreach ($request->questions as $questionData) {
                if (DB::table('slipta_questions')->where('q_code', $questionData['q_code'])->exists()) {
                    continue;
                }

                $questionId = DB::table('slipta_questions')->insertGetId([
                    'section_id' => $request->section_id,
                    'q_code' => $questionData['q_code'],
                    'weight' => (int)$questionData['weight'],
                    'requires_all_subs_for_yes' => $questionData['requires_all_subs_for_yes'] ?? false,
                    'text' => $questionData['text'],
                    'iso_reference' => $questionData['iso_reference'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                if (!empty($questionData['sub_questions'])) {
                    $this->storeSubQuestions($questionId, $questionData['sub_questions']);
                }

                $createdCount++;
            }

            DB::commit();

            return $this->successResponse(
                "Successfully imported {$createdCount} questions.",
                [
                    'created_count' => $createdCount,
                    'questions' => $this->getQuestionsForSection($request->section_id),
                    'system_totals' => $this->calculateSystemTotals()
                ],
                $request
            );

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ========================================
    // DATA RETRIEVAL METHODS
    // ========================================

    private function getQuestionsForSection($sectionId, $search = null)
    {
        $query = DB::table('slipta_questions')
            ->join('slipta_sections', 'slipta_questions.section_id', '=', 'slipta_sections.id')
            ->select(
                'slipta_questions.*',
                'slipta_sections.title as section_title',
                'slipta_sections.code as section_code'
            )
            ->where('slipta_questions.section_id', $sectionId)
            ->orderBy('slipta_questions.q_code');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('slipta_questions.q_code', 'LIKE', "%{$search}%")
                  ->orWhere('slipta_questions.text', 'LIKE', "%{$search}%")
                  ->orWhere('slipta_questions.iso_reference', 'LIKE', "%{$search}%");
            });
        }

        return $query->get()->map(fn($q) => $this->enrichQuestionData($q));
    }

    private function getQuestionWithStats($id)
    {
        $question = DB::table('slipta_questions')
            ->join('slipta_sections', 'slipta_questions.section_id', '=', 'slipta_sections.id')
            ->select(
                'slipta_questions.*',
                'slipta_sections.title as section_title',
                'slipta_sections.code as section_code'
            )
            ->where('slipta_questions.id', $id)
            ->first();

        if (!$question) {
            throw new Exception('Question not found.');
        }

        return $this->enrichQuestionData($question);
    }

    private function enrichQuestionData($question)
    {
        $subQuestionCount = DB::table('slipta_subquestions')
            ->where('question_id', $question->id)
            ->count();

        $auditResponseCount = DB::table('audit_responses')
            ->where('question_id', $question->id)
            ->count();

        return [
            'id' => $question->id,
            'section_id' => $question->section_id,
            'section_code' => $question->section_code,
            'section_title' => $question->section_title,
            'q_code' => $question->q_code,
            'weight' => (int)$question->weight,
            'requires_all_subs_for_yes' => (bool)$question->requires_all_subs_for_yes,
            'text' => $question->text,
            'iso_reference' => $question->iso_reference,
            'sub_question_count' => $subQuestionCount,
            'audit_response_count' => $auditResponseCount,
            'has_audit_responses' => $auditResponseCount > 0,
            'can_edit_weight' => $auditResponseCount === 0,
            'can_edit_code' => $auditResponseCount === 0,
            'can_delete' => $auditResponseCount === 0,
            'created_at' => $question->created_at,
            'updated_at' => $question->updated_at
        ];
    }

    private function getSubQuestions($questionId)
    {
        return DB::table('slipta_subquestions')
            ->where('question_id', $questionId)
            ->orderBy('sub_code')
            ->get()
            ->map(fn($sub) => [
                'id' => $sub->id,
                'sub_code' => $sub->sub_code,
                'text' => $sub->text,
                'created_at' => $sub->created_at
            ]);
    }

    private function getQuestionAuditStats($questionId)
    {
        return DB::table('audit_responses')
            ->where('question_id', $questionId)
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

    private function getAvailableSections()
    {
        return DB::table('slipta_sections')
            ->select('id', 'code', 'title')
            ->orderBy('code')
            ->get()
            ->map(function ($section) {
                $questionCount = DB::table('slipta_questions')->where('section_id', $section->id)->count();

                // CRITICAL: ENUM CAST bug fix
                $totalPoints = DB::table('slipta_questions')
                    ->where('section_id', $section->id)
                    ->sum(DB::raw("CASE WHEN weight = '2' THEN 2 WHEN weight = '3' THEN 3 ELSE 0 END"));

                $expectedPoints = self::SECTION_CONFIG[$section->code]['points'] ?? 0;

                return [
                    'id' => $section->id,
                    'code' => $section->code,
                    'title' => $section->title,
                    'question_count' => $questionCount,
                    'total_points' => (int)$totalPoints,
                    'max_points_expected' => $expectedPoints,
                    'is_complete' => ((int)$totalPoints === $expectedPoints)
                ];
            });
    }

    private function getSectionDetails($sectionId)
    {
        $section = DB::table('slipta_sections')->where('id', $sectionId)->first();

        if (!$section) {
            throw new Exception('Section not found.');
        }

        $questionCount = DB::table('slipta_questions')->where('section_id', $sectionId)->count();

        // CRITICAL: ENUM CAST bug fix
        $totalPoints = DB::table('slipta_questions')
            ->where('section_id', $sectionId)
            ->sum(DB::raw("CASE WHEN weight = '2' THEN 2 WHEN weight = '3' THEN 3 ELSE 0 END"));

        $expectedPoints = self::SECTION_CONFIG[$section->code]['points'] ?? 0;

        return [
            'id' => $section->id,
            'code' => $section->code,
            'title' => $section->title,
            'description' => $section->description,
            'question_count' => $questionCount,
            'total_points' => (int)$totalPoints,
            'max_points_expected' => $expectedPoints,
            'is_complete' => ((int)$totalPoints === $expectedPoints),
            'points_remaining' => $expectedPoints - (int)$totalPoints
        ];
    }

    private function calculateSystemTotals()
    {
        $totalQuestions = DB::table('slipta_questions')->count();
        $totalSubQuestions = DB::table('slipta_subquestions')->count();

        // CRITICAL: ENUM CAST bug fix - use explicit CASE instead of CAST
        $totalActualPoints = DB::table('slipta_questions')
            ->sum(DB::raw("CASE WHEN weight = '2' THEN 2 WHEN weight = '3' THEN 3 ELSE 0 END"));

        return [
            'total_questions' => $totalQuestions,
            'total_sub_questions' => $totalSubQuestions,
            'total_points_actual' => (int)$totalActualPoints,
            'total_points_expected' => self::TOTAL_POSSIBLE_POINTS,
            'system_integrity_valid' => ((int)$totalActualPoints === self::TOTAL_POSSIBLE_POINTS),
            'points_remaining' => self::TOTAL_POSSIBLE_POINTS - (int)$totalActualPoints,
            'completion_percentage' => round(((int)$totalActualPoints / self::TOTAL_POSSIBLE_POINTS) * 100, 2)
        ];
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    private function storeSubQuestions($questionId, array $subQuestions)
    {
        foreach ($subQuestions as $subQuestion) {
            if (!empty($subQuestion['text']) && !empty($subQuestion['sub_code'])) {
                DB::table('slipta_subquestions')->insert([
                    'question_id' => $questionId,
                    'sub_code' => $subQuestion['sub_code'],
                    'text' => $subQuestion['text'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    private function findQuestionOrFail($id)
    {
        $question = DB::table('slipta_questions')->where('id', $id)->first();
        if (!$question) {
            throw new Exception('Question not found.');
        }
        return $question;
    }

    private function hasAuditResponses($questionId)
    {
        return DB::table('audit_responses')->where('question_id', $questionId)->exists();
    }

    // ========================================
    // VALIDATION METHODS
    // ========================================

    private function validateQuestionInput($request)
    {
        $request->validate([
            'section_id' => 'required|exists:slipta_sections,id',
            'q_code' => 'required|string|max:20|unique:slipta_questions,q_code',
            'weight' => ['required', 'integer', function ($attribute, $value, $fail) {
                if (!in_array((int)$value, self::ALLOWED_WEIGHTS)) {
                    $fail('Weight must be 2 or 3 points per WHO SLIPTA specification.');
                }
            }],
            'text' => 'required|string',
            'iso_reference' => 'nullable|string|max:100',
            'requires_all_subs_for_yes' => 'boolean',
            'sub_questions' => 'nullable|array',
            'sub_questions.*.sub_code' => 'required_with:sub_questions.*.text|string|max:20',
            'sub_questions.*.text' => 'required_with:sub_questions.*.sub_code|string',
        ]);
    }

    private function validateQuestionUpdateInput($request, $question)
    {
        $request->validate([
            'q_code' => 'required|string|max:20|unique:slipta_questions,q_code,' . $question->id,
            'weight' => ['required', 'integer', function ($attribute, $value, $fail) {
                if (!in_array((int)$value, self::ALLOWED_WEIGHTS)) {
                    $fail('Weight must be 2 or 3 points per WHO SLIPTA specification.');
                }
            }],
            'text' => 'required|string',
            'iso_reference' => 'nullable|string|max:100',
            'requires_all_subs_for_yes' => 'boolean',
            'sub_questions' => 'nullable|array',
            'sub_questions.*.sub_code' => 'required_with:sub_questions.*.text|string|max:20',
            'sub_questions.*.text' => 'required_with:sub_questions.*.sub_code|string',
        ]);
    }

    private function validateSectionPointLimits($sectionId, $newWeight, $excludeQuestionId = null)
    {
        $section = DB::table('slipta_sections')->where('id', $sectionId)->first();
        if (!$section) {
            throw new Exception('Section not found.');
        }

        $query = DB::table('slipta_questions')->where('section_id', $sectionId);

        if ($excludeQuestionId) {
            $query->where('id', '!=', $excludeQuestionId);
        }

        // CRITICAL: ENUM CAST bug fix
        $currentPoints = (int)$query->sum(DB::raw("CASE WHEN weight = '2' THEN 2 WHEN weight = '3' THEN 3 ELSE 0 END"));

        $newTotal = $currentPoints + $newWeight;
        $expectedMax = self::SECTION_CONFIG[$section->code]['points'] ?? 0;

        if ($newTotal > $expectedMax) {
            throw ValidationException::withMessages([
                'weight' => "Adding this question would exceed section {$section->code} point limit. Current: {$currentPoints}, Adding: {$newWeight}, Max: {$expectedMax}"
            ]);
        }
    }

    private function validateSystemIntegrity()
    {
        if (count(self::SECTION_CONFIG) !== self::EXACT_SECTION_COUNT) {
            throw new Exception('System integrity error: Expected 12 sections');
        }

        if (array_sum(array_column(self::SECTION_CONFIG, 'points')) !== self::TOTAL_POSSIBLE_POINTS) {
            throw new Exception('System integrity error: Section points must sum to 367');
        }
    }

    // ========================================
    // RESPONSE HELPERS
    // ========================================

    private function successResponse($message, $data, $request)
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge(['success' => true, 'message' => $message], $data));
        }
        return redirect()->back()->with('success', $message);
    }

    private function handleException(Exception $e, $request)
    {
        if ($e instanceof ValidationException) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors())->withInput();
        }

        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $e->getMessage()], 500)
            : back()->with('error', $e->getMessage());
    }
}
