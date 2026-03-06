{{-- resources/views/reclassification/section5.blade.php --}}
<div class="flex flex-col gap-1 mb-4">
    <h2 class="text-2xl font-semibold text-gray-800">Reclassification - Section V</h2>
    <p class="text-sm text-gray-500">Professional & Community Leadership Service (Max 30 pts / 7.5%)</p>
</div>
<form method="POST" action="{{ route('reclassification.section.save', 5) }}" enctype="multipart/form-data" data-validate-evidence>
@csrf

<div x-data="sectionFive(@js($sectionData ?? []), @js($globalEvidence ?? []))" x-init="init()" class="py-12 bg-bu-muted min-h-screen">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    {{-- =======================
    STICKY HEADER (Score + Caps)
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
                Section V Score Summary
              </h3>

              <template x-if="Number(rawTotal()) <= 30">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
                  Within limit
                </span>
              </template>
              <template x-if="Number(rawTotal()) > 30">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
                  Over limit
                </span>
              </template>
            </div>

            <p class="text-xs text-gray-600 mt-1">
              Raw: <span class="font-semibold text-gray-800" x-text="rawTotal()"></span>
              <span class="text-gray-400">/ 30</span>
              <span class="mx-2 text-gray-300">•</span>
              Counted (capped): <span class="font-semibold text-gray-800" x-text="cappedTotal()"></span>
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
            Select exact options based on the paper form to avoid miscalculation. Evidence is uploaded once and referenced per row.
          </p>

          <div class="mt-3 grid grid-cols-1 sm:grid-cols-5 gap-3">
            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">A (cap 5)</p>
              <p class="text-xl font-semibold text-gray-800"><span x-text="sumA_capped()"></span></p>
              <p class="text-xs text-gray-500 mt-1">Raw: <span x-text="sumA_raw()"></span></p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">B (cap 10)</p>
              <p class="text-xl font-semibold text-gray-800"><span x-text="sumB_capped()"></span></p>
              <p class="text-xs text-gray-500 mt-1">Raw: <span x-text="sumB_raw()"></span></p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">C (cap 15)</p>
              <p class="text-xl font-semibold text-gray-800"><span x-text="sumC_capped()"></span></p>
              <p class="text-xs text-gray-500 mt-1">Raw: <span x-text="sumC_raw()"></span></p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">D (cap 10)</p>
              <p class="text-xl font-semibold text-gray-800"><span x-text="sumD_capped()"></span></p>
              <p class="text-xs text-gray-500 mt-1">Raw: <span x-text="sumD_raw()"></span></p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Previous (1/3)</p>
              <p class="text-xl font-semibold text-gray-800"><span x-text="prevThird()"></span></p>
              <p class="text-xs text-gray-500 mt-1">Input: <span x-text="Number(previous||0).toFixed(2)"></span></p>
            </div>
          </div>

          <template x-if="Number(rawTotal()) > 30">
            <p class="mt-3 text-xs text-red-600">
              Your raw total exceeds the 30-point limit. Excess points will not be counted.
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
            Professional and community leadership service within the last three (3) years.
            Section total is capped at 30 pts. Previous reclassification points are applied as 1/3 within B, C, and D caps.
          </p>
      </div>
    </div>

    {{-- ===============================
    PREVIOUS RECLASSIFICATION
    =============================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="p-6">
        <p class="text-sm text-gray-600">
          Previous reclassification points are entered per section (B, C, and D) and are applied as one-third
          to the respective caps.
        </p>
        <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-3">
          <label class="block text-xs text-gray-500">Previous Reclassification (Whole Section V) Points</label>
          <input type="hidden" name="section5[previous_points_id]" :value="previous_id || ''">
          <input
            x-model.number="previous"
            name="section5[previous_points]"
            type="number" step="0.01"
            class="mt-1 w-56 max-w-full rounded border-gray-300 text-sm"
            placeholder="Enter previous total points"
          >
          <p class="text-xs text-gray-500 mt-1">
            Counted: <span class="font-medium text-gray-700" x-text="prevThird()"></span>
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
    A. AWARDS AND CITATION (cap 5)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          A. Awards and Citation <span class="text-sm text-gray-500">(Cap 5 pts)</span>
        </h3>
        <p class="text-xs text-gray-500 mt-1">
          Paper options: Professional (Intl=5, Natl=4, Reg=3, Local=2, School=1) • Civic/Social same • Scholarship: Full=5, Partial=3–4, Observation/Travel=1–2
        </p>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="aRows.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="aRows.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 text-left">Award / Citation</th>
                <th class="p-2 text-left">Category</th>
                <th class="p-2 text-left">Level / Type</th>
                <th class="p-2 text-left">Pts</th>
                <th class="p-2 text-left">Evidence</th>
                <th class="p-2"></th>
              </tr>
            </thead>

                          <template x-for="(row,i) in aRows" :key="i">
                            <tbody class="divide-y">

                <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                  <td class="p-2">
                    <input type="hidden" :name="`section5[a][${i}][id]`" :value="row.id || ''">
                    <input type="hidden" :name="`section5[a][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                    <input x-model="row.title"
                           :name="`section5[a][${i}][title]`"
                           class="w-full rounded border-gray-300"
                           placeholder="e.g., Best Paper Award (include year)">
                  </td>

                  <td class="p-2">
                    <select x-model="row.kind"
                            :name="`section5[a][${i}][kind]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select category (required)</option>
                      <option value="professional">Professional</option>
                      <option value="civic">Civic / Social</option>
                      <option value="scholarship">Scholarship / Fellowship Grant</option>
                    </select>
                  </td>

                  <td class="p-2">
                    {{-- ✅ force blank placeholders so user must choose correctly --}}
                    <template x-if="row.kind !== 'scholarship'">
                      <select x-model="row.level"
                              :name="`section5[a][${i}][level]`"
                              class="rounded border-gray-300 w-full">
                        <option value="" disabled>Select level (required)</option>
                        <option value="international">International — 5 pts</option>
                        <option value="national">National — 4 pts</option>
                        <option value="regional">Regional — 3 pts</option>
                        <option value="local">Local — 2 pts</option>
                        <option value="school">School — 1 pt</option>
                      </select>
                    </template>

                    <template x-if="row.kind === 'scholarship'">
                      <select x-model="row.grant"
                              :name="`section5[a][${i}][grant]`"
                              class="rounded border-gray-300 w-full">
                        <option value="" disabled>Select grant type (required)</option>
                        <option value="full">Full Grant — 5 pts</option>
                        <option value="partial_4">Partial Grant — 4 pts</option>
                        <option value="partial_3">Partial Grant — 3 pts</option>
                        <option value="travel_2">Observation / Travel Grant — 2 pts</option>
                        <option value="travel_1">Observation / Travel Grant — 1 pt</option>
                      </select>
                    </template>
                  </td>

                  <td class="p-2 text-gray-700">
                    <span x-text="Number(rowPoints('a', i)).toFixed(2)"></span>
                    <span class="text-xs text-gray-400">(Auto)</span>
                    <div class="mt-1 text-xs">
                      <span x-show="rowCounted('a', i)" class="text-green-700">Counted</span>
                      <span x-show="rowDuplicate('a', i)" class="text-amber-600">Not counted (duplicate)</span>
                    </div>
                  </td>

                  <td class="p-2">
                    <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                      <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                            :class="rowEvidenceCount('a', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                        <span x-text="rowEvidenceCount('a', i) > 0 ? `Evidence attached (${rowEvidenceCount('a', i)})` : 'No evidence'"></span>
                      </span>

                      <button type="button"
                              @click="openSelectEvidence('a', i)"
                              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                        <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                      </button>

                      <button type="button"
                              @click="openShowEvidence('a', i)"
                              :disabled="rowEvidenceCount('a', i) === 0"
                              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                              :class="rowEvidenceCount('a', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                        Show Evidence
                        <span class="text-[11px]" x-text="`(${rowEvidenceCount('a', i)})`"></span>
                      </button>
                    </div>

                    <select x-model="row.evidence"
                            multiple
                            :name="`section5[a][${i}][evidence][]`"
                            class="sr-only"
                            tabindex="-1"
                            aria-hidden="true">
                      <option value="" disabled>Select evidence (required)</option>
                      <template x-for="opt in evidenceOptions()" :key="opt.value">
                        <option :value="opt.value" x-text="opt.label"></option>
                      </template>
                    </select>
                    <template x-for="token in (row.evidence || [])" :key="token">
                      <input type="hidden" :name="`section5[a][${i}][evidence][]`" :value="token">
                    </template>
                  </td>

                  <td class="p-2 text-right">
                    <div class="inline-flex items-center justify-end gap-2">
                      <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                      <button type="button" @click="requestRowToggleRemove(aRows, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
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
                @click="aRows.push({ title:'', kind:'', level:'', grant:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add award / citation
        </button>

        <p class="text-xs text-gray-500">Counted points for A are capped at 5.</p>
      </div>
    </div>

    {{-- =====================================================
    B. MEMBERSHIP & LEADERSHIP (cap 10)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          B. Membership & Leadership in Professional Organizations <span class="text-sm text-gray-500">(Cap 10 pts)</span>
        </h3>
        <p class="text-xs text-gray-500 mt-1">
          Paper options: Officer/Board (10/8/6/4/2) • Committee Chairman (5/4/3/2/1) • Committee Member (4/3/2/1.5/1) • Member (3/2.5/2/1/0.5)
        </p>
      </div>

        <div class="p-6 space-y-3">
          <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
            <label class="block text-xs text-gray-500">Previous Reclassification (B) Points</label>
            <input type="hidden" name="section5[b_prev_id]" :value="b_prev_id || ''">
            <input type="number"
                   min="0"
                   step="0.01"
                   x-model.number="b_prev"
                   name="section5[b_prev]"
                   class="mt-1 w-56 max-w-full rounded border-gray-300 text-sm"
                   placeholder="Enter previous B points">
            <p class="mt-1 text-[11px] text-gray-500">
              Counted: <span class="font-medium text-gray-700" x-text="prevBThird()"></span>
            </p>
            <template x-if="(b_prev_comments || []).length">
              <div class="mt-2" x-data="{ row: { comments: b_prev_comments } }">
                @include('reclassification.partials.entry-review-comments-inline')
              </div>
            </template>
          </div>
          <p x-show="bRows.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="bRows.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 text-left">Organization</th>
                <th class="p-2 text-left">Role</th>
                <th class="p-2 text-left">Level</th>
                <th class="p-2 text-left">Pts</th>
                <th class="p-2 text-left">Evidence</th>
                <th class="p-2"></th>
              </tr>
            </thead>

                          <template x-for="(row,i) in bRows" :key="i">
                            <tbody class="divide-y">

                <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                  <td class="p-2">
                    <input type="hidden" :name="`section5[b][${i}][id]`" :value="row.id || ''">
                    <input type="hidden" :name="`section5[b][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                    <input x-model="row.org"
                           :name="`section5[b][${i}][org]`"
                           class="w-full rounded border-gray-300"
                           placeholder="e.g., IEEE (include year/s)">
                  </td>

                  <td class="p-2">
                    <select x-model="row.role"
                            :name="`section5[b][${i}][role]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select role (required)</option>
                      <option value="officer">Officer / Board of Directors</option>
                      <option value="chairman">Committee Chairman</option>
                      <option value="member_committee">Committee Member</option>
                      <option value="member">Member</option>
                    </select>
                  </td>

                  <td class="p-2">
                    <select x-model="row.level"
                            :name="`section5[b][${i}][level]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select level (required)</option>
                      <option value="international">International</option>
                      <option value="national">National</option>
                      <option value="regional">Regional</option>
                      <option value="local">Local</option>
                      <option value="school">School</option>
                    </select>
                  </td>

                  <td class="p-2 text-gray-700">
                    <span x-text="Number(rowPoints('b', i)).toFixed(2)"></span>
                    <span class="text-xs text-gray-400">(Auto)</span>
                    <div class="mt-1 text-xs">
                      <span x-show="rowCounted('b', i)" class="text-green-700">Counted</span>
                      <span x-show="rowDuplicate('b', i)" class="text-amber-600">Not counted (duplicate)</span>
                    </div>
                  </td>

                  <td class="p-2">
                    <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                      <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                            :class="rowEvidenceCount('b', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                        <span x-text="rowEvidenceCount('b', i) > 0 ? `Evidence attached (${rowEvidenceCount('b', i)})` : 'No evidence'"></span>
                      </span>

                      <button type="button"
                              @click="openSelectEvidence('b', i)"
                              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                        <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                      </button>

                      <button type="button"
                              @click="openShowEvidence('b', i)"
                              :disabled="rowEvidenceCount('b', i) === 0"
                              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                              :class="rowEvidenceCount('b', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                        Show Evidence
                        <span class="text-[11px]" x-text="`(${rowEvidenceCount('b', i)})`"></span>
                      </button>
                    </div>

                    <select x-model="row.evidence"
                            multiple
                            :name="`section5[b][${i}][evidence][]`"
                            class="sr-only"
                            tabindex="-1"
                            aria-hidden="true">
                      <option value="" disabled>Select evidence (required)</option>
                      <template x-for="opt in evidenceOptions()" :key="opt.value">
                        <option :value="opt.value" x-text="opt.label"></option>
                      </template>
                    </select>
                    <template x-for="token in (row.evidence || [])" :key="token">
                      <input type="hidden" :name="`section5[b][${i}][evidence][]`" :value="token">
                    </template>
                  </td>

                  <td class="p-2 text-right">
                    <div class="inline-flex items-center justify-end gap-2">
                      <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                      <button type="button" @click="requestRowToggleRemove(bRows, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
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
                @click="bRows.push({ org:'', role:'', level:'', evidence:[] })"
                class="text-sm text-bu hover:underline">
          + Add organization role
        </button>

        <p class="text-xs text-gray-500">Counted points for B are capped at 10.</p>
      </div>
    </div>

    {{-- =====================================================
    C. SERVICE TO THE UNIVERSITY (cap 15)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          C. Service to the University <span class="text-sm text-gray-500">(Cap 15 pts)</span>
        </h3>
        <p class="text-xs text-gray-500 mt-1">
          Paper caps: C1 cap 10 • C2 cap 5 • C3 cap 10 • Overall C cap 15
        </p>
      </div>

        <div class="p-6 space-y-6">
          <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
            <label class="block text-xs text-gray-500">Previous Reclassification (C) Points</label>
            <input type="hidden" name="section5[c_prev_id]" :value="c_prev_id || ''">
            <input type="number"
                   min="0"
                   step="0.01"
                   x-model.number="c_prev"
                   name="section5[c_prev]"
                   class="mt-1 w-56 max-w-full rounded border-gray-300 text-sm"
                   placeholder="Enter previous C points">
            <p class="mt-1 text-[11px] text-gray-500">
              Counted: <span class="font-medium text-gray-700" x-text="prevCThird()"></span>
            </p>
            <template x-if="(c_prev_comments || []).length">
              <div class="mt-2" x-data="{ row: { comments: c_prev_comments } }">
                @include('reclassification.partials.entry-review-comments-inline')
              </div>
            </template>
          </div>
          {{-- C1 --}}
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-semibold text-gray-800">C1. Academic committee work</p>
              <p class="text-xs text-gray-500 mt-1">Subject area work, accreditation prep, syllabi, curriculum revision, etc.</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500">Counted</p>
              <p class="text-lg font-semibold text-gray-800" x-text="c1_capped()"></p>
            </div>
          </div>

          <p x-show="c1.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

          <div class="overflow-x-auto">
            <table x-show="c1.length" class="w-full text-sm border rounded-lg overflow-hidden">
              <thead class="bg-gray-50">
                <tr>
                  <th class="p-2 text-left">Activity</th>
                  <th class="p-2 text-left">Role</th>
                    <th class="p-2 text-left">Pts</th>
                    <th class="p-2 text-left">Evidence</th>
                    <th class="p-2"></th>
                </tr>
              </thead>

                                  <template x-for="(row,i) in c1" :key="i">
                                    <tbody class="divide-y">

                      <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                        <td class="p-2">
                          <input type="hidden" :name="`section5[c1][${i}][id]`" :value="row.id || ''">
                    <input type="hidden" :name="`section5[c1][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                      <input x-model="row.title"
                             :name="`section5[c1][${i}][title]`"
                             class="w-full rounded border-gray-300"
                             placeholder="e.g., Curriculum revision (year)">
                    </td>
                    <td class="p-2">
                      <select x-model="row.role"
                              :name="`section5[c1][${i}][role]`"
                              class="rounded border-gray-300 w-full">
                        <option value="" disabled>Select role</option>
                        <option value="overall">Over-all Chairman (7 pts)</option>
                        <option value="chairman">Chairman (5 pts)</option>
                        <option value="member">Member (2 pts)</option>
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
                                :name="`section5[c1][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                          <option value="" disabled>Select evidence</option>
                          <template x-for="opt in evidenceOptions()" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label"></option>
</template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                          <input type="hidden" :name="`section5[c1][${i}][evidence][]`" :value="token">
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
                   @click="c1.push({ title:'', role:'', evidence:[] })"
                    class="text-sm text-bu hover:underline">
              + Add committee work
            </button>

          <p class="text-xs text-gray-500">Counted points for C1 are capped at 10.</p>
        </div>

        {{-- C2 --}}
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-semibold text-gray-800">C2. Co-curricular activities</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500">Counted</p>
              <p class="text-lg font-semibold text-gray-800" x-text="c2_capped()"></p>
            </div>
          </div>

          <p x-show="c2.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

          <div class="overflow-x-auto">
            <table x-show="c2.length" class="w-full text-sm border rounded-lg overflow-hidden">
              <thead class="bg-gray-50">
                <tr>
                  <th class="p-2 text-left">Activity</th>
                  <th class="p-2 text-left">Type</th>
                    <th class="p-2 text-left">Pts</th>
                    <th class="p-2 text-left">Evidence</th>
                    <th class="p-2"></th>
                </tr>
              </thead>

                                  <template x-for="(row,i) in c2" :key="i">
                                    <tbody class="divide-y">

                      <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                        <td class="p-2">
                          <input type="hidden" :name="`section5[c2][${i}][id]`" :value="row.id || ''">
                    <input type="hidden" :name="`section5[c2][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                      <input x-model="row.title"
                             :name="`section5[c2][${i}][title]`"
                             class="w-full rounded border-gray-300"
                             placeholder="e.g., Campus activity (year)">
                    </td>
                    <td class="p-2">
                      <select x-model="row.type"
                              :name="`section5[c2][${i}][type]`"
                              class="rounded border-gray-300 w-full">
                        <option value="" disabled>Select type</option>
                        <option value="campus">Campus (5 pts)</option>
                        <option value="department">Department (3 pts)</option>
                        <option value="class">Class (1 pt)</option>
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
                                :name="`section5[c2][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                          <option value="" disabled>Select evidence</option>
                          <template x-for="opt in evidenceOptions()" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label"></option>
</template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                          <input type="hidden" :name="`section5[c2][${i}][evidence][]`" :value="token">
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
                   @click="c2.push({ title:'', type:'', evidence:[] })"
                    class="text-sm text-bu hover:underline">
              + Add co-curricular activity
            </button>

          <p class="text-xs text-gray-500">Counted points for C2 are capped at 5.</p>
        </div>

        {{-- C3 --}}
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-semibold text-gray-800">C3. University activities</p>
              <p class="text-xs text-gray-500 mt-1">Faculty dev seminars, school programs, graduation, intramurals, etc.</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500">Counted</p>
              <p class="text-lg font-semibold text-gray-800" x-text="c3_capped()"></p>
            </div>
          </div>

          <p x-show="c3.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

          <div class="overflow-x-auto">
            <table x-show="c3.length" class="w-full text-sm border rounded-lg overflow-hidden">
              <thead class="bg-gray-50">
                <tr>
                  <th class="p-2 text-left">Activity</th>
                  <th class="p-2 text-left">Role</th>
                    <th class="p-2 text-left">Pts</th>
                    <th class="p-2 text-left">Evidence</th>
                    <th class="p-2"></th>
                </tr>
              </thead>

                                  <template x-for="(row,i) in c3" :key="i">
                                    <tbody class="divide-y">

                      <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                        <td class="p-2">
                          <input type="hidden" :name="`section5[c3][${i}][id]`" :value="row.id || ''">
                    <input type="hidden" :name="`section5[c3][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                      <input x-model="row.title"
                             :name="`section5[c3][${i}][title]`"
                             class="w-full rounded border-gray-300"
                             placeholder="e.g., Graduation activities (year)">
                    </td>
                    <td class="p-2">
                      <select x-model="row.role"
                              :name="`section5[c3][${i}][role]`"
                              class="rounded border-gray-300 w-full">
                        <option value="" disabled>Select role</option>
                        <option value="overall">Over-all Chairman (5 pts)</option>
                        <option value="chairman">Committee Chairman (3 pts)</option>
                        <option value="member">Member (1 pt)</option>
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
                                :name="`section5[c3][${i}][evidence][]`"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true">
                          <option value="" disabled>Select evidence</option>
                          <template x-for="opt in evidenceOptions()" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label"></option>
</template>
                        </select>
                        <template x-for="token in (row.evidence || [])" :key="token">
                          <input type="hidden" :name="`section5[c3][${i}][evidence][]`" :value="token">
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
                   @click="c3.push({ title:'', role:'', evidence:[] })"
                    class="text-sm text-bu hover:underline">
              + Add university activity
            </button>

          <p class="text-xs text-gray-500">Counted points for C3 are capped at 10. Overall C is capped at 15.</p>
        </div>
      </div>
    </div>

    {{-- =====================================================
    D. COMMUNITY PROJECTS (cap 10)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          D. Active Participation in Community Projects / Programs <span class="text-sm text-gray-500">(Cap 10 pts)</span>
        </h3>
        <p class="text-xs text-gray-500 mt-1">
          Paper: Chairman=5 pts/activity • Coordinator/Trainor=3 • Participant=1
        </p>
      </div>

        <div class="p-6 space-y-3">
          <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
            <label class="block text-xs text-gray-500">Previous Reclassification (D) Points</label>
            <input type="hidden" name="section5[d_prev_id]" :value="d_prev_id || ''">
            <input type="number"
                   min="0"
                   step="0.01"
                   x-model.number="d_prev"
                   name="section5[d_prev]"
                   class="mt-1 w-56 max-w-full rounded border-gray-300 text-sm"
                   placeholder="Enter previous D points">
            <p class="mt-1 text-[11px] text-gray-500">
              Counted: <span class="font-medium text-gray-700" x-text="prevDThird()"></span>
            </p>
            <template x-if="(d_prev_comments || []).length">
              <div class="mt-2" x-data="{ row: { comments: d_prev_comments } }">
                @include('reclassification.partials.entry-review-comments-inline')
              </div>
            </template>
          </div>
          <p x-show="dRows.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="dRows.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 text-left">Project / Program</th>
                <th class="p-2 text-left">Role</th>
                  <th class="p-2 text-left">Pts</th>
                <th class="p-2 text-left">Evidence</th>
                <th class="p-2"></th>
              </tr>
            </thead>

                          <template x-for="(row,i) in dRows" :key="i">
                            <tbody class="divide-y">

                <tr class="border-t" :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
                  <td class="p-2">
                    <input type="hidden" :name="`section5[d][${i}][id]`" :value="row.id || ''">
                    <input type="hidden" :name="`section5[d][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
                    <input x-model="row.title"
                           :name="`section5[d][${i}][title]`"
                           class="w-full rounded border-gray-300"
                           placeholder="e.g., Community Outreach Program (include year)">
                  </td>

                  <td class="p-2">
                    <select x-model="row.role"
                            :name="`section5[d][${i}][role]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select role (required)</option>
                      <option value="chairman">Chairman — 5 pts/activity</option>
                      <option value="coordinator">Coordinator / Trainor — 3 pts/activity</option>
                      <option value="participant">Participant — 1 pt/activity</option>
                    </select>
                  </td>

                    <td class="p-2 text-gray-700">
                    <span x-text="Number(rowPoints('d', i)).toFixed(2)"></span>
                    <span class="text-xs text-gray-400">(Auto)</span>
                    <div class="mt-1 text-xs">
                      <span x-show="rowCounted('d', i)" class="text-green-700">Counted</span>
                      <span x-show="rowDuplicate('d', i)" class="text-amber-600">Not counted (duplicate)</span>
                    </div>
                  </td>

                  <td class="p-2">
                    <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                      <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                            :class="rowEvidenceCount('d', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                        <span x-text="rowEvidenceCount('d', i) > 0 ? `Evidence attached (${rowEvidenceCount('d', i)})` : 'No evidence'"></span>
                      </span>

                      <button type="button"
                              @click="openSelectEvidence('d', i)"
                              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                        <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                      </button>

                      <button type="button"
                              @click="openShowEvidence('d', i)"
                              :disabled="rowEvidenceCount('d', i) === 0"
                              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                              :class="rowEvidenceCount('d', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                        Show Evidence
                        <span class="text-[11px]" x-text="`(${rowEvidenceCount('d', i)})`"></span>
                      </button>
                    </div>

                      <select x-model="row.evidence"
                              multiple
                              :name="`section5[d][${i}][evidence][]`"
                              class="sr-only"
                              tabindex="-1"
                              aria-hidden="true">
                        <option value="" disabled>Select evidence (required)</option>
                        <template x-for="opt in evidenceOptions()" :key="opt.value">
                          <option :value="opt.value" x-text="opt.label"></option>
</template>
                      </select>
                      <template x-for="token in (row.evidence || [])" :key="token">
                        <input type="hidden" :name="`section5[d][${i}][evidence][]`" :value="token">
                      </template>
                  </td>

                  <td class="p-2 text-right">
                    <div class="inline-flex items-center justify-end gap-2">
                      <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                      <button type="button" @click="requestRowToggleRemove(dRows, i)" :class="isRemovedRow(row) ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'" class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition"><span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span></button>
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
                 @click="dRows.push({ title:'', role:'', evidence:[] })"
                  class="text-sm text-bu hover:underline">
            + Add community activity
          </button>

        <p class="text-xs text-gray-500">Counted points for D are capped at 10.</p>
      </div>
    </div>

    <div class="hidden" x-effect="emitScore(cappedTotal())"></div>

    {{-- ACTIONS --}}
    <div class="flex justify-end gap-4"></div>


  </div>

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

<script>
function sectionFive(initial = {}, globalEvidence = []) {
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

      aRows: initial.a || [],
      bRows: initial.b || [],
      c1: initial.c1 || [],
      c2: initial.c2 || [],
      c3: initial.c3 || [],
      dRows: initial.d || [],
      b_prev: Number(initial.b_prev || 0),
      b_prev_id: initial.b_prev_id || '',
      b_prev_comments: Array.isArray(initial.b_prev_comments) ? initial.b_prev_comments : [],
      c_prev: Number(initial.c_prev || 0),
      c_prev_id: initial.c_prev_id || '',
      c_prev_comments: Array.isArray(initial.c_prev_comments) ? initial.c_prev_comments : [],
      d_prev: Number(initial.d_prev || 0),
      d_prev_id: initial.d_prev_id || '',
      d_prev_comments: Array.isArray(initial.d_prev_comments) ? initial.d_prev_comments : [],
      previous: Number(initial.previous_points || 0),
      previous_id: initial.previous_points_id || '',
      previous_comments: Array.isArray(initial.previous_points_comments) ? initial.previous_points_comments : [],

    init() {
      const lists = ['aRows','bRows','c1','c2','c3','dRows'];
      const toArray = (val) => {
        if (Array.isArray(val)) return val;
        if (val === null || val === undefined || val === '') return [];
        return [String(val)];
      };
      const toComments = (val) => Array.isArray(val) ? val : [];
      this.b_prev_comments = toComments(this.b_prev_comments);
      this.c_prev_comments = toComments(this.c_prev_comments);
      this.d_prev_comments = toComments(this.d_prev_comments);
      this.previous_comments = toComments(this.previous_comments);

      lists.forEach((k) => {
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
        lists.forEach((k) => {
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
      const rowsByKey = {
        a: this.aRows,
        b: this.bRows,
        c1: this.c1,
        c2: this.c2,
        c3: this.c3,
        d: this.dRows,
      };
      const rows = rowsByKey[key] || [];
      const row = rows[index] || {};
      return row.evidence || [];
    },

    setRowEvidence(key, index, values) {
      const clean = Array.isArray(values) ? values.filter(Boolean) : [];
      const ensure = (arr) => {
        if (!arr[index]) arr[index] = { evidence: [] };
        arr[index].evidence = clean;
      };
      if (key === 'a') return ensure(this.aRows);
      if (key === 'b') return ensure(this.bRows);
      if (key === 'c1') return ensure(this.c1);
      if (key === 'c2') return ensure(this.c2);
      if (key === 'c3') return ensure(this.c3);
      if (key === 'd') return ensure(this.dRows);
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
      const rowsByKey = {
        a: this.aRows,
        b: this.bRows,
        c1: this.c1,
        c2: this.c2,
        c3: this.c3,
        d: this.dRows,
      };
      const rows = rowsByKey[key] || [];

      if (key === 'c1') {
        return rows.map((row) => {
          if (this.isRemovedRow(row)) {
            return { ...row, points: 0, counted: false };
          }
          const points = Number(this.ptsC1(row) || 0);
          return {
            ...row,
            points,
            counted: points > 0,
          };
        });
      }

      const keyFns = {
        a: (r) => `${r.kind || ''}|${(r.kind || '') === 'scholarship' ? (r.grant || '') : (r.level || '')}`,
        b: (r) => `${r.role || ''}|${r.level || ''}`,
        c1: (r) => r.role || '',
        c2: (r) => r.type || '',
        c3: (r) => r.role || '',
        d: (r) => r.role || '',
      };

      const pointsFns = {
        a: (r) => this.ptsA(r),
        b: (r) => this.ptsB(r),
        c1: (r) => this.ptsC1(r),
        c2: (r) => this.ptsC2(r),
        c3: (r) => this.ptsC3(r),
        d: (r) => this.ptsD(r),
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
        a: (r) => `${r.kind || ''}|${(r.kind || '') === 'scholarship' ? (r.grant || '') : (r.level || '')}`,
        b: (r) => `${r.role || ''}|${r.level || ''}`,
        c1: (r) => r.role || '',
        c2: (r) => r.type || '',
        c3: (r) => r.role || '',
        d: (r) => r.role || '',
      };
      return String(keyFns[key] ? keyFns[key](row || {}) : '');
    },

    bucketPoints(key, row) {
      if (this.isRemovedRow(row)) return 0;
      const pointsFns = {
        a: (r) => this.ptsA(r),
        b: (r) => this.ptsB(r),
        c1: (r) => this.ptsC1(r),
        c2: (r) => this.ptsC2(r),
        c3: (r) => this.ptsC3(r),
        d: (r) => this.ptsD(r),
      };
      return Number(pointsFns[key] ? pointsFns[key](row || {}) : 0);
    },

    rowDuplicate(key, index) {
      if (key === 'c1') return false;
      const rowsByKey = {
        a: this.aRows,
        b: this.bRows,
        c1: this.c1,
        c2: this.c2,
        c3: this.c3,
        d: this.dRows,
      };
      const rows = rowsByKey[key] || [];
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

    ptsA(row) {
      if (!row || !row.kind) return 0;

      if (row.kind !== 'scholarship') {
        if (!row.level) return 0;
        const lvl = { international:5, national:4, regional:3, local:2, school:1 };
        return Number(lvl[row.level] || 0);
      }

      if (!row.grant) return 0;
      const grant = { full:5, partial_4:4, partial_3:3, travel_2:2, travel_1:1 };
      return Number(grant[row.grant] || 0);
    },

    ptsB(row) {
      if (!row || !row.role || !row.level) return 0;

      const officer = { international:10, national:8, regional:6, local:4, school:2 };
      const chairman = { international:5, national:4, regional:3, local:2, school:1 };
      const committee = { international:4, national:3, regional:2, local:1.5, school:1 };
      const member = { international:3, national:2.5, regional:2, local:1, school:0.5 };

      const mapByRole = {
        officer,
        chairman,
        member_committee: committee,
        member,
      };

      return Number(mapByRole[row.role]?.[row.level] || 0);
    },

      ptsC1(row) {
        if (!row || !row.role) return 0;
        const per = { overall:7, chairman:5, member:2 };
        return Number(per[row.role] || 0);
      },

      ptsC2(row) {
        if (!row || !row.type) return 0;
        const per = { campus:5, department:3, class:1 };
        return Number(per[row.type] || 0);
      },

      ptsC3(row) {
        if (!row || !row.role) return 0;
        const per = { overall:5, chairman:3, member:1 };
        return Number(per[row.role] || 0);
      },

      ptsD(row) {
        if (!row || !row.role) return 0;
        const per = { chairman:5, coordinator:3, participant:1 };
        return Number(per[row.role] || 0);
      },

    cap(v, max) { v = Number(v || 0); return v > max ? max : v; },

    sumA_raw() {
      const t = this.bucketedRows('a').reduce((s,r)=> s + Number(r.points || 0), 0);
      return t.toFixed(2);
    },
    sumA_capped() { return Number(this.cap(this.sumA_raw(), 5)).toFixed(2); },

      sumB_raw() {
        const t = this.bucketedRows('b').reduce((s,r)=> s + Number(r.points || 0), 0);
        const prev = Number(this.prevBThird());
        return (t + prev).toFixed(2);
      },
      sumB_capped() { return Number(this.cap(this.sumB_raw(), 10)).toFixed(2); },

      c1_raw() { return this.bucketedRows('c1').reduce((s,r)=> s + Number(r.points || 0), 0).toFixed(2); },
      c1_capped() { return Number(this.cap(this.c1_raw(), 10)).toFixed(2); },

    c2_raw() { return this.bucketedRows('c2').reduce((s,r)=> s + Number(r.points || 0), 0).toFixed(2); },
    c2_capped() { return Number(this.cap(this.c2_raw(), 5)).toFixed(2); },

    c3_raw() { return this.bucketedRows('c3').reduce((s,r)=> s + Number(r.points || 0), 0).toFixed(2); },
    c3_capped() { return Number(this.cap(this.c3_raw(), 10)).toFixed(2); },

      sumC_raw() {
        const t = Number(this.c1_raw()) + Number(this.c2_raw()) + Number(this.c3_raw());
        const prev = Number(this.prevCThird());
        return (t + prev).toFixed(2);
      },
      sumC_capped() {
        const t = Number(this.c1_capped()) + Number(this.c2_capped()) + Number(this.c3_capped()) + Number(this.prevCThird());
        return Number(this.cap(t, 15)).toFixed(2);
      },

      sumD_raw() {
        const t = this.bucketedRows('d').reduce((s,r)=> s + Number(r.points || 0), 0);
        const prev = Number(this.prevDThird());
        return (t + prev).toFixed(2);
      },
      sumD_capped() { return Number(this.cap(this.sumD_raw(), 10)).toFixed(2); },

      subtotal() {
        return (
          Number(this.sumA_capped()) +
          Number(this.sumB_capped()) +
          Number(this.sumC_capped()) +
          Number(this.sumD_capped())
        ).toFixed(2);
      },

      prevBThird() { return (Number(this.b_prev || 0) / 3).toFixed(2); },
      prevCThird() { return (Number(this.c_prev || 0) / 3).toFixed(2); },
      prevDThird() { return (Number(this.d_prev || 0) / 3).toFixed(2); },
      prevThird() { return (Number(this.previous || 0) / 3).toFixed(2); },

      rawTotal() { return (Number(this.subtotal()) + Number(this.prevThird())).toFixed(2); },

    cappedTotal() {
      const raw = Number(this.rawTotal());
      return (raw > 30 ? 30 : raw).toFixed(2);
    },

    emitScore(points) {
      document.dispatchEvent(new CustomEvent('section-score', {
        detail: { section: '5', points: Number(points || 0) },
      }));
    },
  }
}
</script>

</form>

