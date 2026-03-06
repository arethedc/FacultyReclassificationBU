{{-- resources/views/reclassification/section2.blade.php --}}
<div class="flex flex-col gap-1 mb-4">
    <h2 class="text-2xl font-semibold text-gray-800">
        Reclassification - Section II
    </h2>
    <p class="text-sm text-gray-500">
        Instructional Competence (Max 120 pts / 30%)
    </p>
</div>

@php
  $actionRoute = $actionRoute ?? route('reclassification.section.save', 2);
  $readOnly = $readOnly ?? false;
  $embedded = $embedded ?? false;
  $asyncRefreshTarget = $asyncRefreshTarget ?? null;
  $useAsyncSave = !$readOnly && !empty($asyncRefreshTarget);
@endphp

<form method="POST"
      action="{{ $actionRoute }}"
      data-validate-evidence
      @if($useAsyncSave)
      data-async-action
      data-async-refresh-target="{{ $asyncRefreshTarget }}"
      data-loading-text="Saving..."
      data-loading-message="Saving Section II..."
      data-success-message="Section II saved."
      @endif
      data-view-only="{{ $readOnly ? 'true' : 'false' }}">
@csrf

<div x-data="sectionTwo(@js($sectionData ?? []), { readOnly: @js($readOnly) })"
     x-init="init()"
     class="{{ $embedded ? 'py-4' : 'py-12 bg-bu-muted min-h-screen' }}">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    {{-- =======================
    STICKY SCORE SUMMARY (Section II)
    ======================== --}}
    <div
      x-data="{ open:true, stuck:false, userOverride:false }"
      x-init="
        const onScroll = () => {
          const nowStuck = window.scrollY > 140;

          if (!stuck && nowStuck) {
            stuck = true;
            if (!userOverride) open = false;
            return;
          }

          if (stuck && !nowStuck) {
            stuck = false;
            if (!userOverride) open = true;
            return;
          }

          stuck = nowStuck;
        };

        window.addEventListener('scroll', onScroll, { passive:true });
        onScroll();
      "
      class="sticky top-20 z-20"
    >
      <div class="bg-white/95 backdrop-blur rounded-2xl border shadow-card">
        <div class="px-5 py-3 flex items-center justify-between gap-4">
          <div class="min-w-0">
            <div class="flex items-center gap-3">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 truncate">
                Section II Score Summary
              </h3>

<template x-if="hasAnyRating() && Number(rawTotal()) <= 120">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
                  Within limit
                </span>
              </template>
<template x-if="hasAnyRating() && Number(rawTotal()) > 120">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
                  Over limit
                </span>
              </template>
            </div>

            <p class="text-xs text-gray-600 mt-1">
         <span class="font-semibold text-gray-800"
      x-text="hasAnyRating() ? rawTotal() : '—'"></span>
<span class="text-gray-400">/ 120</span>

<span class="mx-2 text-gray-300">•</span>

Counted (capped):
<span class="font-semibold text-gray-800"
      x-text="hasAnyRating() ? cappedTotal() : '—'"></span>
            </p>
          </div>

          <button type="button"
                  @click="userOverride = true; open = !open"
                  class="px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
            <span x-text="open ? 'Hide details' : 'Show details'"></span>
          </button>
        </div>

        <div x-show="open" x-collapse class="px-5 pb-4">
          <p class="text-xs text-gray-500">
            Equivalent points follow the paper rating tables.
            Weighted totals: Dean × 0.40, Chair × 0.30, Student × 0.30.
            + 1/3 of previous reclassification points. Subject to validation.
          </p>

          <div class="mt-3 rounded-xl border bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">Dean Inputted Scores</div>
            <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2">
                <div class="text-gray-500">Item 1</div>
                <div class="font-semibold text-gray-800" x-text="Number(ratings.dean.i1 || 0) > 0 ? Number(ratings.dean.i1).toFixed(2) : '-'"></div>
              </div>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2">
                <div class="text-gray-500">Item 2</div>
                <div class="font-semibold text-gray-800" x-text="Number(ratings.dean.i2 || 0) > 0 ? Number(ratings.dean.i2).toFixed(2) : '-'"></div>
              </div>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2">
                <div class="text-gray-500">Item 3</div>
                <div class="font-semibold text-gray-800" x-text="Number(ratings.dean.i3 || 0) > 0 ? Number(ratings.dean.i3).toFixed(2) : '-'"></div>
              </div>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2">
                <div class="text-gray-500">Item 4</div>
                <div class="font-semibold text-gray-800" x-text="Number(ratings.dean.i4 || 0) > 0 ? Number(ratings.dean.i4).toFixed(2) : '-'"></div>
              </div>
            </div>
            <div class="mt-2 text-xs text-gray-600">
              Dean equivalent points:
              <span class="font-semibold text-gray-800" x-text="Number(deanTotalPts()).toFixed(2)"></span>
            </div>
          </div>

          <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Weighted Total (No Previous)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="Number(weightedTotal()).toFixed(2)"></span>
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Dean pts: <span class="font-medium text-gray-700" x-text="Number(deanTotalPts()).toFixed(2)"></span>
                <span class="mx-1 text-gray-300">•</span>
                Chair pts: <span class="font-medium text-gray-700" x-text="Number(chairTotalPts()).toFixed(2)"></span>
                <span class="mx-1 text-gray-300">•</span>
                Student pts: <span class="font-medium text-gray-700" x-text="Number(studentTotalPts()).toFixed(2)"></span>
              </p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Previous Reclass (1/3)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="Number(prevThird()).toFixed(2)"></span>
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Input: <span class="font-medium text-gray-700" x-text="Number(previous || 0).toFixed(2)"></span>
              </p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Final (Raw)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="Number(rawTotal()).toFixed(2)"></span>
                <span class="text-sm font-medium text-gray-400">/ 120</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Counted: <span class="font-medium text-gray-700" x-text="Number(cappedTotal()).toFixed(2)"></span>
              </p>
            </div>
          </div>

<template x-if="hasAnyRating() && Number(rawTotal()) > 120">
            <p class="mt-3 text-xs text-red-600">
              Your raw total exceeds the 120-point limit. Excess points will not be counted.
            </p>
          </template>
        </div>
      </div>
    </div>

    {{-- ======================================================
    SECTION II – INSTRUCTIONAL COMPETENCE
    ====================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">
                Instructional Competence Evaluation
            </h3>
            <p class="text-sm text-gray-500">
                Ratings are based on the past three (3) years. Subject to validation.
            </p>
        </div>

        <div class="p-6 space-y-6">

            <div class="overflow-x-auto">
                <table class="w-full text-sm border rounded-lg overflow-hidden">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Criteria</th>
                            <th class="px-4 py-2 text-center">Dean Rating</th>
                            <th class="px-4 py-2 text-center">Dept. Chair Rating</th>
                            <th class="px-4 py-2 text-center">Student Rating</th>
                            <th class="px-4 py-2 text-center">Equivalent Points (Auto)</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">

                        {{-- 1 --}}
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                1. Subject Matter Expertise (Max 40 pts)
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.dean.i1"
                                       name="section2[ratings][dean][i1]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.chair.i1"
                                       name="section2[ratings][chair][i1]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.student.i1"
                                       name="section2[ratings][student][i1]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="font-medium text-gray-800" x-text="Number(eqPtsForItem(1)).toFixed(2)"></span>
                            </td>
                        </tr>

                        {{-- 2 --}}
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                2. Teaching Methodologies (Max 30 pts)
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.dean.i2"
                                       name="section2[ratings][dean][i2]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.chair.i2"
                                       name="section2[ratings][chair][i2]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.student.i2"
                                       name="section2[ratings][student][i2]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="font-medium text-gray-800" x-text="Number(eqPtsForItem(2)).toFixed(2)"></span>
                            </td>
                        </tr>

                        {{-- 3 --}}
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                3. Classroom Management (Max 25 pts)
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.dean.i3"
                                       name="section2[ratings][dean][i3]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.chair.i3"
                                       name="section2[ratings][chair][i3]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.student.i3"
                                       name="section2[ratings][student][i3]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="font-medium text-gray-800" x-text="Number(eqPtsForItem(3)).toFixed(2)"></span>
                            </td>
                        </tr>

                        {{-- 4 --}}
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                4. Teacher–Student Relationship (Max 25 pts)
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.dean.i4"
                                       name="section2[ratings][dean][i4]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.chair.i4"
                                       name="section2[ratings][chair][i4]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input x-model.number="ratings.student.i4"
                                       name="section2[ratings][student][i4]"
                                       type="number" step="0.01" min="0" max="4"
                                       class="w-24 text-center rounded border-gray-300"
                                       :disabled="readOnly || !isEditMode"
                                       placeholder="1.00–4.00">
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="font-medium text-gray-800" x-text="Number(eqPtsForItem(4)).toFixed(2)"></span>
                            </td>
                        </tr>

                        {{-- TOTAL ROW --}}
                        <tr class="bg-gray-50">
                            <td class="px-4 py-3 font-semibold">Total</td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-800">
                                <span x-text="Number(deanTotalPts()).toFixed(2)"></span>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-800">
                                <span x-text="Number(chairTotalPts()).toFixed(2)"></span>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-800">
                                <span x-text="Number(studentTotalPts()).toFixed(2)"></span>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-800">
                                <span x-text="Number(weightedTotal()).toFixed(2)"></span>
                                <span class="text-xs text-gray-400"> (Weighted)</span>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            {{-- =========================
            VIEW RATING TABLES (Collapsible)
            ========================== --}}
            <div x-data="{ showTables: false }" class="bg-gray-50 border rounded-xl">
                <button type="button"
                        @click="showTables = !showTables"
                        class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-800">
                    <span>View Rating Tables (Equivalent Points Guide)</span>
                    <span class="text-gray-500" x-text="showTables ? 'Hide' : 'Show'"></span>
                </button>

                <div x-show="showTables" x-collapse class="px-4 pb-4">
                    <p class="text-xs text-gray-500 mb-3">
                        Use these tables to determine equivalent points based on rating ranges.
                        (Displayed for reference; points are system-suggested and subject to validation.)
                    </p>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 text-sm">
                        {{-- Item 1 --}}
                        <div class="bg-white rounded-xl border p-4">
                            <h4 class="font-semibold text-gray-800 mb-2">Item 1 (Max 40 pts)</h4>
                            <table class="w-full text-xs border rounded-lg overflow-hidden">
                                <thead class="bg-gray-50">
                                    <tr><th class="px-2 py-2 text-left">Rating Range</th><th class="px-2 py-2 text-right">Points</th></tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr><td class="px-2 py-2">3.72 – 4.00</td><td class="px-2 py-2 text-right">40</td></tr>
                                    <tr><td class="px-2 py-2">3.42 – 3.71</td><td class="px-2 py-2 text-right">36</td></tr>
                                    <tr><td class="px-2 py-2">3.12 – 3.41</td><td class="px-2 py-2 text-right">32</td></tr>
                                    <tr><td class="px-2 py-2">2.82 – 3.11</td><td class="px-2 py-2 text-right">28</td></tr>
                                    <tr><td class="px-2 py-2">2.52 – 2.81</td><td class="px-2 py-2 text-right">24</td></tr>
                                    <tr><td class="px-2 py-2">2.22 – 2.51</td><td class="px-2 py-2 text-right">20</td></tr>
                                    <tr><td class="px-2 py-2">1.92 – 2.21</td><td class="px-2 py-2 text-right">16</td></tr>
                                    <tr><td class="px-2 py-2">1.62 – 1.91</td><td class="px-2 py-2 text-right">12</td></tr>
                                    <tr><td class="px-2 py-2">1.31 – 1.61</td><td class="px-2 py-2 text-right">8</td></tr>
                                    <tr><td class="px-2 py-2">1.00 – 1.30</td><td class="px-2 py-2 text-right">4</td></tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Item 2 --}}
                        <div class="bg-white rounded-xl border p-4">
                            <h4 class="font-semibold text-gray-800 mb-2">Item 2 (Max 30 pts)</h4>
                            <table class="w-full text-xs border rounded-lg overflow-hidden">
                                <thead class="bg-gray-50">
                                    <tr><th class="px-2 py-2 text-left">Rating Range</th><th class="px-2 py-2 text-right">Points</th></tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr><td class="px-2 py-2">3.72 – 4.00</td><td class="px-2 py-2 text-right">30</td></tr>
                                    <tr><td class="px-2 py-2">3.42 – 3.71</td><td class="px-2 py-2 text-right">27</td></tr>
                                    <tr><td class="px-2 py-2">3.12 – 3.41</td><td class="px-2 py-2 text-right">24</td></tr>
                                    <tr><td class="px-2 py-2">2.82 – 3.11</td><td class="px-2 py-2 text-right">21</td></tr>
                                    <tr><td class="px-2 py-2">2.52 – 2.81</td><td class="px-2 py-2 text-right">18</td></tr>
                                    <tr><td class="px-2 py-2">2.22 – 2.51</td><td class="px-2 py-2 text-right">15</td></tr>
                                    <tr><td class="px-2 py-2">1.92 – 2.21</td><td class="px-2 py-2 text-right">12</td></tr>
                                    <tr><td class="px-2 py-2">1.62 – 1.91</td><td class="px-2 py-2 text-right">9</td></tr>
                                    <tr><td class="px-2 py-2">1.31 – 1.61</td><td class="px-2 py-2 text-right">6</td></tr>
                                    <tr><td class="px-2 py-2">1.00 – 1.30</td><td class="px-2 py-2 text-right">3</td></tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Items 3 & 4 --}}
                        <div class="bg-white rounded-xl border p-4">
                            <h4 class="font-semibold text-gray-800 mb-2">Items 3 & 4 (Max 25 pts)</h4>
                            <table class="w-full text-xs border rounded-lg overflow-hidden">
                                <thead class="bg-gray-50">
                                    <tr><th class="px-2 py-2 text-left">Rating Range</th><th class="px-2 py-2 text-right">Points</th></tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr><td class="px-2 py-2">3.72 – 4.00</td><td class="px-2 py-2 text-right">25</td></tr>
                                    <tr><td class="px-2 py-2">3.42 – 3.71</td><td class="px-2 py-2 text-right">22.5</td></tr>
                                    <tr><td class="px-2 py-2">3.12 – 3.41</td><td class="px-2 py-2 text-right">20</td></tr>
                                    <tr><td class="px-2 py-2">2.82 – 3.11</td><td class="px-2 py-2 text-right">17.5</td></tr>
                                    <tr><td class="px-2 py-2">2.52 – 2.81</td><td class="px-2 py-2 text-right">15</td></tr>
                                    <tr><td class="px-2 py-2">2.22 – 2.51</td><td class="px-2 py-2 text-right">12.5</td></tr>
                                    <tr><td class="px-2 py-2">1.92 – 2.21</td><td class="px-2 py-2 text-right">10</td></tr>
                                    <tr><td class="px-2 py-2">1.62 – 1.91</td><td class="px-2 py-2 text-right">7.5</td></tr>
                                    <tr><td class="px-2 py-2">1.31 – 1.61</td><td class="px-2 py-2 text-right">5</td></tr>
                                    <tr><td class="px-2 py-2">1.00 – 1.30</td><td class="px-2 py-2 text-right">2.5</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PREVIOUS RECLASSIFICATION --}}
            <div class="border-t pt-6">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                    <label class="block text-xs text-gray-500">
                        Points from Previous Reclassification (if applicable)
                    </label>
                    <input x-model.number="previous"
                           name="section2[previous_points]"
                           type="number" step="0.01"
                           class="mt-1 w-56 max-w-full rounded border-gray-300 text-sm"
                           :disabled="readOnly || !isEditMode"
                           placeholder="0.00">
                    <p class="text-xs text-gray-500 mt-1">
                        Counted: <span class="font-medium text-gray-700" x-text="Number(prevThird()).toFixed(2)"></span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        System applies 1/3 of this value. Subject to validation.
                    </p>
                </div>
            </div>

{{-- ACTIONS --}}
<div class="flex justify-end gap-4 pt-2">
    @php
        $section2Saved = str_contains(strtolower((string) session('success', '')), 'section ii');
    @endphp
    @if($section2Saved)
        <span class="self-center text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-1.5">
            Section II saved.
        </span>
    @endif

    <template x-if="!readOnly && !isEditMode">
        <button type="button"
                @click="startEdit()"
                class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
            Edit Section II
        </button>
    </template>

    <template x-if="!readOnly && isEditMode">
        <div class="flex items-center gap-3">
            <button type="button"
                    @click="discardChanges()"
                    class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50">
                Discard Changes
            </button>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-bu text-white hover:bg-bu-dark shadow-soft"
                    x-text="hasSavedData ? 'Save Changes' : 'Save Section II'">
            </button>
        </div>
    </template>
</div>

        </div>
    </div>

</div>
</div>
</form>

<script>
function sectionTwo(initial = {}, options = {}) {
  return {
readOnly: Boolean(options.readOnly ?? false),
isEditMode: false,
hasSavedData: false,
originalState: null,

hasAnyRating() {
  const all = [
    this.ratings.dean.i1, this.ratings.dean.i2, this.ratings.dean.i3, this.ratings.dean.i4,
    this.ratings.chair.i1, this.ratings.chair.i2, this.ratings.chair.i3, this.ratings.chair.i4,
    this.ratings.student.i1, this.ratings.student.i2, this.ratings.student.i3, this.ratings.student.i4,
  ];
  return all.some(v => this.n(v) != null);
},

captureState() {
  return {
    ratings: JSON.parse(JSON.stringify(this.ratings)),
    previous: Number(this.previous || 0),
  };
},

applyState(state) {
  if (!state) return;
  this.ratings = JSON.parse(JSON.stringify(state.ratings || {
    dean: { i1: 0, i2: 0, i3: 0, i4: 0 },
    chair: { i1: 0, i2: 0, i3: 0, i4: 0 },
    student: { i1: 0, i2: 0, i3: 0, i4: 0 },
  }));
  this.previous = Number(state.previous || 0);
},

computeHasSavedData() {
  return this.hasAnyRating() || Number(this.previous || 0) > 0;
},

startEdit() {
  if (this.readOnly) return;
  this.originalState = this.captureState();
  this.isEditMode = true;
},

discardChanges() {
  if (this.readOnly) return;
  this.applyState(this.originalState);
  this.isEditMode = !this.hasSavedData;
},

clampRating(rater, key) {
  if (!this.ratings?.[rater]) return;
  const raw = this.ratings[rater][key];
  if (raw === null || raw === '') {
    this.ratings[rater][key] = 0;
    return;
  }

  let val = Number(raw);
  if (!Number.isFinite(val)) {
    this.ratings[rater][key] = 0;
    return;
  }

  if (val <= 0) val = 0;
  else if (val < 1) val = 1;
  if (val > 4) val = 4;
  this.ratings[rater][key] = Number(val.toFixed(2));
},

    ratings: {
      dean:    { i1: 0, i2: 0, i3: 0, i4: 0 },
      chair:   { i1: 0, i2: 0, i3: 0, i4: 0 },
      student: { i1: 0, i2: 0, i3: 0, i4: 0 },
    },
    previous: 0,

    init() {
      if (initial.ratings) {
        this.ratings = {
          dean:    { i1: initial.ratings?.dean?.i1 ?? 0, i2: initial.ratings?.dean?.i2 ?? 0, i3: initial.ratings?.dean?.i3 ?? 0, i4: initial.ratings?.dean?.i4 ?? 0 },
          chair:   { i1: initial.ratings?.chair?.i1 ?? 0, i2: initial.ratings?.chair?.i2 ?? 0, i3: initial.ratings?.chair?.i3 ?? 0, i4: initial.ratings?.chair?.i4 ?? 0 },
          student: { i1: initial.ratings?.student?.i1 ?? 0, i2: initial.ratings?.student?.i2 ?? 0, i3: initial.ratings?.student?.i3 ?? 0, i4: initial.ratings?.student?.i4 ?? 0 },
        };
      }
      if (initial.previous_points !== undefined && initial.previous_points !== null && initial.previous_points !== '') {
        this.previous = Number(initial.previous_points || 0);
      }

      ['dean', 'chair', 'student'].forEach((rater) => {
        ['i1', 'i2', 'i3', 'i4'].forEach((key) => this.clampRating(rater, key));
      });

      this.$el.addEventListener('input', (event) => {
        const name = String(event?.target?.name || '');
        const match = name.match(/^section2\[ratings\]\[(dean|chair|student)\]\[(i[1-4])\]$/);
        if (!match) return;
        this.clampRating(match[1], match[2]);
      });

      this.hasSavedData = this.computeHasSavedData();
      this.originalState = this.captureState();
      this.isEditMode = !this.readOnly && !this.hasSavedData;
    },

    cap(v, max) {
      v = Number(v || 0);
      return v > max ? max : v;
    },
    n(v) {
      const x = Number(v);
      return Number.isFinite(x) && x > 0 ? x : null;
    },

    // ✅ Paper tables (rating -> points)
    pointsFromRatingItem1(r) {
      if (r == null) return 0;
      if (r >= 3.72) return 40;
      if (r >= 3.42) return 36;
      if (r >= 3.12) return 32;
      if (r >= 2.82) return 28;
      if (r >= 2.52) return 24;
      if (r >= 2.22) return 20;
      if (r >= 1.92) return 16;
      if (r >= 1.62) return 12;
      if (r >= 1.31) return 8;
      return 4;
    },
    pointsFromRatingItem2(r) {
      if (r == null) return 0;
      if (r >= 3.72) return 30;
      if (r >= 3.42) return 27;
      if (r >= 3.12) return 24;
      if (r >= 2.82) return 21;
      if (r >= 2.52) return 18;
      if (r >= 2.22) return 15;
      if (r >= 1.92) return 12;
      if (r >= 1.62) return 9;
      if (r >= 1.31) return 6;
      return 3;
    },
    pointsFromRatingItem34(r) {
      if (r == null) return 0;
      if (r >= 3.72) return 25;
      if (r >= 3.42) return 22.5;
      if (r >= 3.12) return 20;
      if (r >= 2.82) return 17.5;
      if (r >= 2.52) return 15;
      if (r >= 2.22) return 12.5;
      if (r >= 1.92) return 10;
      if (r >= 1.62) return 7.5;
      if (r >= 1.31) return 5;
      return 2.5;
    },

    // ✅ Convert a single rater’s rating to points (per item)
    itemPts(itemNo, rater) {
      const key = 'i' + itemNo;
      const r = this.n(this.ratings[rater][key]);
      if (r == null) return 0;
      if (itemNo === 1) return this.pointsFromRatingItem1(r);
      if (itemNo === 2) return this.pointsFromRatingItem2(r);
      return this.pointsFromRatingItem34(r);
    },

    // ✅ Equivalent points shown per row = POINTS of AVERAGE rating (Dean/Chair/Student)
    eqPtsForItem(itemNo) {
      const d = this.n(this.ratings.dean['i'+itemNo]);
      const c = this.n(this.ratings.chair['i'+itemNo]);
      const s = this.n(this.ratings.student['i'+itemNo]);

      const vals = [d,c,s].filter(v => v != null);
      if (vals.length === 0) return '0.00';

      const avg = vals.reduce((a,b)=>a+b,0) / vals.length;

      if (itemNo === 1) return this.pointsFromRatingItem1(avg);
      if (itemNo === 2) return this.pointsFromRatingItem2(avg);
      return this.pointsFromRatingItem34(avg);
    },

    // ✅ Totals per rater (sum of POINTS per item, not ratings)
    deanTotalPts() {
      return this.itemPts(1,'dean') + this.itemPts(2,'dean') + this.itemPts(3,'dean') + this.itemPts(4,'dean');
    },
    chairTotalPts() {
      return this.itemPts(1,'chair') + this.itemPts(2,'chair') + this.itemPts(3,'chair') + this.itemPts(4,'chair');
    },
    studentTotalPts() {
      return this.itemPts(1,'student') + this.itemPts(2,'student') + this.itemPts(3,'student') + this.itemPts(4,'student');
    },

    // ✅ Weighted total uses rater totals
    weightedTotal() {
      const d = this.deanTotalPts();
      const c = this.chairTotalPts();
      const s = this.studentTotalPts();
      return (d * 0.40) + (c * 0.30) + (s * 0.30);
    },

    prevThird() {
      const p = Number(this.previous || 0);
      return p / 3;
    },

    rawTotal() {
      return this.weightedTotal() + this.prevThird();
    },

    cappedTotal() {
      return this.cap(this.rawTotal(), 120);
    },
  }
}
</script>




