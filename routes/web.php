<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FacultyProfileController;
use App\Http\Controllers\ReclassificationFormController;
use App\Http\Controllers\ReclassificationPeriodController;
use App\Http\Controllers\ReclassificationReviewController;
use App\Http\Controllers\ReclassificationWorkflowController;
use App\Http\Controllers\ReclassificationEvidenceReviewController;
use App\Http\Controllers\ReclassificationRowCommentController;
use App\Http\Controllers\ReclassificationAdminController;
use App\Models\ReclassificationApplication;
use App\Models\ReclassificationPeriod;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Generic dashboard entrypoint: redirect to role dashboard
    Route::get('/dashboard', function () {
        $role = strtolower((string) (request()->user()->role ?? ''));

        return match ($role) {
            'faculty' => redirect()->route('faculty.dashboard'),
            'dean' => redirect()->route('dean.dashboard'),
            'hr' => redirect()->route('hr.dashboard'),
            'vpaa' => redirect()->route('vpaa.dashboard'),
            'president' => redirect()->route('president.dashboard'),
            default => view('dashboard'),
        };
    })->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Role dashboards
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:faculty'])->get('/faculty/dashboard', function () {
        $user = request()->user()->load(['department', 'facultyProfile', 'facultyHighestDegree']);
        $applications = ReclassificationApplication::where('faculty_user_id', $user->id)
            ->latest()
            ->get();
        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');
        $activePeriod = ReclassificationPeriod::query()
            ->when(
                Schema::hasColumn('reclassification_periods', 'status'),
                fn ($query) => $query->where('status', 'active'),
                fn ($query) => $query->where('is_open', true)
            )
            ->orderByDesc('created_at')
            ->first();
        $openPeriod = ReclassificationPeriod::query()
            ->when(
                Schema::hasColumn('reclassification_periods', 'status'),
                fn ($query) => $query->where('status', 'active')->where('is_open', true),
                fn ($query) => $query->where('is_open', true)
            )
            ->orderByDesc('created_at')
            ->first();
        $currentCycleRejectedApplication = null;
        $currentCycleFinalizedApplication = null;
        if ($activePeriod) {
            $currentCycleRejectedApplication = ReclassificationApplication::query()
                ->where('faculty_user_id', $user->id)
                ->where('status', 'rejected_final')
                ->when(
                    $hasPeriodId,
                    function ($query) use ($activePeriod) {
                        $query->where(function ($builder) use ($activePeriod) {
                            $builder->where('period_id', $activePeriod->id);
                            if (!empty($activePeriod->cycle_year)) {
                                $builder->orWhere(function ($fallback) use ($activePeriod) {
                                    $fallback->whereNull('period_id')
                                        ->where('cycle_year', $activePeriod->cycle_year);
                                });
                            }
                        });
                    },
                    function ($query) use ($activePeriod) {
                        if (!empty($activePeriod->cycle_year)) {
                            $query->where('cycle_year', $activePeriod->cycle_year);
                        } else {
                            $query->whereRaw('1 = 0');
                        }
                    }
                )
                ->latest('updated_at')
                ->first();

            $currentCycleFinalizedApplication = ReclassificationApplication::query()
                ->where('faculty_user_id', $user->id)
                ->where('status', 'finalized')
                ->when(
                    $hasPeriodId,
                    function ($query) use ($activePeriod) {
                        $query->where(function ($builder) use ($activePeriod) {
                            $builder->where('period_id', $activePeriod->id);
                            if (!empty($activePeriod->cycle_year)) {
                                $builder->orWhere(function ($fallback) use ($activePeriod) {
                                    $fallback->whereNull('period_id')
                                        ->where('cycle_year', $activePeriod->cycle_year);
                                });
                            }
                        });
                    },
                    function ($query) use ($activePeriod) {
                        if (!empty($activePeriod->cycle_year)) {
                            $query->where('cycle_year', $activePeriod->cycle_year);
                        } else {
                            $query->whereRaw('1 = 0');
                        }
                    }
                )
                ->latest('updated_at')
                ->first();
        }
        $promotionNotification = null;
        if (Schema::hasTable('notifications')) {
            $promotionNotification = $user->unreadNotifications()
                ->where('type', \App\Notifications\ReclassificationPromotedNotification::class)
                ->latest()
                ->first();
            if ($promotionNotification) {
                $promotionNotification->markAsRead();
            }
        }

        return view('dashboards.faculty', compact(
            'user',
            'applications',
            'promotionNotification',
            'activePeriod',
            'openPeriod',
            'currentCycleRejectedApplication',
            'currentCycleFinalizedApplication',
        ));
    })->name('faculty.dashboard');

    Route::middleware(['role:dean'])->get('/dean/dashboard', function () {
        $user = request()->user()->load('department');
        $departmentId = $user->department_id;
        $activePeriod = ReclassificationPeriod::query()
            ->when(
                Schema::hasColumn('reclassification_periods', 'status'),
                fn ($query) => $query->where('status', 'active'),
                fn ($query) => $query->where('is_open', true)
            )
            ->orderByDesc('created_at')
            ->first();
        $hasActivePeriod = (bool) $activePeriod;
        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');

        $applyActivePeriodScope = function ($query) use ($activePeriod, $hasPeriodId) {
            if (!$activePeriod) {
                $query->whereRaw('1 = 0');
                return;
            }

            if (!$hasPeriodId) {
                if (!empty($activePeriod->cycle_year)) {
                    $query->where('cycle_year', $activePeriod->cycle_year);
                } else {
                    $query->whereRaw('1 = 0');
                }
                return;
            }

            $query->where(function ($builder) use ($activePeriod) {
                $builder->where('period_id', $activePeriod->id);
                if (!empty($activePeriod->cycle_year)) {
                    $builder->orWhere(function ($fallback) use ($activePeriod) {
                        $fallback->whereNull('period_id')
                            ->where('cycle_year', $activePeriod->cycle_year);
                    });
                }
            });
        };

        $statusCounts = ReclassificationApplication::query()
            ->where('status', '!=', 'draft')
            ->when($departmentId, function ($query) use ($departmentId) {
                $query->whereHas('faculty', function ($faculty) use ($departmentId) {
                    $faculty->where('department_id', $departmentId);
                });
            })
            ->tap($applyActivePeriodScope)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentApplications = ReclassificationApplication::query()
            ->when($departmentId, function ($query) use ($departmentId) {
                $query->whereHas('faculty', function ($faculty) use ($departmentId) {
                    $faculty->where('department_id', $departmentId);
                });
            })
            ->where('status', '!=', 'draft')
            ->tap($applyActivePeriodScope)
            ->with(['faculty.department'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $facultyCount = User::query()
            ->where('role', 'faculty')
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->count();

        $departmentName = $user->department?->name ?? 'Department';

        return view('dashboards.dean', compact(
            'statusCounts',
            'recentApplications',
            'facultyCount',
            'departmentName',
            'activePeriod',
            'hasActivePeriod',
        ));
    })->name('dean.dashboard');

    Route::middleware(['role:dean'])->group(function () {
        Route::get('/dean/users/create', [UserController::class, 'create'])
            ->name('dean.users.create');
        Route::post('/dean/users', [UserController::class, 'store'])
            ->name('dean.users.store');
        Route::get('/dean/faculty', [FacultyProfileController::class, 'index'])
            ->name('dean.faculty.index');
        Route::get('/dean/submissions', [ReclassificationAdminController::class, 'deanIndex'])
            ->name('dean.submissions');
        Route::get('/dean/approved', [ReclassificationAdminController::class, 'deanApproved'])
            ->name('dean.approved');
    });

    Route::middleware(['role:dean,hr,vpaa,president'])->prefix('reclassification')->group(function () {
        Route::get('/review-queue', [ReclassificationReviewController::class, 'index'])
            ->name('reclassification.review.queue');
        Route::get('/review/{application}', [ReclassificationReviewController::class, 'show'])
            ->name('reclassification.review.show');
        Route::post('/review/{application}/section2', [ReclassificationReviewController::class, 'saveSectionTwo'])
            ->name('reclassification.review.section2.save');
        Route::post('/review/{application}/section1-c/{entry}', [ReclassificationReviewController::class, 'updateSectionOneC'])
            ->name('reclassification.review.section1c.update');

        Route::get('/dean/review', [ReclassificationReviewController::class, 'index'])
            ->name('reclassification.dean.review');
        Route::get('/dean/review/{application}', [ReclassificationReviewController::class, 'show'])
            ->name('reclassification.dean.review.show');
        Route::post('/dean/review/{application}/section2', [ReclassificationReviewController::class, 'saveSectionTwo'])
            ->name('reclassification.dean.section2.save');
        Route::post('/dean/review/{application}/section1-c/{entry}', [ReclassificationReviewController::class, 'updateSectionOneC'])
            ->name('reclassification.dean.section1c.update');
    });

    Route::middleware(['role:hr'])->get('/hr/dashboard', function () {
        $activePeriod = ReclassificationPeriod::query()
            ->when(
                Schema::hasColumn('reclassification_periods', 'status'),
                fn ($query) => $query->where('status', 'active'),
                fn ($query) => $query->where('is_open', true)
            )
            ->orderByDesc('created_at')
            ->first();
        $hasActivePeriod = (bool) $activePeriod;
        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');

        $applyActivePeriodScope = function ($query) use ($activePeriod, $hasPeriodId) {
            if (!$activePeriod) {
                $query->whereRaw('1 = 0');
                return;
            }

            if (!$hasPeriodId) {
                if (!empty($activePeriod->cycle_year)) {
                    $query->where('cycle_year', $activePeriod->cycle_year);
                } else {
                    $query->whereRaw('1 = 0');
                }
                return;
            }

            $query->where(function ($builder) use ($activePeriod) {
                $builder->where('period_id', $activePeriod->id);
                if (!empty($activePeriod->cycle_year)) {
                    $builder->orWhere(function ($fallback) use ($activePeriod) {
                        $fallback->whereNull('period_id')
                            ->where('cycle_year', $activePeriod->cycle_year);
                    });
                }
            });
        };

        $statusCounts = ReclassificationApplication::query()
            ->where('status', '!=', 'draft')
            ->tap($applyActivePeriodScope)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $openPeriod = ReclassificationPeriod::query()
            ->when(
                Schema::hasColumn('reclassification_periods', 'status'),
                fn ($query) => $query->where('status', 'active')->where('is_open', true),
                fn ($query) => $query->where('is_open', true)
            )
            ->orderByDesc('created_at')
            ->first();

        $recentApplications = ReclassificationApplication::query()
            ->where('status', '!=', 'draft')
            ->tap($applyActivePeriodScope)
            ->with('faculty.department')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $facultyCount = User::query()->where('role', 'faculty')->count();

        return view('dashboards.hr', compact(
            'statusCounts',
            'openPeriod',
            'recentApplications',
            'facultyCount',
            'activePeriod',
            'hasActivePeriod',
        ));
    })->name('hr.dashboard');

    Route::middleware(['role:hr'])->prefix('reclassification')->group(function () {
        Route::get('/periods', [ReclassificationPeriodController::class, 'index'])
            ->name('reclassification.periods');
        Route::post('/periods', [ReclassificationPeriodController::class, 'store'])
            ->name('reclassification.periods.store');
        Route::delete('/periods/{period}', [ReclassificationPeriodController::class, 'destroy'])
            ->name('reclassification.periods.destroy');
        Route::post('/periods/{period}/toggle', [ReclassificationPeriodController::class, 'toggle'])
            ->name('reclassification.periods.toggle');
        Route::post('/periods/{period}/submission-toggle', [ReclassificationPeriodController::class, 'toggleSubmission'])
            ->name('reclassification.periods.submission.toggle');
        Route::post('/periods/{period}/window', [ReclassificationPeriodController::class, 'updateWindow'])
            ->name('reclassification.periods.window.update');
        Route::get('/submissions', [ReclassificationAdminController::class, 'index'])
            ->name('reclassification.admin.submissions');
        Route::post('/submissions/{application}/toggle-reject', [ReclassificationAdminController::class, 'toggleReject'])
            ->name('reclassification.admin.submissions.toggle-reject');
        Route::delete('/submissions/{application}', [ReclassificationAdminController::class, 'destroySubmission'])
            ->name('reclassification.admin.submissions.destroy');
        Route::get('/approved', [ReclassificationAdminController::class, 'approved'])
            ->name('reclassification.admin.approved');
    });

    Route::middleware(['role:vpaa'])->get('/vpaa/dashboard', function () {
        $activePeriod = ReclassificationPeriod::query()
            ->when(
                Schema::hasColumn('reclassification_periods', 'status'),
                fn ($query) => $query->where('status', 'active'),
                fn ($query) => $query->where('is_open', true)
            )
            ->orderByDesc('created_at')
            ->first();
        $hasActivePeriod = (bool) $activePeriod;
        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');

        $applyActivePeriodScope = function ($query) use ($activePeriod, $hasPeriodId) {
            if (!$activePeriod) {
                $query->whereRaw('1 = 0');
                return;
            }

            if (!$hasPeriodId) {
                if (!empty($activePeriod->cycle_year)) {
                    $query->where('cycle_year', $activePeriod->cycle_year);
                } else {
                    $query->whereRaw('1 = 0');
                }
                return;
            }

            $query->where(function ($builder) use ($activePeriod) {
                $builder->where('period_id', $activePeriod->id);
                if (!empty($activePeriod->cycle_year)) {
                    $builder->orWhere(function ($fallback) use ($activePeriod) {
                        $fallback->whereNull('period_id')
                            ->where('cycle_year', $activePeriod->cycle_year);
                    });
                }
            });
        };

        $statusCounts = ReclassificationApplication::query()
            ->tap($applyActivePeriodScope)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentApplications = ReclassificationApplication::query()
            ->with('faculty.department')
            ->where('status', '!=', 'draft')
            ->tap($applyActivePeriodScope)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $totalSubmissions = ReclassificationApplication::query()
            ->where('status', '!=', 'draft')
            ->tap($applyActivePeriodScope)
            ->count();

        return view('dashboards.vpaa', compact(
            'statusCounts',
            'recentApplications',
            'totalSubmissions',
            'activePeriod',
            'hasActivePeriod',
        ));
    })->name('vpaa.dashboard');

    Route::middleware(['role:president'])->get('/president/dashboard', function () {
        $activePeriod = ReclassificationPeriod::query()
            ->when(
                Schema::hasColumn('reclassification_periods', 'status'),
                fn ($query) => $query->where('status', 'active'),
                fn ($query) => $query->where('is_open', true)
            )
            ->orderByDesc('created_at')
            ->first();
        $hasActivePeriod = (bool) $activePeriod;
        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');

        $applyActivePeriodScope = function ($query) use ($activePeriod, $hasPeriodId) {
            if (!$activePeriod) {
                $query->whereRaw('1 = 0');
                return;
            }

            if (!$hasPeriodId) {
                if (!empty($activePeriod->cycle_year)) {
                    $query->where('cycle_year', $activePeriod->cycle_year);
                } else {
                    $query->whereRaw('1 = 0');
                }
                return;
            }

            $query->where(function ($builder) use ($activePeriod) {
                $builder->where('period_id', $activePeriod->id);
                if (!empty($activePeriod->cycle_year)) {
                    $builder->orWhere(function ($fallback) use ($activePeriod) {
                        $fallback->whereNull('period_id')
                            ->where('cycle_year', $activePeriod->cycle_year);
                    });
                }
            });
        };

        $statusCounts = ReclassificationApplication::query()
            ->tap($applyActivePeriodScope)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentApplications = ReclassificationApplication::query()
            ->with('faculty.department')
            ->where('status', '!=', 'draft')
            ->tap($applyActivePeriodScope)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $totalSubmissions = ReclassificationApplication::query()
            ->where('status', '!=', 'draft')
            ->tap($applyActivePeriodScope)
            ->count();

        return view('dashboards.president', compact(
            'statusCounts',
            'recentApplications',
            'totalSubmissions',
            'activePeriod',
            'hasActivePeriod',
        ));
    })->name('president.dashboard');

    Route::middleware(['role:vpaa,president'])->prefix('reclassification')->group(function () {
        Route::get('/all-submissions', [ReclassificationAdminController::class, 'index'])
            ->name('reclassification.review.submissions');
        Route::get('/approved-list', [ReclassificationAdminController::class, 'reviewerApproved'])
            ->name('reclassification.review.approved');
        Route::get('/approved-reclassification', [ReclassificationAdminController::class, 'reviewerFinalized'])
            ->name('reclassification.review.finalized');
    });

    Route::middleware(['role:dean,hr,vpaa,president'])->prefix('reclassification')->group(function () {
        Route::get('/approved/print', [ReclassificationAdminController::class, 'approvedPrint'])
            ->name('reclassification.approved.print');
        Route::get('/approved/export-csv', [ReclassificationAdminController::class, 'approvedExportCsv'])
            ->name('reclassification.approved.export.csv');
        Route::get('/history', [ReclassificationAdminController::class, 'history'])
            ->name('reclassification.history');
        Route::get('/history/{period}', [ReclassificationAdminController::class, 'historyPeriod'])
            ->name('reclassification.history.period');
        Route::get('/faculty-records', [FacultyProfileController::class, 'recordsIndex'])
            ->name('reclassification.faculty-records');
    });

    Route::middleware(['role:vpaa'])->prefix('reclassification')->group(function () {
        Route::post('/approved-list/forward', [ReclassificationAdminController::class, 'forwardApprovedToPresident'])
            ->name('reclassification.review.approved.forward');
    });

    Route::middleware(['role:president'])->prefix('reclassification')->group(function () {
        Route::post('/approved-list/finalize', [ReclassificationAdminController::class, 'finalizeApprovedByPresident'])
            ->name('reclassification.review.approved.finalize');
    });

    /*
    |----------------------------------------------------------------------
    | User Management (HR only)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:hr'])->group(function () {
        Route::resource('users', UserController::class)->only([
            'index', 'create', 'store', 'destroy'
        ]);
    });

    Route::middleware(['role:hr,dean'])->group(function () {
        Route::get('/users/email-availability', [UserController::class, 'createEmailAvailability'])
            ->name('users.create-email-availability');
        Route::get('/users/employee-no-availability', [UserController::class, 'createEmployeeNoAvailability'])
            ->name('users.create-employee-no-availability');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->name('users.update');
        Route::get('/users/{user}/email-availability', [UserController::class, 'emailAvailability'])
            ->name('users.email-availability');
        Route::get('/users/{user}/employee-no-availability', [UserController::class, 'employeeNoAvailability'])
            ->name('users.employee-no-availability');
    });

    /*
    |----------------------------------------------------------------------
    | Reclassification (Faculty form pages)
    |----------------------------------------------------------------------
    | ✅ Single source of truth:
    | - GET show + sections via controller
    | - POST actions for workflow + evidence review
    */
    Route::middleware(['role:faculty'])->prefix('reclassification')->group(function () {

        // Main
        Route::get('/', [ReclassificationFormController::class, 'show'])
            ->name('reclassification.show');

        // Sections (1..5)
        Route::get('/section/{number}', [ReclassificationFormController::class, 'section'])
            ->whereNumber('number')
            ->name('reclassification.section');

        Route::post('/section/{number}', [ReclassificationFormController::class, 'saveSection'])
            ->whereNumber('number')
            ->name('reclassification.section.save');

        Route::post('/section/{number}/reset', [ReclassificationFormController::class, 'resetSection'])
            ->whereNumber('number')
            ->name('reclassification.section.reset');

        // Review page (optional)
        Route::get('/review', [ReclassificationFormController::class, 'review'])
            ->name('reclassification.review');
        Route::post('/review', [ReclassificationFormController::class, 'reviewSave'])
            ->name('reclassification.review.save');

        Route::post('/reset', [ReclassificationFormController::class, 'resetApplication'])
            ->name('reclassification.reset');

        // Submitted / under review screen
        Route::get('/submitted', [ReclassificationFormController::class, 'submitted'])
            ->name('reclassification.submitted');

        // Submitted summary (read-only)
        Route::get('/submitted-summary', [ReclassificationFormController::class, 'submittedSummary'])
            ->name('reclassification.submitted-summary');
        Route::get('/submitted-summary/{application}', [ReclassificationFormController::class, 'submittedSummaryShow'])
            ->name('reclassification.submitted-summary.show');
        Route::get('/drafts/{application}/summary', [ReclassificationFormController::class, 'draftSummaryShow'])
            ->name('reclassification.drafts.summary');
        Route::delete('/drafts/{application}', [ReclassificationFormController::class, 'destroyDraft'])
            ->name('reclassification.drafts.destroy');

        // Workflow actions
        Route::post('/{application}/submit', [ReclassificationWorkflowController::class, 'submit'])
            ->name('reclassification.submit');
        Route::post('/{application}/request-return', [ReclassificationWorkflowController::class, 'requestReturn'])
            ->name('reclassification.request-return');
        Route::post('/{application}/entries/{entry}/restore', [ReclassificationFormController::class, 'restoreEntry'])
            ->name('reclassification.entries.restore');

        Route::post('/evidences', [ReclassificationFormController::class, 'uploadEvidence'])
            ->name('reclassification.evidence.upload');

        Route::post('/evidences/{evidence}/detach', [ReclassificationFormController::class, 'detachEvidence'])
            ->name('reclassification.evidence.detach');

        Route::delete('/evidences/{evidence}', [ReclassificationFormController::class, 'deleteEvidence'])
            ->name('reclassification.evidence.delete');

        Route::post('/row-comments/{comment}/reply', [ReclassificationRowCommentController::class, 'reply'])
            ->name('reclassification.row-comments.reply');
        Route::post('/row-comments/{comment}/reply-update', [ReclassificationRowCommentController::class, 'updateReply'])
            ->name('reclassification.row-comments.reply.update');
        Route::post('/row-comments/{comment}/address', [ReclassificationRowCommentController::class, 'address'])
            ->name('reclassification.row-comments.address');
    });

    /*
    |----------------------------------------------------------------------
    | Reviewer actions (Dean/HR/VPAA/President)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:dean,hr,vpaa,president'])->prefix('reclassification')->group(function () {

        // Workflow actions
        Route::post('/{application}/return', [ReclassificationWorkflowController::class, 'returnToFaculty'])
            ->name('reclassification.return');

        Route::post('/{application}/forward', [ReclassificationWorkflowController::class, 'forward'])
            ->name('reclassification.forward');

        // Evidence review actions
        Route::post('/evidences/{evidence}/accept', [ReclassificationEvidenceReviewController::class, 'accept'])
            ->name('reclassification.evidence.accept');

        Route::post('/evidences/{evidence}/reject', [ReclassificationEvidenceReviewController::class, 'reject'])
            ->name('reclassification.evidence.reject');

        Route::post('/{application}/entries/{entry}/comments', [ReclassificationRowCommentController::class, 'store'])
            ->name('reclassification.row-comments.store');

        Route::post('/row-comments/{comment}/resolve', [ReclassificationRowCommentController::class, 'resolve'])
            ->name('reclassification.row-comments.resolve');
        Route::post('/row-comments/{comment}/undo-resolve', [ReclassificationRowCommentController::class, 'undoResolve'])
            ->name('reclassification.row-comments.undo-resolve');
        Route::post('/row-comments/{comment}/reopen', [ReclassificationRowCommentController::class, 'reopen'])
            ->name('reclassification.row-comments.reopen');
        Route::delete('/row-comments/{comment}', [ReclassificationRowCommentController::class, 'destroy'])
            ->name('reclassification.row-comments.destroy');

    });

    /*
    |----------------------------------------------------------------------
    | Alias route for dashboard link
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:faculty'])->get('/faculty/reclassification', function () {
        return redirect()->route('reclassification.show');
    })->name('faculty.reclassification');

    /*
    |----------------------------------------------------------------------
    | Profile (Breeze)
    |----------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Faculty Profiles (HR / internal)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:hr,dean'])->get('/faculty-profiles/{user}/edit', function (User $user) {
        return redirect()->route('users.edit', $user);
    })->name('faculty-profiles.edit');

    Route::middleware(['role:hr,dean'])->put('/faculty-profiles/{user}', [UserController::class, 'update'])
        ->name('faculty-profiles.update');

    Route::get('/faculty/{user}/records', [FacultyProfileController::class, 'records'])
        ->name('faculty.records');

    Route::get('/faculty', [FacultyProfileController::class, 'index'])
        ->name('faculty.index');
});

require __DIR__.'/auth.php';
