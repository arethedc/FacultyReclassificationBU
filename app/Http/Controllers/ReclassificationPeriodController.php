<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\ReclassificationPeriod;
use App\Models\ReclassificationApplication;
use App\Services\ReclassificationNotificationService;

class ReclassificationPeriodController extends Controller
{
    private function composeWindowDateTime(
        ?int $month,
        ?int $day,
        ?string $time,
        int $year
    ): ?Carbon {
        if ($month === null && $day === null && $time === null) {
            return null;
        }

        if ($month === null || $day === null || $time === null) {
            return null;
        }

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return Carbon::createFromFormat(
            'Y-n-j H:i',
            "{$year}-{$month}-{$day} {$time}",
            config('app.timezone')
        );
    }

    private function resolveSubmissionWindow(array $data): array
    {
        $currentYear = (int) now()->year;
        $startMonth = isset($data['start_month']) ? (int) $data['start_month'] : null;
        $startDay = isset($data['start_day']) ? (int) $data['start_day'] : null;
        $startTime = $data['start_time'] ?? null;
        $endMonth = isset($data['end_month']) ? (int) $data['end_month'] : null;
        $endDay = isset($data['end_day']) ? (int) $data['end_day'] : null;
        $endTime = $data['end_time'] ?? null;

        $hasAnyStartWindowField = $startMonth !== null || $startDay !== null || $startTime !== null;
        $hasAnyEndWindowField = $endMonth !== null || $endDay !== null || $endTime !== null;

        $startAt = null;
        if ($hasAnyStartWindowField) {
            $startAt = $this->composeWindowDateTime($startMonth, $startDay, $startTime, $currentYear);
            if (!$startAt) {
                return [
                    'error_key' => 'start_at',
                    'error_message' => 'Start window is invalid. Select valid month, day, and time.',
                ];
            }
        }

        $endAt = $this->composeWindowDateTime($endMonth, $endDay, $endTime, $currentYear);
        if ($hasAnyEndWindowField && !$endAt) {
            return [
                'error_key' => 'end_at',
                'error_message' => 'End window is invalid. Select valid month, day, and time.',
            ];
        }

        if ($startAt && $endAt && $endAt->lessThanOrEqualTo($startAt)) {
            return [
                'error_key' => 'end_at',
                'error_message' => 'End date and time must be later than start date and time.',
            ];
        }

        return [
            'start_at' => $startAt,
            'end_at' => $endAt,
            'error_key' => null,
            'error_message' => null,
        ];
    }

    private function blockingStatuses(): array
    {
        // Draft is intentionally excluded per workflow rule.
        return [
            'returned_to_faculty',
            'dean_review',
            'hr_review',
            'vpaa_review',
            'vpaa_approved',
            'president_review',
        ];
    }

    private function blockingSubmissionCounts(ReclassificationPeriod $period): array
    {
        if (!Schema::hasTable('reclassification_applications')) {
            return ['total' => 0, 'by_status' => []];
        }

        $query = ReclassificationApplication::query()
            ->whereIn('status', $this->blockingStatuses());

        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');
        if ($hasPeriodId) {
            $query->where(function ($builder) use ($period) {
                $builder->where('period_id', $period->id);
                if (!empty($period->cycle_year)) {
                    $builder->orWhere(function ($fallback) use ($period) {
                        $fallback->whereNull('period_id')
                            ->where('cycle_year', $period->cycle_year);
                    });
                }
            });
        } elseif (!empty($period->cycle_year)) {
            $query->where('cycle_year', $period->cycle_year);
        } else {
            return ['total' => 0, 'by_status' => []];
        }

        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $total = array_sum(array_map('intval', $byStatus));

        return ['total' => (int) $total, 'by_status' => $byStatus];
    }

    private function hasStatusColumn(): bool
    {
        return Schema::hasColumn('reclassification_periods', 'status');
    }

    private function blockingSummary(array $blocking): string
    {
        $parts = collect($blocking['by_status'] ?? [])
            ->map(function ($count, $status) {
                $label = ucfirst(str_replace('_', ' ', (string) $status));
                return "{$label}: {$count}";
            })
            ->values()
            ->all();

        return implode(', ', $parts);
    }

    private function activePeriodQuery()
    {
        $query = ReclassificationPeriod::query();
        if ($this->hasStatusColumn()) {
            return $query->where('status', 'active');
        }

        return $query->where('is_open', true);
    }

    public function index()
    {
        $periods = ReclassificationPeriod::orderByDesc('created_at')->get();
        $activePeriod = $this->activePeriodQuery()
            ->orderByDesc('created_at')
            ->first();
        $openSubmissionPeriod = $this->activePeriodQuery()
            ->where('is_open', true)
            ->orderByDesc('created_at')
            ->first();

        return view('reclassification.periods', compact('periods', 'activePeriod', 'openSubmissionPeriod'));
    }

    public function store(Request $request)
    {
        if (
            !Schema::hasColumn('reclassification_periods', 'cycle_year')
            || !Schema::hasColumn('reclassification_periods', 'start_year')
            || !Schema::hasColumn('reclassification_periods', 'end_year')
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'cycle_year' => 'Database update required. Run "php artisan migrate" to add cycle support columns.',
                ]);
        }

        $data = $request->validate([
            'start_year' => ['required', 'integer', 'digits:4', 'min:2023', 'max:2095'],
        ], [
            'start_year.min' => 'Start year must be 2023 or later.',
        ]);

        $startYear = (int) $data['start_year'];
        if ((($startYear - 2023) % 3) !== 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_year' => 'Start year must follow the 3-year cycle (2023, 2026, 2029, ...).',
                ]);
        }

        $endYear = $startYear + 3;
        $cycleYear = "{$startYear}-{$endYear}";

        // Prevent overlapping cycles using half-open bounds:
        // [new_start, new_end) overlaps [existing_start, existing_end)
        // when new_start < existing_end && existing_start < new_end.
        $overlap = ReclassificationPeriod::query()
            ->get()
            ->first(function (ReclassificationPeriod $period) use ($startYear, $endYear) {
                $existingStart = (int) ($period->start_year ?? 0);
                $existingEnd = (int) ($period->end_year ?? 0);

                if ($existingStart === 0 || $existingEnd === 0) {
                    if (!preg_match('/^(\d{4})-(\d{4})$/', (string) $period->cycle_year, $matches)) {
                        return false;
                    }
                    $existingStart = (int) $matches[1];
                    $existingEnd = (int) $matches[2];
                }

                if ($existingStart >= $existingEnd) {
                    return false;
                }

                return $startYear < $existingEnd && $existingStart < $endYear;
            });

        if ($overlap) {
            return back()
                ->withInput()
                ->withErrors([
                    'cycle_year' => "Cycle {$cycleYear} overlaps existing cycle {$overlap->cycle_year}.",
                ]);
        }

        $payload = [
            'name' => "CY {$cycleYear}",
            'cycle_year' => $cycleYear,
            'start_year' => $startYear,
            'end_year' => $endYear,
            'start_at' => null,
            'end_at' => null,
            'created_by_user_id' => $request->user()->id,
            'is_open' => false,
        ];
        if (Schema::hasColumn('reclassification_periods', 'status')) {
            $payload['status'] = 'draft';
        }

        ReclassificationPeriod::create($payload);

        return redirect()
            ->route('reclassification.periods')
            ->with('success', 'Submission period created.');
    }

    public function toggle(Request $request, ReclassificationPeriod $period)
    {
        $hasStatus = $this->hasStatusColumn();
        $isEnding = $hasStatus
            ? ((string) ($period->status ?? '') === 'active')
            : (bool) $period->is_open;
        $wasSubmissionOpen = (bool) $period->is_open;

        if ($isEnding) {
            $blocking = $this->blockingSubmissionCounts($period);
            if (($blocking['total'] ?? 0) > 0) {
                $detail = $this->blockingSummary($blocking);

                return redirect()
                    ->route('reclassification.periods')
                    ->withErrors([
                        'period' => "Cannot end period. {$blocking['total']} submissions are still in progress. {$detail}",
                    ]);
            }
        } elseif ($hasStatus) {
            $otherActivePeriods = ReclassificationPeriod::query()
                ->where('id', '!=', $period->id)
                ->where('status', 'active')
                ->get();

            foreach ($otherActivePeriods as $activePeriod) {
                $blocking = $this->blockingSubmissionCounts($activePeriod);
                if (($blocking['total'] ?? 0) < 1) {
                    continue;
                }

                $activeLabel = trim((string) ($activePeriod->name ?? 'Active period'));
                $detail = $this->blockingSummary($blocking);

                return redirect()
                    ->route('reclassification.periods')
                    ->withErrors([
                        'period' => "Cannot set another period active. {$activeLabel} still has {$blocking['total']} in-progress submissions. {$detail}",
                    ]);
            }
        }

        DB::transaction(function () use ($period, $hasStatus) {
            if ($hasStatus) {
                $isActive = (string) ($period->status ?? '') === 'active';

                if ($isActive) {
                    $period->update([
                        'status' => 'ended',
                        'is_open' => false,
                    ]);
                    return;
                }

                ReclassificationPeriod::query()
                    ->where('id', '!=', $period->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'ended',
                        'is_open' => false,
                    ]);

                $period->update([
                    'status' => 'active',
                    'is_open' => false,
                ]);
                return;
            }

            if (!$period->is_open) {
                ReclassificationPeriod::where('id', '!=', $period->id)->update(['is_open' => false]);
            }
            $period->update(['is_open' => !$period->is_open]);
        });

        $period->refresh();
        $isNowActive = $hasStatus
            ? (string) ($period->status ?? '') === 'active'
            : (bool) $period->is_open;
        if (!$isNowActive && $isEnding) {
            $notifier = app(ReclassificationNotificationService::class);
            $notifier->notifyPeriodEnded($period);
            if ($wasSubmissionOpen) {
                $notifier->notifySubmissionClosed($period);
            }
        }

        return redirect()
            ->route('reclassification.periods')
            ->with('success', $isNowActive ? 'Period set to Active. Open submissions separately when ready.' : 'Period ended.');
    }

    public function toggleSubmission(Request $request, ReclassificationPeriod $period)
    {
        $hasStatus = $this->hasStatusColumn();

        if ($hasStatus && (string) ($period->status ?? '') !== 'active') {
            return redirect()
                ->route('reclassification.periods')
                ->withErrors([
                    'period' => 'Only an active period can open/close submission.',
                ]);
        }

        $period->update([
            'is_open' => !$period->is_open,
        ]);
        $period->refresh();

        $notifier = app(ReclassificationNotificationService::class);
        if ($period->is_open) {
            $sent = $notifier->notifySubmissionOpened($period);
            $message = "Submission is now Open for the active period. Notification emails sent to {$sent} faculty account(s).";
        } else {
            $sent = $notifier->notifySubmissionClosed($period);
            $message = "Submission is now Closed for the active period. Notification emails sent to {$sent} faculty account(s).";
        }

        return redirect()
            ->route('reclassification.periods')
            ->with('success', $message);
    }

    public function updateWindow(Request $request, ReclassificationPeriod $period)
    {
        if ($this->hasStatusColumn() && (string) ($period->status ?? '') !== 'active') {
            return redirect()
                ->route('reclassification.periods')
                ->withErrors([
                    'period' => 'You can edit submission start/end only for the active period.',
                ]);
        }

        $data = $request->validate([
            'start_month' => ['nullable', 'integer', 'between:1,12'],
            'start_day' => ['nullable', 'integer', 'between:1,31'],
            'start_time' => ['nullable', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'end_month' => ['nullable', 'integer', 'between:1,12'],
            'end_day' => ['nullable', 'integer', 'between:1,31'],
            'end_time' => ['nullable', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
        ]);

        $window = $this->resolveSubmissionWindow($data);
        if (!empty($window['error_key'])) {
            return redirect()
                ->route('reclassification.periods')
                ->withInput()
                ->with('edit_period_id', $period->id)
                ->withErrors([
                    $window['error_key'] => $window['error_message'],
                ]);
        }

        $period->update([
            'start_at' => $window['start_at'],
            'end_at' => $window['end_at'],
        ]);

        return redirect()
            ->route('reclassification.periods')
            ->with('success', 'Submission window updated.');
    }

    public function destroy(Request $request, ReclassificationPeriod $period)
    {
        $hasStatus = $this->hasStatusColumn();
        $isActive = $hasStatus
            ? ((string) ($period->status ?? '') === 'active')
            : (bool) ($period->is_open ?? false);
        if ($isActive) {
            return redirect()
                ->route('reclassification.periods')
                ->withErrors([
                    'period' => 'Cannot delete an active period. End it first.',
                ]);
        }

        if (Schema::hasTable('reclassification_applications')) {
            $applicationQuery = ReclassificationApplication::query();
            $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');
            if ($hasPeriodId) {
                $applicationQuery->where('period_id', $period->id);
            } else {
                $applicationQuery->whereRaw('1 = 0');
            }

            if (!empty($period->cycle_year)) {
                $applicationQuery->orWhere(function ($fallback) use ($period, $hasPeriodId) {
                    if ($hasPeriodId) {
                        $fallback->whereNull('period_id');
                    }
                    $fallback->where('cycle_year', $period->cycle_year);
                });
            }

            if ($applicationQuery->exists()) {
                return redirect()
                    ->route('reclassification.periods')
                    ->withErrors([
                        'period' => 'Cannot delete this period because it already has reclassification records.',
                    ]);
            }
        }

        $periodName = (string) ($period->name ?? 'period');
        $period->delete();

        return redirect()
            ->route('reclassification.periods')
            ->with('success', "Period deleted: {$periodName}.");
    }
}
