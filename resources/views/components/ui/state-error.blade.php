@props([
    'title' => 'Something went wrong',
    'message' => 'Please retry in a few seconds.',
])

<div class="ux-state ux-state-error" role="alert">
    <p class="font-semibold">{{ $title }}</p>
    <p class="mt-1">{{ $message }}</p>
</div>
