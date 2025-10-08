<?php
namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuditResponseController extends Controller
{
    /**
     * CRITICAL FIX 1: Admin editing now allowed
     * CRITICAL FIX 2: Sub-question comment is nullable
     * CRITICAL FIX 3: Evidence uses 'public' disk
     * CRITICAL FIX 4: Returns 403 JSON for permission errors
     */

    public function index(Request $request)
    {
        try {
            $userContext = $this->getUserContext();

            $audits = $this->getScopedInProgressAudits($userContext)
                ->orderByDesc('a.updated_at')
                ->orderByDesc('a.created_at')
                ->get();

            return view('audits.select', [
                'audits'      => $audits,
                'userContext' => $userContext,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to load audit selection', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load audits: ' . $e->getMessage());
        }
    }

    public function show($auditId)
    {
        try {
            $userContext = $this->getUserContext();

            if (! $this->canAccessAudit($userContext, (int) $auditId)) {
                return redirect()->route('audits.select')
                    ->with('error', 'Access denied: select an audit within your scope.');
            }

            $audit = DB::table('audits as a')
                ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
                ->join('countries as c', 'l.country_id', '=', 'c.id')
                ->where('a.id', (int) $auditId)
                ->select('a.*', 'l.name as lab_name', 'l.lab_number', 'c.name as country_name', 'l.country_id')
                ->first();

            if (! $audit) {
                return redirect()->route('audits.select')->with('error', 'Audit not found');
            }

            $readOnly = ! $this->canEditAudit($userContext, (int) $auditId);

            // Batch load catalog & responses (no N+1)
            $sections   = DB::table('slipta_sections')->orderBy('code')->get();
            $sectionIds = $sections->pluck('id')->all();

            $questions = DB::table('slipta_questions')
                ->whereIn('section_id', $sectionIds)
                ->orderByRaw('
        CAST(SUBSTRING_INDEX(q_code, ".", 1) AS UNSIGNED),
        CAST(SUBSTRING_INDEX(q_code, ".", -1) AS UNSIGNED)
    ')
                ->get();
            $questionIds = $questions->pluck('id')->all();

            $subQuestions = DB::table('slipta_subquestions')
                ->whereIn('question_id', $questionIds ?: [0])
                ->orderBy('sub_code')
                ->get();

            $responses = DB::table('audit_responses')
                ->where('audit_id', (int) $auditId)
                ->whereIn('question_id', $questionIds ?: [0])
                ->get()
                ->keyBy('question_id');

            $subResponsesRaw = DB::table('audit_subquestion_responses')
                ->where('audit_id', (int) $auditId)
                ->whereIn('subquestion_id', $subQuestions->pluck('id')->all() ?: [0])
                ->get();

            $evidenceRaw = DB::table('audit_evidence')
                ->where('audit_id', (int) $auditId)
                ->whereIn('question_id', $questionIds ?: [0])
                ->get();

            // Map helpers
            $subResponsesById = [];
            foreach ($subResponsesRaw as $sr) {
                $subResponsesById[$sr->subquestion_id] = $sr;
            }

            $evidenceByQuestion = [];
            foreach ($evidenceRaw as $ev) {
                $evidenceByQuestion[$ev->question_id][] = $ev;
            }

            $subByQuestion = [];
            foreach ($subQuestions as $sq) {
                $subByQuestion[$sq->question_id][] = $sq;
            }

            $questionsBySection = [];
            foreach ($questions as $q) {
                $questionsBySection[$q->section_id][] = $q;
            }

            $sectionsData = [];
            foreach ($sections as $section) {
                $qList = $questionsBySection[$section->id] ?? [];
                $qData = [];
                foreach ($qList as $question) {
                    $subs       = $subByQuestion[$question->id] ?? [];
                    $subsMapped = [];
                    foreach ($subs as $sub) {
                        $subsMapped[$sub->id] = $subResponsesById[$sub->id] ?? null;
                    }

                    $qData[] = [
                        'question'      => $question,
                        'subquestions'  => $subs,
                        'response'      => $responses[$question->id] ?? null,
                        'sub_responses' => $subsMapped,
                        'evidence'      => $evidenceByQuestion[$question->id] ?? [],
                    ];
                }

                $sectionsData[] = [
                    'section'   => $section,
                    'questions' => $qData,
                ];
            }

            $progress = $this->calculateProgress((int) $auditId);
            $scores   = $this->calculateScores((int) $auditId);

            return view('audits.responses', [
                'audit'       => $audit,
                'sections'    => $sectionsData,
                'progress'    => $progress,
                'scores'      => $scores,
                'userContext' => $userContext,
                'readOnly'    => $readOnly,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to load audit responses', [
                'audit_id' => $auditId,
                'error'    => $e->getMessage(),
            ]);
            return redirect()->route('audits.select')->with('error', 'Failed to load audit: ' . $e->getMessage());
        }
    }

    public function storeResponse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id'         => 'required|integer|exists:audits,id',
            'question_id'      => 'required|integer|exists:slipta_questions,id',
            'answer'           => 'required|in:Y,P,N,NA',
            'comment'          => [
                'nullable', 'string', 'max:5000',
                Rule::requiredIf(fn() => in_array($request->input('answer'), ['P', 'N', 'NA'], true)),
            ],
            'na_justification' => [
                'nullable', 'string', 'max:5000',
                Rule::requiredIf(fn() => $request->input('answer') === 'NA'),
            ],
        ], [
            'answer.in'                 => 'Answer must be Y, P, N, or NA',
            'comment.required'          => 'Comment is REQUIRED for P, N, or NA responses',
            'na_justification.required' => 'Justification is REQUIRED for NA responses',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $userContext = $this->getUserContext();

            if (! $this->canEditAudit($userContext, (int) $request->audit_id)) {
                return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
            }

            $inProgress = $this->getScopedInProgressAudits($userContext)
                ->where('a.id', (int) $request->audit_id)
                ->exists();
            if (! $inProgress) {
                return response()->json(['success' => false, 'error' => 'This audit is not in progress or not within your scope.'], 403);
            }

            $question = DB::table('slipta_questions')->where('id', (int) $request->question_id)->first();
            if (! $question) {
                throw new Exception('Question not found');
            }

            // SLIPTA composite gating: if requires_all_subs_for_yes, enforce all sub-questions answered Y/NA
            if ((int) $question->requires_all_subs_for_yes === 1 && $request->answer === 'Y') {
                $subIds = DB::table('slipta_subquestions')
                    ->where('question_id', (int) $request->question_id)
                    ->pluck('id');

                if ($subIds->count() > 0) {
                    $subResponses = DB::table('audit_subquestion_responses')
                        ->where('audit_id', (int) $request->audit_id)
                        ->whereIn('subquestion_id', $subIds->all())
                        ->get();

                    if ($subResponses->count() !== $subIds->count()) {
                        throw new Exception('VALIDATION ERROR: All sub-questions must be answered before Y');
                    }
                    $invalid = $subResponses->first(fn($sr) => ! in_array($sr->answer, ['Y', 'NA'], true));
                    if ($invalid) {
                        throw new Exception('VALIDATION ERROR: All sub-questions must be Y or NA to mark Y');
                    }
                }
            }

            $answer  = $request->answer;
            $comment = in_array($answer, ['P', 'N', 'NA'], true) ? (string) ($request->comment ?? '') : null;
            $naJust  = $answer === 'NA' ? (string) ($request->na_justification ?? '') : null;

            DB::table('audit_responses')->updateOrInsert(
                ['audit_id' => (int) $request->audit_id, 'question_id' => (int) $request->question_id],
                [
                    'answer'           => $answer,
                    'comment'          => $comment,
                    'na_justification' => $naJust,
                    'responded_by'     => auth()->id(),
                    'updated_at'       => now(),
                ]
            );

            // Auto-create finding on P/N
            if (in_array($answer, ['P', 'N'], true)) {
                $this->createFindingFromResponse((int) $request->audit_id, (int) $request->question_id, $answer, $comment);
            }

            DB::table('audits')->where('id', (int) $request->audit_id)->update([
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            DB::commit();

            $progress = $this->calculateProgress((int) $request->audit_id);
            $scores   = $this->calculateScores((int) $request->audit_id);

            return response()->json([
                'success'  => true,
                'message'  => 'Response saved successfully',
                'progress' => $progress,
                'scores'   => $scores,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Response submission failed', [
                'error'   => $e->getMessage(),
                'payload' => $request->except(['_token']),
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function storeSubResponse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audit_id'       => 'required|integer|exists:audits,id',
            'subquestion_id' => 'required|integer|exists:slipta_subquestions,id',
            'question_id'    => 'required|integer|exists:slipta_questions,id',
            'answer'         => 'required|in:Y,P,N,NA',
            'comment'        => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $userContext = $this->getUserContext();

            if (! $this->canEditAudit($userContext, (int) $request->audit_id)) {
                return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
            }

            $inProgress = $this->getScopedInProgressAudits($userContext)
                ->where('a.id', (int) $request->audit_id)
                ->exists();
            if (! $inProgress) {
                return response()->json(['success' => false, 'error' => 'This audit is not in progress or not within your scope.'], 403);
            }

            $comment = $request->comment ? (string) $request->comment : null;

            // Save sub-question response
            DB::table('audit_subquestion_responses')->updateOrInsert(
                ['audit_id' => (int) $request->audit_id, 'subquestion_id' => (int) $request->subquestion_id],
                [
                    'answer'       => $request->answer,
                    'comment'      => $comment,
                    'responded_by' => auth()->id(),
                    'updated_at'   => now(),
                ]
            );

            // CRITICAL: Check if this change invalidates parent Y answer
            $parentInvalidated = false;
            $newParentAnswer   = null;
            $parentComment     = null;

            $question = DB::table('slipta_questions')->where('id', (int) $request->question_id)->first();

            if ($question && (int) $question->requires_all_subs_for_yes === 1) {
                // Get parent response
                $parentResponse = DB::table('audit_responses')
                    ->where('audit_id', (int) $request->audit_id)
                    ->where('question_id', (int) $request->question_id)
                    ->first();

                // If parent is Y and sub-question is now P or N, invalidate parent
                if ($parentResponse && $parentResponse->answer === 'Y' && in_array($request->answer, ['P', 'N'], true)) {

                    // Change parent to P with auto-generated comment
                    $newParentAnswer = 'P';
                    $parentComment   = 'Auto-changed from Y to P due to sub-question conflict: Sub-question ' .
                    $request->subquestion_id . ' was changed to ' . $request->answer;

                    // FIX: Use only fields that exist in audit_responses table
                    DB::table('audit_responses')->where('id', $parentResponse->id)->update([
                        'answer'     => $newParentAnswer,
                        'comment'    => $parentComment,
                        'updated_at' => now(), // This will auto-update anyway, but explicit is fine
                    ]);

                    $parentInvalidated = true;

                    // Create audit trail for this automatic change
                    Log::info('Parent answer auto-invalidated', [
                        'audit_id'    => $request->audit_id,
                        'question_id' => $request->question_id,
                        'old_answer'  => 'Y',
                        'new_answer'  => $newParentAnswer,
                        'reason'      => 'Sub-question changed to ' . $request->answer,
                        'changed_by'  => auth()->id(),
                    ]);
                }
            }

            // Update audit timestamp (audits table has updated_by)
            DB::table('audits')->where('id', (int) $request->audit_id)->update([
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Recalculate scores and progress after change
            $progress = $this->calculateProgress((int) $request->audit_id);
            $scores   = $this->calculateScores((int) $request->audit_id);

            return response()->json([
                'success'            => true,
                'message'            => 'Sub-question response saved successfully',
                'parent_invalidated' => $parentInvalidated,
                'new_parent_answer'  => $newParentAnswer,
                'parent_comment'     => $parentComment,
                'progress'           => $progress,
                'scores'             => $scores,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Sub-response submission failed', [
                'error'   => $e->getMessage(),
                'payload' => $request->except(['_token']),
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function uploadEvidence(Request $request)
    {
        // CRITICAL FIX 3: Now uses 'public' disk instead of 'local'
        $validator = Validator::make($request->all(), [
            'audit_id'      => 'required|integer|exists:audits,id',
            'question_id'   => 'required|integer|exists:slipta_questions,id',
            'evidence_file' => 'required|file|max:10240',
            'display_name'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $filePath = null;

        DB::beginTransaction();
        try {
            $userContext = $this->getUserContext();

            if (! $this->canEditAudit($userContext, (int) $request->audit_id)) {
                return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
            }

            $inProgress = $this->getScopedInProgressAudits($userContext)
                ->where('a.id', (int) $request->audit_id)
                ->exists();
            if (! $inProgress) {
                return response()->json(['success' => false, 'error' => 'This audit is not in progress or not within your scope.'], 403);
            }

            $file         = $request->file('evidence_file');
            $originalName = $file->getClientOriginalName();
            $mimeType     = $file->getMimeType();
            $fileSize     = $file->getSize();

            // Minimal allowlist
            $allowedStarts = ['image/', 'application/pdf'];
            $allowedExact  = ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $ok            = (collect($allowedStarts)->first(fn($p) => str_starts_with($mimeType, $p)) !== null)
            || in_array($mimeType, $allowedExact, true);
            if (! $ok) {
                throw new Exception('Unsupported evidence MIME type');
            }

            $safeName = preg_replace('/[^\w.\-]/', '_', $originalName);
            $filename = time() . '_' . (int) $request->audit_id . '_' . (int) $request->question_id . '_' . $safeName;

            // CRITICAL FIX 3: Store on 'public' disk (ensures asset() URLs work)
            $filePath = $file->storeAs('audit_evidence', $filename, 'public');

            $absPath  = Storage::disk('public')->path($filePath);
            $fileHash = hash_file('sha256', $absPath);

            $type = str_starts_with($mimeType, 'image/') ? 'image'
                : (in_array($mimeType, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true) ? 'document' : 'file');

            $evidenceId = DB::table('audit_evidence')->insertGetId([
                'audit_id'      => (int) $request->audit_id,
                'question_id'   => (int) $request->question_id,
                'original_name' => $originalName,
                'type'          => $type,
                'display_name'  => $request->display_name ?: $originalName,
                'file_path'     => $filePath,
                'file_size'     => $fileSize,
                'mime_type'     => $mimeType,
                'file_hash'     => $fileHash,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Evidence uploaded successfully',
                'evidence' => [
                    'id'           => $evidenceId,
                    'display_name' => $request->display_name ?: $originalName,
                    'type'         => $type,
                    'file_size'    => $fileSize,
                    'file_path'    => $filePath,
                ],
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            Log::error('Evidence upload failed', [
                'error'       => $e->getMessage(),
                'audit_id'    => $request->audit_id ?? null,
                'question_id' => $request->question_id ?? null,
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    protected function calculateProgress($auditId): array
    {
        $totalQuestions = (int) DB::table('slipta_questions')->count();

        $answeredQuestions = (int) DB::table('audit_responses')
            ->where('audit_id', (int) $auditId)
            ->count();

        $naQuestions = (int) DB::table('audit_responses')
            ->where('audit_id', (int) $auditId)
            ->where('answer', 'NA')
            ->count();

        $answerableQuestions = max(0, $totalQuestions - $naQuestions);
        $answeredNonNA       = max(0, $answeredQuestions - $naQuestions);

        $overallPctTraditional = $totalQuestions > 0
            ? round(($answeredQuestions / $totalQuestions) * 100, 1) : 0;

        $overallPctNonNA = $answerableQuestions > 0
            ? round(($answeredNonNA / $answerableQuestions) * 100, 1) : 0;

        $sectionProgress = DB::table('slipta_sections as s')
            ->leftJoin('slipta_questions as q', 's.id', '=', 'q.section_id')
            ->leftJoin('audit_responses as r', function ($join) use ($auditId) {
                $join->on('q.id', '=', 'r.question_id')
                    ->where('r.audit_id', (int) $auditId);
            })
            ->select(
                's.id', 's.code', 's.title',
                DB::raw('COUNT(q.id) as total_questions'),
                DB::raw('COUNT(r.id) as answered_questions'),
                DB::raw('SUM(CASE WHEN r.answer = "NA" THEN 1 ELSE 0 END) as na_questions')
            )
            ->groupBy('s.id', 's.code', 's.title')
            ->get();

        return [
            'total_questions'        => $totalQuestions,
            'answered_questions'     => $answeredQuestions,
            'na_questions'           => $naQuestions,
            'answerable_questions'   => $answerableQuestions,
            'answered_non_na'        => $answeredNonNA,
            'percentage_traditional' => $overallPctTraditional,
            'percentage_non_na'      => $overallPctNonNA,
            'sections'               => $sectionProgress,
        ];
    }

    protected function calculateScores($auditId): array
    {
        $this->validateSystemIntegrity();

        // ENUM bug avoidance: explicit CASE statement
        $responses = DB::table('audit_responses as r')
            ->join('slipta_questions as q', 'r.question_id', '=', 'q.id')
            ->where('r.audit_id', (int) $auditId)
            ->select(
                'r.answer',
                DB::raw("CASE WHEN q.weight = '2' THEN 2 WHEN q.weight = '3' THEN 3 ELSE NULL END as weight_value"),
                'q.section_id',
                'q.q_code'
            )
            ->get();

        $totalEarned   = 0;
        $totalNaPoints = 0;
        $sectionScores = [];

        foreach ($responses as $resp) {
            $sid = $resp->section_id;
            if (! isset($sectionScores[$sid])) {
                $sectionScores[$sid] = ['earned' => 0, 'na_points' => 0];
            }

            if (in_array($resp->answer, ['Y', 'NA'], true) && $resp->weight_value === null) {
                throw new \RuntimeException("Invalid weight for question {$resp->q_code}");
            }

            switch ($resp->answer) {
                case 'Y':
                    $pts = (int) $resp->weight_value;
                    $totalEarned += $pts;
                    $sectionScores[$sid]['earned'] += $pts;
                    break;
                case 'P':
                    $totalEarned += 1;
                    $sectionScores[$sid]['earned'] += 1;
                    break;
                case 'N':
                    break;
                case 'NA':
                    $na = (int) $resp->weight_value;
                    $totalNaPoints += $na;
                    $sectionScores[$sid]['na_points'] += $na;
                    break;
            }
        }

        // Section max from catalog (resilient to catalog updates)
        $catalogSectionMax = DB::table('slipta_questions')
            ->select('section_id', DB::raw("SUM(CASE WHEN weight='2' THEN 2 WHEN weight='3' THEN 3 ELSE 0 END) as max_points"))
            ->groupBy('section_id')
            ->pluck('max_points', 'section_id')->toArray();

        foreach ($sectionScores as $sid => &$s) {
            $max                       = (int) ($catalogSectionMax[$sid] ?? 0);
            $adj                       = max(0, $max - (int) $s['na_points']);
            $pct                       = $adj > 0 ? round(($s['earned'] / $adj) * 100, 2) : 0.0;
            $s['max_points']           = $max;
            $s['adjusted_denominator'] = $adj;
            $s['percentage']           = $pct;
        }
        unset($s);

        $baseTotal           = 367;
        $adjustedDenominator = max(0, $baseTotal - $totalNaPoints);
        $percentage          = $adjustedDenominator > 0 ? ($totalEarned / $adjustedDenominator) * 100 : 0.0;
        $starLevel           = $this->determineStarLevel($percentage);

        return [
            'total_earned'         => $totalEarned,
            'total_possible'       => $baseTotal,
            'na_points_excluded'   => $totalNaPoints,
            'adjusted_denominator' => $adjustedDenominator,
            'percentage'           => round($percentage, 2),
            'star_level'           => $starLevel,
            'section_scores'       => $sectionScores,
        ];
    }

    protected function validateSystemIntegrity(): void
    {
        $invalidWeightCount = (int) DB::table('slipta_questions')
            ->whereNotIn('weight', ['2', '3'])
            ->count();
        if ($invalidWeightCount > 0) {
            throw new \RuntimeException("CRITICAL DRIFT: Found {$invalidWeightCount} questions with non-2/3 weights.");
        }

        $actualTotal = (int) DB::table('slipta_questions')
            ->selectRaw("COALESCE(SUM(CASE WHEN weight='2' THEN 2 WHEN weight='3' THEN 3 ELSE 0 END),0) as total")
            ->value('total');

        if ($actualTotal !== 367) {
            throw new \RuntimeException("CRITICAL DRIFT: Catalog total points = {$actualTotal}, expected 367.");
        }
    }

    protected function determineStarLevel(float $percentage): int
    {
        if ($percentage >= 95.0) {
            return 5;
        }

        if ($percentage >= 85.0) {
            return 4;
        }

        if ($percentage >= 75.0) {
            return 3;
        }

        if ($percentage >= 65.0) {
            return 2;
        }

        if ($percentage >= 55.0) {
            return 1;
        }

        return 0;
    }

    protected function createFindingFromResponse(int $auditId, int $questionId, string $answer, string $comment = null): void
    {
        $question = DB::table('slipta_questions')->where('id', $questionId)->first();
        if (! $question) {
            return;
        }

        $severity = $answer === 'N' ? 'high' : 'medium';

        DB::table('audit_findings')->insert([
            'audit_id'    => $auditId,
            'section_id'  => $question->section_id,
            'question_id' => $questionId,
            'title'       => 'Non-compliance: ' . $question->q_code,
            'description' => $comment,
            'severity'    => $severity,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    protected function getUserContext(): array
    {
        $user = auth()->user();

        $roles = DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $user->id)
            ->where('ur.is_active', 1)
            ->select('r.name', 'ur.country_id', 'ur.laboratory_id')
            ->get();

        $names = $roles->pluck('name');

        return [
            'user'             => $user,
            'roles'            => $roles,
            'role_names'       => $names,
            'has_global_view'  => $names->contains('system_admin') || $names->contains('project_coordinator'),
            'country_ids'      => $roles->pluck('country_id')->filter()->unique()->values()->all(),
            'laboratory_ids'   => $roles->pluck('laboratory_id')->filter()->unique()->values()->all(),
            'is_country_coord' => $names->contains('country_coordinator'),
        ];
    }

    protected function canAccessAudit(array $ctx, int $auditId): bool
    {
        $audit = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->select('a.id', 'a.laboratory_id', 'l.country_id')
            ->where('a.id', $auditId)
            ->first();

        if (! $audit) {
            return false;
        }

        if ($ctx['has_global_view']) {
            return true;
        }

        if (! empty($ctx['is_country_coord']) && in_array($audit->country_id, $ctx['country_ids'], true)) {
            return true;
        }

        $isTeamMember = DB::table('audit_team_members')
            ->where('audit_id', $auditId)
            ->where('user_id', $ctx['user']->id)
            ->exists();
        if ($isTeamMember) {
            return true;
        }

        return in_array($audit->laboratory_id, $ctx['laboratory_ids'], true);
    }

    // CRITICAL FIX 1: Admins/coordinators can now edit
    protected function canEditAudit(array $ctx, int $auditId): bool
    {
        // Allow global roles to edit any audit
        if ($ctx['role_names']->contains('system_admin')
            || $ctx['role_names']->contains('project_coordinator')
            || $ctx['role_names']->contains('country_coordinator')) {
            return true;
        }

        // Otherwise must be lead/member on the audit team
        return DB::table('audit_team_members')
            ->where('audit_id', $auditId)
            ->where('user_id', $ctx['user']->id)
            ->whereIn('role', ['lead', 'member'])
            ->exists();
    }

    protected function getScopedInProgressAudits(array $ctx)
    {
        $q = DB::table('audits as a')
            ->join('laboratories as l', 'a.laboratory_id', '=', 'l.id')
            ->join('countries as c', 'l.country_id', '=', 'c.id')
            ->leftJoin('audit_team_members as atm', function ($j) use ($ctx) {
                $j->on('atm.audit_id', '=', 'a.id')
                    ->where('atm.user_id', $ctx['user']->id);
            })
            ->whereRaw('LOWER(a.status) = ?', ['in_progress'])
            ->select('a.*', 'l.name as lab_name', 'l.lab_number', 'l.country_id', 'c.name as country_name');

        if ($ctx['has_global_view']) {
            return $q;
        }

        if (! empty($ctx['is_country_coord']) && ! empty($ctx['country_ids'])) {
            $q->whereIn('l.country_id', $ctx['country_ids']);
        } else {
            $q->where(function ($w) use ($ctx) {
                $w->whereNotNull('atm.user_id');
                if (! empty($ctx['laboratory_ids'])) {
                    $w->orWhereIn('a.laboratory_id', $ctx['laboratory_ids']);
                }
            });
        }
        return $q;
    }
}
