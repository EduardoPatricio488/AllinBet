@props([
    'title',
])

<div x-data="{ open: false }" {{ $attributes }}>
    <button type="button" class="casino-button casino-button--quiet" x-on:click="open = true">{{ $trigger }}</button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        x-on:click.self="open = false"
        x-on:keydown.escape.window="open = false"
        class="casino-modal-backdrop"
        role="presentation"
    >
        <section
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="casino-modal-panel"
            role="dialog"
            aria-modal="true"
            aria-labelledby="casino-modal-title"
            tabindex="-1"
        >
            <header class="mb-4 flex items-start justify-between gap-4">
                <h2 id="casino-modal-title" class="font-display text-xl text-casino-gold-bright">{{ $title }}</h2>
                <button type="button" class="casino-button casino-button--quiet min-h-9 px-3 py-1 text-xs" x-on:click="open = false">Fechar</button>
            </header>
            <div>{{ $slot }}</div>
        </section>
    </div>
</div>
