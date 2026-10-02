@props([
    'game',
])

<article
    class="casino-game-card casino-game-card--{{ $game['slug'] }}"
    data-casino-reveal
    x-show="matches('{{ $game['category'] }}', '{{ strtolower($game['name'].' '.$game['label'].' '.$game['tag']) }}', '{{ $game['slug'] }}')"
    x-transition
    x-data="{
        tiltX: 0, tiltY: 0, glowX: 50, glowY: 50,
        move(event) {
            if (window.matchMedia('(hover: none)').matches) return;
            const rect = this.$el.getBoundingClientRect();
            this.tiltX = ((event.clientY - rect.top) / rect.height - .5) * -5;
            this.tiltY = ((event.clientX - rect.left) / rect.width - .5) * 6;
            this.glowX = ((event.clientX - rect.left) / rect.width) * 100;
            this.glowY = ((event.clientY - rect.top) / rect.height) * 100;
        },
        reset() { this.tiltX = 0; this.tiltY = 0; this.glowX = 50; this.glowY = 50; }
    }"
    x-on:pointermove="move($event)"
    x-on:pointerleave="reset()"
    x-bind:style="\`--tilt-x:\${tiltX}deg; --tilt-y:\${tiltY}deg; --glow-x:\${glowX}%; --glow-y:\${glowY}%\`"
>
    <a
        href="{{ auth()->check() ? route($game['route']) : route('login') }}"
        class="casino-game-card__link"
        wire:navigate
        aria-label="Abrir {{ $game['name'] }}"
    >
        <span class="casino-game-card__aura" aria-hidden="true"></span>
        <span class="casino-game-card__shine" aria-hidden="true"></span>
        <div class="casino-game-card__art" aria-hidden="true">
            <x-casino.game-mark :slug="$game['slug']" />
        </div>
        <span class="casino-game-card__index">{{ $game['code'] }}</span>
        <div class="casino-game-card__copy">
            <span class="casino-game-card__tag">{{ $game['tag'] }} · JOGO {{ $game['code'] }}</span>
            <h3>{{ $game['name'] }}</h3>
            <p>{{ $game['label'] }}</p>
        </div>
        <span class="casino-game-card__arrow" aria-hidden="true">↗</span>
    </a>

    <button type="button"
            class="casino-game-card__favorite"
            x-on:click.stop="toggleFavorite('{{ $game['slug'] }}')"
            x-bind:class="{ 'is-active': isFavorite('{{ $game['slug'] }}') }"
            x-bind:aria-pressed="isFavorite('{{ $game['slug'] }}')"
            aria-label="Adicionar {{ $game['name'] }} aos favoritos">
        <span aria-hidden="true">★</span>
    </button>
</article>