{{-- resources/views/reclassification/section4.blade.php --}}
@php
    $employmentType = auth()->user()->facultyProfile?->employment_type
        ?? (auth()->user()->employment_type ?? 'full_time');
    $isPartTimeFaculty = $employmentType === 'part_time';
@endphp
<div class="flex flex-col gap-1 mb-4">
    <h2 class="text-2xl font-semibold text-gray-800">Reclassification - Section IV</h2>
    <p class="text-sm text-gray-500">Teaching Experience / Professional / Administrative Experience (Max 40 pts / 10%)</p>
</div>
<form method="POST" action="{{ route('reclassification.section.save', 4) }}" enctype="multipart/form-data" data-validate-evidence>
@csrf

<div x-data="sectionFour(@js($sectionData ?? []), @js($globalEvidence ?? []))" x-init="init()" class="py-12 bg-bu-muted min-h-screen">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    {{-- =======================
    STICKY SCORE SUMMARY (Section IV)
    ======================== --}}
    <div
      x-data="{ open:true }"
      class="sticky top-20 z-20"
    >
      <div class="bg-white/95 backdrop-blur rounded-2xl border shadow-card">
        <div class="px-5 py-3 flex items-center justify-between gap-4">
          <div class="min-w-0">
            <div class="flex items-center gap-3">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 truncate">
                Section IV Score Summary
              </h3>

<template x-if="Number(finalCapped()) <= 40">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
                  Within limit
                </span>
              </template>
<template x-if="Number(finalCapped()) > 40">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
                  Over limit
                </span>
              </template>

              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-50 text-gray-700 border">
                Counted track:
                <span class="ml-1 font-semibold" x-text="countedTrackLabel()"></span>
              </span>

              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                Scoring mode:
                <span class="ml-1 font-semibold" x-text="scoringModeLabel()"></span>
              </span>
            </div>

            <p class="text-xs text-gray-600 mt-1">
              Teaching (A) capped: <span class="font-semibold text-gray-800" x-text="teachingTotalCapped()"></span>
              <span class="mx-2 text-gray-300">•</span>
              Industry/Admin (B) capped: <span class="font-semibold text-gray-800" x-text="industryCapped()"></span>
              <span class="mx-2 text-gray-300">•</span>
              Final: <span class="font-semibold text-gray-800" x-text="finalCapped()"></span>
              <span class="text-gray-400">/ 40</span>
            </p>
          </div>

          <button
            type="button"
            @click="open = !open"
            class="px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50"
          >
            <span x-text="open ? 'Hide details' : 'Show details'"></span>
          </button>
        </div>

        <div x-show="open" x-collapse class="px-5 pb-4">
          <p class="text-xs text-gray-500">
            Rule: credit is given only for Teaching Experience (A) OR Industry/Professional/Admin Experience (B),
            whichever is higher in points. For part-time faculty, deduction (50%) is applied on the final counted track.
            Subject to validation.
          </p>

          <div class="mt-3 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">A1 (Before BU)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="a1Capped()"></span>
                <span class="text-sm font-medium text-gray-400">/ 20</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">2 pts/year (capped)</p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">A2 (After BU)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="a2Capped()"></span>
                <span class="text-sm font-medium text-gray-400">/ 40</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">3 pts/year (capped)</p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Teaching Total (A)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="teachingTotalCapped()"></span>
                <span class="text-sm font-medium text-gray-400">/ 40</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">A1 + A2, then cap</p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Industry/Admin (B)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="industryCapped()"></span>
                <span class="text-sm font-medium text-gray-400">/ 20</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">2 pts/year (capped)</p>
            </div>
          </div>

          <template x-if="isPartTime">
            <p class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
              Part-time selected: entry points are shown in full; 50% deduction is applied on the final counted score.
            </p>
          </template>
        </div>
      </div>
    </div>

    {{-- ======================================================
    SECTION IV – FORM
    ====================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">
                Teaching Experience / Professional / Administrative Experience
            </h3>
            <p class="text-sm text-gray-500">
                Encode years only. System suggests points and applies caps. Subject to validation.
            </p>
        </div>

        <div class="p-6 space-y-8">

         <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-gray-50 border">
    <div>
        <p class="text-sm font-medium text-gray-800">Employment Type</p>
        <p class="text-xs text-gray-500">
            Entry points are shown in full; part-time deduction is applied on final counted score.
        </p>
    </div>

    <div class="text-sm font-semibold"
         @class([
            'text-gray-800' => !$isPartTimeFaculty,
            'text-amber-700' => $isPartTimeFaculty,
         ])>
        {{ $isPartTimeFaculty ? 'Part-time (50% applied)' : 'Full-time (100% applied)' }}
    </div>
</div>

            {{-- A. Teaching Experience --}}
            <div class="rounded-2xl border p-5 space-y-4">
                <div>
                    <h4 class="font-semibold text-gray-800">A. Teaching Experience</h4>
                    <p class="text-sm text-gray-500">
                        A1 capped at 20 pts; A2 capped at 40 pts; Teaching total capped at 40 pts.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- A1 --}}
                    <div class="rounded-xl border p-4">
                        <p class="text-sm font-medium text-gray-800">
                            A1. Teaching before joining BU
                        </p>
                        <p class="text-xs text-gray-500">
                            2 pts per year (cap 20)
                        </p>

                        <div class="mt-3 flex items-end justify-between gap-3">
                            <div class="flex-1">
                                <input type="hidden" name="section4[a][a1_id]" :value="a1Id || ''">
                                <label class="block text-xs text-gray-600">Years</label>
                                <input
                                    x-model.number="a1Years"
                                    name="section4[a][a1_years]"
                                    type="number" min="0" step="1"
                                    class="mt-1 w-full rounded border-gray-300"
                                    placeholder="0"
                                >
                            </div>

                            <div class="text-right">
                                <p class="text-xs text-gray-500">Points (Auto)</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    <span x-text="a1Capped()"></span>
                                    <span class="text-xs font-medium text-gray-400">/20</span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-3" x-show="a1Years > 0 || (a1Comments || []).length" data-evidence-block="a1">
                            <label class="block text-xs text-gray-600 mb-1">Evidence</label>
                            <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                                      :class="rowEvidenceCount('a1') > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                                    <span x-text="rowEvidenceCount('a1') > 0 ? `Evidence attached (${rowEvidenceCount('a1')})` : 'No evidence'"></span>
                                </span>

                                <button type="button"
                                        @click="openSelectEvidence('a1')"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                                </button>

                                <button type="button"
                                        @click="openShowEvidence('a1')"
                                        :disabled="rowEvidenceCount('a1') === 0"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                                        :class="rowEvidenceCount('a1') === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                                    Show Evidence
                                    <span class="text-[11px]" x-text="`(${rowEvidenceCount('a1')})`"></span>
                                </button>
                            </div>

                            <template x-if="a1Comments.length">
                                <div class="mt-2" x-data="{ row: { comments: a1Comments } }">
                                    @include('reclassification.partials.entry-review-comments-inline')
                                </div>
                            </template>

                            <select x-model="a1Evidence"
                                    multiple
                                    name="section4[a][a1_evidence][]"
                                    class="sr-only"
                                    tabindex="-1"
                                    aria-hidden="true">
                                <option value="" disabled>Select evidence</option>
                                <template x-for="opt in evidenceOptions()" :key="opt.value">
                                    <option :value="opt.value" x-text="opt.label"></option>
                                </template>
                            </select>
                            <template x-for="token in (a1Evidence || [])" :key="token">
                                <input type="hidden" name="section4[a][a1_evidence][]" :value="token">
                            </template>
                        </div>
                    </div>

                    {{-- A2 --}}
                    <div class="rounded-xl border p-4">
                        <p class="text-sm font-medium text-gray-800">
                            A2. Actual services after joining BU
                        </p>
                        <p class="text-xs text-gray-500">
                            3 pts per year (cap 40)
                        </p>

                        <div class="mt-3 flex items-end justify-between gap-3">
                            <div class="flex-1">
                                <input type="hidden" name="section4[a][a2_id]" :value="a2Id || ''">
                                <label class="block text-xs text-gray-600">Years</label>
                                <input
                                    x-model.number="a2Years"
                                    name="section4[a][a2_years]"
                                    type="number" min="0" step="1"
                                    class="mt-1 w-full rounded border-gray-300"
                                    placeholder="0"
                                >
                            </div>

                            <div class="text-right">
                                <p class="text-xs text-gray-500">Points (Auto)</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    <span x-text="a2Capped()"></span>
                                    <span class="text-xs font-medium text-gray-400">/40</span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-3" x-show="a2Years > 0 || (a2Comments || []).length" data-evidence-block="a2">
                            <label class="block text-xs text-gray-600 mb-1">Evidence</label>
                            <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                                      :class="rowEvidenceCount('a2') > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                                    <span x-text="rowEvidenceCount('a2') > 0 ? `Evidence attached (${rowEvidenceCount('a2')})` : 'No evidence'"></span>
                                </span>

                                <button type="button"
                                        @click="openSelectEvidence('a2')"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                                </button>

                                <button type="button"
                                        @click="openShowEvidence('a2')"
                                        :disabled="rowEvidenceCount('a2') === 0"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                                        :class="rowEvidenceCount('a2') === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                                    Show Evidence
                                    <span class="text-[11px]" x-text="`(${rowEvidenceCount('a2')})`"></span>
                                </button>
                            </div>

                            <template x-if="a2Comments.length">
                                <div class="mt-2" x-data="{ row: { comments: a2Comments } }">
                                    @include('reclassification.partials.entry-review-comments-inline')
                                </div>
                            </template>

                            <select x-model="a2Evidence"
                                    multiple
                                    name="section4[a][a2_evidence][]"
                                    class="sr-only"
                                    tabindex="-1"
                                    aria-hidden="true">
                                <option value="" disabled>Select evidence</option>
                                <template x-for="opt in evidenceOptions()" :key="opt.value">
                                    <option :value="opt.value" x-text="opt.label"></option>
                                </template>
                            </select>
                            <template x-for="token in (a2Evidence || [])" :key="token">
                                <input type="hidden" name="section4[a][a2_evidence][]" :value="token">
                            </template>
                        </div>
                    </div>
                </div>

                {{-- A Total --}}
                <div class="flex items-center justify-between rounded-xl bg-gray-50 border px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800">Teaching Total (A)</p>
                        <p class="text-xs text-gray-500">A1 + A2, capped at 40</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-semibold text-gray-800">
                            <span x-text="teachingTotalCapped()"></span>
                            <span class="text-xs font-medium text-gray-400">/40</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- B. Industry/Professional/Admin Experience --}}
            <div class="rounded-2xl border p-5 space-y-4">
                <div>
                    <h4 class="font-semibold text-gray-800">B. Industry / Professional / Administrative Experience</h4>
                    <p class="text-sm text-gray-500">
                        2 pts per year (cap 20). External validation may require minimum teaching experience.
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3"
                       x-show="!bUnlocked()">
                        B is unlocked if you have at least 3 years after joining BU and 2 years before BU, or at least 5 years after joining BU.
                    </p>
                    <div class="mt-1 flex items-end justify-between gap-3">
                        <div class="flex-1">
                            <input type="hidden" name="section4[b][id]" :value="bId || ''">
                            <label class="block text-xs text-gray-600">Years</label>
                            <input
                                x-model.number="bYears"
                                name="section4[b][years]"
                                type="number" min="0" step="1"
                                class="mt-1 w-full rounded border-gray-300"
                                placeholder="0"
                                :disabled="!bUnlocked()"
                            >
                        </div>

                        <div class="text-right">
                            <p class="text-xs text-gray-500">Points (Auto)</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <span x-text="industryCapped()"></span>
                                <span class="text-xs font-medium text-gray-400">/20</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-3" x-show="(bYears > 0 && bUnlocked()) || (bComments || []).length" data-evidence-block="b">
                        <label class="block text-xs text-gray-600 mb-1">Evidence</label>
                        <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                                  :class="rowEvidenceCount('b') > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                                <span x-text="rowEvidenceCount('b') > 0 ? `Evidence attached (${rowEvidenceCount('b')})` : 'No evidence'"></span>
                            </span>

                            <button type="button"
                                    @click="openSelectEvidence('b')"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                                <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                            </button>

                            <button type="button"
                                    @click="openShowEvidence('b')"
                                    :disabled="rowEvidenceCount('b') === 0"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                                    :class="rowEvidenceCount('b') === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                                Show Evidence
                                <span class="text-[11px]" x-text="`(${rowEvidenceCount('b')})`"></span>
                            </button>
                        </div>

                        <template x-if="bComments.length">
                            <div class="mt-2" x-data="{ row: { comments: bComments } }">
                                @include('reclassification.partials.entry-review-comments-inline')
                            </div>
                        </template>

                        <select x-model="bEvidence"
                                multiple
                                name="section4[b][evidence][]"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                            <option value="" disabled>Select evidence</option>
                            <template x-for="opt in evidenceOptions()" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-for="token in (bEvidence || [])" :key="token">
                            <input type="hidden" name="section4[b][evidence][]" :value="token">
                        </template>
                    </div>
                </div>
            </div>

            {{-- Final counted --}}
            <div class="rounded-2xl border p-5 bg-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-800">Section IV Final (Counted)</h4>
                        <p class="text-sm text-gray-500">
                            Only the higher track is counted (A vs B). Final capped at 40.
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Final (Auto)</p>
                        <p class="text-2xl font-semibold text-gray-800">
                            <span x-text="finalCapped()"></span>
                            <span class="text-sm font-medium text-gray-400">/40</span>
                        </p>
                    </div>
                </div>

	                <p class="mt-3 text-xs text-gray-500">
	                    Counted track: <span class="font-medium text-gray-700" x-text="countedTrackLabel()"></span>
	                </p>
                    <template x-if="isPartTime">
                        <p class="mt-2 text-xs text-amber-700">
                            Part-time deduction applied: final score is 50% of the counted track after cap.
                        </p>
                    </template>
	            </div>

            <div class="hidden" x-effect="emitScore(finalCapped())"></div>
            <div class="hidden" x-effect="enforceBUnlockState()"></div>

            {{-- ACTIONS --}}
            <div class="flex justify-end gap-4 pt-2"></div>


        </div>
    </div>

</div>
{{-- EVIDENCE MODAL --}}
<div x-cloak x-show="evidenceModalOpen" data-return-lock-ignore class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="absolute inset-0 bg-black/40" @click="closeEvidenceModal()"></div>

  <div class="relative bg-white w-full max-w-3xl mx-4 rounded-2xl shadow-xl border"
       role="dialog"
       aria-labelledby="evidence-modal-title"
       aria-describedby="evidence-modal-desc"
       x-ref="evidenceModal"
       @keydown.tab.prevent="cycleFocus($event, 'evidence')"
       @keydown.escape.window="closeEvidenceModal()">

    <div class="px-6 py-4 border-b flex items-center justify-between">
      <div>
        <h3 id="evidence-modal-title" class="text-lg font-semibold text-gray-800"
            x-text="evidenceModalMode === 'show' ? 'Selected Evidence' : 'Select Evidence'"></h3>
        <p id="evidence-modal-desc" class="text-xs text-gray-500">
          Attach evidence files from your upload library. You can select multiple files.
        </p>
      </div>
      <button type="button" @click="closeEvidenceModal()" class="text-gray-500 hover:text-gray-700">
        Close
      </button>
    </div>

    <div class="p-6 max-h-[60vh] overflow-y-auto">
      <template x-if="evidenceModalMode === 'select'">
        <div>
          <template x-if="evidencePool().length === 0">
            <div class="rounded-xl border border-dashed p-6 text-center text-sm text-gray-500 space-y-3">
              <p>No uploaded files yet. Use the Evidence Library below to add files.</p>
              <p class="text-xs text-amber-700">
                Allowed file types: PDF and image files (JPG, JPEG, PNG, GIF, WEBP, BMP, SVG, TIFF, HEIC/HEIF).
              </p>
              <button type="button"
                      @click="openEvidenceUploader(currentRow.key)"
                      class="px-4 py-2 rounded-lg bg-bu text-white text-sm">
                Upload Evidence
              </button>
            </div>
          </template>

          <template x-if="evidencePool().length > 0">
            <div class="space-y-3">
              <template x-for="item in evidencePool()" :key="item.value">
                <label class="flex items-center gap-3 p-3 rounded-xl border hover:bg-gray-50">
                  <input type="checkbox" class="rounded text-bu"
                         :value="item.value" x-model="evidenceSelection">
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="item.label"></p>
                    <div class="text-xs text-gray-500 flex items-center gap-2">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px]"
                            x-text="item.typeLabel"></span>
                      <span x-text="item.uploadedAt || (item.isNew ? 'New upload' : '')"></span>
                    </div>
                  </div>
                  <div class="flex items-center gap-3">
                    <button type="button"
                            @click.stop="openPreview(item)"
                            class="text-xs text-bu hover:underline"
                            :disabled="!item.url && !item.file">
                      Preview
                    </button>
                    <button type="button"
                            @click.stop="removeEvidenceFromLibrary(item)"
                            class="text-xs text-red-600 hover:underline"
                            :disabled="!item.canRemove"
                            :class="!item.canRemove ? 'opacity-50 cursor-not-allowed' : ''">
                      Remove
                    </button>
                  </div>
                </label>
              </template>
            </div>
          </template>
        </div>
      </template>

      <template x-if="evidenceModalMode === 'show'">
        <div>
          <template x-if="currentEvidenceItems().length === 0">
            <div class="rounded-xl border border-dashed p-6 text-center text-sm text-gray-500 space-y-3">
              <p>No evidence attached yet.</p>
              <button type="button"
                      @click="openSelectEvidence(currentRow.key)"
                      class="px-4 py-2 rounded-lg bg-bu text-white text-sm">
                <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
              </button>
            </div>
          </template>

          <template x-if="currentEvidenceItems().length > 0">
            <div class="overflow-hidden rounded-xl border">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                  <tr>
                    <th class="px-4 py-2">File</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Uploaded</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <template x-for="item in currentEvidenceItems()" :key="item.value">
                    <tr>
                      <td class="px-4 py-2">
                        <p class="font-medium text-gray-800" x-text="item.label"></p>
                      </td>
                      <td class="px-4 py-2 text-gray-500" x-text="item.typeLabel"></td>
                      <td class="px-4 py-2 text-gray-500" x-text="item.uploadedAt || (item.isNew ? 'New upload' : '')"></td>
                      <td class="px-4 py-2 text-right space-x-2">
                        <button type="button"
                                @click="openPreview(item)"
                                class="text-xs text-bu hover:underline"
                                :disabled="!item.url && !item.file">
                          View
                        </button>
                        <button type="button"
                                @click="openSelectEvidence(currentRow.key)"
                                class="text-xs text-gray-600 hover:underline">
                          Change
                        </button>
                        <button type="button"
                                @click="detachEvidence(item.value)"
                                class="text-xs text-red-600 hover:underline">
                          Detach
                        </button>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </template>
        </div>
      </template>
    </div>

    <div class="px-6 py-4 border-t flex items-center justify-between">
      <p class="text-xs text-gray-500">
        Tip: You can attach multiple evidence files.
      </p>
      <div class="flex items-center gap-2">
        <button type="button" @click="closeEvidenceModal()"
                class="px-4 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">
          Close
        </button>
        <button type="button"
                x-show="evidenceModalMode === 'select' && evidencePool().length > 0"
                @click="openEvidenceUploader(currentRow.key)"
                class="px-4 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">
          Upload Evidence
        </button>
        <button type="button"
                x-show="evidenceModalMode === 'select' && evidencePool().length > 0"
                @click="attachSelectedEvidence()"
                class="px-4 py-2 rounded-lg bg-bu text-white text-sm">
          Attach Selected
        </button>
      </div>
    </div>
  </div>
</div>

{{-- PREVIEW MODAL --}}
<div x-cloak x-show="previewOpen" class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="absolute inset-0 bg-black/40" @click="closePreview()"></div>
  <div class="relative bg-white w-full max-w-4xl mx-4 rounded-2xl shadow-xl border"
       role="dialog"
       aria-labelledby="preview-modal-title"
       x-ref="previewModal"
       @keydown.tab.prevent="cycleFocus($event, 'preview')"
       @keydown.escape.window="closePreview()">
    <div class="px-6 py-4 border-b flex items-center justify-between">
      <h3 id="preview-modal-title" class="text-lg font-semibold text-gray-800" x-text="previewItem?.label || 'Preview'"></h3>
      <button type="button" @click="closePreview()" class="text-gray-500 hover:text-gray-700">Close</button>
    </div>
    <div class="p-6">
      <template x-if="previewItem && previewItem.isImage">
        <img :src="previewItem.previewUrl" alt="Evidence preview" class="max-h-[70vh] mx-auto rounded-lg border" />
      </template>
      <template x-if="previewItem && previewItem.isPdf">
        <iframe :src="previewItem.previewUrl" class="w-full h-[70vh] rounded-lg border"></iframe>
      </template>
      <template x-if="previewItem && !previewItem.isImage && !previewItem.isPdf">
        <div class="text-sm text-gray-600 space-y-3">
          <p>Preview is not available for this file type.</p>
          <template x-if="previewItem.url">
            <a :href="previewItem.url" target="_blank" class="text-bu hover:underline">Open in new tab</a>
          </template>
        </div>
      </template>
    </div>
  </div>
</div>

{{-- TOAST --}}
<div x-cloak x-show="toast.show" x-transition class="fixed bottom-6 left-6 z-50">
  <div class="px-4 py-2 rounded-lg shadow-lg text-sm text-white"
       :class="toast.type === 'success' ? 'bg-green-600' : 'bg-gray-800'">
    <span x-text="toast.message"></span>
  </div>
</div>
</div>
</form>


<script>
function sectionFour(initial = {}, globalEvidence = []) {
  return {
    isPartTime: {{ $isPartTimeFaculty ? 'true' : 'false' }},
    globalEvidence: globalEvidence || [],
    evidenceModalOpen: false,
    evidenceModalMode: 'select',
    evidenceSelection: [],
    currentRow: { key: null },
    lastFocusEl: null,
    previewOpen: false,
    previewItem: null,
    pendingOpenSelectAfterUpload: false,
    toast: { show: false, message: '', type: 'success' },
    toastTimer: null,

    a1Id: initial.a1_id ? Number(initial.a1_id) : '',
    a2Id: initial.a2_id ? Number(initial.a2_id) : '',
    bId: initial.b_id ? Number(initial.b_id) : '',
    a1Years: Number(initial.a1_years || 0),
    a2Years: Number(initial.a2_years || 0),
    bYears: Number(initial.b_years || 0),
    a1Evidence: Array.isArray(initial.a1_evidence) ? initial.a1_evidence : [],
    a2Evidence: Array.isArray(initial.a2_evidence) ? initial.a2_evidence : [],
    bEvidence: Array.isArray(initial.b_evidence) ? initial.b_evidence : [],
    a1Comments: Array.isArray(initial.a1_comments) ? initial.a1_comments : [],
    a2Comments: Array.isArray(initial.a2_comments) ? initial.a2_comments : [],
    bComments: Array.isArray(initial.b_comments) ? initial.b_comments : [],

    init() {
      const toArray = (val) => {
        if (Array.isArray(val)) return val;
        if (val === null || val === undefined || val === '') return [];
        return [String(val)];
      };
      const toComments = (val) => Array.isArray(val) ? val : [];
      this.a1Evidence = toArray(this.a1Evidence);
      this.a2Evidence = toArray(this.a2Evidence);
      this.bEvidence = toArray(this.bEvidence);
      this.a1Comments = toComments(this.a1Comments);
      this.a2Comments = toComments(this.a2Comments);
      this.bComments = toComments(this.bComments);

      window.addEventListener('evidence-detached', (event) => {
        const id = event.detail?.id;
        if (!id) return;
        const token = `e:${id}`;
        const removeToken = (arr) => Array.isArray(arr) ? arr.filter((v) => String(v) !== token) : [];
        this.a1Evidence = removeToken(this.a1Evidence);
        this.a2Evidence = removeToken(this.a2Evidence);
        this.bEvidence = removeToken(this.bEvidence);
      });

      window.addEventListener('evidence-updated', (event) => {
        const list = event.detail?.evidence;
        if (Array.isArray(list)) {
          this.globalEvidence = list;
          if (this.pendingOpenSelectAfterUpload && this.currentRow?.key) {
            this.pendingOpenSelectAfterUpload = false;
            const key = this.currentRow.key;
            if (this.hasLibraryEvidence()) {
              this.openSelectEvidence(key);
            }
          }
        }
      });
    },

    evidenceOptions() {
	      return (this.globalEvidence || []).map((ev) => ({
	        id: Number(ev.id || 0),
	        value: `e:${ev.id}`,
	        label: ev.name,
	        url: ev.url || null,
	        mime: ev.mime_type || '',
	        uploadedAt: ev.uploaded_at || '',
	        entryCount: Number(ev.entry_count || 0),
	        canRemove: Number(ev.entry_count || 0) === 0,
	        isNew: false,
	        file: null,
	      }));
	    },

    fileTypeLabel(name, mime) {
      if (mime) {
        const parts = mime.split('/');
        return (parts[1] || parts[0]).toUpperCase();
      }
      const ext = (name || '').split('.').pop();
      return ext ? ext.toUpperCase() : 'FILE';
    },

    evidencePool() {
      return this.evidenceOptions().map((item) => {
        const typeLabel = this.fileTypeLabel(item.label, item.mime);
        const isImage = (item.mime || '').startsWith('image/') || /\.(png|jpe?g|gif|webp)$/i.test(item.label || '');
        const isPdf = (item.mime || '') === 'application/pdf' || /\.pdf$/i.test(item.label || '');
	        return {
	          ...item,
	          typeLabel,
	          isImage,
	          isPdf,
	        };
	      });
	    },

	    removeEvidenceFromLibrary(item) {
	      if (!item?.id) return;
	      if (!item.canRemove) {
	        this.toastMessage('Cannot remove evidence that is already attached.', 'error');
	        return;
	      }
	      window.dispatchEvent(new CustomEvent('evidence-remove-request', { detail: { id: item.id } }));
	    },

    hasLibraryEvidence() {
      return this.evidencePool().length > 0;
    },

    selectedEvidence(values) {
      const list = [];
      const map = new Map(this.evidencePool().map((opt) => [String(opt.value), opt]));
      const arr = Array.isArray(values) ? values : (values ? [values] : []);
      arr.forEach((val) => {
        const key = String(val);
        if (!key) return;
        list.push(map.get(key) || { value: key, label: `Selected file (${key})`, url: null, isNew: false, mime: '', typeLabel: 'FILE', isImage: false, isPdf: false });
      });
      return list;
    },

    rowEvidenceCount(key) {
      const list = this.getRowEvidence(key);
      return Array.isArray(list) ? list.length : 0;
    },

    getRowEvidence(key) {
      if (key === 'a1') return this.a1Evidence || [];
      if (key === 'a2') return this.a2Evidence || [];
      if (key === 'b') return this.bEvidence || [];
      return [];
    },

    setRowEvidence(key, values) {
      const clean = Array.isArray(values) ? values.filter(Boolean) : [];
      if (key === 'a1') this.a1Evidence = clean;
      if (key === 'a2') this.a2Evidence = clean;
      if (key === 'b') this.bEvidence = clean;
    },

    openSelectEvidence(key) {
      this.lastFocusEl = document.activeElement;
      this.currentRow = { key };
      this.evidenceSelection = [...this.getRowEvidence(key)];
      this.evidenceModalMode = 'select';
      this.evidenceModalOpen = true;
      this.$nextTick(() => this.focusFirst('evidence'));
    },

    openEvidenceUploader(key = null) {
      if (key !== null) {
        this.currentRow = { key };
        this.pendingOpenSelectAfterUpload = true;
      }
      const picker = document.getElementById('global-evidence-picker-input');
      if (!picker) {
        this.pendingOpenSelectAfterUpload = false;
        this.toastMessage('Evidence picker unavailable.', 'error');
        return;
      }
      picker.click();
    },

    openShowEvidence(key) {
      this.lastFocusEl = document.activeElement;
      this.currentRow = { key };
      this.evidenceModalMode = 'show';
      this.evidenceModalOpen = true;
      this.$nextTick(() => this.focusFirst('evidence'));
    },

    closeEvidenceModal() {
      this.evidenceModalOpen = false;
      this.evidenceSelection = [];
      this.$nextTick(() => {
        if (this.lastFocusEl) this.lastFocusEl.focus({ preventScroll: true });
      });
    },

    attachSelectedEvidence() {
      this.setRowEvidence(this.currentRow.key, this.evidenceSelection);
      this.toastMessage('Evidence attached', 'success');
      this.closeEvidenceModal();
    },

    detachEvidence(value) {
      if (!confirm('Detach evidence from this criterion? The file will remain in your uploaded files.')) return;
      const current = this.getRowEvidence(this.currentRow.key);
      const next = current.filter((v) => v !== value);
      this.setRowEvidence(this.currentRow.key, next);
      this.toastMessage('Evidence detached', 'success');
      if (next.length === 0) {
        this.evidenceModalMode = 'show';
      }
    },

    currentEvidenceItems() {
      return this.selectedEvidence(this.getRowEvidence(this.currentRow.key));
    },

    openPreview(item) {
      if (!item) return;
      this.lastFocusEl = document.activeElement;
      let previewUrl = item.url || null;
      if (!previewUrl && item.file instanceof File) {
        previewUrl = URL.createObjectURL(item.file);
      }
      this.previewItem = {
        ...item,
        previewUrl,
      };
      this.previewOpen = true;
      this.$nextTick(() => this.focusFirst('preview'));
    },

    closePreview() {
      this.previewOpen = false;
      this.previewItem = null;
      this.$nextTick(() => {
        if (this.lastFocusEl) this.lastFocusEl.focus({ preventScroll: true });
      });
    },

    focusFirst(which) {
      const ref = which === 'preview'
        ? this.$refs.previewModal
        : this.$refs.evidenceModal;
      if (!ref) return;
      const focusable = ref.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex=\"-1\"])');
      if (focusable.length) {
        focusable[0].focus({ preventScroll: true });
      }
    },

    cycleFocus(event, which) {
      const ref = which === 'preview'
        ? this.$refs.previewModal
        : this.$refs.evidenceModal;
      if (!ref) return;
      const focusable = Array.from(ref.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex=\"-1\"])'))
        .filter((el) => !el.disabled);
      if (!focusable.length) return;
      const index = focusable.indexOf(document.activeElement);
      const nextIndex = event.shiftKey ? (index <= 0 ? focusable.length - 1 : index - 1) : (index === focusable.length - 1 ? 0 : index + 1);
      focusable[nextIndex].focus({ preventScroll: true });
    },

    scrollToLibrary() {
      const el = document.getElementById('global-evidence-library');
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    },

    toastMessage(message, type = 'success') {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.show = true;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => { this.toast.show = false; }, 2000);
    },

    bUnlocked() {
      const beforeBuYears = this.n(this.a1Years);
      const afterBuYears = this.n(this.a2Years);
      return (afterBuYears >= 3 && beforeBuYears >= 2) || afterBuYears >= 5;
    },

    enforceBUnlockState() {
      if (this.bUnlocked()) return;
      if (this.n(this.bYears) !== 0) {
        this.bYears = 0;
      }
      if (Array.isArray(this.bEvidence) && this.bEvidence.length > 0) {
        this.bEvidence = [];
      }
    },

    n(v) {
      const x = Number(v);
      return Number.isFinite(x) ? x : 0;
    },
    cap(v, max) {
      v = Number(v || 0);
      return v > max ? max : v;
    },
    a1Raw() {
      return this.n(this.a1Years) * 2;
    },
    a1Capped() {
      const v = this.cap(this.a1Raw(), 20);
      return Number(v).toFixed(2);
    },

    a2Raw() {
      return this.n(this.a2Years) * 3;
    },
    a2Capped() {
      const v = this.cap(this.a2Raw(), 40);
      return Number(v).toFixed(2);
    },

    teachingTotalRawCapped() {
      const a1 = this.cap(this.a1Raw(), 20);
      const a2 = this.cap(this.a2Raw(), 40);
      return this.cap(a1 + a2, 40);
    },
    teachingTotalCapped() {
      return Number(this.teachingTotalRawCapped()).toFixed(2);
    },

    industryRawCapped() {
      if (!this.bUnlocked()) return 0;
      return this.cap(this.n(this.bYears) * 2, 20);
    },
    industryCapped() {
      return Number(this.industryRawCapped()).toFixed(2);
    },

    rawCountedNumber() {
      const a = this.teachingTotalRawCapped();
      const b = this.industryRawCapped();
      return Math.max(a, b);
    },
    deductionRate() {
      return this.isPartTime ? 0.5 : 1;
    },
    finalCapped() {
      const adjusted = this.rawCountedNumber() * this.deductionRate();
      return this.cap(adjusted, 40).toFixed(2);
    },

    countedTrackLabel() {
      const a = this.teachingTotalRawCapped();
      const b = this.industryRawCapped();
      if (a === 0 && b === 0) return 'None yet';
      return (a >= b) ? 'A. Teaching Experience' : 'B. Industry/Admin Experience';
    },

    scoringModeLabel() {
      return this.isPartTime ? 'Part-time (50%)' : 'Full-time (100%)';
    },

    emitScore(points) {
      document.dispatchEvent(new CustomEvent('section-score', {
        detail: { section: '4', points: Number(points || 0) },
      }));
    },
  }
}
</script>

