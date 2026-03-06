@props([
    'title' => 'No records found',
    'message' => 'Try adjusting your search or filters.',
])

<div class="ux-state ux-state-empty" role="status" aria-live="polite">
    <p class="font-semibold text-gray-700">{{ $title }}</p>
    <p class="mt-1">{{ $message }}</p>
</div>
