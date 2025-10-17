<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\FindingsController;
use App\Http\Controllers\AuditTeamController;
use App\Http\Controllers\CountriesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActionPlansController;
use App\Http\Controllers\LaboratoriesController;
use App\Http\Controllers\AuditResponseController;
use App\Http\Controllers\SliptaSectionsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SliptaQuestionsController;


// Dashboard Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard_main', [DashboardController::class, 'index'])->name('dashboard_main');
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});



// SLIPTA Report Generation Routes (RBAC Protected)
Route::middleware(['auth'])->group(function () {
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/laboratories/{countryId}', [ReportsController::class, 'getLaboratories']);
    Route::get('/reports/audits/{laboratoryId}', [ReportsController::class, 'getAudits']);
    Route::get('/reports/generate/{auditId}', [ReportsController::class, 'generate'])->name('reports.generate');
});

// Get questions by section for findings modal
Route::get('/sections/{sectionId}/questions', function($sectionId) {
    $questions = DB::table('slipta_questions')
        ->where('section_id', (int)$sectionId)
        ->orderBy('q_code')
        ->select('id', 'q_code', 'text')
        ->get();

    return response()->json($questions);
})->middleware('auth');
// Add these routes to your web.php file inside the auth middleware group

Route::prefix('findings')->name('findings.')->group(function () {
    // List all audits with responses
    Route::get('/', [FindingsController::class, 'index'])->name('index');

    // Show findings for specific audit
    Route::get('/{auditId}', [FindingsController::class, 'show'])->name('show');

    // CRUD operations for findings
    Route::post('/', [FindingsController::class, 'store'])->name('store');
    Route::put('/{findingId}', [FindingsController::class, 'update'])->name('update');
    Route::delete('/{findingId}', [FindingsController::class, 'destroy'])->name('destroy');

    // Advanced actions
    Route::post('/auto-sync/{auditId}', [FindingsController::class, 'autoSyncFindings'])->name('auto-sync');
    Route::post('/generate-action-plans/{auditId}', [FindingsController::class, 'generateActionPlans'])->name('generate-action-plans');
    Route::post('/close-audit/{auditId}', [FindingsController::class, 'closeAudit'])->name('close-audit');
    Route::post('/reopen-audit/{auditId}', [FindingsController::class, 'reopenAudit'])->name('reopen-audit');

    // Reports
    Route::get('/download-report/{auditId}', [FindingsController::class, 'downloadReport'])->name('download-report');
});

// Route::get('/', function () {
//     return view('layouts.app');
// });


Route::middleware(['auth'])->group(function () {

    // Audit selection (HTML page)
    Route::get('/audits/select', [AuditResponseController::class, 'index'])
        ->name('audits.select');

    // Audit selection GATE (AJAX → merges/saves snapshot; blocks if missing auditors/core)
    Route::post('/audits/select', [AuditResponseController::class, 'index'])
        ->name('audits.select.gate');

    // Audit workspace
    Route::get('/audits/{auditId}', [AuditResponseController::class, 'show'])
        ->whereNumber('auditId')
        ->name('audits.show');

    // Save responses
    Route::post('/audits/response', [AuditResponseController::class, 'storeResponse'])
        ->name('audits.responses.store');

    Route::post('/audits/sub-response', [AuditResponseController::class, 'storeSubResponse'])
        ->name('audits.subresponses.store');

    Route::post('/audits/evidence', [AuditResponseController::class, 'uploadEvidence'])
        ->name('audits.evidence.upload');
});


// Audit Linking Routes (scope-enforced)
Route::middleware(['auth'])->prefix('audits/linking')->name('audits.linking.')->group(function () {
    // Main linking interface
    Route::get('/', [App\Http\Controllers\AuditLinkingController::class, 'index'])
        ->name('index');

    // Get linkable audits for a laboratory (API)
    Route::get('/linkable', [App\Http\Controllers\AuditLinkingController::class, 'getLinkableAudits'])
        ->name('linkable');

    // Link audit to prior audit
    Route::post('/link', [App\Http\Controllers\AuditLinkingController::class, 'linkAudit'])
        ->name('link');

    // Unlink audit from prior audit
    Route::post('/unlink', [App\Http\Controllers\AuditLinkingController::class, 'unlinkAudit'])
        ->name('unlink');
});

// Audit Team Management Routes
Route::middleware(['auth'])->prefix('audits/team')->group(function () {
    Route::get('/', [AuditTeamController::class, 'index'])->name('audits.team.index');
    Route::get('/get', [AuditTeamController::class, 'getTeam'])->name('audits.team.get');
    Route::post('/assign', [AuditTeamController::class, 'assignTeam'])->name('audits.team.assign');
    Route::post('/add-member', [AuditTeamController::class, 'addMember'])->name('audits.team.add-member');
    Route::post('/remove-member', [AuditTeamController::class, 'removeMember'])->name('audits.team.remove-member');
    Route::post('/update-role', [AuditTeamController::class, 'updateMemberRole'])->name('audits.team.update-role');
});

// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/audits', [AuditController::class, 'index'])->name('audits.index');
    Route::post('/audits', [AuditController::class, 'store'])->name('audits.store');
    Route::put('/audits/{id}', [AuditController::class, 'update'])->name('audits.update');
    Route::delete('/audits/{id}', [AuditController::class, 'destroy'])->name('audits.destroy');
});

Route::middleware(['auth'])->group(function () {

    // Route::get('/audits/workspace', [AuditWorkspaceController::class, 'index'])->name('audits.workspace');
    // Route::post('/audits', [AuditWorkspaceController::class, 'store'])->name('audits.store');
    // Route::post('/audits/link-prior', [AuditWorkspaceController::class, 'linkPriorAudit'])->name('audits.link-prior');
    // Route::post('/audits/assign-team', [AuditWorkspaceController::class, 'assignTeam'])->name('audits.assign-team');
    // Route::get('/audits/linkable', [AuditWorkspaceController::class, 'getLinkableAudits'])->name('audits.linkable');
    // Route::get('/audits/team', [AuditWorkspaceController::class, 'getAuditTeam'])->name('audits.team');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserManagementController::class, 'update'])->name('users.update');
    Route::post('/users/{id}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::get('/users/{id}', [UserManagementController::class, 'show'])->name('users.show');
    Route::post('/users/assign-role', [UserManagementController::class, 'assignRole'])->name('users.assign-role');
    Route::delete('/users/roles/{userRoleId}', [UserManagementController::class, 'revokeRole'])->name('users.revoke-role');
});

Route::prefix('slipta/questions')->name('slipta.questions.')->group(function () {

    Route::get('/', [SliptaQuestionsController::class, 'index'])
        ->name('index');

    Route::post('/', [SliptaQuestionsController::class, 'store'])
        ->name('store');

    Route::put('/{id}', [SliptaQuestionsController::class, 'update'])
        ->name('update');

    Route::delete('/{id}', [SliptaQuestionsController::class, 'destroy'])
        ->name('destroy');

    Route::get('/get-section/{sectionId}', [SliptaQuestionsController::class, 'getSectionQuestions'])
        ->name('get.section.questions');

    Route::get('/get-details/{questionId}', [SliptaQuestionsController::class, 'getQuestionDetails'])
        ->name('get.question.details');

    Route::post('/validate-import', [SliptaQuestionsController::class, 'validateImport'])
        ->name('validate.import');

    Route::post('/bulk-import', [SliptaQuestionsController::class, 'bulkImport'])
        ->name('bulk.import');
});

// Main sections route group
Route::prefix('slipta/sections')->name('slipta.sections.')->group(function () {

    // Primary route - single view handles all operations
    Route::get('/', [SliptaSectionsController::class, 'index'])->name('index');

    // CRUD operations (support both AJAX and native responses)
    Route::post('/', [SliptaSectionsController::class, 'store'])->name('store');
    Route::get('/{section}', [SliptaSectionsController::class, 'show'])->name('show');
    Route::put('/{section}', [SliptaSectionsController::class, 'update'])->name('update');
    Route::delete('/{section}', [SliptaSectionsController::class, 'destroy'])->name('destroy');

    // Special operations
    Route::post('/initialize-all', [SliptaSectionsController::class, 'initializeAll'])->name('initialize-all');
    Route::get('/data', [SliptaSectionsController::class, 'getData'])->name('data');
});

// Add this route to handle CSRF token refresh
Route::get('/csrf-token', function () {
    return response()->json([
        'token'   => csrf_token(),
        'success' => true,
    ]);
});

// Add to routes/web.php

Route::middleware(['auth'])->group(function () {

// Laboratories management
    Route::get('/laboratories', [LaboratoriesController::class, 'index'])->name('laboratories.index');
    Route::post('/laboratories', [LaboratoriesController::class, 'store'])->name('laboratories.store');
    Route::put('/laboratories/{id}', [LaboratoriesController::class, 'update'])->name('laboratories.update');
    Route::get('/laboratories/{id}', [LaboratoriesController::class, 'show'])->name('laboratories.show');
    Route::delete('/laboratories/{id}', [LaboratoriesController::class, 'destroy'])->name('laboratories.destroy');

    Route::get('/countries', [CountriesController::class, 'index'])->name('countries.index');
    Route::post('/countries', [CountriesController::class, 'store'])->name('countries.store');
    Route::get('/countries/{id}', [CountriesController::class, 'show'])->name('countries.show');
    Route::delete('/countries/{id}', [CountriesController::class, 'destroy'])->name('countries.destroy');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
