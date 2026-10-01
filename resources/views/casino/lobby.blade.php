@extends('layouts.casino')

@section('page-title', 'Casino lobby')

@section('content')
    @php
        $games = $casinoGames ?? \App\Support\CasinoCatalog::games();
        $categories = \App\Support\CasinoCatalog::categories();
        $featured = array_slice($games, 0, 3);
        $playFirst = auth()->check() && isset($games[0]) ? route($games[0]['route']) : route('login');
    @endphp

    <div class="casino-lobby space-y-10">
        <section class="casino-lobby-hero" data-casino-reveal>
            <span class="casino-bulbs" aria-hidden="true"></span>
            <div class="casino-lobby-hero__copy">
                <p class="casino-eyebrow"><span></span> ALLINBET · CLUBE DE JOGOS VIRTUAIS</p>
                <h1>Escolha a sua<br><em class="casino-shimmer-text">próxima mesa.</em></h1>
                <p>Jogos originais, resultados transparentes e créditos virtuais sem valor monetário.</p>
                <div class="casino-lobby-hero__actions">
                    <a class="casino-button casino-button--primary relative" href="{{ $playFirst }}" wire:navigate><span class="casino-cta-ring" aria-hidden="true"></span>Jogar agora <span aria-hidden="true">↗</span></a>
                    <span class="casino-lobby-count"><strong data-casino-count-to="{{ count($games) }}">{{ count($games) }}</strong><span>jogos<br>disponíveis</span></span>
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

        <section class="casino-lobby-section" aria-label="Em destaque" data-casino-reveal>
            <div class="casino-section-heading">
                <div><p class="casino-eyebrow">MESAS DA CASA</p><h2>Em destaque</h2></div>
            </div>
            <div class="casino-featured-strip">
                @foreach ($featured as $game)
                    <a class="casino-featured casino-game-card--{{ $game['slug'] }}" href="{{ auth()->check() ? route($game['route']) : route('login') }}" wire:navigate>
                        <p class="casino-eyebrow">{{ $game['tag'] }}</p>
                        <h3>{{ $game['name'] }}</h3>
                        <p>{{ $game['blurb'] }}</p>
                        <span class="casino-featured__cta">Entrar na mesa ↗</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="casino-lobby-section" aria-label="Estatísticas reais" data-casino-reveal>
            <livewire:casino.lobby-stats />
        </section>

        <section class="casino-lobby-section" aria-label="Jogos disponíveis">
            <div class="casino-lobby-toolbar" data-casino-reveal>
                <div><p class="casino-eyebrow">A CASA ESTÁ ABERTA</p><h2 class="mt-1 font-[family-name:var(--font-display)] text-3xl font-medium">Todos os jogos</h2></div>
                <span class="casino-section-count">{{ str_pad((string) count($games), 2, '0', STR_PAD_LEFT) }} EXPERIÊNCIAS</span>
            </div>

            <div
                x-data="{
                    filter: 'all',
                    q: '',
                    matches(category, haystack) {
                        const query = this.q.trim().toLowerCase();
                        const categoryOk = this.filter === 'all' || this.filter === 'originais' || this.filter === category;
                        const queryOk = query === '' || haystack.includes(query);
                        return categoryOk && queryOk;
                    }
                }"
            >
                <div class="casino-lobby-toolbar mb-4">
                    <div class="casino-tabs" role="tablist" aria-label="Filtrar jogos">
                        @foreach ($categories as $key => $label)
                            <button type="button" role="tab" x-on:click="filter = '{{ $key }}'" x-bind:class="{ 'is-active': filter === '{{ $key }}' }" x-bind:aria-selected="filter === '{{ $key }}'">{{ $label }}</button>
                        @endforeach
                    </div>
                    <label class="sr-only" for="lobby-search">Procurar jogos</label>
                    <input id="lobby-search" x-model="q" type="search" placeholder="Filtrar por nome…" class="casino-field casino-lobby-search">
                </div>

                <div class="casino-game-grid">
                    @foreach ($games as $game)
                        <x-casino.game-tile :game="$game" />
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
            <section class="casino-card casino-fair p-5">
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
                <div class="casino-card casino-lobby-history casino-history-wrap"><livewire:casino.history /></div>
                @php
                    $recent = \App\Models\GameRound::where('user_id', auth()->id())->latest()->limit(20)->pluck('game')->map(fn ($game) => $game instanceof \BackedEnum ? $game->value : (string) $game)->unique()->take(3);
                @endphp
                @if ($recent->isNotEmpty())
                    <div class="casino-card p-5">
                        <p class="casino-eyebrow">CONTINUAR A JOGAR</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($recent as $g)
                                @if (Route::has('casino.'.$g))
                                    <a class="casino-button casino-button--secondary" href="{{ route('casino.'.$g) }}" wire:navigate>{{ ucfirst($g) }} ↗</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="casino-card casino-bonus-card" x-on:daily-bonus-claimed.window="$el.classList.add('casino-bonus-card--claimed')">
                    <div class="casino-bonus-card__seal" aria-hidden="true"><span>+</span></div>
                    <div class="casino-bonus-card__copy"><p class="casino-eyebrow">PRESENTE DIÁRIO</p><h2>Bónus de créditos</h2><p>Um pequeno reforço virtual para a próxima mesa.</p><strong>{{ config('casino.daily_bonus', 100) }} <small>CRÉDITOS</small></strong></div>
                    <div class="casino-bonus-card__action">
                        @if (auth()->user()->hasVerifiedEmail())
                            <livewire:casino.daily-bonus />
                        @else
                            <a class="casino-button casino-button--secondary" href="{{ route('verification.notice') }}" wire:navigate>Verifique o e-mail para receber o bónus</a>
                        @endif
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
