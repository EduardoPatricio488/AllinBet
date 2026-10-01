@props([
    'target',
])

<div wire:loading.flex wire:target="{{ $target }}" class="casino-round-overlay" role="status" aria-live="polite">
    <span class="casino-round-overlay__spinner" aria-hidden="true"></span>
    <span>O servidor está a processar a ronda…</span>
</div>
