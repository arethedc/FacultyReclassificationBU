{{-- resources/views/reclassification/review.blade.php --}}
<x-app-layout>
    <nav class="sticky top-16 z-30 bg-white/95 backdrop-blur border-b border-gray-200">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex flex-wrap items-center gap-2">
          @for ($i = 1; $i <= 5; $i++)
            <a href="{{ route('reclassification.section', $i) }}"
               class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50">
              Section {{ $i }}
            </a>
          @endfor
          <span class="mx-2 h-4 w-px bg-gray-200 hidden sm:inline-flex"></span>
          <a href="{{ route('reclassification.review') }}"
             class="px-3 py-1.5 rounded-lg bg-bu text-white text-xs font-semibold shadow-soft">
            Review Summary
          </a>
        </div>
      </div>
    </nav>

<x-slot name="header">
    <div class="flex flex-col gap-1">
        <h2 class="text-2xl font-semibold text-gray-800">
            Reclassification – Review & Summary
        </h2>
        <p class="text-sm text-gray-500">
            Final computation summary (All sections) + Equivalent Percentage + Recommended Equivalent Rank
        </p>
    </div>
</x-slot>

<form method="POST" action="{{ route('reclassification.review.save') }}">
@csrf

{{-- ✅ Backend should provide COUNTED/CAPPED totals per section (already validated by section pages) --}}
<input type="hidden" name="summary[section1]" value="{{ $section1 ?? 0 }}">
<input type="hidden" name="summary[section2]" value="{{ $section2 ?? 0 }}">
<input type="hidden" name="summary[section3]" value="{{ $section3 ?? 0 }}">
<input type="hidden" name="summary[section4]" value="{{ $section4 ?? 0 }}">
<input type="hidden" name="summary[section5]" value="{{ $section5 ?? 0 }}">

{{-- ✅ Auto track (no manual selection). Use authenticated user rank or pass $currentRank from controller --}}
@php
  // Expect: $currentRank = 'Instructor'|'Assistant Professor'|'Associate Professor'|'Full Professor'
  $currentRank = $currentRank ?? (auth()->user()->teaching_rank ?? auth()->user()->rank ?? 'Instructor');

  // Normalize for Alpine
  $trackKey = match (strtolower(trim($currentRank))) {
      'full professor', 'full' => 'full',
      'associate professor', 'associate' => 'associate',
      'assistant professor', 'assistant' => 'assistant',
      default => 'instructor',
  };

  // ✅ Eligibility flags (compute in controller for accuracy; these are safe defaults)
  // Suggested controller flags:
  // $hasMasters, $hasDoctorate, $hasResearchEquivalent, $hasAcceptedResearchOutput
  $hasMasters = $hasMasters ?? (bool)($hasMasters ?? false);
  $hasDoctorate = $hasDoctorate ?? (bool)($hasDoctorate ?? false);
  $hasResearchEquivalent = $hasResearchEquivalent ?? (bool)($hasResearchEquivalent ?? false); // e.g., Section III criteria met >=2 or other allowed equivalent
  $hasAcceptedResearchOutput = $hasAcceptedResearchOutput ?? (bool)($hasAcceptedResearchOutput ?? false); // for Full Prof note
  $hasMinBuYears = $hasMinBuYears ?? (bool)($hasMinBuYears ?? false);
@endphp

<div
  x-data="reviewSummary({
    s1: Number(document.querySelector('[name=&quot;summary[section1]&quot;]').value || 0),
    s2: Number(document.querySelector('[name=&quot;summary[section2]&quot;]').value || 0),
    s3: Number(document.querySelector('[name=&quot;summary[section3]&quot;]').value || 0),
    s4: Number(document.querySelector('[name=&quot;summary[section4]&quot;]').value || 0),
    s5: Number(document.querySelector('[name=&quot;summary[section5]&quot;]').value || 0),

    // auto track from user profile
    track: '{{ $trackKey }}',
    trackLabel: @js($currentRank),

    // eligibility flags (computed server-side ideally)
    hasMasters: {{ $hasMasters ? 'true' : 'false' }},
    hasDoctorate: {{ $hasDoctorate ? 'true' : 'false' }},
    hasResearchEquivalent: {{ $hasResearchEquivalent ? 'true' : 'false' }},
    hasAcceptedResearchOutput: {{ $hasAcceptedResearchOutput ? 'true' : 'false' }},
    hasMinBuYears: {{ $hasMinBuYears ? 'true' : 'false' }},

    // OPTIONAL: if you pass these from Section III backend:
    // criteriaMet3: {{ $criteriaMet3 ?? 0 }},
  })"
  class="py-12 bg-bu-muted min-h-screen"
>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
    {{-- =======================
    MY INFORMATION
    ======================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">My Information</h3>
      </div>
      <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-3">
          <div>
            <div class="text-xs text-gray-500">Name</div>
            <div class="text-sm font-semibold text-gray-800">{{ $facultyName ?? (auth()->user()->name ?? 'Faculty') }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Date of Original Appointment</div>
            <div class="text-sm font-semibold text-gray-800">{{ $appointmentDate ?? '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Total Years of Service (BU)</div>
            <div class="text-sm font-semibold text-gray-800">
              {{ $yearsService !== null ? (int) $yearsService . ' years' : '—' }}
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <div>
            <div class="text-xs text-gray-500">Current Teaching Rank</div>
            <div class="text-sm font-semibold text-gray-800">{{ $currentRank ?? 'Instructor' }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Rank Based on Points</div>
            <div class="text-sm font-semibold text-gray-800" x-text="isSection2Pending() ? 'Not yet available' : (pointsRankLabel() || '—')"></div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Allowed Rank (Rules Applied)</div>
            <div class="text-sm font-semibold text-gray-800" x-text="isSection2Pending() ? 'Not yet available' : (allowedRankLabel() || 'Not eligible')"></div>
          </div>
          <template x-if="isSection2Pending()">
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
              Section II is not yet answered. Rank outputs are provisional and may change after Dean ratings.
            </div>
          </template>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 space-y-1">
          <div class="font-semibold text-amber-800">Rank rules applied</div>
          <div>• Must have Master’s degree.</div>
          <div>• Must have at least one research output/equivalent.</div>
          <div>• Full Professor requires Doctorate + accepted research output.</div>
          <div>• Only one rank step per cycle.</div>
        </div>
      </div>
    </div>

    {{-- =======================
    STICKY FINAL SUMMARY
    ======================== --}}
    <div
      x-data="{ open:true, stuck:false, userOverride:false }"
      x-init="
        const onScroll = () => {
          const nowStuck = window.scrollY > 140;
          if (!stuck && nowStuck) { stuck = true; if (!userOverride) open = false; return; }
          if (stuck && !nowStuck) { stuck = false; if (!userOverride) open = true; return; }
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
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 truncate">
                Final Summary
              </h3>

              {{-- current track (auto) --}}
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-50 text-gray-700 border border-gray-200">
                Current Rank: <span class="ml-1 font-semibold" x-text="trackLabel"></span>
              </span>

              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-50 text-gray-700 border border-gray-200">
                Equivalent %: <span class="ml-1 font-semibold" x-text="eqPercent().toFixed(2)"></span>
              </span>

              <template x-if="recommendedRank()">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
                  Recommended: <span class="ml-1 font-semibold" x-text="recommendedRank()"></span>
                </span>
              </template>

              <template x-if="!recommendedRank()">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                  Outside A/B/C band
                </span>
              </template>
            </div>

            <p class="text-xs text-gray-600 mt-1">
              Total Points: <span class="font-semibold text-gray-800" x-text="totalPoints().toFixed(2)"></span>
              <span class="mx-2 text-gray-300">•</span>
              Equivalent % (÷ 4): <span class="font-semibold text-gray-800" x-text="eqPercent().toFixed(2)"></span>
            </p>
          </div>

          <button type="button"
                  @click="userOverride = true; open = !open"
                  class="px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
            <span x-text="open ? 'Hide details' : 'Show details'"></span>
          </button>
        </div>

        <div x-show="open" x-collapse class="px-5 pb-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

            {{-- Totals card --}}
            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Computed totals</p>
              <p class="text-xl font-semibold text-gray-800 mt-2">
                <span x-text="totalPoints().toFixed(2)"></span>
              </p>
              <p class="text-xs text-gray-500 mt-1">
                Equivalent %: <span class="font-medium text-gray-700" x-text="eqPercent().toFixed(2)"></span>
              </p>
              <p class="text-xs text-gray-500 mt-1">
                Recommended: <span class="font-medium text-gray-700" x-text="recommendedRank() || '—'"></span>
              </p>
            </div>

            {{-- Eligibility (auto) --}}
            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Auto validation checks</p>

              <ul class="mt-2 space-y-1 text-sm">
                <li class="flex items-center justify-between">
                  <span>Master’s degree requirement</span>
                  <span class="text-xs font-semibold"
                        :class="hasMasters ? 'text-green-700' : 'text-red-700'"
                        x-text="hasMasters ? 'OK' : 'Missing'"></span>
                </li>

                  <li class="flex items-center justify-between">
                    <span>At least 3 years service in BU</span>
                    <span class="text-xs font-semibold"
                        :class="hasMinBuYears ? 'text-green-700' : 'text-red-700'"
                        x-text="hasMinBuYears ? 'OK' : 'Missing'"></span>
                  </li>

                  <li class="flex items-center justify-between">
                    <span>Research / equivalent requirement</span>
                    <span class="text-xs font-semibold"
                        :class="hasResearchEquivalent ? 'text-green-700' : 'text-red-700'"
                        x-text="hasResearchEquivalent ? 'OK' : 'Missing'"></span>
                </li>

                <li class="flex items-center justify-between">
                  <span>Doctorate (Full Professor note)</span>
                  <span class="text-xs font-semibold"
                        :class="hasDoctorate ? 'text-green-700' : 'text-gray-500'"
                        x-text="hasDoctorate ? 'Present' : 'Not provided'"></span>
                </li>

                <li class="flex items-center justify-between">
                  <span>Accepted research output (Full Professor note)</span>
                  <span class="text-xs font-semibold"
                        :class="hasAcceptedResearchOutput ? 'text-green-700' : 'text-gray-500'"
                        x-text="hasAcceptedResearchOutput ? 'Present' : 'Not provided'"></span>
                </li>
              </ul>

              <template x-if="track === 'full'">
                <p class="mt-2 text-xs text-gray-500">
                  For Full Professor: doctorate + at least one accepted research output is required (per notes).
                </p>
              </template>
            </div>

            {{-- Actions/Reminder --}}
            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Review flow</p>
              <p class="text-sm text-gray-700 mt-2">
                This page is read-only. If you need to adjust entries/evidence, open the section and edit there.
              </p>
              <p class="text-xs text-gray-500 mt-2">
                Final validation is still handled externally.
              </p>
            </div>

          </div>
        </div>
      </div>
    </div>

    {{-- =======================
    SECTION TOTALS (quick review)
    ======================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b flex items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-semibold text-gray-800">Total Points Summary</h3>
          <p class="text-sm text-gray-500">
            Counted totals per section (caps already applied on each section page).
          </p>
        </div>
        <div class="text-right">
          <p class="text-xs text-gray-500">TOTAL</p>
          <p class="text-lg font-semibold text-gray-900" x-text="totalPoints().toFixed(2)"></p>
        </div>
      </div>

      <div class="p-6">
        <div class="overflow-x-auto">
          <table class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left">Section</th>
                <th class="px-4 py-3 text-right">Counted Points</th>
                <th class="px-4 py-3 text-right">Quick Action</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr>
                <td class="px-4 py-3 font-medium">Section I – Academic Preparation & Professional Development</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="s1.toFixed(2)"></td>
                <td class="px-4 py-3 text-right">
                  <a href="{{ route('reclassification.section', 1) }}"
                     class="text-bu text-xs font-medium hover:underline">
                    View Section I
                  </a>
                </td>
              </tr>

              <tr>
                <td class="px-4 py-3 font-medium">Section II – Instructional Competence</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="s2.toFixed(2)"></td>
                <td class="px-4 py-3 text-right">
                  <a href="{{ route('reclassification.section', 2) }}"
                     class="text-bu text-xs font-medium hover:underline">
                    View Section II
                  </a>
                </td>
              </tr>

              <tr>
                <td class="px-4 py-3 font-medium">Section III – Research Competence & Productivity</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="s3.toFixed(2)"></td>
                <td class="px-4 py-3 text-right">
                  <a href="{{ route('reclassification.section', 3) }}"
                     class="text-bu text-xs font-medium hover:underline">
                    View Section III
                  </a>
                </td>
              </tr>

              <tr>
                <td class="px-4 py-3 font-medium">Section IV – Teaching / Professional / Administrative Experience</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="s4.toFixed(2)"></td>
                <td class="px-4 py-3 text-right">
                  <a href="{{ route('reclassification.section', 4) }}"
                     class="text-bu text-xs font-medium hover:underline">
                    View Section IV
                  </a>
                </td>
              </tr>

              <tr>
                <td class="px-4 py-3 font-medium">Section V – Professional & Community Leadership Service</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800" x-text="s5.toFixed(2)"></td>
                <td class="px-4 py-3 text-right">
                  <a href="{{ route('reclassification.section', 5) }}"
                     class="text-bu text-xs font-medium hover:underline">
                    View Section V
                  </a>
                </td>
              </tr>

              <tr class="bg-gray-50">
                <td class="px-4 py-3 font-semibold">TOTAL POINTS</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-900" x-text="totalPoints().toFixed(2)"></td>
                <td class="px-4 py-3"></td>
              </tr>

              <tr class="bg-green-600 text-white">
                <td class="px-4 py-3 font-semibold">EQUIVALENT PERCENTAGE (Total / 4)</td>
                <td class="px-4 py-3 text-right font-semibold" x-text="eqPercent().toFixed(2)"></td>
                <td class="px-4 py-3"></td>
              </tr>
            </tbody>
          </table>
        </div>

        {{-- send computed values to backend on submit --}}
        <input type="hidden" name="summary[track]" value="{{ $trackKey }}">
        <input type="hidden" name="summary[current_rank]" value="{{ $currentRank }}">
        <input type="hidden" name="summary[total_points]" :value="totalPoints().toFixed(2)">
        <input type="hidden" name="summary[equivalent_percentage]" :value="eqPercent().toFixed(2)">
        <input type="hidden" name="summary[recommended_rank]" :value="recommendedRank()">
      </div>
    </div>

    {{-- =======================
    RANK TABLES (REFERENCE)
    ======================== --}}
    <div x-data="{ showRanks:false }" class="bg-white rounded-2xl shadow-card border border-gray-200">
      <button type="button"
              @click="showRanks = !showRanks"
              class="w-full px-6 py-4 border-b flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-800">Ranks and Equivalent Percentages</h3>
          <p class="text-sm text-gray-500">Reference table used to determine A/B/C rank letter.</p>
        </div>
        <span class="text-sm text-gray-600" x-text="showRanks ? 'Hide' : 'Show'"></span>
      </button>

      <div x-show="showRanks" x-collapse class="p-6 space-y-4">
        <div class="overflow-x-auto">
          <table class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left">Track</th>
                <th class="px-4 py-3 text-left">A</th>
                <th class="px-4 py-3 text-left">B</th>
                <th class="px-4 py-3 text-left">C</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr>
                <td class="px-4 py-3 font-medium">Full Professor</td>
                <td class="px-4 py-3">95.87 – 100.00</td>
                <td class="px-4 py-3">91.50 – 95.86</td>
                <td class="px-4 py-3">87.53 – 91.49</td>
              </tr>
              <tr>
                <td class="px-4 py-3 font-medium">Associate Professor</td>
                <td class="px-4 py-3">83.34 – 87.52</td>
                <td class="px-4 py-3">79.19 – 83.33</td>
                <td class="px-4 py-3">75.02 – 79.18</td>
              </tr>
              <tr>
                <td class="px-4 py-3 font-medium">Assistant Professor</td>
                <td class="px-4 py-3">70.85 – 75.01</td>
                <td class="px-4 py-3">66.68 – 70.84</td>
                <td class="px-4 py-3">62.51 – 66.67</td>
              </tr>
              <tr>
                <td class="px-4 py-3 font-medium">Instructor</td>
                <td class="px-4 py-3">58.34 – 62.50</td>
                <td class="px-4 py-3">54.14 – 58.33</td>
                <td class="px-4 py-3">50.00 – 54.16</td>
              </tr>
            </tbody>
          </table>
        </div>

        <p class="text-xs text-gray-500">
          Your current Equivalent % is
          <span class="font-semibold text-gray-700" x-text="eqPercent().toFixed(2)"></span>.
          For your current rank track (<span class="font-semibold text-gray-700" x-text="trackLabel"></span>),
          recommended equivalent rank is
          <span class="font-semibold text-gray-700" x-text="recommendedRank() || '—'"></span>.
        </p>
      </div>
    </div>

    {{-- =======================
    NOTES TO THE RATER (DISPLAY ONLY)
    ======================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">Notes to the Rater</h3>
      </div>
      <div class="p-6 text-sm text-gray-700 space-y-2">
        <p>• No faculty member can be promoted to the rank of Full Professor who has not earned a doctorate degree in his field of teaching assignment or allied field of discipline and has produced at least one accepted research output; or recognition of outstanding accomplishments in arts and sciences; attainment of higher responsible position in government service, business and industry.</p>
        <p>• No faculty member can be promoted to more than one rank (not step) during any one reclassification term.</p>
        <p>• Normally, a new faculty member starts as a probationary instructor, but he may be appointed to a higher rank depending on his credentials.</p>
        <p>• A faculty member cannot be ranked if he does not have a master’s degree.</p>
        <p>• A faculty member cannot be ranked without any research or its equivalent.</p>
        <p>• A faculty member who has just earned his/her master’s degree can be classified even if it is not within the reclassification term in the University.</p>
      </div>
    </div>

    {{-- =======================
    ACTIONS
    ======================== --}}
    <div class="flex justify-end gap-4">
      <button type="submit" name="action" value="draft" data-skip-validate="true"
              class="px-6 py-2.5 rounded-xl border border-gray-300">
        Save Draft
      </button>

      {{-- Optional: block submit if key requirements missing --}}
      <button
        type="submit"
        formaction="{{ route('reclassification.submit', $application->id) }}"
        class="px-6 py-2.5 rounded-xl bg-bu text-white"
        :disabled="!canFinalSubmit()"
        :class="!canFinalSubmit() ? 'opacity-60 cursor-not-allowed' : ''"
        @click="if (!canFinalSubmit()) { $event.preventDefault(); return; } if (!window.confirm(finalSubmitConfirmMessage())) { $event.preventDefault(); }"
      >
        Final Submit
      </button>
    </div>

    <template x-if="!canFinalSubmit()">
      <div class="bg-white rounded-2xl shadow-card border border-amber-200">
        <div class="p-6">
          <p class="text-sm text-amber-700 font-medium">
            You can’t final submit yet.
          </p>
          <p class="text-sm text-gray-600 mt-1">
            Please satisfy minimum requirements first (Master’s + Research/Equivalent). You can still save as draft.
          </p>
        </div>
      </div>
    </template>

  </div>
</div>

<script>
function reviewSummary(init) {
  return {
    // counted totals (already capped on section pages)
    s1: Number(init.s1 || 0),
    s2: Number(init.s2 || 0),
    s3: Number(init.s3 || 0),
    s4: Number(init.s4 || 0),
    s5: Number(init.s5 || 0),

    // auto track from user profile
    track: init.track || 'instructor',
    trackLabel: init.trackLabel || 'Instructor',

    // eligibility flags (server-side preferred)
    hasMasters: !!init.hasMasters,
    hasDoctorate: !!init.hasDoctorate,
    hasResearchEquivalent: !!init.hasResearchEquivalent,
    hasAcceptedResearchOutput: !!init.hasAcceptedResearchOutput,
    hasMinBuYears: !!init.hasMinBuYears,

    totalPoints() {
      return Number(this.s1 + this.s2 + this.s3 + this.s4 + this.s5);
    },

    // PAPER: equivalent percentage = total points divided by 4
    eqPercent() {
      return this.totalPoints() / 4;
    },

    isSection2Pending() {
      return Number(this.s2 || 0) <= 0;
    },

    // Determine A/B/C based on CURRENT user rank track (auto)
    recommendedRank() {
      const p = this.eqPercent();

      const ranges = {
        full: [
          { letter:'A', min:95.87, max:100.00 },
          { letter:'B', min:91.50, max:95.86 },
          { letter:'C', min:87.53, max:91.49 },
        ],
        associate: [
          { letter:'A', min:83.34, max:87.52 },
          { letter:'B', min:79.19, max:83.33 },
          { letter:'C', min:75.02, max:79.18 },
        ],
        assistant: [
          { letter:'A', min:70.85, max:75.01 },
          { letter:'B', min:66.68, max:70.84 },
          { letter:'C', min:62.51, max:66.67 },
        ],
        instructor: [
          { letter:'A', min:58.34, max:62.50 },
          { letter:'B', min:54.14, max:58.33 },
          { letter:'C', min:50.00, max:54.16 },
        ],
      };

      const list = ranges[this.track] || [];
      const hit = list.find(r => p >= r.min && p <= r.max);
      if (!hit) return '';

      const trackLabel = {
        full:'Full Professor',
        associate:'Associate Professor',
        assistant:'Assistant Professor',
        instructor:'Instructor',
      }[this.track] || this.trackLabel;

      return `${trackLabel} – ${hit.letter}`;
    },

    pointsRank() {
      const p = this.eqPercent();
      const ranges = {
        full: [
          { letter:'A', min:95.87, max:100.00 },
          { letter:'B', min:91.50, max:95.86 },
          { letter:'C', min:87.53, max:91.49 },
        ],
        associate: [
          { letter:'A', min:83.34, max:87.52 },
          { letter:'B', min:79.19, max:83.33 },
          { letter:'C', min:75.02, max:79.18 },
        ],
        assistant: [
          { letter:'A', min:70.85, max:75.01 },
          { letter:'B', min:66.68, max:70.84 },
          { letter:'C', min:62.51, max:66.67 },
        ],
        instructor: [
          { letter:'A', min:58.34, max:62.50 },
          { letter:'B', min:54.14, max:58.33 },
          { letter:'C', min:50.00, max:54.16 },
        ],
      };
      const order = ['full','associate','assistant','instructor'];
      for (const key of order) {
        const list = ranges[key];
        const hit = list.find(r => p >= r.min && p <= r.max);
        if (hit) return { track: key, letter: hit.letter };
      }
      return null;
    },

    pointsRankLabel() {
      const hit = this.pointsRank();
      if (!hit) return '';
      const labels = {
        full: 'Full Professor',
        associate: 'Associate Professor',
        assistant: 'Assistant Professor',
        instructor: 'Instructor',
      };
      return `${labels[hit.track]} – ${hit.letter}`;
    },

	    allowedRankLabel() {
	      if (!this.hasMasters || !this.hasResearchEquivalent) return '';
	      const labels = {
	        full: 'Full Professor',
        associate: 'Associate Professor',
        assistant: 'Assistant Professor',
        instructor: 'Instructor',
      };
      const order = { instructor: 1, assistant: 2, associate: 3, full: 4 };
      const nextRank = (key) => {
        const target = order[key] + 1;
        const hit = Object.keys(order).find((k) => order[k] === target);
        return hit || key;
      };

      let desired = this.pointsRank()?.track || this.track;
      const maxAllowed = (this.hasDoctorate && this.hasAcceptedResearchOutput) ? 'full' : 'associate';
      if (order[desired] > order[maxAllowed]) desired = maxAllowed;

	      const oneStep = nextRank(this.track);
	      if (order[desired] > order[oneStep]) desired = oneStep;

	      let letter = this.pointsRank()?.letter || '';
	      if (this.pointsRank()?.track && this.pointsRank()?.track !== desired) {
	        // If capped down from a higher points rank, show the highest letter in the allowed rank.
	        letter = 'A';
	      }

	      return letter ? `${labels[desired]} - ${letter}` : (labels[desired] || '');
	    },

    // ✅ minimum submit gate (based on your notes)
    // - must have Masters
    // - must have Research or equivalent
    // For Full Professor note: doctorate + accepted research output required (if you want strict gate)
    canFinalSubmit() {
      if (!this.hasMasters) return false;
      if (!this.hasMinBuYears) return false;
      if (!this.hasResearchEquivalent) return false;

      // OPTIONAL strict gate for Full Professor:
      // if (this.track === 'full') {
      //   if (!this.hasDoctorate) return false;
      //   if (!this.hasAcceptedResearchOutput) return false;
      // }

      return true;
    },

    finalSubmitConfirmMessage() {
      return "Are you sure you want to final submit this reclassification?\n\nPlease make sure all required documents are complete.\n\nOnly one submission is allowed per period, and you cannot fully revise after final submit unless a reviewer returns the form.";
    },
  }
}
</script>

<script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

</form>
</x-app-layout>
