@extends('layouts.casino')

@section('page-title', 'Casino lobby')

@section('content')
    @php
        $games = [
            ['route' => 'casino.coinflip', 'name' => 'Coinflip', 'code' => '01', 'kind' => 'coinflip', 'cat' => 'sorte', 'tag' => 'CLÁSSICO', 'label' => 'Cara ou coroa'],
            ['route' => 'casino.dice', 'name' => 'Dados', 'code' => '02', 'kind' => 'dice', 'cat' => 'dados', 'tag' => 'NOVO', 'label' => 'Abaixo ou acima'],
            ['route' => 'casino.roulette', 'name' => 'Roleta europeia', 'code' => '03', 'kind' => 'roulette', 'cat' => 'sorte', 'tag' => 'NOVO', 'label' => 'Roda de zero único'],
            ['route' => 'casino.blackjack', 'name' => 'Blackjack', 'code' => '04', 'kind' => 'blackjack', 'cat' => 'cartas', 'tag' => 'NOVO', 'label' => 'Pedir, parar ou dobrar'],
            ['route' => 'casino.slots', 'name' => 'Slots', 'code' => '05', 'kind' => 'slots', 'cat' => 'sorte', 'tag' => 'NOVO', 'label' => 'Rolos e linhas de prémio'],
        ];
    @endphp

    <div class="casino-lobby space-y-10">
        <section class="casino-lobby-hero" data-casino-reveal>
            <span class="casino-bulbs" aria-hidden="true"></span>
            <div class="casino-lobby-hero__copy">
                <p class="casino-eyebrow"><span></span> ALLINBET · CLUBE DE JOGOS VIRTUAIS</p>
                <h1>Escolha a sua<br><em class="casino-shimmer-text">próxima mesa.</em></h1>
                <p>Jogos originais, resultados transparentes e créditos virtuais sem valor monetário.</p>
                <div class="casino-lobby-hero__actions">
                    <a class="casino-button casino-button--primary relative" href="{{ auth()->check() ? route('casino.coinflip') : route('login') }}" wire:navigate><span class="casino-cta-ring" aria-hidden="true"></span>Jogar agora <span aria-hidden="true">↗</span></a>
                    <span class="casino-lobby-count"><strong data-casino-count-to="5">5</strong><span>jogos<br>disponíveis</span></span>
                </div>
            </div>
            <div class="casino-lobby-hero__art" aria-hidden="true">
                <div class="casino-orbit casino-orbit--outer"></div>
                <div class="casino-orbit casino-orbit--inner"></div>
                <div class="casino-hero-chip"><span>ALLIN</span><strong>100</strong><small>CRÉDITOS</small></div>
                <div class="casino-hero-spark casino-hero-spark--one"></div>
                <div class="casino-hero-spark casino-hero-spark--two"></div>
                <p>PLAY<br><em>VIRTUAL</em></p>
                <span class="casino-float-chip" style="--x:8%; --y:18%; --d:0s">100</span>
                <span class="casino-float-chip" style="--x:84%; --y:12%; --d:-1.4s">25</span>
                <span class="casino-float-chip" style="--x:78%; --y:76%; --d:-2.6s">5</span>
            </div>
        </section>

        <section class="casino-lobby-section" aria-label="Estatísticas reais" data-casino-reveal>
            <livewire:casino.lobby-stats />
        </section>

        <section class="casino-lobby-section" aria-label="Jogos disponíveis">
            <div class="casino-section-heading" data-casino-reveal>
                <div><p class="casino-eyebrow">A CASA ESTÁ ABERTA</p><h2>Jogos em destaque</h2></div>
                <span class="casino-section-count">05 EXPERIÊNCIAS</span>
            </div>

            <div x-data="{ filter: 'all' }">
                <div class="casino-tabs" role="tablist" aria-label="Filtrar jogos">
                    @foreach (['all' => 'Todos', 'sorte' => 'Sorte', 'cartas' => 'Cartas', 'dados' => 'Dados'] as $key => $label)
                        <button type="button" role="tab" x-on:click="filter = '{{ $key }}'" x-bind:class="{ 'is-active': filter === '{{ $key }}' }" x-bind:aria-selected="filter === '{{ $key }}'">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="casino-game-grid">
            @foreach ($games as $game)
                <a
                    href="{{ auth()->check() ? route($game['route']) : route('login') }}"
                    class="casino-game-card casino-game-card--{{ $game['kind'] }}"
                    wire:navigate
                    data-casino-reveal
                    x-show="filter === 'all' || filter === '{{ $game['cat'] }}'"
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
                    x-bind:style="`--tilt-x:${tiltX}deg; --tilt-y:${tiltY}deg; --glow-x:${glowX}%; --glow-y:${glowY}%`"
                >
                    <span class="casino-game-card__aura" aria-hidden="true"></span>
                    <span class="casino-game-card__shine" aria-hidden="true"></span>
                    <div class="casino-game-card__art" aria-hidden="true">
                        @switch($game['kind'])
                            @case('coinflip')
                                <svg viewBox="0 0 120 120" fill="none"><circle cx="60" cy="60" r="45" stroke="currentColor" stroke-width="3"/><circle cx="60" cy="60" r="36" stroke="currentColor" stroke-opacity=".45" stroke-width="1.5"/><path d="M60 27v66M46 43h18a9 9 0 0 1 0 18H49a9 9 0 0 0 0 18h25" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><path d="m60 20 5 8-5 8-5-8 5-8Zm0 56 5 8-5 8-5-8 5-8Z" fill="currentColor"/></svg>
                                @break
                            @case('dice')
                                <svg viewBox="0 0 120 120" fill="none"><path d="m60 15 39 22v46l-39 22-39-22V37l39-22Z" stroke="currentColor" stroke-width="3"/><path d="m21 37 39 23 39-23M60 60v45" stroke="currentColor" stroke-width="2"/><circle cx="43" cy="42" r="4" fill="currentColor"/><circle cx="77" cy="42" r="4" fill="currentColor"/><circle cx="43" cy="77" r="4" fill="currentColor"/><circle cx="77" cy="77" r="4" fill="currentColor"/><circle cx="60" cy="60" r="4" fill="currentColor"/></svg>
                                @break
                            @case('roulette')
                                <svg viewBox="0 0 120 120" fill="none"><circle cx="60" cy="60" r="47" stroke="currentColor" stroke-width="3"/><circle cx="60" cy="60" r="37" stroke="currentColor" stroke-opacity=".5" stroke-width="1.5"/><path d="M60 13v24m33-10L77 47m30 13H83M93 93 77 77m-17 30V83m-33 10 16-16M13 60h24m-10-33 16 16" stroke="currentColor" stroke-width="3"/><circle cx="60" cy="60" r="16" stroke="currentColor" stroke-width="3"/><circle cx="60" cy="60" r="4" fill="currentColor"/></svg>
                                @break
                            @case('blackjack')
                                <svg viewBox="0 0 120 120" fill="none"><rect x="22" y="19" width="48" height="70" rx="7" transform="rotate(-12 22 19)" stroke="currentColor" stroke-width="3"/><rect x="50" y="27" width="48" height="70" rx="7" transform="rotate(9 50 27)" stroke="currentColor" stroke-width="3"/><path d="M40 36c7-8 19-1 15 8-2 5-9 10-9 10s-8-5-10-10c-3-6 0-9 4-8Zm33 11c7-8 19-1 15 8-2 5-9 10-9 10s-8-5-10-10c-3-6 0-9 4-8Z" fill="currentColor"/></svg>
                                @break
                            @default
                                <svg viewBox="0 0 120 120" fill="none"><rect x="15" y="28" width="27" height="64" rx="5" stroke="currentColor" stroke-width="3"/><rect x="47" y="20" width="27" height="80" rx="5" stroke="currentColor" stroke-width="3"/><rect x="79" y="28" width="27" height="64" rx="5" stroke="currentColor" stroke-width="3"/><path d="M23 47h11m-11 18h11m-11 18h11m25-43h11m-11 20h11m-11 20h11m21-15h11m-11 18h11" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><path d="m60 8 3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7Zm35 79 2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5Z" fill="currentColor"/></svg>
                        @endswitch
                    </div>
                    <span class="casino-game-card__index">{{ $game['code'] }}</span>
                    <div class="casino-game-card__copy">
                        <span class="casino-game-card__tag">{{ $game['tag'] }} · JOGO {{ $game['code'] }}</span>
                        <h3>{{ $game['name'] }}</h3>
                        <p>{{ $game['label'] }}</p>
                    </div>
                    <span class="casino-game-card__arrow" aria-hidden="true">↗</span>
                </a>
            @endforeach
                </div>
            </div>
        </section>

        <section class="casino-win-section" data-casino-reveal>
            <div class="casino-section-heading"><div><p class="casino-eyebrow">DA MESA PARA O TICKER</p><h2>Últimas vitórias</h2></div><span class="casino-live-status"><i></i> ATUALIZAÇÃO AO VIVO</span></div>
            <livewire:casino.recent-wins />
        </section>

        <section class="casino-lobby-bottom grid gap-6 md:grid-cols-2" data-casino-reveal>
            <livewire:casino.top-wins />
            <section class="casino-card casino-fair">
                <div class="casino-fair__icon" aria-hidden="true">🔐</div>
                <div>
                    <p class="casino-eyebrow">JOGO TRANSPARENTE</p>
                    <h2 class="text-lg font-semibold">Cada resultado pode ser verificado</h2>
                    <p class="mt-2 text-sm text-zinc-400">As rondas concluídas guardam o compromisso da semente e podem ser verificadas individualmente no histórico.</p>
                </div>
                <a class="casino-button casino-button--secondary" href="{{ route('casino.history') }}" wire:navigate>Ver histórico ↗</a>
            </section>
        </section>

        @auth
            <section class="casino-lobby-bottom" data-casino-reveal>
                <div class="casino-section-heading"><div><p class="casino-eyebrow">SUA ATIVIDADE</p><h2>Movimento recente</h2></div></div>
                <div class="casino-card casino-lobby-history"><livewire:casino.history /></div>
                @php
                    $recent = \App\Models\GameRound::where('user_id', auth()->id())->latest()->limit(20)->pluck('game')->map(fn ($game) => $game instanceof \BackedEnum ? $game->value : (string) $game)->unique()->take(3);
                @endphp
                @if ($recent->isNotEmpty())
                    <div class="casino-card">
                        <p class="casino-eyebrow">CONTINUAR A JOGAR</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($recent as $g)
                                @if (Route::has('casino.'.$g))
                                    <a class="casino-button casino-button--secondary" href="{{ route('casino.'.$g) }}" wire:navigate>{{ ucfirst($g) }} ↗</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="casino-card casino-bonus-card" x-data="{
                    remaining: 0,
                    timer: null,
                    init() { this.update(); this.timer = window.setInterval(() => this.update(), 1000); },
                    destroy() { window.clearInterval(this.timer); },
                    update() { const now = new Date(); const next = new Date(now); next.setHours(24, 0, 0, 0); this.remaining = Math.max(0, Math.floor((next - now) / 1000)); },
                    pad(value) { return String(value).padStart(2, '0'); },
                    get countdown() { const h = Math.floor(this.remaining / 3600); const m = Math.floor((this.remaining % 3600) / 60); const s = this.remaining % 60; return `${this.pad(h)}:${this.pad(m)}:${this.pad(s)}`; }
                }" x-on:daily-bonus-claimed.window="$el.classList.add('casino-bonus-card--claimed')">
                    <div class="casino-bonus-card__seal" aria-hidden="true"><span>+</span></div>
                    <div class="casino-bonus-card__copy"><p class="casino-eyebrow">PRESENTE DIÁRIO</p><h2>Bónus de créditos</h2><p>Um pequeno reforço virtual para a próxima mesa.</p><strong>{{ config('casino.daily_bonus', 100) }} <small>CRÉDITOS</small></strong></div>
                    <div class="casino-bonus-card__action">
                        @if (auth()->user()->hasVerifiedEmail())
                            <livewire:casino.daily-bonus />
                        @else
                            <a class="casino-button casino-button--secondary" href="{{ route('verification.notice') }}" wire:navigate>Verifique o e-mail para receber o bónus</a>
                        @endif
                        <p>Próxima atualização em <time x-text="countdown" class="tabular-nums"></time></p>
                    </div>
                </div>
            </section>
        @else
            <section class="casino-bonus-card casino-bonus-card--guest" data-casino-reveal>
                <div class="casino-bonus-card__seal" aria-hidden="true"><span>+</span></div>
                <div class="casino-bonus-card__copy"><p class="casino-eyebrow">PRESENTE DIÁRIO</p><h2>Créditos virtuais à sua espera</h2><p>Crie uma conta para ver o bónus disponível na sua carteira.</p></div>
                <a class="casino-button casino-button--primary" href="{{ route('register') }}" wire:navigate>Criar conta <span aria-hidden="true">↗</span></a>
            </section>
        @endauth
    </div>
@endsection
