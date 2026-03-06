@props([
    'id' => null,
    'loadingMessage' => 'Loading data...',
    'rows' => 6,
    'cols' => 6,
])

<div {{ $attributes->merge(['id' => $id, 'data-ux-panel' => '1', 'aria-busy' => 'false', 'class' => 'space-y-4']) }}>
    <div data-ux-panel-skeleton class="hidden">
        @if (isset($skeleton))
            {{ $skeleton }}
        @else
            <x-ui.skeleton-table :rows="$rows" :cols="$cols" />
        @endif
    </div>
    <div data-ux-panel-content>
        {{ $slot }}
    </div>
    <p class="sr-only" data-ux-panel-status aria-live="polite" aria-atomic="true">{{ $loadingMessage }}</p>
</div>
