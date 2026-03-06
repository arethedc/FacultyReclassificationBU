<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-gray-800">Manage Periods</h2>
                <p class="text-sm text-gray-500">Open or close submission windows for faculty.</p>
            </div>
            <button type="button"
                    onclick="window.dispatchEvent(new CustomEvent('open-create-period'));"
                    class="h-11 px-6 rounded-xl bg-bu text-white text-sm font-semibold hover:bg-bu-dark shadow-soft transition inline-flex items-center justify-center whitespace-nowrap self-start sm:self-auto">
                + Create Period
            </button>
        </div>
    </x-slot>

    @php
        $maxStartYear = (int) date('Y') + 15;
        $oldStartYear = (int) old('start_year', 0);
        $monthOptions = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];
        $timeOptions = [];
        for ($h = 0; $h < 24; $h++) {
            foreach ([0, 30] as $m) {
                $value = sprintf('%02d:%02d', $h, $m);
                $timeOptions[$value] = date('g:i A', strtotime($value));
            }
        }
        $usedStartYears = collect($periods ?? [])
            ->map(function ($period) {
                if (!empty($period->start_year)) {
                    return (int) $period->start_year;
                }
                if (preg_match('/^(\d{4})-(\d{4})$/', (string) ($period->cycle_year ?? ''), $matches)) {
                    return (int) $matches[1];
                }
                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
        $openCreateModal = !session()->has('edit_period_id')
            && (
                old('start_year')
                || $errors->has('start_year')
                || $errors->has('cycle_year')
            );
        $sessionEditPeriodId = session()->has('edit_period_id')
            ? (int) session('edit_period_id')
            : null;
    @endphp

    <div class="py-12 bg-bu-muted min-h-screen"
         x-data="periodsPage({
             show: @js(session()->has('success') || $errors->any()),
             message: @js(session('success') ?? ($errors->first() ?? '')),
             type: @js(session()->has('success') ? 'success' : ($errors->any() ? 'error' : 'info')),
             createOpen: @js((bool) $openCreateModal),
             editingPeriodId: @js($sessionEditPeriodId)
         })"
         x-on:open-create-period.window="openCreateModal = true"
         x-init="init()">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8"
             data-ux-panel
             data-ux-initial-panel
             aria-busy="false">
            <div data-ux-panel-skeleton class="hidden space-y-6" aria-hidden="true">
                <div class="ux-skeleton-card space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="space-y-2">
                            <div class="ux-skeleton h-6 w-52"></div>
                            <div class="ux-skeleton h-4 w-60"></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="ux-skeleton h-7 w-28 rounded-full"></div>
                            <div class="ux-skeleton h-7 w-32 rounded-full"></div>
                        </div>
                    </div>
                    <x-ui.skeleton-table :rows="8" :cols="7" />
                </div>
            </div>

            <div data-ux-panel-content class="space-y-6">
            <div class="bg-white rounded-2xl shadow-card border border-gray-200 overflow-visible" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Reclassification Periods</h3>
                            <p class="text-sm text-gray-500">Only one period can be active at a time.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if($activePeriod)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border bg-green-50 text-green-700 border-green-200">
                                    Active: {{ $activePeriod->name }}
                                </span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ !empty($openSubmissionPeriod) ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                    Submission: {{ !empty($openSubmissionPeriod) ? 'Open' : 'Closed' }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border bg-gray-50 text-gray-600 border-gray-200">
                                    No active period
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto md:overflow-visible">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left">Name</th>
                                <th class="px-6 py-3 text-left">Cycle</th>
                                <th class="px-6 py-3 text-left">Period Stage</th>
                                <th class="px-6 py-3 text-left">Submission</th>
                                <th class="px-6 py-3 text-left">Start</th>
                                <th class="px-6 py-3 text-left">End</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($periods as $period)
                                @php
                                    $status = (string) ($period->status ?? ($period->is_open ? 'active' : 'ended'));
                                    $statusLabel = ucfirst($status);
                                    $statusClass = match($status) {
                                        'active' => 'bg-green-50 text-green-700 border-green-200',
                                        'draft' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        default => 'bg-gray-50 text-gray-600 border-gray-200',
                                    };
                                    $togglePeriodLabel = $status === 'active' ? 'End Period' : 'Set Active';
                                    $togglePeriodTitle = $status === 'active' ? 'End this period?' : 'Set this period as active?';
                                    $togglePeriodMessage = $status === 'active'
                                        ? 'This will end the period and close submission. In-progress papers will block this action.'
                                        : 'This will make this period active. Any current active period will be ended only if no in-progress papers exist.';
                                    $submissionToggleLabel = $period->is_open ? 'Close Submission' : 'Open Submission';
                                    $submissionToggleTitle = $period->is_open ? 'Close submission window?' : 'Open submission window?';
                                    $submissionToggleMessage = $period->is_open
                                        ? 'Faculty can no longer submit while submission is closed.'
                                        : 'Faculty can submit while submission is open.';
                                    $canEditWindow = $status === 'active';
                                @endphp
                                <tr class="transition hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-800">{{ $period->name }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $period->cycle_year ?? '-' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border {{ $period->is_open ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                            {{ $period->is_open ? 'Open' : 'Closed' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ optional($period->start_at)->format('M d, h:i A') ?? '-' }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ optional($period->end_at)->format('M d, h:i A') ?? '-' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <form id="period-toggle-{{ (int) $period->id }}"
                                              method="POST"
                                              action="{{ route('reclassification.periods.toggle', $period) }}"
                                              data-loading-text="Updating period..."
                                              class="hidden">
                                            @csrf
                                            <button type="submit"><span data-submit-label>{{ $togglePeriodLabel }}</span></button>
                                        </form>
                                        <form id="period-submission-toggle-{{ (int) $period->id }}"
                                              method="POST"
                                              action="{{ route('reclassification.periods.submission.toggle', $period) }}"
                                              data-loading-text="Updating submission..."
                                              class="hidden">
                                            @csrf
                                            <button type="submit"><span data-submit-label>{{ $submissionToggleLabel }}</span></button>
                                        </form>
                                        <form id="period-delete-{{ (int) $period->id }}"
                                              method="POST"
                                              action="{{ route('reclassification.periods.destroy', $period) }}"
                                              data-loading-text="Deleting period..."
                                              class="hidden">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"><span data-submit-label>Delete</span></button>
                                        </form>

                                        <div class="relative inline-block text-left"
                                             x-data="{ open: false }"
                                             @click.away="open = false"
                                             @keydown.escape.window="open = false">
                                            <button type="button"
                                                    @click="open = !open"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                                                    aria-label="More actions">
                                                &#8942;
                                            </button>

                                            <div x-show="open"
                                                 x-cloak
                                                 x-transition
                                                 class="absolute right-0 top-full z-50 mt-2 w-52 rounded-xl border border-gray-300 bg-white shadow-xl">
                                                <div class="absolute -top-2 right-3 h-3 w-3 rotate-45 border-l border-t border-gray-300 bg-white"></div>
                                                <div class="p-1">
                                                    <button type="button"
                                                            @click="open = false; editingPeriodId = editingPeriodId === {{ (int) $period->id }} ? null : {{ (int) $period->id }}"
                                                            @disabled(!$canEditWindow)
                                                            class="block w-full rounded-lg px-4 py-2 text-left text-sm font-medium {{ $canEditWindow ? 'text-gray-700 hover:bg-gray-50' : 'text-gray-400 cursor-not-allowed' }}">
                                                        <span x-text="editingPeriodId === {{ (int) $period->id }} ? 'Close Window Editor' : 'Edit Window'"></span>
                                                    </button>

                                                    <button type="button"
                                                            @click="open = false; openConfirm({
                                                                formId: @js('period-submission-toggle-' . (int) $period->id),
                                                                title: @js($submissionToggleTitle),
                                                                message: @js($submissionToggleMessage),
                                                                confirmLabel: @js($submissionToggleLabel),
                                                                confirmClass: 'bg-indigo-600 hover:bg-indigo-700'
                                                            })"
                                                            @disabled(!$canEditWindow)
                                                            class="block w-full rounded-lg px-4 py-2 text-left text-sm font-medium {{ $canEditWindow ? 'text-gray-700 hover:bg-gray-50' : 'text-gray-400 cursor-not-allowed' }}">
                                                        {{ $submissionToggleLabel }}
                                                    </button>

                                                    <div class="my-1 border-t border-gray-200"></div>

                                                    <button type="button"
                                                            @click="open = false; openConfirm({
                                                                formId: @js('period-toggle-' . (int) $period->id),
                                                                title: @js($togglePeriodTitle),
                                                                message: @js($togglePeriodMessage),
                                                                confirmLabel: @js($togglePeriodLabel),
                                                                confirmClass: @js($status === 'active' ? 'bg-red-600 hover:bg-red-700' : 'bg-bu hover:bg-bu-dark')
                                                            })"
                                                            class="block w-full rounded-lg px-4 py-2 text-left text-sm font-medium {{ $status === 'active' ? 'text-red-700 hover:bg-red-50' : 'text-gray-700 hover:bg-gray-50' }}">
                                                        {{ $togglePeriodLabel }}
                                                    </button>

                                                    <div class="my-1 border-t border-gray-200"></div>

                                                    <button type="button"
                                                            @click="open = false; openConfirm({
                                                                formId: @js('period-delete-' . (int) $period->id),
                                                                title: 'Delete this period?',
                                                                message: 'This is for testing only and cannot be undone.',
                                                                confirmLabel: 'Delete Period',
                                                                confirmClass: 'bg-red-600 hover:bg-red-700'
                                                            })"
                                                            class="block w-full rounded-lg px-4 py-2 text-left text-sm font-medium text-red-700 hover:bg-red-50">
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-6 text-center text-sm text-gray-500">
                                        No submission periods yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>

        <div x-cloak
             x-show="openCreateModal"
             x-transition.opacity
             class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="openCreateModal = false"></div>
            <div class="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl border border-gray-200 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Create New Period</h3>
                        <p class="text-sm text-gray-500 mt-1">Cycle is fixed at 3 years. Start years follow 2023, 2026, 2029... Submission start/end can be set after this period becomes active.</p>
                    </div>
                    <button type="button"
                            @click="openCreateModal = false"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                            aria-label="Close create period modal">
                        &times;
                    </button>
                </div>

                <form method="POST"
                      action="{{ route('reclassification.periods.store') }}"
                      data-loading-text="Creating period..."
                      x-data="{
                          startYear: '{{ old('start_year') }}',
                          endYear() { return this.startYear ? (Number(this.startYear) + 3) : ''; }
                      }"
                      class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Start Year</label>
                        <select name="start_year"
                                x-model="startYear"
                                required
                                class="w-full rounded-xl border-gray-300 focus:border-bu focus:ring-bu">
                            <option value="">Select start year</option>
                            @for($year = 2023; $year <= $maxStartYear; $year += 3)
                                @if(!in_array($year, $usedStartYears, true) || $oldStartYear === $year)
                                    <option value="{{ $year }}" @selected(old('start_year') == $year)>{{ $year }}</option>
                                @endif
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">End Year</label>
                        <input type="text"
                               x-bind:value="endYear()"
                               readonly
                               class="w-full rounded-xl border-gray-200 bg-gray-50 text-gray-700">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Auto Name</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">
                            <span x-text="startYear && endYear() ? `CY ${startYear}-${endYear()}` : 'CY -'"></span>
                        </div>
                    </div>
                    <div class="md:col-span-3 flex items-center justify-end gap-2">
                        <button type="button"
                                @click="openCreateModal = false"
                                class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark">
                            <span data-submit-label>Create Period</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div x-cloak
             x-show="editingPeriodId !== null"
             x-transition.opacity
             class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="editingPeriodId = null"></div>
            <div class="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl border border-gray-200 p-6">
                @foreach ($periods as $period)
                    @php
                        $isEditingFromSession = (int) session('edit_period_id', 0) === (int) $period->id;
                        $periodStartMonth = optional($period->start_at)->format('n');
                        $periodStartDay = optional($period->start_at)->format('j');
                        $periodStartTime = optional($period->start_at)->format('H:i');
                        $periodEndMonth = optional($period->end_at)->format('n');
                        $periodEndDay = optional($period->end_at)->format('j');
                        $periodEndTime = optional($period->end_at)->format('H:i');
                        $startMonthValue = $isEditingFromSession ? old('start_month', $periodStartMonth) : $periodStartMonth;
                        $startDayValue = $isEditingFromSession ? old('start_day', $periodStartDay) : $periodStartDay;
                        $startTimeValue = $isEditingFromSession ? old('start_time', $periodStartTime) : $periodStartTime;
                        $endMonthValue = $isEditingFromSession ? old('end_month', $periodEndMonth) : $periodEndMonth;
                        $endDayValue = $isEditingFromSession ? old('end_day', $periodEndDay) : $periodEndDay;
                        $endTimeValue = $isEditingFromSession ? old('end_time', $periodEndTime) : $periodEndTime;
                    @endphp
                    <div x-show="editingPeriodId === {{ (int) $period->id }}" x-cloak>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Edit Submission Window</h3>
                                <p class="text-sm text-gray-500 mt-1">{{ $period->name }} ({{ $period->cycle_year ?? '-' }})</p>
                            </div>
                            <button type="button"
                                    @click="editingPeriodId = null"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                                    aria-label="Close edit window modal">
                                &times;
                            </button>
                        </div>

                        <form method="POST"
                              action="{{ route('reclassification.periods.window.update', $period) }}"
                              class="mt-4 space-y-4"
                              data-loading-text="Saving window...">
                            @csrf
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-gray-600">Start</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <select name="start_month" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-sm">
                                            <option value="">Month</option>
                                            @foreach($monthOptions as $monthNo => $monthLabel)
                                                <option value="{{ $monthNo }}" @selected((string) $startMonthValue === (string) $monthNo)>{{ $monthLabel }}</option>
                                            @endforeach
                                        </select>
                                        <select name="start_day" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-sm">
                                            <option value="">Day</option>
                                            @for($day = 1; $day <= 31; $day++)
                                                <option value="{{ $day }}" @selected((string) $startDayValue === (string) $day)>{{ $day }}</option>
                                            @endfor
                                        </select>
                                        <select name="start_time" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-sm">
                                            <option value="">Time</option>
                                            @foreach($timeOptions as $timeValue => $timeLabel)
                                                <option value="{{ $timeValue }}" @selected((string) $startTimeValue === (string) $timeValue)>{{ $timeLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-gray-600">End</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <select name="end_month" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-sm">
                                            <option value="">Month</option>
                                            @foreach($monthOptions as $monthNo => $monthLabel)
                                                <option value="{{ $monthNo }}" @selected((string) $endMonthValue === (string) $monthNo)>{{ $monthLabel }}</option>
                                            @endforeach
                                        </select>
                                        <select name="end_day" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-sm">
                                            <option value="">Day</option>
                                            @for($day = 1; $day <= 31; $day++)
                                                <option value="{{ $day }}" @selected((string) $endDayValue === (string) $day)>{{ $day }}</option>
                                            @endfor
                                        </select>
                                        <select name="end_time" class="w-full rounded-lg border-gray-300 focus:border-bu focus:ring-bu text-sm">
                                            <option value="">Time</option>
                                            @foreach($timeOptions as $timeValue => $timeLabel)
                                                <option value="{{ $timeValue }}" @selected((string) $endTimeValue === (string) $timeValue)>{{ $timeLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                        @click="editingPeriodId = null"
                                        class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold hover:bg-bu-dark">
                                    <span data-submit-label>Save Window</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        <div x-cloak
             x-show="confirm.show"
             x-transition.opacity
             class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="confirm.show = false"></div>
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-200 p-5">
                <h4 class="text-base font-semibold text-gray-800" x-text="confirm.title"></h4>
                <p class="mt-2 text-sm text-gray-600" x-text="confirm.message"></p>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="confirm.show = false"
                            class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button"
                            @click="confirmSubmit()"
                            class="px-4 py-2 rounded-xl text-sm font-semibold text-white"
                            :class="confirm.confirmClass"
                            x-text="confirm.confirmLabel"></button>
                </div>
            </div>
        </div>

        <div x-cloak
             x-show="toast.show"
             x-transition
             class="fixed bottom-6 left-6 z-50">
            <div class="px-4 py-2 rounded-lg shadow-lg text-sm text-white"
                 :class="toast.type === 'success' ? 'bg-green-600' : (toast.type === 'error' ? 'bg-red-600' : 'bg-slate-800')">
                <span x-text="toast.message"></span>
            </div>
        </div>
    </div>

        <script>
        function periodsPage(initialToast) {
            return {
                openCreateModal: !!initialToast?.createOpen,
                editingPeriodId: initialToast?.editingPeriodId ?? null,
                confirm: {
                    show: false,
                    formId: null,
                    title: '',
                    message: '',
                    confirmLabel: 'Confirm',
                    confirmClass: 'bg-bu hover:bg-bu-dark',
                },
                toast: {
                    show: !!initialToast?.show,
                    message: initialToast?.message || '',
                    type: initialToast?.type || 'info',
                },
                toastTimer: null,
                init() {
                    if (this.toast.show) {
                        this.toastTimer = setTimeout(() => {
                            this.toast.show = false;
                        }, 3200);
                    }
                    this.bindLoadingStates();
                },
                bindLoadingStates() {
                    document.querySelectorAll('form[data-loading-text]').forEach((form) => {
                        if (form.dataset.loadingBound === '1') return;
                        form.dataset.loadingBound = '1';

                        form.addEventListener('submit', () => {
                            if (form.dataset.submitting === '1') return;
                            form.dataset.submitting = '1';
                            const submit = form.querySelector('button[type="submit"], input[type="submit"]');
                            if (!submit) return;

                            submit.disabled = true;
                            submit.classList.add('opacity-60', 'cursor-not-allowed');

                            const loadingText = form.dataset.loadingText || 'Saving...';
                            if (submit.tagName === 'INPUT') {
                                submit.value = loadingText;
                                return;
                            }

                            const label = submit.querySelector('[data-submit-label]') || submit;
                            if (!label.dataset.originalText) {
                                label.dataset.originalText = label.textContent || '';
                            }
                            label.textContent = loadingText;
                        });
                    });
                },
                openConfirm(payload) {
                    this.confirm = {
                        show: true,
                        formId: payload?.formId || null,
                        title: payload?.title || 'Confirm action?',
                        message: payload?.message || '',
                        confirmLabel: payload?.confirmLabel || 'Confirm',
                        confirmClass: payload?.confirmClass || 'bg-bu hover:bg-bu-dark',
                    };
                },
                confirmSubmit() {
                    const formId = this.confirm.formId;
                    this.confirm.show = false;
                    if (!formId) return;
                    const form = document.getElementById(formId);
                    if (!form) return;
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                        return;
                    }
                    form.submit();
                },
            };
        }
    </script>
</x-app-layout>
