{{-- resources/views/reclassification/section1.blade.php --}}
<div class="flex flex-col gap-1 mb-4">
  <h2 class="text-2xl font-semibold text-gray-800">Reclassification - Section I</h2>
  <p class="text-sm text-gray-500">
    Academic Preparation and Professional Development (Max 140 pts / 35%)
  </p>
</div>

<form method="POST"
      action="{{ route('reclassification.section.save', 1) }}"
      enctype="multipart/form-data"
      data-validate-evidence>
@csrf

<div x-data="sectionOne(@js($sectionData ?? []), @js($globalEvidence ?? []))" class="space-y-10">

{{-- =======================
IMPROVED STICKY SCORE SUMMARY (Expandable)
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
  class="sticky top-24 z-20"
>
  <div class="bg-white/95 backdrop-blur rounded-2xl border shadow-card">
    <div class="px-5 py-3 flex items-center justify-between gap-4">
      <div class="min-w-0">
        <div class="flex items-center gap-3">
          <h3 class="text-sm sm:text-base font-semibold text-gray-800 truncate">
            Section I Score Summary
          </h3>

          <template x-if="Number(rawTotal()) <= 140">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
              Within limit
            </span>
          </template>
          <template x-if="Number(rawTotal()) > 140">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
              Over limit
            </span>
          </template>
        </div>

        <p class="text-xs text-gray-600 mt-1">
          Raw: <span class="font-semibold text-gray-800" x-text="Number(rawTotal()).toFixed(2)"></span>
          <span class="text-gray-400">/ 140</span>
          <span class="mx-2 text-gray-300">•</span>
          Counted: <span class="font-semibold text-gray-800" x-text="Number(cappedTotal()).toFixed(2)"></span>
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
        System-suggested points (subject to validation). Limits are applied for guidance only.
      </p>

      <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-xl border p-4">
          <p class="text-xs text-gray-500">Total (Raw)</p>
          <p class="text-xl font-semibold text-gray-800">
            <span x-text="Number(rawTotal()).toFixed(2)"></span>
            <span class="text-sm font-medium text-gray-400">/ 140</span>
          </p>
          <p class="mt-1 text-xs text-gray-500">
            Counted (capped): <span class="font-medium text-gray-700" x-text="Number(cappedTotal()).toFixed(2)"></span>
          </p>
        </div>

        <div class="rounded-xl border p-4">
          <div class="flex items-center justify-between">
            <p class="text-xs text-gray-500">A. Academic Degree Earned</p>
            <span class="text-[11px] px-2 py-0.5 rounded-full border bg-gray-50 text-gray-600">Max 140</span>
          </div>
          <p class="text-lg font-semibold text-gray-800 mt-1">
            <span x-text="Number(rawA()).toFixed(2)"></span>
            <span class="text-sm font-medium text-gray-400">/ 140</span>
          </p>
          <p class="text-xs text-gray-500">
            Counted: <span class="font-medium text-gray-700" x-text="Number(cap(rawA(),140)).toFixed(2)"></span>
          </p>

          <div class="mt-2 text-xs text-gray-500 space-y-1">
            <div class="flex items-center justify-between">
              <span>A8 Exams cap</span>
              <span>
                <span class="font-medium text-gray-700" x-text="Number(rawA8()).toFixed(2)"></span>
                <span class="text-gray-400">/ 15</span>
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span>A9 Certifications cap</span>
              <span>
                <span class="font-medium text-gray-700" x-text="Number(rawA9()).toFixed(2)"></span>
                <span class="text-gray-400">/ 10</span>
              </span>
            </div>
          </div>
        </div>

        <div class="rounded-xl border p-4 space-y-3">
          <div>
            <div class="flex items-center justify-between">
              <p class="text-xs text-gray-500">B. Specialized Training</p>
              <span class="text-[11px] px-2 py-0.5 rounded-full border bg-gray-50 text-gray-600">Max 20</span>
            </div>
            <p class="text-lg font-semibold text-gray-800 mt-1">
              <span x-text="Number(rawB()).toFixed(2)"></span>
              <span class="text-sm font-medium text-gray-400">/ 20</span>
            </p>
            <p class="text-xs text-gray-500">
              Counted: <span class="font-medium text-gray-700" x-text="Number(cap(rawB(),20)).toFixed(2)"></span>
            </p>
          </div>

          <div class="border-t pt-3">
            <div class="flex items-center justify-between">
              <p class="text-xs text-gray-500">C. Seminars/Workshops</p>
              <span class="text-[11px] px-2 py-0.5 rounded-full border bg-gray-50 text-gray-600">Max 20</span>
            </div>
            <p class="text-lg font-semibold text-gray-800 mt-1">
              <span x-text="Number(rawC()).toFixed(2)"></span>
              <span class="text-sm font-medium text-gray-400">/ 20</span>
            </p>
            <p class="text-xs text-gray-500">
              Counted: <span class="font-medium text-gray-700" x-text="Number(cap(rawC(),20)).toFixed(2)"></span>
            </p>
          </div>
        </div>
      </div>

      <template x-if="Number(rawTotal()) > 140">
        <p class="mt-3 text-xs text-red-600">
          Your raw total exceeds the 140-point limit. Excess points will not be counted.
        </p>
      </template>
    </div>
  </div>
</div>

{{-- ======================================================
A. ACADEMIC DEGREE EARNED
====================================================== --}}
<div class="bg-white rounded-2xl shadow-card border">

<div class="px-6 py-4 border-b">
<h3 class="text-lg font-semibold text-gray-800">A. Academic Degree Earned</h3>
<p class="text-sm text-gray-500">
Instruction: Kindly check the corresponding points in the blanks and write the Final Rating for each Academic Qualifications.
</p>
</div>

<div class="p-6 space-y-10">

{{-- A1 BACHELOR --}}
<div data-comment-anchor="1:a1">
  <h4 class="font-medium text-gray-800 mb-2">A1. Bachelor’s Degree</h4>
  <div class="space-y-2 text-sm">
    <label class="flex gap-2">
      <input x-model="a1Honors" type="radio" name="section1[a1][honors]" value="summa">
      Summa Cum Laude (3 pts)
    </label>
    <label class="flex gap-2">
      <input x-model="a1Honors" type="radio" name="section1[a1][honors]" value="magna">
      Magna Cum Laude (2 pts)
    </label>
    <label class="flex gap-2">
      <input x-model="a1Honors" type="radio" name="section1[a1][honors]" value="cum">
      Cum Laude (1 pt)
    </label>
    <label class="flex gap-2">
      <input x-model="a1Honors" type="radio" name="section1[a1][honors]" value="none">
      None
    </label>
  </div>

  <div class="mt-3" x-show="a1Honors && a1Honors !== 'none'" data-evidence-block="a1">
    <input type="hidden" name="section1[a1][id]" :value="a1Id || ''">
    <label class="block text-xs text-gray-600 mb-1">Evidence for Latin honors</label>
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
            name="section1[a1][evidence][]"
            class="sr-only"
            tabindex="-1"
            aria-hidden="true">
      <option value="" disabled>Select evidence</option>
      <template x-for="opt in evidenceOptions()" :key="opt.value">
        <option :value="opt.value" x-text="opt.label"></option>
      </template>
    </select>
    <template x-for="token in (a1Evidence || [])" :key="token">
      <input type="hidden" name="section1[a1][evidence][]" :value="token">
    </template>
  </div>
</div>

@php
$tables = [
'A2. For every additional bachelor’s degree' => [
  'key' => 'a2',
  'cols' => ['Degree','Category','Pts','Evidence'],
  'placeholder' => ['Degree' => 'e.g., BSIT',]
],
'A3. Master’s degree (including LLB-bar passer)' => [
  'key' => 'a3',
  'cols' => ['Degree','Category','Thesis','Pts','Evidence'],
  'placeholder' => ['Degree' => 'e.g., MAEd / LLB',]
],
'A4. Master’s degree units (9-unit minimum)' => [
  'key' => 'a4',
  'cols' => ['Category','Units','Pts','Evidence'],
],
'A5. For every additional Master’s degree' => [
  'key' => 'a5',
  'cols' => ['Degree','Category','Pts','Evidence'],
  'placeholder' => ['Degree' => 'e.g., MBA',]
],
'A6. Doctoral degree units (9-unit minimum)' => [
  'key' => 'a6',
  'cols' => ['Category','Units','Pts','Evidence'],
],
'A7. Doctor’s degree' => [
  'key' => 'a7',
  'cols' => ['Degree','Category','Pts','Evidence'],
  'placeholder' => ['Degree' => 'e.g., PhD / EdD',]
],
'A8. Qualifying Government Examinations (cap 15)' => [
  'key' => 'a8',
  'cols' => ['Exam','Relation','Pts','Evidence'],
  'placeholder' => ['Exam' => 'e.g., Civil Service / LET',]
],
'A9. International / National Certifications (cap 10)' => [
  'key' => 'a9',
  'cols' => ['Certification','Level','Pts','Evidence'],
  'placeholder' => ['Certification' => 'e.g., Cisco / Microsoft',]
],
];
@endphp

@foreach($tables as $title => $cfg)
<div class="space-y-2">
  <h4 class="font-medium text-gray-800">{{ $title }}</h4>

  <p x-show="{{ $cfg['key'] }}.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

  <table x-show="{{ $cfg['key'] }}.length > 0" class="w-full text-sm border rounded-lg overflow-hidden">
    <thead class="bg-gray-50">
      <tr>
        @foreach($cfg['cols'] as $col)
          <th class="px-3 py-2 text-left">{{ $col }}</th>
        @endforeach
        <th class="px-3 py-2"></th>
      </tr>
    </thead>    

          <template x-for="(row,i) in {{ $cfg['key'] }}" :key="i">
            <tbody class="divide-y">

        <tr :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
          @foreach($cfg['cols'] as $col)
            <td class="px-3 py-2 align-top">
              @if($loop->first)
                <input type="hidden" :name="`section1[{{ $cfg['key'] }}][${i}][id]`" :value="row.id || ''">
                <input type="hidden" :name="`section1[{{ $cfg['key'] }}][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
              @endif
              @if($col === 'Pts')
                <span class="text-gray-800 font-semibold" x-text="Number(rowPoints('{{ $cfg['key'] }}', i)).toFixed(2)"></span>
                <span class="text-gray-400 text-xs"> (Auto)</span>
                <div class="mt-1 text-xs" x-show="isBucketed('{{ $cfg['key'] }}')">
                  <span x-show="rowCounted('{{ $cfg['key'] }}', i)" class="text-green-700">Counted</span>
                  <span x-show="rowDuplicate('{{ $cfg['key'] }}', i)" class="text-amber-600">Not counted (duplicate)</span>
                </div>

              @elseif($col === 'Evidence')
                <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                        :class="rowEvidenceCount('{{ $cfg['key'] }}', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                    <span x-text="rowEvidenceCount('{{ $cfg['key'] }}', i) > 0 ? `Evidence attached (${rowEvidenceCount('{{ $cfg['key'] }}', i)})` : 'No evidence'"></span>
                  </span>

                  <button type="button"
                          @click="openSelectEvidence('{{ $cfg['key'] }}', i)"
                          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                  </button>

                  <button type="button"
                          @click="openShowEvidence('{{ $cfg['key'] }}', i)"
                          :disabled="rowEvidenceCount('{{ $cfg['key'] }}', i) === 0"
                          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                          :class="rowEvidenceCount('{{ $cfg['key'] }}', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                    Show Evidence
                    <span class="text-[11px]" x-text="`(${rowEvidenceCount('{{ $cfg['key'] }}', i)})`"></span>
                  </button>
                </div>

                <select x-model="row.evidence"
                        multiple
                        :name="`section1[{{ $cfg['key'] }}][${i}][evidence][]`"
                        class="sr-only"
                        tabindex="-1"
                        aria-hidden="true">
                  <option value="" disabled>Select evidence</option>
                  <template x-for="opt in evidenceOptions()" :key="opt.value">
                    <option :value="opt.value" x-text="opt.label"></option>
                  </template>
                </select>
                <template x-for="token in (row.evidence || [])" :key="token">
                  <input type="hidden" :name="`section1[{{ $cfg['key'] }}][${i}][evidence][]`" :value="token">
                </template>

              @elseif($col === 'Category')
                <template x-if="['a4','a6'].includes('{{ $cfg['key'] }}')">
                  <select x-model="row.category"
                          :name="`section1[{{ $cfg['key'] }}][${i}][category]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select category</option>
                    <option value="specialization">Field of specialization / allied field</option>
                    <option value="other">Other fields</option>
                  </select>
                </template>

                <template x-if="!['a4','a6'].includes('{{ $cfg['key'] }}')">
                  <select x-model="row.category"
                          :name="`section1[{{ $cfg['key'] }}][${i}][category]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select category</option>
                    <option value="teaching">Teaching field</option>
                    <option value="not_teaching">Not in the teaching field</option>
                  </select>
                </template>

              @elseif($col === 'Thesis')
                <select x-model="row.thesis"
                        :name="`section1[{{ $cfg['key'] }}][${i}][thesis]`"
                        class="rounded border-gray-300 w-full">
                  <option value="" disabled selected>Select thesis option</option>
                  <option value="with">With thesis</option>
                  <option value="without">Without thesis</option>
                </select>

              @elseif($col === 'Relation')
                <select x-model="row.relation"
                        :name="`section1[{{ $cfg['key'] }}][${i}][relation]`"
                        class="rounded border-gray-300 w-full">
                  <option value="" disabled selected>Select relation</option>
                  <option value="direct">Directly related / required by teaching area</option>
                  <option value="not_direct">Not directly related / required</option>
                </select>

              @elseif($col === 'Level')
                <select x-model="row.level"
                        :name="`section1[{{ $cfg['key'] }}][${i}][level]`"
                        class="rounded border-gray-300 w-full">
                  <option value="" disabled selected>Select level</option>
                  <option value="international">International</option>
                  <option value="national">National</option>
                </select>

              @elseif($col === 'Units')
                <input type="number" min="0" step="1"
                       x-model.number="row.units"
                       :name="`section1[{{ $cfg['key'] }}][${i}][units]`"
                       class="w-full rounded border-gray-300"
                       placeholder="Enter units">

              @else
                <input x-model="row.text"
                       :name="`section1[{{ $cfg['key'] }}][${i}][text]`"
                       class="w-full rounded border-gray-300"
                       :placeholder="placeholders['{{ $cfg['key'] }}']['{{ $col }}'] || 'Enter value'">
              @endif
            </td>
          @endforeach

          <td class="px-3 py-2 text-right">
            <div class="inline-flex items-center justify-end gap-2">
              <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
              <button type="button"
                      @click="requestRowToggleRemove({{ $cfg['key'] }}, i)"
                      :class="isRemovedRow(row)
                        ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100'
                        : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'"
                      class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition">
                <span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span>
              </button>
            </div>
          </td>
        </tr>
        <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
          <td colspan="99" class="px-3 py-2">
            @include('reclassification.partials.entry-review-comments-inline')
          </td>
        </tr>
            </tbody>
</template>

  </table>

  <button type="button"
          @click="addRow('{{ $cfg['key'] }}')"
          class="text-sm text-bu hover:underline">
    + Add entry
  </button>
</div>
@endforeach

</div>
</div>

{{-- ======================================================
B. ADVANCED / SPECIALIZED TRAINING (paper: fixed options)
====================================================== --}}
<div class="bg-white rounded-2xl shadow-card border">
  <div class="px-6 py-4 border-b">
    <h3 class="text-lg font-semibold text-gray-800">B. Advanced or Specialized Training (non-degree)</h3>
    <p class="text-sm text-gray-500">
      Within the last three years only. Supported by evidences. Max 20 pts.
    </p>
  </div>

    <div class="p-6 space-y-2">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
          <label class="block text-xs text-gray-500">Previous Reclassification (B) Points</label>
          <input type="hidden" name="section1[b_prev_id]" :value="b_prev_id || ''">
          <input type="number"
                 min="0"
                 step="0.01"
                 x-model="b_prev"
                 name="section1[b_prev]"
                 class="mt-1 w-56 max-w-full rounded border-gray-300 text-sm"
                 placeholder="Enter previous B points">
          <p class="mt-1 text-[11px] text-gray-500">
            Counted: <span class="font-medium text-gray-700" x-text="Number(b_prev || 0) / 3"></span>
          </p>
          <template x-if="(b_prev_comments || []).length">
            <div class="mt-2" x-data="{ row: { comments: b_prev_comments } }">
              @include('reclassification.partials.entry-review-comments-inline')
            </div>
          </template>
        </div>
      </div>
      <p x-show="b.length === 0" class="text-sm italic text-gray-500">No training added.</p>

    <table x-show="b.length > 0" class="w-full text-sm border rounded-lg overflow-hidden">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">Training Title</th>
          <th class="px-3 py-2 text-left">Hours Category</th>
          <th class="px-3 py-2 text-left">Pts</th>
          <th class="px-3 py-2 text-left">Evidence</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>      

              <template x-for="(row,i) in b" :key="i">
                <tbody class="divide-y">

          <tr :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
            <td class="px-3 py-2">
              <input type="hidden" :name="`section1[b][${i}][id]`" :value="row.id || ''">
              <input type="hidden" :name="`section1[b][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
              <input x-model="row.title"
                     :name="`section1[b][${i}][title]`"
                     class="w-full rounded border-gray-300"
                     placeholder="e.g., Advanced Training Title">
            </td>

            <td class="px-3 py-2">
              <select x-model="row.hours"
                      :name="`section1[b][${i}][hours]`"
                      class="rounded border-gray-300 w-full">
                <option value="" disabled selected>Select hours (required)</option>
                <option value="120">At least 120 hours (15 pts)</option>
                <option value="80">At least 80 hours (10 pts)</option>
                <option value="50">At least 50 hours (6 pts)</option>
                <option value="20">At least 20 hours (4 pts)</option>
              </select>
            </td>

            <td class="px-3 py-2">
              <span class="text-gray-800 font-semibold" x-text="Number(rowPoints('b', i)).toFixed(2)"></span>
              <span class="text-gray-400 text-xs"> (Auto)</span>
              <div class="mt-1 text-xs">
                <span x-show="rowCounted('b', i)" class="text-green-700">Counted</span>
                <span x-show="rowDuplicate('b', i)" class="text-amber-600">Not counted (duplicate)</span>
              </div>
            </td>

            <td class="px-3 py-2">
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
                      :name="`section1[b][${i}][evidence][]`"
                      class="sr-only"
                      tabindex="-1"
                      aria-hidden="true">
                <option value="" disabled>Select evidence</option>
                <template x-for="opt in evidenceOptions()" :key="opt.value">
                  <option :value="opt.value" x-text="opt.label"></option>
                </template>
              </select>
              <template x-for="token in (row.evidence || [])" :key="token">
                <input type="hidden" :name="`section1[b][${i}][evidence][]`" :value="token">
              </template>
            </td>

            <td class="px-3 py-2 text-right">
              <div class="inline-flex items-center justify-end gap-2">
                <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                <button type="button"
                        @click="requestRowToggleRemove(b, i)"
                        :class="isRemovedRow(row)
                          ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100'
                          : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'"
                        class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition">
                  <span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span>
                </button>
              </div>
            </td>
          </tr>
          <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
            <td colspan="99" class="px-3 py-2">
              @include('reclassification.partials.entry-review-comments-inline')
            </td>
          </tr>
                </tbody>
</template>

    </table>

    <button type="button"
            @click="b.push({ title:'', hours:'', evidence:'' })"
            class="text-sm text-bu hover:underline">
      + Add training
    </button>
  </div>
</div>

{{-- ======================================================
C. SEMINARS / WORKSHOPS / CONFERENCES
✅ Auto-calculated using PAPER ranges (uses MIN of range)
====================================================== --}}
<div class="bg-white rounded-2xl shadow-card border">
  <div class="px-6 py-4 border-b">
    <h3 class="text-lg font-semibold text-gray-800">C. Attendance at Workshops / Seminars / Conferences</h3>
    <p class="text-sm text-gray-500">
      Within the last three years only. Supported by evidences. Max 20 pts.
    </p>
  </div>

    <div class="p-6 space-y-2">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
          <label class="block text-xs text-gray-500">Previous Reclassification (C) Points</label>
          <input type="hidden" name="section1[c_prev_id]" :value="c_prev_id || ''">
          <input type="number"
                 min="0"
                 step="0.01"
                 x-model="c_prev"
                 name="section1[c_prev]"
                 class="mt-1 w-56 max-w-full rounded border-gray-300 text-sm"
                 placeholder="Enter previous C points">
          <p class="mt-1 text-[11px] text-gray-500">
            Counted: <span class="font-medium text-gray-700" x-text="Number(c_prev || 0) / 3"></span>
          </p>
          <template x-if="(c_prev_comments || []).length">
            <div class="mt-2" x-data="{ row: { comments: c_prev_comments } }">
              @include('reclassification.partials.entry-review-comments-inline')
            </div>
          </template>
        </div>
      </div>
      <p x-show="c.length === 0" class="text-sm italic text-gray-500">No activity added.</p>

    <table x-show="c.length > 0" class="w-full text-sm border rounded-lg overflow-hidden">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">Title</th>
          <th class="px-3 py-2 text-left">Role</th>
          <th class="px-3 py-2 text-left">Level</th>
          <th class="px-3 py-2 text-left">Points (Auto)</th>
          <th class="px-3 py-2 text-left">Evidence</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>      


              <template x-for="(row,i) in c" :key="i">
                <tbody class="divide-y">

          <tr :class="isRemovedRow(row) ? 'bg-gray-100/70 text-gray-500' : ''">
            <td class="px-3 py-2">
              <input type="hidden" :name="`section1[c][${i}][id]`" :value="row.id || ''">
              <input type="hidden" :name="`section1[c][${i}][is_removed]`" :value="isRemovedRow(row) ? 1 : 0">
              <input x-model="row.title"
                     :name="`section1[c][${i}][title]`"
                     class="w-full rounded border-gray-300"
                     placeholder="e.g., Seminar Title">
            </td>

            <td class="px-3 py-2">
              <select x-model="row.role"
                      :name="`section1[c][${i}][role]`"
                      class="rounded border-gray-300 w-full">
                <option value="" disabled selected>Select role (required)</option>
                <option value="speaker">Speaker</option>
                <option value="resource">Resource Person / Consultant</option>
                <option value="participant">Participant / Delegate</option>
              </select>
            </td>

            <td class="px-3 py-2">
              <select x-model="row.level"
                      :name="`section1[c][${i}][level]`"
                      class="rounded border-gray-300 w-full">
                <option value="" disabled selected>Select level (required)</option>
                <option value="international">International</option>
                <option value="national">National</option>
                <option value="regional">Regional</option>
                <option value="provincial">Provincial</option>
                <option value="municipal">Municipal</option>
                <option value="school">School</option>
              </select>
            </td>

            <td class="px-3 py-2">
              <div class="font-semibold text-gray-800">
                <span x-text="Number(rowPoints('c', i)).toFixed(2)"></span>
                <span class="text-xs text-gray-400">(Auto)</span>
              </div>

              <div class="text-xs text-gray-500 mt-1" x-text="rangeHintC(row)"></div>
              <div class="mt-1 text-xs">
                <span x-show="rowCounted('c', i)" class="text-green-700">Counted</span>
                <span x-show="rowDuplicate('c', i)" class="text-amber-600">Not counted (duplicate)</span>
              </div>

              <input type="hidden"
                     :name="`section1[c][${i}][points]`"
                     :value="ptsC(row)">
            </td>

            <td class="px-3 py-2">
              <div class="flex items-center flex-wrap gap-2" data-evidence-proxy>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium"
                      :class="rowEvidenceCount('c', i) > 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-600 border border-gray-200'">
                  <span x-text="rowEvidenceCount('c', i) > 0 ? `Evidence attached (${rowEvidenceCount('c', i)})` : 'No evidence'"></span>
                </span>

                <button type="button"
                        @click="openSelectEvidence('c', i)"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
                  <span x-text="hasLibraryEvidence() ? 'Select Evidence' : 'Upload Evidence'"></span>
                </button>

                <button type="button"
                        @click="openShowEvidence('c', i)"
                        :disabled="rowEvidenceCount('c', i) === 0"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium"
                        :class="rowEvidenceCount('c', i) === 0 ? 'text-gray-300 border-gray-200' : 'text-gray-700 hover:bg-gray-50'">
                  Show Evidence
                  <span class="text-[11px]" x-text="`(${rowEvidenceCount('c', i)})`"></span>
                </button>
              </div>

              <select x-model="row.evidence"
                      multiple
                      :name="`section1[c][${i}][evidence][]`"
                      class="sr-only"
                      tabindex="-1"
                      aria-hidden="true">
                <option value="" disabled>Select evidence</option>
                <template x-for="opt in evidenceOptions()" :key="opt.value">
                  <option :value="opt.value" x-text="opt.label"></option>
                </template>
              </select>
              <template x-for="token in (row.evidence || [])" :key="token">
                <input type="hidden" :name="`section1[c][${i}][evidence][]`" :value="token">
              </template>
            </td>

            <td class="px-3 py-2 text-right">
              <div class="inline-flex items-center justify-end gap-2">
                <span x-show="isRemovedRow(row)" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700">Removed</span>
                <button type="button"
                        @click="requestRowToggleRemove(c, i)"
                        :class="isRemovedRow(row)
                          ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100'
                          : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'"
                        class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition">
                  <span x-text="isRemovedRow(row) ? 'Restore Entry' : '{{ (($application->status ?? '') === 'draft') ? 'Remove' : 'Mark Removed' }}'"></span>
                </button>
              </div>
            </td>
          </tr>
          <tr x-show="(row.comments || []).length" data-row-review-comments class="bg-gray-50/40">
            <td colspan="99" class="px-3 py-2">
              @include('reclassification.partials.entry-review-comments-inline')
            </td>
          </tr>
                </tbody>
</template>

    </table>

    <button type="button"
            @click="c.push({ title:'', role:'', level:'', evidence:'' })"
            class="text-sm text-bu hover:underline">
      + Add activity
    </button>
  </div>
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

{{-- UPLOAD MODAL --}}
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
          <template x-if="!previewItem.url">
            <p class="text-gray-500">New uploads can be previewed after saving.</p>
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

<div class="hidden" x-effect="emitScore(cappedTotal())"></div>

</div>
</form>

<script>
function sectionOne(initial = {}, globalEvidence = []) {
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
    removeConfirmOpen: false,
    removePendingRows: null,
    removePendingIndex: null,
    toast: { show: false, message: '', type: 'success' },
    toastTimer: null,
    a1Id: (initial.a1 && initial.a1.id) ? Number(initial.a1.id) : '',
    a1Honors: (initial.a1 && initial.a1.honors) ? initial.a1.honors : '',
    a1Evidence: (initial.a1 && Array.isArray(initial.a1.evidence)) ? initial.a1.evidence : [],
    a1Comments: (initial.a1 && Array.isArray(initial.a1.comments)) ? initial.a1.comments : [],

    // A arrays
    a2: initial.a2 || [],
    a3: initial.a3 || [],
    a4: initial.a4 || [],
    a5: initial.a5 || [],
    a6: initial.a6 || [],
    a7: initial.a7 || [],
    a8: initial.a8 || [],
    a9: initial.a9 || [],
    // B/C arrays
    b: initial.b || [],
    c: initial.c || [],
    b_prev: initial.b_prev || '',
    b_prev_id: initial.b_prev_id || '',
    b_prev_comments: initial.b_prev_comments || [],
    c_prev: initial.c_prev || '',
    c_prev_id: initial.c_prev_id || '',
    c_prev_comments: initial.c_prev_comments || [],

    // placeholders for text inputs
    placeholders: {
      a2: { Degree: 'e.g., BSIT' },
      a3: { Degree: 'e.g., MAEd / LLB' },
      a5: { Degree: 'e.g., MBA' },
      a7: { Degree: 'e.g., PhD / EdD' },
      a8: { Exam: 'e.g., LET / Civil Service' },
      a9: { Certification: 'e.g., Cisco / Microsoft' },
    },

    init() {
      const keys = ['a2','a3','a4','a5','a6','a7','a8','a9','b','c'];
      keys.forEach((k) => {
        if (!Array.isArray(this[k])) this[k] = [];
      });
      const toArray = (val) => {
        if (Array.isArray(val)) return val;
        if (val === null || val === undefined || val === '') return [];
        return [String(val)];
      };
      const toComments = (val) => Array.isArray(val) ? val : [];
      this.b_prev_comments = toComments(this.b_prev_comments);
      this.c_prev_comments = toComments(this.c_prev_comments);

      this.a1Evidence = toArray(this.a1Evidence);
      this.a1Comments = toComments(this.a1Comments);
      if (!this.a1Honors || this.a1Honors === 'none') {
        this.a1Evidence = [];
      }

      keys.forEach((k) => {
        this[k] = this[k].map((row) => ({
          ...row,
          is_removed: this.isRemovedRow(row),
          evidence: toArray(row.evidence),
          comments: toComments(row.comments),
        }));
      });

      this.$watch('a1Honors', (value) => {
        if (!value || value === 'none') {
          this.a1Evidence = [];
        }
      });

      window.addEventListener('evidence-detached', (event) => {
        const id = event.detail?.id;
        if (!id) return;
        const token = `e:${id}`;
        const removeToken = (arr) => Array.isArray(arr) ? arr.filter((v) => String(v) !== token) : [];
        this.a1Evidence = removeToken(this.a1Evidence);
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
      if (key === 'a1') return this.a1Evidence || [];
      const rows = this[key] || [];
      const row = rows[index] || {};
      return row.evidence || [];
    },

    setRowEvidence(key, index, values) {
      const clean = Array.isArray(values) ? values.filter(Boolean) : [];
      if (key === 'a1') {
        this.a1Evidence = clean;
        return;
      }
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

    addRow(key) {
      const base = { text:'', category:'', thesis:'', relation:'', level:'', units:'', evidence:[] };
      this[key].push({ ...base });
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

    cap(v, max) {
      v = Number(v || 0);
      return v > max ? max : v;
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
      return ['a2','a3','a4','a5','a6','a7','a8','b','c'].includes(key);
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
        return rows.map((row) => {
          if (this.isRemovedRow(row)) {
            return { ...row, points: 0, counted: false };
          }
          return { ...row, points: this.ptsA(key, row), counted: true };
        });
      }

      const keyFns = {
        a2: (r) => r.category || '',
        a3: (r) => `${r.category || ''}|${r.thesis || ''}`,
        a4: (r) => r.category || '',
        a5: (r) => r.category || '',
        a6: (r) => r.category || '',
        a7: (r) => r.category || '',
        a8: (r) => r.relation || '',
        b: (r) => r.hours || '',
        c: (r) => `${r.role || ''}|${r.level || ''}`,
      };

      const pointsFns = {
        a2: (r) => this.ptsA('a2', r),
        a3: (r) => this.ptsA('a3', r),
        a4: (r) => this.ptsA('a4', r),
        a5: (r) => this.ptsA('a5', r),
        a6: (r) => this.ptsA('a6', r),
        a7: (r) => this.ptsA('a7', r),
        a8: (r) => this.ptsA('a8', r),
        b: (r) => this.ptsB(r),
        c: (r) => this.ptsC(r),
      };

      return this.bucketOnceRows(rows, keyFns[key], pointsFns[key]);
    },

    bucketKey(key, row) {
      const keyFns = {
        a2: (r) => r.category || '',
        a3: (r) => `${r.category || ''}|${r.thesis || ''}`,
        a4: (r) => r.category || '',
        a5: (r) => r.category || '',
        a6: (r) => r.category || '',
        a7: (r) => r.category || '',
        a8: (r) => r.relation || '',
        b: (r) => r.hours || '',
        c: (r) => `${r.role || ''}|${r.level || ''}`,
      };
      return String(keyFns[key] ? keyFns[key](row || {}) : '');
    },

    bucketPoints(key, row) {
      if (this.isRemovedRow(row)) return 0;
      const pointsFns = {
        a2: (r) => this.ptsA('a2', r),
        a3: (r) => this.ptsA('a3', r),
        a4: (r) => this.ptsA('a4', r),
        a5: (r) => this.ptsA('a5', r),
        a6: (r) => this.ptsA('a6', r),
        a7: (r) => this.ptsA('a7', r),
        a8: (r) => this.ptsA('a8', r),
        b: (r) => this.ptsB(r),
        c: (r) => this.ptsC(r),
      };
      return Number(pointsFns[key] ? pointsFns[key](row || {}) : 0);
    },

    rowPoints(key, index) {
      const rows = this.bucketedRows(key);
      return Number(rows[index]?.points || 0);
    },

    rowCounted(key, index) {
      const rows = this.bucketedRows(key);
      return !!rows[index]?.counted;
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
      for (let i = 0; i < rows.length; i++) {
        const other = rows[i];
        if (this.bucketKey(key, other) !== bucket) continue;
        if (this.bucketPoints(key, other) <= 0) continue;
        return i < index;
      }
      return false;
    },

    ptsA(key, row) {
      const cat = (row?.category || '');
      const thesis = (row?.thesis || '');
      const rel = (row?.relation || '');
      const lvl = (row?.level || '');
      const units = Number(row?.units || 0);
      const hasNineUnits = units >= 9;

      if (key === 'a2') {
        if (cat === 'teaching') return 10;
        if (cat === 'not_teaching') return 5;
        return 0;
      }

      if (key === 'a3') {
        if (cat === 'teaching' && thesis === 'with') return 100;
        if (cat === 'teaching' && thesis === 'without') return 90;
        if (cat === 'not_teaching' && thesis === 'with') return 80;
        if (cat === 'not_teaching' && thesis === 'without') return 70;
        return 0;
      }

      if (key === 'a4') {
        if (cat === 'specialization') return hasNineUnits ? 4 : 0;
        if (cat === 'other') return hasNineUnits ? 3 : 0;
        return 0;
      }

      if (key === 'a5') {
        if (cat === 'teaching') return 15;
        if (cat === 'not_teaching') return 10;
        return 0;
      }

      if (key === 'a6') {
        if (cat === 'specialization') return hasNineUnits ? 5 : 0;
        if (cat === 'other') return hasNineUnits ? 4 : 0;
        return 0;
      }

      if (key === 'a7') {
        if (cat === 'teaching') return 140;
        if (cat === 'not_teaching') return 120;
        return 0;
      }

      if (key === 'a8') {
        if (rel === 'direct') return 10;
        if (rel === 'not_direct') return 5;
        return 0;
      }

      if (key === 'a9') {
        if (lvl === 'international') return 5;
        if (lvl === 'national') return 3;
        return 0;
      }

      return 0;
    },

    a1Pts() {
      const v = this.a1Honors || '';
      if (v === 'summa') return 3;
      if (v === 'magna') return 2;
      if (v === 'cum') return 1;
      return 0;
    },

    rawA8() {
      return this.bucketedRows('a8').reduce((t, r) => t + Number(r.points || 0), 0);
    },

    rawA9() {
      return this.bucketedRows('a9').reduce((t, r) => t + Number(r.points || 0), 0);
    },

    rawA() {
      const a1 = this.a1Pts();
      const a2 = this.bucketedRows('a2').reduce((t, r) => t + Number(r.points || 0), 0);
      const a3 = this.bucketedRows('a3').reduce((t, r) => t + Number(r.points || 0), 0);
      const a4 = this.bucketedRows('a4').reduce((t, r) => t + Number(r.points || 0), 0);
      const a5 = this.bucketedRows('a5').reduce((t, r) => t + Number(r.points || 0), 0);
      const a6 = this.bucketedRows('a6').reduce((t, r) => t + Number(r.points || 0), 0);
      const a7 = this.bucketedRows('a7').reduce((t, r) => t + Number(r.points || 0), 0);

      const a8 = this.cap(this.rawA8(), 15);
      const a9 = this.cap(this.rawA9(), 10);

      return a1 + a2 + a3 + a4 + a5 + a6 + a7 + a8 + a9;
    },

    ptsB(row) {
      const h = String(row?.hours || '');
      if (h === '120') return 15;
      if (h === '80') return 10;
      if (h === '50') return 6;
      if (h === '20') return 4;
      return 0;
    },

      rawB() {
        const prev = Number(this.b_prev || 0) / 3;
        return this.bucketedRows('b').reduce((t, r) => t + Number(r.points || 0), 0) + prev;
      },

    pointOptionsForC(row) {
      const role = row?.role;
      const level = row?.level;

      if (!role || !level) return [];

      const ranges = {
        speaker: {
          international: [13,15],
          national: [11,12],
          regional: [9,10],
          provincial: [7,8],
          municipal: [4,6],
          school: [1,3],
        },
        resource: {
          international: [11,12],
          national: [9,10],
          regional: [7,8],
          provincial: [5,6],
          municipal: [3,4],
          school: [1,2],
        },
        participant: {
          international: [9,10],
          national: [7,8],
          regional: [5,6],
          provincial: [3,4],
          municipal: [2,2],
          school: [1,1],
        },
      };

      const r = ranges?.[role]?.[level];
      if (!r) return [];

      const [min,max] = r;
      const opts = [];
      for (let p = min; p <= max; p++) opts.push({ value: p, label: `${p} pt${p>1?'s':''}` });
      return opts;
    },

    rangeHintC(row) {
      const role = row?.role;
      const level = row?.level;
      if (!role || !level) return '';

      const ranges = {
        speaker: {
          international: [13,15],
          national: [11,12],
          regional: [9,10],
          provincial: [7,8],
          municipal: [4,6],
          school: [1,3],
        },
        resource: {
          international: [11,12],
          national: [9,10],
          regional: [7,8],
          provincial: [5,6],
          municipal: [3,4],
          school: [1,2],
        },
        participant: {
          international: [9,10],
          national: [7,8],
          regional: [5,6],
          provincial: [3,4],
          municipal: [2,2],
          school: [1,1],
        },
      };

      const r = ranges?.[role]?.[level];
      if (!r) return '';

      return `Range: ${r[0]}-${r[1]} pts`;
    },

    ptsC(row) {
      const role = (row?.role || '').trim();
      const level = (row?.level || '').trim();
      if (!role || !level) return 0;

      const minMap = {
        speaker:     { international: 13, national: 11, regional: 9, provincial: 7, municipal: 4, school: 1 },
        resource:    { international: 11, national: 9,  regional: 7, provincial: 5, municipal: 3, school: 1 },
        participant: { international: 9,  national: 7,  regional: 5, provincial: 3, municipal: 2, school: 1 },
      };

      return Number(minMap?.[role]?.[level] || 0);
    },

      rawC() {
        const prev = Number(this.c_prev || 0) / 3;
        return this.bucketedRows('c').reduce((t, r) => t + Number(r.points || 0), 0) + prev;
      },

    rawTotal() {
      return this.rawA() + this.rawB() + this.rawC();
    },

    cappedTotal() {
      return this.cap(
        this.cap(this.rawA(), 140) +
        this.cap(this.rawB(), 20) +
        this.cap(this.rawC(), 20),
        140
      );
    },

    emitScore(points) {
      document.dispatchEvent(new CustomEvent('section-score', {
        detail: { section: '1', points: Number(points || 0) },
      }));
    },
  }
}
</script>

