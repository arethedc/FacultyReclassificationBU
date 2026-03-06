{{-- resources/views/reclassification/section3.blade.php --}}
<div class="flex flex-col gap-1 mb-4">
    <h2 class="text-2xl font-semibold text-gray-800">Reclassification - Section III</h2>
    <p class="text-sm text-gray-500">Research Competence & Productivity (Max 70 pts / 17.5%)</p>
</div>
<form method="POST" action="{{ route('reclassification.section.save', 3) }}" enctype="multipart/form-data" data-validate-evidence>
@csrf

<div x-data="sectionThree(@js($sectionData ?? []), @js($globalEvidence ?? []))" x-init="init()" class="py-12 bg-bu-muted min-h-screen">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    {{-- =======================
    STICKY HEADER (Score + Criteria Met + Caps)
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
                Section III Score Summary
              </h3>

              <template x-if="criteriaMet() >= 1">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
                  Minimum criteria met (1/1)
                </span>
              </template>
              <template x-if="criteriaMet() < 1">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                  Need at least 1 criterion
                </span>
              </template>

              <template x-if="Number(rawTotal()) <= 70">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
                  Within limit
                </span>
              </template>
              <template x-if="Number(rawTotal()) > 70">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
                  Over limit
                </span>
              </template>
            </div>

            <p class="text-xs text-gray-600 mt-1">
              Minimum required:
              <span class="font-semibold text-gray-800" x-text="criteriaMet() >= 1 ? '1/1' : '0/1'"></span>
              <span class="mx-2 text-gray-300">•</span>
              Criteria with entries: <span class="font-semibold text-gray-800" x-text="criteriaMet()"></span>
              <span class="text-gray-400">/ 9</span>
              <span class="mx-2 text-gray-300">•</span>
              Raw: <span class="font-semibold text-gray-800" x-text="rawTotal()"></span>
              <span class="text-gray-400">/ 70</span>
              <span class="mx-2 text-gray-300">•</span>
              Counted (capped): <span class="font-semibold text-gray-800" x-text="cappedTotal()"></span>
            </p>
          </div>

          <button
            type="button"
            @click="userOverride = true; open = !open"
            class="px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50"
          >
            <span x-text="open ? 'Hide details' : 'Show details'"></span>
          </button>
        </div>

        <div x-show="open" x-collapse class="px-5 pb-4">
          <p class="text-xs text-gray-500">
            Points are system-suggested from your selections. Evidence is uploaded once for the section and referenced per row.
            Subject to validation.
          </p>

          <div class="mt-3 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Total (No Previous)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="subtotal()"></span>
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Book: <span class="font-medium text-gray-700" x-text="sum1()"></span>
                <span class="mx-1 text-gray-300">•</span>
                Workbooks: <span class="font-medium text-gray-700" x-text="sum2()"></span>
                <span class="mx-1 text-gray-300">•</span>
                Articles: <span class="font-medium text-gray-700" x-text="sum4()"></span>
              </p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Previous Reclass (1/3)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="prevThird()"></span>
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Input: <span class="font-medium text-gray-700" x-text="Number(previous || 0).toFixed(2)"></span>
              </p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Final (Raw)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="rawTotal()"></span>
                <span class="text-sm font-medium text-gray-400">/ 70</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Counted: <span class="font-medium text-gray-700" x-text="cappedTotal()"></span>
              </p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Evidence Library</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="evidenceCount()"></span>
              </p>
              <p class="mt-1 text-xs text-gray-500">files available</p>
            </div>
          </div>

          <template x-if="Number(rawTotal()) > 70">
            <p class="mt-3 text-xs text-red-600">
              Your raw total exceeds the 70-point limit. Excess points will not be counted.
            </p>
          </template>
        </div>
      </div>
    </div>

    {{-- ===============================
    SECTION INTRO
    =============================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="p-6">
        <p class="text-sm text-gray-600">
          Publications and Creative Works within the last three years (supported by evidences) + 1/3 of the points earned in the last reclassification.
          At least one (1) criterion below must be met.
        </p>
      </div>
    </div>

    {{-- ===============================
    PREVIOUS RECLASSIFICATION
    =============================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="p-6">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
          <label class="block text-xs text-gray-500">
            Points from Previous Reclassification (if applicable)
          </label>
          <input type="hidden" name="section3[previous_points_id]" :value="previous_id || ''">
          <input
            x-model.number="previous"
            name="section3[previous_points]"
            type="number" step="0.01"
            class="mt-1 w-56 max-w-full rounded border-gray-300 text-sm"
            placeholder="0.00"
          >
          <p class="text-xs text-gray-500 mt-1">
            Counted: <span class="font-medium text-gray-700" x-text="Number(prevThird()).toFixed(2)"></span>
          </p>
          <p class="text-xs text-gray-500 mt-1">
            System applies 1/3 of this value. Subject to validation.
          </p>
          <template x-if="(previous_comments || []).length">
            <div class="mt-2" x-data="{ row: { comments: previous_comments } }">
              @include('reclassification.partials.entry-review-comments-inline')
            </div>
          </template>
        </div>
      </div>
    </div>

    {{-- =====================================================
    1. BOOK AUTHORSHIP
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">1. Authorship / Co-authorship of Book</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c1.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c1.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Authorship</th>
              <th class="p-2 text-left">Publication</th>
              <th class="p-2 text-left">Publisher Type</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>

                        <template x-for="(row,i) in c1" :key="i">
                          <tbody class="divide-y">

              <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                <td class="p-2">
                  <input type="hidden" :name="`section3[c1][${i}][id]`" :value="row.id || ''">
                  <input type="hidden" :name="`section3[c1][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                  <input x-model="row.title"
                         :name="`section3[c1][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter book title">
                </td>

                <td class="p-2">
                  <select x-model="row.authorship"
                          :name="`section3[c1][${i}][authorship]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select authorship</option>
                    <option value="sole">Sole authorship</option>
                    <option value="co">Co-authorship</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.edition"
                          :name="`section3[c1][${i}][edition]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select edition</option>
                    <option value="new">New book</option>
                    <option value="revised">Revised edition</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.publisher"
                          :name="`section3[c1][${i}][publisher]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select publisher type</option>
                    <option value="registered">Registered publisher</option>
                    <option value="printed_approved">Printed by author + approved by textbook board</option>
                  </select>
                </td>

                <td class="p-2 text-gray-700">
                  <span x-text="Number(rowPoints('c1', i)).toFixed(2)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                  <div class="mt-1 text-xs">
                    <span x-show="rowCounted('c1', i)" class="text-green-700">Counted</span>
                    <span x-show="rowDuplicate('c1', i)" class="text-amber-600">Not counted (duplicate)</span>
                  </div>
                </td>

                <td class="p-2">
                  <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                          :class="rowEvidenceCount('c1', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                      <span x-text="rowEvidenceCount('c1', i) > 0 ? `Evidence attached (${rowEvidenceCount('c1', i)})` : 'No evidence'"></span>
                    </span>

                    <button type="button"
                            @click="openSelectEvidence('c1', i)"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                      <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                    </button>

                    <button type="button"
                            @click="openShowEvidence('c1', i)"
                            :disabled="rowEvidenceCount('c1', i) === 0"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                            :class="rowEvidenceCount('c1', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                      Show Evidence
                      <span class="text-[11px]" x-text="`(${rowEvidenceCount('c1', i)})`"></span>
                    </button>
                  </div>

                        <select x-model="row.evidence"
                                multiple
                                :name="`section3[c1][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                            <option value="" disabled>Select evidence</option>
                            <template x-for="opt in evidenceOptions()" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                            <input type="hidden" :name="`section3[c1][${i}][evidence][]`" :value="token">
</template>
                </td>

                <td class="p-2 text-right">
                  <div class="inline-flex items-center justify-end gap-2">
                    <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                  <button type="button" @click="requestRowToggleRemove(c1, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
                  </div>
                </td>
              </tr>
              <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
              <td colspan="99" class="p-2">
                <div class="w-full min-w-0">
                  @include('reclassification.partials.entry-review-comments-inline')
                </div>
              </td>
            </tr>
                          </tbody>
</template>

          </table>
        </div>

        <button type="button"
                @click="c1.push({ title:'', authorship:'', edition:'', publisher:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add book
        </button>
      </div>
    </div>

    {{-- =====================================================
    2. WORKBOOKS / MANUALS
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">2. Workbooks / Manuals / Instructional Materials</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c2.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c2.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Authorship</th>
              <th class="p-2 text-left">Edition</th>
              <th class="p-2 text-left">Publisher Type</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>

                        <template x-for="(row,i) in c2" :key="i">
                          <tbody class="divide-y">

              <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                <td class="p-2">
                  <input type="hidden" :name="`section3[c2][${i}][id]`" :value="row.id || ''">
                  <input type="hidden" :name="`section3[c2][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                  <input x-model="row.title"
                         :name="`section3[c2][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter material title">
                </td>

                <td class="p-2">
                  <select x-model="row.authorship"
                          :name="`section3[c2][${i}][authorship]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select authorship</option>
                    <option value="sole">Sole authorship</option>
                    <option value="co">Co-authorship</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.edition"
                          :name="`section3[c2][${i}][edition]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select edition</option>
                    <option value="new">New edition</option>
                    <option value="revised">Revised edition</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.publisher"
                          :name="`section3[c2][${i}][publisher]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select publisher type</option>
                    <option value="registered">Registered publisher</option>
                    <option value="printed_approved">Printed by author + approved by textbook board</option>
                  </select>
                </td>

                <td class="p-2 text-gray-700">
                  <span x-text="Number(rowPoints('c2', i)).toFixed(2)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                  <div class="mt-1 text-xs">
                    <span x-show="rowCounted('c2', i)" class="text-green-700">Counted</span>
                    <span x-show="rowDuplicate('c2', i)" class="text-amber-600">Not counted (duplicate)</span>
                  </div>
                </td>

                <td class="p-2">
                  <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                          :class="rowEvidenceCount('c2', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                      <span x-text="rowEvidenceCount('c2', i) > 0 ? `Evidence attached (${rowEvidenceCount('c2', i)})` : 'No evidence'"></span>
                    </span>

                    <button type="button"
                            @click="openSelectEvidence('c2', i)"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                      <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                    </button>

                    <button type="button"
                            @click="openShowEvidence('c2', i)"
                            :disabled="rowEvidenceCount('c2', i) === 0"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                            :class="rowEvidenceCount('c2', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                      Show Evidence
                      <span class="text-[11px]" x-text="`(${rowEvidenceCount('c2', i)})`"></span>
                    </button>
                  </div>

                        <select x-model="row.evidence"
                                multiple
                                :name="`section3[c2][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                            <option value="" disabled>Select evidence</option>
                            <template x-for="opt in evidenceOptions()" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                            <input type="hidden" :name="`section3[c2][${i}][evidence][]`" :value="token">
</template>
                </td>

                <td class="p-2 text-right">
                  <div class="inline-flex items-center justify-end gap-2">
                    <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                  <button type="button" @click="requestRowToggleRemove(c2, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
                  </div>
                </td>
              </tr>
              <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
              <td colspan="99" class="p-2">
                <div class="w-full min-w-0">
                  @include('reclassification.partials.entry-review-comments-inline')
                </div>
              </td>
            </tr>
                          </tbody>
</template>

          </table>
        </div>

        <button type="button"
                @click="c2.push({ title:'', authorship:'', edition:'', publisher:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add material
        </button>
      </div>
    </div>

    {{-- =====================================================
    3. COMPILATIONS / ANTHOLOGIES
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">3. Compilations / Anthologies</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c3.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c3.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Authorship</th>
              <th class="p-2 text-left">Edition</th>
              <th class="p-2 text-left">Publisher Type</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>

                        <template x-for="(row,i) in c3" :key="i">
                          <tbody class="divide-y">

              <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                <td class="p-2">
                  <input type="hidden" :name="`section3[c3][${i}][id]`" :value="row.id || ''">
                  <input type="hidden" :name="`section3[c3][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                  <input x-model="row.title"
                         :name="`section3[c3][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter compilation title">
                </td>

                <td class="p-2">
                  <select x-model="row.authorship"
                          :name="`section3[c3][${i}][authorship]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select authorship</option>
                    <option value="sole">Sole authorship</option>
                    <option value="co">Co-authorship</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.edition"
                          :name="`section3[c3][${i}][edition]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select edition</option>
                    <option value="new">New edition</option>
                    <option value="revised">Revised edition</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.publisher"
                          :name="`section3[c3][${i}][publisher]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select publisher type</option>
                    <option value="registered">Registered publisher</option>
                    <option value="printed_approved">Printed by author + approved by textbook board</option>
                  </select>
                </td>

                <td class="p-2 text-gray-700">
                  <span x-text="Number(rowPoints('c3', i)).toFixed(2)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                  <div class="mt-1 text-xs">
                    <span x-show="rowCounted('c3', i)" class="text-green-700">Counted</span>
                    <span x-show="rowDuplicate('c3', i)" class="text-amber-600">Not counted (duplicate)</span>
                  </div>
                </td>

                <td class="p-2">
                  <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                          :class="rowEvidenceCount('c3', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                      <span x-text="rowEvidenceCount('c3', i) > 0 ? `Evidence attached (${rowEvidenceCount('c3', i)})` : 'No evidence'"></span>
                    </span>

                    <button type="button"
                            @click="openSelectEvidence('c3', i)"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                      <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                    </button>

                    <button type="button"
                            @click="openShowEvidence('c3', i)"
                            :disabled="rowEvidenceCount('c3', i) === 0"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                            :class="rowEvidenceCount('c3', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                      Show Evidence
                      <span class="text-[11px]" x-text="`(${rowEvidenceCount('c3', i)})`"></span>
                    </button>
                  </div>

                        <select x-model="row.evidence"
                                multiple
                                :name="`section3[c3][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                            <option value="" disabled>Select evidence</option>
                            <template x-for="opt in evidenceOptions()" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                            <input type="hidden" :name="`section3[c3][${i}][evidence][]`" :value="token">
</template>
                </td>

                <td class="p-2 text-right">
                  <div class="inline-flex items-center justify-end gap-2">
                    <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                  <button type="button" @click="requestRowToggleRemove(c3, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
                  </div>
                </td>
              </tr>
              <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
              <td colspan="99" class="p-2">
                <div class="w-full min-w-0">
                  @include('reclassification.partials.entry-review-comments-inline')
                </div>
              </td>
            </tr>
                          </tbody>
</template>

          </table>
        </div>

        <button type="button"
                @click="c3.push({ title:'', authorship:'', edition:'', publisher:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add compilation
        </button>
      </div>
    </div>

    {{-- =====================================================
    4. ARTICLES (incl. Other publications)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">4. Articles Published</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c4.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c4.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Type</th>
              <th class="p-2 text-left">Authorship</th>
              <th class="p-2 text-left">Scope</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>

                        <template x-for="(row,i) in c4" :key="i">
                          <tbody class="divide-y">

              <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                <td class="p-2">
                  <input type="hidden" :name="`section3[c4][${i}][id]`" :value="row.id || ''">
                  <input type="hidden" :name="`section3[c4][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                  <input x-model="row.title"
                         :name="`section3[c4][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter article title">
                </td>
                <td class="p-2">
                  <select x-model="row.kind"
                          @change="row.scope = ''"
                          :name="`section3[c4][${i}][kind]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select type</option>
                    <option value="refereed">Refereed</option>
                    <option value="nonrefereed">Non-refereed</option>
                    <option value="otherpub">Other publications (columns / contributions)</option>
                  </select>
                </td>
                <td class="p-2">
                  <select x-model="row.authorship"
                          :name="`section3[c4][${i}][authorship]`"
                          class="rounded border-gray-300 w-full"
                          :disabled="row.kind === 'otherpub'">
                    <option value="" disabled selected>Select authorship</option>
                    <option value="sole">Sole Author</option>
                    <option value="co">Co-Author</option>
                  </select>
                </td>
                <td class="p-2">
                  <select x-model="row.scope"
                          :name="`section3[c4][${i}][scope]`"
                          class="rounded border-gray-300 w-full">
                    <template x-if="row.kind !== 'otherpub'">
                      <optgroup label="Journal / Magazine Scope">
                          <option value="">Select scope</option>
                        <option value="international">International</option>
                        <option value="national">National</option>
                        <option value="university">University</option>
                      </optgroup>
                    </template>
                    <template x-if="row.kind === 'otherpub'">
                      <optgroup label="Other publications">
                          <option value="">Select scope</option>
                        <option value="national_periodicals">National periodicals</option>
                        <option value="local_periodicals">Local periodicals</option>
                        <option value="university_newsletters">University/department newsletters</option>
                      </optgroup>
                    </template>
                  </select>
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="Number(rowPoints('c4', i)).toFixed(2)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                  <div class="mt-1 text-xs">
                    <span x-show="rowCounted('c4', i)" class="text-green-700">Counted</span>
                    <span x-show="rowDuplicate('c4', i)" class="text-amber-600">Not counted (duplicate)</span>
                  </div>
                </td>
                <td class="p-2">
                  <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                          :class="rowEvidenceCount('c4', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                      <span x-text="rowEvidenceCount('c4', i) > 0 ? `Evidence attached (${rowEvidenceCount('c4', i)})` : 'No evidence'"></span>
                    </span>

                    <button type="button"
                            @click="openSelectEvidence('c4', i)"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                      <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                    </button>

                    <button type="button"
                            @click="openShowEvidence('c4', i)"
                            :disabled="rowEvidenceCount('c4', i) === 0"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                            :class="rowEvidenceCount('c4', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                      Show Evidence
                      <span class="text-[11px]" x-text="`(${rowEvidenceCount('c4', i)})`"></span>
                    </button>
                  </div>

                        <select x-model="row.evidence"
                                multiple
                                :name="`section3[c4][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                            <option value="" disabled>Select evidence</option>
                            <template x-for="opt in evidenceOptions()" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                            <input type="hidden" :name="`section3[c4][${i}][evidence][]`" :value="token">
</template>
                </td>
                <td class="p-2 text-right">
                  <div class="inline-flex items-center justify-end gap-2">
                    <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                  <button type="button" @click="requestRowToggleRemove(c4, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
                  </div>
                </td>
              </tr>
              <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
              <td colspan="99" class="p-2">
                <div class="w-full min-w-0">
                  @include('reclassification.partials.entry-review-comments-inline')
                </div>
              </td>
            </tr>
                          </tbody>
</template>

          </table>
        </div>

        <button type="button"
                @click="c4.push({ title:'', kind:'', authorship:'', scope:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add article / publication
        </button>
      </div>
    </div>

    {{-- =====================================================
    5. CONFERENCE PAPERS
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">5. Conference Paper Presentations</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c5.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c5.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Level</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>

                        <template x-for="(row,i) in c5" :key="i">
                          <tbody class="divide-y">

              <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                <td class="p-2">
                  <input type="hidden" :name="`section3[c5][${i}][id]`" :value="row.id || ''">
                  <input type="hidden" :name="`section3[c5][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                  <input x-model="row.title"
                         :name="`section3[c5][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter paper title">
                </td>
                <td class="p-2">
                  <select x-model="row.level"
                          :name="`section3[c5][${i}][level]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select level</option>
                    <option value="international">International</option>
                    <option value="national">National</option>
                    <option value="regional">Regional / Provincial</option>
                    <option value="institutional">Institutional / Local</option>
                  </select>
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="Number(rowPoints('c5', i)).toFixed(2)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                  <div class="mt-1 text-xs">
                    <span x-show="rowCounted('c5', i)" class="text-green-700">Counted</span>
                    <span x-show="rowDuplicate('c5', i)" class="text-amber-600">Not counted (duplicate)</span>
                  </div>
                </td>
                <td class="p-2">
                  <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                          :class="rowEvidenceCount('c5', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                      <span x-text="rowEvidenceCount('c5', i) > 0 ? `Evidence attached (${rowEvidenceCount('c5', i)})` : 'No evidence'"></span>
                    </span>

                    <button type="button"
                            @click="openSelectEvidence('c5', i)"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                      <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                    </button>

                    <button type="button"
                            @click="openShowEvidence('c5', i)"
                            :disabled="rowEvidenceCount('c5', i) === 0"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                            :class="rowEvidenceCount('c5', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                      Show Evidence
                      <span class="text-[11px]" x-text="`(${rowEvidenceCount('c5', i)})`"></span>
                    </button>
                  </div>

                        <select x-model="row.evidence"
                                multiple
                                :name="`section3[c5][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                            <option value="" disabled>Select evidence</option>
                            <template x-for="opt in evidenceOptions()" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                            <input type="hidden" :name="`section3[c5][${i}][evidence][]`" :value="token">
</template>
                </td>
                <td class="p-2 text-right">
                  <div class="inline-flex items-center justify-end gap-2">
                    <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                  <button type="button" @click="requestRowToggleRemove(c5, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
                  </div>
                </td>
              </tr>
              <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
              <td colspan="99" class="p-2">
                <div class="w-full min-w-0">
                  @include('reclassification.partials.entry-review-comments-inline')
                </div>
              </td>
            </tr>
                          </tbody>
</template>

          </table>
        </div>

        <button type="button"
                @click="c5.push({ title:'', level:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add conference paper
        </button>
      </div>
    </div>

    {{-- =====================================================
    6. COMPLETED RESEARCH
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          6. Completed Research (Not part of graduate degree requirement)
        </h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c6.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c6.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Role</th>
              <th class="p-2 text-left">Level</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>

                        <template x-for="(row,i) in c6" :key="i">
                          <tbody class="divide-y">

              <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                <td class="p-2">
                  <input type="hidden" :name="`section3[c6][${i}][id]`" :value="row.id || ''">
                  <input type="hidden" :name="`section3[c6][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                  <input x-model="row.title"
                         :name="`section3[c6][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter research title">
                </td>
                <td class="p-2">
                  <select x-model="row.role"
                          :name="`section3[c6][${i}][role]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select role</option>
                    <option value="principal">Principal proponent</option>
                    <option value="team">Team member</option>
                  </select>
                </td>
                <td class="p-2">
                  <select x-model="row.level"
                          :name="`section3[c6][${i}][level]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select level</option>
                    <option value="international">International</option>
                    <option value="national">National</option>
                    <option value="regional">Regional / Provincial</option>
                    <option value="institutional">Institutional / Local</option>
                  </select>
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="Number(rowPoints('c6', i)).toFixed(2)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                  <div class="mt-1 text-xs">
                    <span x-show="rowCounted('c6', i)" class="text-green-700">Counted</span>
                    <span x-show="rowDuplicate('c6', i)" class="text-amber-600">Not counted (duplicate)</span>
                  </div>
                </td>
                <td class="p-2">
                  <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                          :class="rowEvidenceCount('c6', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                      <span x-text="rowEvidenceCount('c6', i) > 0 ? `Evidence attached (${rowEvidenceCount('c6', i)})` : 'No evidence'"></span>
                    </span>

                    <button type="button"
                            @click="openSelectEvidence('c6', i)"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                      <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                    </button>

                    <button type="button"
                            @click="openShowEvidence('c6', i)"
                            :disabled="rowEvidenceCount('c6', i) === 0"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                            :class="rowEvidenceCount('c6', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                      Show Evidence
                      <span class="text-[11px]" x-text="`(${rowEvidenceCount('c6', i)})`"></span>
                    </button>
                  </div>

                        <select x-model="row.evidence"
                                multiple
                                :name="`section3[c6][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                            <option value="" disabled>Select evidence</option>
                            <template x-for="opt in evidenceOptions()" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                            <input type="hidden" :name="`section3[c6][${i}][evidence][]`" :value="token">
</template>
                </td>
                <td class="p-2 text-right">
                  <div class="inline-flex items-center justify-end gap-2">
                    <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                  <button type="button" @click="requestRowToggleRemove(c6, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
                  </div>
                </td>
              </tr>
              <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
              <td colspan="99" class="p-2">
                <div class="w-full min-w-0">
                  @include('reclassification.partials.entry-review-comments-inline')
                </div>
              </td>
            </tr>
                          </tbody>
</template>

          </table>
        </div>

        <button type="button"
                @click="c6.push({ title:'', role:'', level:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add research
        </button>
      </div>
    </div>

    {{-- =====================================================
    7. RESEARCH / PROJECT PROPOSALS APPROVED
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          7. Research / Project Proposals Approved (Reviewed & approved by Research Center)
        </h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c7.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c7.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Role</th>
              <th class="p-2 text-left">Level</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>

                        <template x-for="(row,i) in c7" :key="i">
                          <tbody class="divide-y">

              <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                <td class="p-2">
                  <input type="hidden" :name="`section3[c7][${i}][id]`" :value="row.id || ''">
                  <input type="hidden" :name="`section3[c7][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                  <input x-model="row.title"
                         :name="`section3[c7][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter proposal title">
                </td>
                <td class="p-2">
                  <select x-model="row.role"
                          :name="`section3[c7][${i}][role]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select role</option>
                    <option value="principal">Principal proponent</option>
                    <option value="team">Team member</option>
                  </select>
                </td>
                <td class="p-2">
                  <select x-model="row.level"
                          :name="`section3[c7][${i}][level]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select level</option>
                    <option value="international">International</option>
                    <option value="national">National</option>
                    <option value="regional">Regional / Provincial</option>
                    <option value="institutional">Institutional / Local</option>
                  </select>
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="Number(rowPoints('c7', i)).toFixed(2)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                  <div class="mt-1 text-xs">
                    <span x-show="rowCounted('c7', i)" class="text-green-700">Counted</span>
                    <span x-show="rowDuplicate('c7', i)" class="text-amber-600">Not counted (duplicate)</span>
                  </div>
                </td>
                <td class="p-2">
                  <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                          :class="rowEvidenceCount('c7', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                      <span x-text="rowEvidenceCount('c7', i) > 0 ? `Evidence attached (${rowEvidenceCount('c7', i)})` : 'No evidence'"></span>
                    </span>

                    <button type="button"
                            @click="openSelectEvidence('c7', i)"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                      <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                    </button>

                    <button type="button"
                            @click="openShowEvidence('c7', i)"
                            :disabled="rowEvidenceCount('c7', i) === 0"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                            :class="rowEvidenceCount('c7', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                      Show Evidence
                      <span class="text-[11px]" x-text="`(${rowEvidenceCount('c7', i)})`"></span>
                    </button>
                  </div>

                        <select x-model="row.evidence"
                                multiple
                                :name="`section3[c7][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                            <option value="" disabled>Select evidence</option>
                            <template x-for="opt in evidenceOptions()" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                            <input type="hidden" :name="`section3[c7][${i}][evidence][]`" :value="token">
</template>
                </td>
                <td class="p-2 text-right">
                  <div class="inline-flex items-center justify-end gap-2">
                    <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                  <button type="button" @click="requestRowToggleRemove(c7, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
                  </div>
                </td>
              </tr>
              <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
              <td colspan="99" class="p-2">
                <div class="w-full min-w-0">
                  @include('reclassification.partials.entry-review-comments-inline')
                </div>
              </td>
            </tr>
                          </tbody>
</template>

          </table>
        </div>

        <button type="button"
                @click="c7.push({ title:'', role:'', level:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add proposal
        </button>
      </div>
    </div>

    {{-- =====================================================
    8. CASE STUDIES / ACTION RESEARCH (fixed 5)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          8. Authorship of Case Studies / Classroom-based Action Research
        </h3>
      </div>

      <div class="p-6 space-y-3">
        <p class="text-sm text-gray-600">Fixed: 5 points per output</p>

        <p x-show="c8.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c8.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>

                        <template x-for="(row,i) in c8" :key="i">
                          <tbody class="divide-y">

              <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                <td class="p-2">
                  <input type="hidden" :name="`section3[c8][${i}][id]`" :value="row.id || ''">
                  <input type="hidden" :name="`section3[c8][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                  <input x-model="row.title"
                         :name="`section3[c8][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter case study / action research title">
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="Number(rowPoints('c8', i)).toFixed(2)"></span>
                  <span class="text-xs text-gray-400">(Fixed)</span>
                  <div class="mt-1 text-xs">
                    <span x-show="rowCounted('c8', i)" class="text-green-700">Counted</span>
                    <span x-show="rowDuplicate('c8', i)" class="text-amber-600">Not counted (duplicate)</span>
                  </div>
                </td>
                <td class="p-2">
                  <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                          :class="rowEvidenceCount('c8', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                      <span x-text="rowEvidenceCount('c8', i) > 0 ? `Evidence attached (${rowEvidenceCount('c8', i)})` : 'No evidence'"></span>
                    </span>

                    <button type="button"
                            @click="openSelectEvidence('c8', i)"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                      <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                    </button>

                    <button type="button"
                            @click="openShowEvidence('c8', i)"
                            :disabled="rowEvidenceCount('c8', i) === 0"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                            :class="rowEvidenceCount('c8', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                      Show Evidence
                      <span class="text-[11px]" x-text="`(${rowEvidenceCount('c8', i)})`"></span>
                    </button>
                  </div>

                        <select x-model="row.evidence"
                                multiple
                                :name="`section3[c8][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                            <option value="" disabled>Select evidence</option>
                            <template x-for="opt in evidenceOptions()" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                            <input type="hidden" :name="`section3[c8][${i}][evidence][]`" :value="token">
</template>
                </td>
                <td class="p-2 text-right">
                  <div class="inline-flex items-center justify-end gap-2">
                    <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                  <button type="button" @click="requestRowToggleRemove(c8, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
                  </div>
                </td>
              </tr>
              <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
              <td colspan="99" class="p-2">
                <div class="w-full min-w-0">
                  @include('reclassification.partials.entry-review-comments-inline')
                </div>
              </td>
            </tr>
                          </tbody>
</template>

          </table>
        </div>

        <button type="button"
                @click="c8.push({ title:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add case study / action research
        </button>
      </div>
    </div>

    {{-- =====================================================
    9. EDITORIAL SERVICES
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">9. Editorial Services</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c9.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c9.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Service Type</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>

                        <template x-for="(row,i) in c9" :key="i">
                          <tbody class="divide-y">

              <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                <td class="p-2">
                  <input type="hidden" :name="`section3[c9][${i}][id]`" :value="row.id || ''">
                  <input type="hidden" :name="`section3[c9][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                  <select x-model="row.service"
                          :name="`section3[c9][${i}][service]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select service type</option>
                    <option value="chief">Editor-in-chief / executive / associate / managing editor (Intl/Natl)</option>
                    <option value="editor">Editor of org/university-based publications</option>
                    <option value="consultant">Editorial consultant / technical adviser</option>
                  </select>
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="Number(rowPoints('c9', i)).toFixed(2)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                  <div class="mt-1 text-xs">
                    <span x-show="rowCounted('c9', i)" class="text-green-700">Counted</span>
                    <span x-show="rowDuplicate('c9', i)" class="text-amber-600">Not counted (duplicate)</span>
                  </div>
                </td>
                <td class="p-2">
                  <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                          :class="rowEvidenceCount('c9', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                      <span x-text="rowEvidenceCount('c9', i) > 0 ? `Evidence attached (${rowEvidenceCount('c9', i)})` : 'No evidence'"></span>
                    </span>

                    <button type="button"
                            @click="openSelectEvidence('c9', i)"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                      <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                    </button>

                    <button type="button"
                            @click="openShowEvidence('c9', i)"
                            :disabled="rowEvidenceCount('c9', i) === 0"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                            :class="rowEvidenceCount('c9', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                      Show Evidence
                      <span class="text-[11px]" x-text="`(${rowEvidenceCount('c9', i)})`"></span>
                    </button>
                  </div>

                        <select x-model="row.evidence"
                                multiple
                                :name="`section3[c9][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                            <option value="" disabled>Select evidence</option>
                            <template x-for="opt in evidenceOptions()" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                            <input type="hidden" :name="`section3[c9][${i}][evidence][]`" :value="token">
</template>
                </td>
                <td class="p-2 text-right">
                  <div class="inline-flex items-center justify-end gap-2">
                    <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                  <button type="button" @click="requestRowToggleRemove(c9, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
                  </div>
                </td>
              </tr>
              <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
              <td colspan="99" class="p-2">
                <div class="w-full min-w-0">
                  @include('reclassification.partials.entry-review-comments-inline')
                </div>
              </td>
            </tr>
                          </tbody>
</template>

          </table>
        </div>

        <button type="button"
                @click="c9.push({ service:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add editorial service
        </button>
      </div>
    </div>

    <div class="hidden" x-effect="emitScore(cappedTotal())"></div>

    {{-- ACTIONS --}}
    <div class="flex justify-end gap-4"></div>

    {{-- REMOVE CONFIRMATION MODAL --}}
<div x-cloak x-show="removeConfirmOpen" data-return-lock-ignore class="fixed inset-0 z-50 flex items-center justify-center">
  <div class="absolute inset-0 bg-black/40" @click="cancelRowToggleRemove()"></div>
  <div class="relative mx-4 w-full max-w-md rounded-2xl border bg-white p-6 shadow-xl">
    <h3 class="text-base font-semibold text-gray-900">Mark this entry as removed?</h3>
    <p class="mt-2 text-sm text-gray-600">
      This entry will stay visible in gray and be excluded from scoring. You can restore it later.
    </p>
    <div class="mt-5 flex items-center justify-end gap-2">
      <button type="button"
              @click="cancelRowToggleRemove()"
              class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
        Cancel
      </button>
      <button type="button"
              @click="confirmRowToggleRemove()"
              class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-100">
        Remove Entry
      </button>
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
              Attach evidence files from your upload library. You can select multiple files per row.
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
                          @click="openEvidenceUploader(currentRow.key, currentRow.index)"
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
                  <p>No evidence attached to this row yet.</p>
                  <button type="button"
                          @click="openSelectEvidence(currentRow.key, currentRow.index)"
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
                                    @click="openSelectEvidence(currentRow.key, currentRow.index)"
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
            Tip: You can attach multiple evidence files per row.
          </p>
          <div class="flex items-center gap-2">
            <button type="button" @click="closeEvidenceModal()"
                    class="px-4 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">
              Close
            </button>
            <button type="button"
                    x-show="evidenceModalMode === 'select' && evidencePool().length > 0"
                    @click="openEvidenceUploader(currentRow.key, currentRow.index)"
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
</div>

<script>
function sectionThree(initial = {}, globalEvidence = []) {
  return {
    softRemoveMode: @json(($application->status ?? '') === 'returned_to_faculty'),
    globalEvidence: globalEvidence || [],
    evidenceModalOpen: false,
    evidenceModalMode: 'select',
    evidenceSelection: [],
    currentRow: { key: null, index: null },
    lastFocusEl: null,
    previewOpen: false,
    previewItem: null,
    pendingOpenSelectAfterUpload: false,
    toast: { show: false, message: '', type: 'success' },
    toastTimer: null,
    removeConfirmOpen: false,
    removePendingRows: null,
    removePendingIndex: null,

    c1: initial.c1 || [],
    c2: initial.c2 || [],
    c3: initial.c3 || [],
    c4: initial.c4 || [],
    c5: initial.c5 || [],
    c6: initial.c6 || [],
    c7: initial.c7 || [],
    c8: initial.c8 || [],
    c9: initial.c9 || [],
    previous: Number(initial.previous_points || 0),
    previous_id: initial.previous_points_id || '',
    previous_comments: Array.isArray(initial.previous_points_comments) ? initial.previous_points_comments : [],

    init() {
      const keys = ['c1','c2','c3','c4','c5','c6','c7','c8','c9'];
      const toArray = (val) => {
        if (Array.isArray(val)) return val;
        if (val === null || val === undefined || val === '') return [];
        return [String(val)];
      };
      const toComments = (val) => Array.isArray(val) ? val : [];
      this.previous_comments = toComments(this.previous_comments);

      keys.forEach((k) => {
        if (!Array.isArray(this[k])) this[k] = [];
        this[k] = this[k].map((row) => ({
          ...row,
          is_removed: this.isRemovedRow(row),
          evidence: toArray(row.evidence),
          comments: toComments(row.comments),
        }));
      });

      window.addEventListener('evidence-detached', (event) => {
        const id = event.detail?.id;
        if (!id) return;
        const token = `e:${id}`;
        const removeToken = (arr) => Array.isArray(arr) ? arr.filter((v) => String(v) !== token) : [];
        keys.forEach((k) => {
          this[k] = (this[k] || []).map((row) => ({
            ...row,
            evidence: removeToken(row.evidence || []),
          }));
        });
      });

      window.addEventListener('evidence-updated', (event) => {
        const list = event.detail?.evidence;
        if (Array.isArray(list)) {
          this.globalEvidence = list;
          if (this.pendingOpenSelectAfterUpload && this.currentRow?.key) {
            this.pendingOpenSelectAfterUpload = false;
            const row = { ...this.currentRow };
            if (this.hasLibraryEvidence()) {
              this.openSelectEvidence(row.key, row.index);
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

    rowEvidenceCount(key, index = null) {
      const list = this.getRowEvidence(key, index);
      return Array.isArray(list) ? list.length : 0;
    },

    getRowEvidence(key, index = null) {
      const rows = this[key] || [];
      const row = rows[index] || {};
      return row.evidence || [];
    },

    setRowEvidence(key, index, values) {
      const clean = Array.isArray(values) ? values.filter(Boolean) : [];
      if (!this[key]) this[key] = [];
      if (!this[key][index]) this[key][index] = { evidence: [] };
      this[key][index].evidence = clean;
    },

    openSelectEvidence(key, index = null) {
      this.lastFocusEl = document.activeElement;
      this.currentRow = { key, index };
      this.evidenceSelection = [...this.getRowEvidence(key, index)];
      this.evidenceModalMode = 'select';
      this.evidenceModalOpen = true;
      this.$nextTick(() => this.focusFirst('evidence'));
    },

    openEvidenceUploader(key = null, index = null) {
      if (key !== null) {
        this.currentRow = { key, index };
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

    openShowEvidence(key, index = null) {
      this.lastFocusEl = document.activeElement;
      this.currentRow = { key, index };
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
      this.setRowEvidence(this.currentRow.key, this.currentRow.index, this.evidenceSelection);
      this.toastMessage('Evidence attached', 'success');
      this.closeEvidenceModal();
    },

    detachEvidence(value) {
      if (!confirm('Detach evidence from this criterion? The file will remain in your uploaded files.')) return;
      const current = this.getRowEvidence(this.currentRow.key, this.currentRow.index);
      const next = current.filter((v) => v !== value);
      this.setRowEvidence(this.currentRow.key, this.currentRow.index, next);
      this.toastMessage('Evidence detached', 'success');
      if (next.length === 0) {
        this.evidenceModalMode = 'show';
      }
    },

    currentEvidenceItems() {
      return this.selectedEvidence(this.getRowEvidence(this.currentRow.key, this.currentRow.index));
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
      const focusable = ref.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
      if (focusable.length) {
        focusable[0].focus({ preventScroll: true });
      }
    },

    cycleFocus(event, which) {
      const ref = which === 'preview'
        ? this.$refs.previewModal
        : this.$refs.evidenceModal;
      if (!ref) return;
      const focusable = Array.from(ref.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
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

    isRemovedRow(row) {
      if (!row || typeof row !== 'object') return false;
      const value = row.is_removed ?? false;
      if (typeof value === 'boolean') return value;
      if (typeof value === 'number') return value === 1;
      return ['1', 'true', 'yes', 'on'].includes(String(value).trim().toLowerCase());
    },


    requestRowToggleRemove(rows, index) {
      if (!Array.isArray(rows) || index < 0 || index >= rows.length) return;
      const row = rows[index] || {};
      if (!this.softRemoveMode || this.isRemovedRow(row)) {
        this.removeOrRestoreRow(rows, index, true);
        return;
      }

      this.removePendingRows = rows;
      this.removePendingIndex = index;
      this.removeConfirmOpen = true;
    },

    cancelRowToggleRemove() {
      this.removeConfirmOpen = false;
      this.removePendingRows = null;
      this.removePendingIndex = null;
    },

    confirmRowToggleRemove() {
      if (!Array.isArray(this.removePendingRows) || this.removePendingIndex === null) {
        this.cancelRowToggleRemove();
        return;
      }
      this.removeOrRestoreRow(this.removePendingRows, this.removePendingIndex, true);
      this.cancelRowToggleRemove();
    },

    removeOrRestoreRow(rows, index, bypassConfirm = false) {
      if (!Array.isArray(rows) || index < 0 || index >= rows.length) return;
      if (!this.softRemoveMode) {
        rows.splice(index, 1);
        return;
      }
      const row = rows[index] || {};
      if (!bypassConfirm && !this.isRemovedRow(row)) {
        this.requestRowToggleRemove(rows, index);
        return;
      }
      row.is_removed = !this.isRemovedRow(row);
      if (row.is_removed) {
        row.points = 0;
        row.counted = false;
      }
    },

    rowHasValue(row) {
      if (!row || typeof row !== 'object') return false;
      return Object.entries(row).some(([key, val]) => {
        if (['id', 'comments', 'is_removed', 'points', 'counted'].includes(key)) return false;
        if (Array.isArray(val)) return val.length > 0;
        if (typeof val === 'number') return val !== 0;
        if (typeof val === 'string') return val.trim() !== '';
        if (typeof val === 'boolean') return val === true;
        return false;
      });
    },

    isBucketed(key) {
      return ['c1','c2','c3','c4','c5','c6','c7','c8','c9'].includes(key);
    },

    bucketOnceRows(rows, keyFn, pointsFn) {
      const seen = new Set();
      return (rows || []).map((row) => {
        if (this.isRemovedRow(row)) {
          return { ...row, points: 0, counted: false };
        }
        const key = String(keyFn(row) || '');
        const points = Number(pointsFn(row) || 0);
        if (!key || points <= 0) {
          return { ...row, points: 0, counted: false };
        }
        if (seen.has(key)) {
          return { ...row, points: 0, counted: false };
        }
        seen.add(key);
        return { ...row, points, counted: true };
      });
    },

    bucketedRows(key) {
      const rows = this[key] || [];
      if (!this.isBucketed(key)) {
        return rows.map((row) => ({ ...row, points: 0, counted: false }));
      }

      const keyFns = {
        c1: (r) => `${r.authorship || ''}|${r.edition || ''}|${r.publisher || ''}`,
        c2: (r) => `${r.authorship || ''}|${r.edition || ''}|${r.publisher || ''}`,
        c3: (r) => `${r.authorship || ''}|${r.edition || ''}|${r.publisher || ''}`,
        c4: (r) => `${r.kind || ''}|${r.authorship || ''}|${r.scope || ''}`,
        c5: (r) => r.level || '',
        c6: (r) => `${r.role || ''}|${r.level || ''}`,
        c7: (r) => `${r.role || ''}|${r.level || ''}`,
        c8: (r) => String(r.title || '').trim().toLowerCase(),
        c9: (r) => r.service || '',
      };

      const pointsFns = {
        c1: (r) => this.ptsBook(r),
        c2: (r) => this.ptsWorkbook(r),
        c3: (r) => this.ptsCompilation(r),
        c4: (r) => this.ptsArticle(r),
        c5: (r) => this.ptsConference(r),
        c6: (r) => this.ptsCompleted(r),
        c7: (r) => this.ptsProposal(r),
        c8: (r) => this.rowHasValue(r) ? 5 : 0,
        c9: (r) => this.ptsEditorial(r),
      };

      return this.bucketOnceRows(rows, keyFns[key], pointsFns[key]);
    },

    rowPoints(key, index) {
      const rows = this.bucketedRows(key);
      return Number(rows[index]?.points || 0);
    },

    rowCounted(key, index) {
      const rows = this.bucketedRows(key);
      return !!rows[index]?.counted;
    },

    bucketKey(key, row) {
      const keyFns = {
        c1: (r) => `${r.authorship || ''}|${r.edition || ''}|${r.publisher || ''}`,
        c2: (r) => `${r.authorship || ''}|${r.edition || ''}|${r.publisher || ''}`,
        c3: (r) => `${r.authorship || ''}|${r.edition || ''}|${r.publisher || ''}`,
        c4: (r) => `${r.kind || ''}|${r.authorship || ''}|${r.scope || ''}`,
        c5: (r) => r.level || '',
        c6: (r) => `${r.role || ''}|${r.level || ''}`,
        c7: (r) => `${r.role || ''}|${r.level || ''}`,
        c8: (r) => String(r.title || '').trim().toLowerCase(),
        c9: (r) => r.service || '',
      };
      return String(keyFns[key] ? keyFns[key](row || {}) : '');
    },

    bucketPoints(key, row) {
      if (this.isRemovedRow(row)) return 0;
      const pointsFns = {
        c1: (r) => this.ptsBook(r),
        c2: (r) => this.ptsWorkbook(r),
        c3: (r) => this.ptsCompilation(r),
        c4: (r) => this.ptsArticle(r),
        c5: (r) => this.ptsConference(r),
        c6: (r) => this.ptsCompleted(r),
        c7: (r) => this.ptsProposal(r),
        c8: (r) => this.rowHasValue(r) ? 5 : 0,
        c9: (r) => this.ptsEditorial(r),
      };
      return Number(pointsFns[key] ? pointsFns[key](row || {}) : 0);
    },

    rowDuplicate(key, index) {
      if (!this.isBucketed(key)) return false;
      const rows = this[key] || [];
      const row = rows[index];
      if (!row) return false;
      const bucket = this.bucketKey(key, row);
      if (!bucket) return false;
      const currentPoints = this.bucketPoints(key, row);
      if (currentPoints <= 0) return false;
      const computed = this.bucketedRows(key);
      const computedRow = computed[index];
      if (!computedRow) return false;
      return !computedRow.counted;
    },

    ptsBook(row) {
      const a = row.authorship;
      const ed = row.edition;
      const pub = row.publisher;
      if (!a || !ed || !pub) return 0;

      const map = {
        sole: {
          new: { registered: 20, printed_approved: 18 },
          revised: { registered: 16, printed_approved: 14 },
        },
        co: {
          new: { registered: 14, printed_approved: 12 },
          revised: { registered: 10, printed_approved: 8 },
        },
      };
      return Number(map?.[a]?.[ed]?.[pub] || 0);
    },

    ptsWorkbook(row) {
      const a = row.authorship;
      const ed = row.edition;
      const pub = row.publisher;
      if (!a || !ed || !pub) return 0;

      const map = {
        sole: {
          new: { registered: 15, printed_approved: 13 },
          revised: { registered: 11, printed_approved: 9 },
        },
        co: {
          new: { registered: 9, printed_approved: 8 },
          revised: { registered: 7, printed_approved: 6 },
        },
      };
      return Number(map?.[a]?.[ed]?.[pub] || 0);
    },

    ptsCompilation(row) {
      const a = row.authorship;
      const ed = row.edition;
      const pub = row.publisher;
      if (!a || !ed || !pub) return 0;

      const map = {
        sole: {
          new: { registered: 12, printed_approved: 11 },
          revised: { registered: 10, printed_approved: 9 },
        },
        co: {
          new: { registered: 8, printed_approved: 7 },
          revised: { registered: 6, printed_approved: 5 },
        },
      };
      return Number(map?.[a]?.[ed]?.[pub] || 0);
    },

    ptsArticle(row) {
      if (!row.kind || !row.scope) return 0;

      if (row.kind === 'otherpub') {
        const other = {
          national_periodicals: 5,
          local_periodicals: 4,
          university_newsletters: 3,
        };
        return Number(other[row.scope] || 0);
      }

      if (!row.authorship) return 0;

      const key = `${row.kind}_${row.authorship}_${row.scope}`;
      const map = {
        refereed_sole_international: 40,
        refereed_co_international: 36,
        refereed_sole_national: 38,
        refereed_co_national: 34,
        refereed_sole_university: 36,
        refereed_co_university: 32,
        nonrefereed_sole_international: 30,
        nonrefereed_co_international: 24,
        nonrefereed_sole_national: 28,
        nonrefereed_co_national: 22,
        nonrefereed_sole_university: 20,
        nonrefereed_co_university: 20,
      };
      return Number(map[key] || 0);
    },

    ptsConference(row) {
      const map = { international: 15, national: 13, regional: 11, institutional: 9 };
      return Number(map[row.level] || 0);
    },

    ptsCompleted(row) {
      const principal = { international: 20, national: 18, regional: 16, institutional: 14 };
      const team = { international: 15, national: 13, regional: 11, institutional: 9 };
      const a = row.role === 'team' ? team : principal;
      return Number(a[row.level] || 0);
    },

    ptsProposal(row) {
      const principal = { international: 15, national: 13, regional: 11, institutional: 9 };
      const team = { international: 11, national: 9, regional: 7, institutional: 5 };
      const a = row.role === 'team' ? team : principal;
      return Number(a[row.level] || 0);
    },

    ptsEditorial(row) {
      const map = { chief: 15, editor: 10, consultant: 5 };
      return Number(map[row.service] || 0);
    },

    sumKey(key) {
      return this.bucketedRows(key).reduce((t, r) => t + Number(r.points || 0), 0);
    },

    sum1() { return this.sumKey('c1').toFixed(2); },
    sum2() { return this.sumKey('c2').toFixed(2); },
    sum3() { return this.sumKey('c3').toFixed(2); },
    sum4() { return this.sumKey('c4').toFixed(2); },
    sum5() { return this.sumKey('c5').toFixed(2); },
    sum6() { return this.sumKey('c6').toFixed(2); },
    sum7() { return this.sumKey('c7').toFixed(2); },
    sum8() { return this.sumKey('c8').toFixed(2); },
    sum9() { return this.sumKey('c9').toFixed(2); },

    subtotal() {
      const total =
        Number(this.sum1()) + Number(this.sum2()) + Number(this.sum3()) +
        Number(this.sum4()) + Number(this.sum5()) + Number(this.sum6()) +
        Number(this.sum7()) + Number(this.sum8()) + Number(this.sum9());
      return total.toFixed(2);
    },

    prevThird() {
      const p = Number(this.previous || 0);
      return (p / 3).toFixed(2);
    },

    rawTotal() {
      return (Number(this.subtotal()) + Number(this.prevThird())).toFixed(2);
    },

    cappedTotal() {
      const raw = Number(this.rawTotal());
      return (raw > 70 ? 70 : raw).toFixed(2);
    },

    criteriaMet() {
      const keys = ['c1','c2','c3','c4','c5','c6','c7','c8','c9'];
      return keys.reduce((count, key) => {
        const rows = this.bucketedRows(key);
        const has = rows.some((r) => Number(r.points || 0) > 0);
        return count + (has ? 1 : 0);
      }, 0);
    },

    evidenceCount() {
      return (this.globalEvidence || []).length;
    },

    emitScore(points) {
      document.dispatchEvent(new CustomEvent('section-score', {
        detail: { section: '3', points: Number(points || 0) },
      }));
    },
  }
}
</script>

</form>

