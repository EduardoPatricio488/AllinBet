@extends('layouts.casino')

@section('page-title', 'Casino lobby')

@section('content')
    @php
        $games = $casinoGames ?? \App\Support\CasinoCatalog::games();
        $categories = \App\Support\CasinoCatalog::categories();
        $featured = array_slice($games, 0, 3);
        $playFirst = auth()->check() && isset($games[0]) ? route($games[0]['route']) : route('login');
        // índice para o filtro e para o estado vazio (a categoria vem do catálogo)
        $index = collect($games)->map(fn ($g) => ['c' => $g['category'] ?? 'originais', 'h' => mb_strtolower(($g['name'] ?? '').' '.($g['tag'] ?? '').' '.($g['blurb'] ?? '')), 's' => $g['slug'] ?? ''])->values();
    @endphp

    <noscript><style>.lb-reveal{opacity:1!important;transform:none!important;filter:none!important}</style></noscript>

    <div class="casino-lobby lb space-y-10"
         x-data="{
            spot(e) { const c = e.target.closest('.lb-spot, .casino-game-card'); if (!c) return; const r = c.getBoundingClientRect(); c.style.setProperty('--mx', (e.clientX - r.left) + 'px'); c.style.setProperty('--my', (e.clientY - r.top) + 'px'); }
         }"
         x-on:pointermove="spot($event)">

        <div class="lb-aurora" aria-hidden="true"><i></i><i></i><i></i></div>

        {{-- HERO --}}
        <section class="casino-lobby-hero lb-hero lb-reveal" x-intersect.once.threshold.10="$el.classList.add('is-in')"
                 x-data="{ i: 0, names: @js(array_values(array_column($games, 'name'))), t: null, init() { if (this.names.length > 1) this.t = setInterval(() => { this.i = (this.i + 1) % this.names.length; }, 2400); }, destroy() { clearInterval(this.t); } }"
                 x-on:pointermove="const r = $el.getBoundingClientRect(); $el.style.setProperty('--px', (($event.clientX - r.left) / r.width - .5) * 2); $el.style.setProperty('--py', (($event.clientY - r.top) / r.height - .5) * 2)"
                 x-on:pointerleave="$el.style.setProperty('--px', 0); $el.style.setProperty('--py', 0)">
            <span class="casino-bulbs" aria-hidden="true"></span>
            <span class="lb-hero-glow" aria-hidden="true"></span>
            <div class="casino-lobby-hero__copy">
                <p class="casino-eyebrow"><span></span> ALLINBET · CLUBE DE JOGOS VIRTUAIS</p>
                <h1>Escolha a sua<br><em class="casino-shimmer-text">próxima mesa.</em></h1>
                <p class="lb-swap-line">Agora a jogar: <span class="lb-swap" aria-hidden="true">
                    <template x-for="(n, k) in names" :key="k"><b x-show="i === k" x-transition.opacity.duration.400ms x-text="n"></b></template>
                </span></p>
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
                <span class="casino-float-chip" style="--x:8%; --y:18%; --d:0s; --k:1.4">100</span>
                <span class="casino-float-chip" style="--x:84%; --y:12%; --d:-1.4s; --k:-1">25</span>
                <span class="casino-float-chip" style="--x:78%; --y:76%; --d:-2.6s; --k:2">5</span>
            </div>
        </section>

        {{-- FAIXA EM MOVIMENTO --}}
        <div class="lb-marquee" aria-hidden="true">
            <div class="lb-marquee__track">
                @for ($k = 0; $k < 2; $k++)
                    @foreach ($games as $g)<span>{{ $g['name'] }}</span><i>✦</i>@endforeach
                    <span>Créditos virtuais</span><i>✦</i><span>Resultados verificáveis</span><i>✦</i>
                @endfor
            </div>
        </div>

        {{-- DESTAQUES (bento) --}}
        <section class="casino-lobby-section" aria-label="Em destaque">
            <div class="casino-section-heading lb-reveal" x-intersect.once="$el.classList.add('is-in')">
                <div><p class="casino-eyebrow">MESAS DA CASA</p><h2 class="lb-underline">Em destaque</h2></div>
            </div>
            <div class="lb-bento">
                @foreach ($featured as $game)
                    <a class="casino-featured lb-feat lb-spot lb-reveal casino-game-card--{{ $game['slug'] }}" style="--i: {{ $loop->index }}"
                       href="{{ auth()->check() ? route($game['route']) : route('login') }}" wire:navigate
                       x-intersect.once.threshold.10="$el.classList.add('is-in')">
                        <span class="lb-feat__num" aria-hidden="true">0{{ $loop->iteration }}</span>
                        <p class="casino-eyebrow">{{ $game['tag'] }}</p>
                        <h3>{{ $game['name'] }}</h3>
                        <p>{{ $game['blurb'] }}</p>
                        <span class="casino-featured__cta">Entrar na mesa <i aria-hidden="true">↗</i></span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="casino-lobby-section lb-reveal" aria-label="Estatísticas reais" x-intersect.once="$el.classList.add('is-in')">
            <livewire:casino.lobby-stats />
        </section>

        {{-- TODOS OS JOGOS --}}
        <section class="casino-lobby-section" aria-label="Jogos disponíveis">
            <div class="casino-lobby-toolbar lb-reveal" x-intersect.once="$el.classList.add('is-in')">
                <div><p class="casino-eyebrow">A CASA ESTÁ ABERTA</p><h2 class="lb-underline mt-1 font-[family-name:var(--font-display)] text-3xl font-medium">Todos os jogos</h2></div>
                <span class="casino-section-count">{{ str_pad((string) count($games), 2, '0', STR_PAD_LEFT) }} EXPERIÊNCIAS</span>
            </div>

            <div x-data="{
                    filter: 'all', q: '', ink: '', games: @js($index), favorites: [],
                    init() {
                        try {
                            const saved = JSON.parse(localStorage.getItem('allinbet:casino:favorites') || '[]');
                            this.favorites = Array.isArray(saved) ? saved.filter((slug) => typeof slug === 'string') : [];
                        } catch { this.favorites = []; }
                    },
                    isFavorite(slug) { return this.favorites.includes(slug); },
                    toggleFavorite(slug) {
                        this.favorites = this.isFavorite(slug)
                            ? this.favorites.filter((item) => item !== slug)
                            : [...this.favorites, slug];
                        try { localStorage.setItem('allinbet:casino:favorites', JSON.stringify(this.favorites)); } catch {}
                    },
                    matches(category, haystack, slug) {
                        const query = this.q.trim().toLowerCase();
                        const categoryMatch = this.filter === 'all'
                            || this.filter === 'originais'
                            || this.filter === category
                            || (this.filter === 'favorites' && this.isFavorite(slug));
                        return categoryMatch && (query === '' || haystack.includes(query));
                    },
                    get none() { return this.games.length > 0 && !this.games.some((g) => this.matches(g.c, g.h, g.s)); },
                    slide(el) { this.ink = 'width:' + el.offsetWidth + 'px;transform:translateX(' + el.offsetLeft + 'px)'; },
                    reset() { this.q = ''; this.filter = 'all'; this.$nextTick(() => this.slide($refs.tabs.querySelector('[data-filter=all]'))); }
                }"
                                x-init="$nextTick(() => slide($refs.tabs.firstElementChild))"
                x-on:resize.window.debounce.150ms="slide($refs.tabs.querySelector('.is-active') || $refs.tabs.firstElementChild)"
                x-on:keydown.window="if ($event.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes($event.target.tagName)) { $event.preventDefault(); $refs.search.focus(); }">

                <div class="casino-lobby-toolbar mb-4">
                    <div class="casino-tabs lb-tabs" role="tablist" aria-label="Filtrar jogos" x-ref="tabs">
                        @foreach ($categories as $key => $label)
                            <button type="button" role="tab" data-filter="{{ $key }}" x-on:click="filter = '{{ $key }}'; slide($el)" x-bind:class="{ 'is-active': filter === '{{ $key }}' }" x-bind:aria-selected="filter === '{{ $key }}'">{{ $label }}</button>
                        @endforeach
                        <button type="button" role="tab" data-filter="favorites" x-on:click="filter = 'favorites'; slide($el)" x-bind:class="{ 'is-active': filter === 'favorites' }" x-bind:aria-selected="filter === 'favorites'">Favoritos <span x-text="'(' + favorites.length + ')'"></span></button>
                        <i class="lb-ink" :style="ink" aria-hidden="true"></i>
                    </div>
                    <div class="lb-search">
                        <label class="sr-only" for="lobby-search">Procurar jogos</label>
                        <input id="lobby-search" x-ref="search" x-model="q" type="search" placeholder="Filtrar por nome…" class="casino-field casino-lobby-search">
                        <kbd aria-hidden="true">/</kbd>
                    </div>
                </div>

                <div class="casino-game-grid lb-grid" x-show="!none">
                    @foreach ($games as $game)
                        <div class="lb-reveal lb-tilewrap" style="--i: {{ $loop->index % 6 }}" x-intersect.once.threshold.05="$el.classList.add('is-in')">
                            <x-casino.game-tile :game="$game" />
                        </div>
                    @endforeach
                </div>

                <div class="lb-empty" x-show="none" x-cloak x-transition.opacity>
                    <span aria-hidden="true">🎲</span>
                    <p>Nenhum jogo encontrado.</p>
                    <button type="button" class="casino-button casino-button--secondary" x-on:click="reset()">Limpar filtros</button>
                </div>
            </div>
        </section>

        {{-- VITÓRIAS --}}
        <section class="casino-win-section lb-reveal" x-intersect.once="$el.classList.add('is-in')">
            <div class="casino-section-heading"><div><p class="casino-eyebrow">DA MESA PARA O TICKER</p><h2 class="lb-underline">Últimas vitórias</h2></div><span class="casino-live-status"><i></i> ATUALIZAÇÃO AO VIVO</span></div>
            <livewire:casino.recent-wins />
        </section>

        <section class="casino-lobby-bottom grid gap-6 md:grid-cols-2">
            <div class="lb-reveal" x-intersect.once="$el.classList.add('is-in')"><livewire:casino.top-wins /></div>
            <section class="casino-card casino-fair p-5 lb-reveal lb-spot" style="--i: 1" x-intersect.once="$el.classList.add('is-in')">
                <div class="casino-fair__icon lb-lock" aria-hidden="true">🔐</div>
                <div>
                    <p class="casino-eyebrow">JOGO TRANSPARENTE</p>
                    <h2 class="text-lg font-semibold">Cada resultado pode ser verificado</h2>
                    <p class="mt-2 text-sm text-zinc-400">As rondas concluídas guardam o compromisso da semente e podem ser verificadas individualmente no histórico.</p>
                </div>
                <a class="casino-button casino-button--secondary" href="{{ route('casino.history') }}" wire:navigate>Ver histórico ↗</a>
            </section>
        </section>

        @auth
            <section class="casino-lobby-bottom">
                <div class="casino-section-heading lb-reveal" x-intersect.once="$el.classList.add('is-in')"><div><p class="casino-eyebrow">SUA ATIVIDADE</p><h2 class="lb-underline">Movimento recente</h2></div></div>
                <div class="casino-card casino-lobby-history casino-history-wrap lb-reveal" x-intersect.once="$el.classList.add('is-in')"><livewire:casino.history /></div>
                @php
                    $recent = \App\Models\GameRound::where('user_id', auth()->id())->latest()->limit(20)->pluck('game')->map(fn ($game) => $game instanceof \BackedEnum ? $game->value : (string) $game)->unique()->take(3);
                @endphp
                @if ($recent->isNotEmpty())
                    <div class="casino-card p-5 lb-reveal" x-intersect.once="$el.classList.add('is-in')">
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
                <div class="casino-card casino-bonus-card lb-reveal lb-bonus" x-intersect.once="$el.classList.add('is-in')" x-on:daily-bonus-claimed.window="$el.classList.add('casino-bonus-card--claimed')">
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
            <section class="casino-bonus-card casino-bonus-card--guest lb-reveal lb-bonus" x-intersect.once="$el.classList.add('is-in')">
                <div class="casino-bonus-card__seal" aria-hidden="true"><span>+</span></div>
                <div class="casino-bonus-card__copy"><p class="casino-eyebrow">PRESENTE DIÁRIO</p><h2>Créditos virtuais à sua espera</h2><p>Crie uma conta para ver o bónus disponível na sua carteira.</p></div>
                <a class="casino-button casino-button--primary" href="{{ route('register') }}" wire:navigate>Criar conta <span aria-hidden="true">↗</span></a>
            </section>
        @endauth

        <style>
        .lb { position: relative; isolation: isolate; --ease: cubic-bezier(.16, 1, .3, 1); --gold: #f5d778; }
        .lb-reveal { opacity: 0; transform: translateY(28px) scale(.985); filter: blur(6px); transition: opacity .7s var(--ease), transform .7s var(--ease), filter .7s var(--ease); transition-delay: calc(var(--i, 0) * 80ms); }
        .lb-reveal.is-in { opacity: 1; transform: none; filter: none; }
        .lb-aurora { position: absolute; inset: -4rem -2rem auto; height: 36rem; z-index: -1; pointer-events: none; filter: blur(70px); opacity: .55; }
        .lb-aurora i { position: absolute; width: 28rem; height: 28rem; border-radius: 50%; animation: lb-blob 16s ease-in-out infinite alternate; }
        .lb-aurora i:nth-child(1) { left: 2%; top: 0; background: rgba(124, 58, 237, .45); }
        .lb-aurora i:nth-child(2) { right: 4%; top: 6rem; background: rgba(35, 217, 154, .3); animation-delay: -5s; }
        .lb-aurora i:nth-child(3) { left: 38%; top: 12rem; background: rgba(245, 196, 81, .28); animation-delay: -9s; }
        @keyframes lb-blob { to { transform: translate(6rem, 3rem) scale(1.25); } }
        .lb-hero { --px: 0; --py: 0; overflow: hidden; }
        .lb-hero-glow { position: absolute; width: 34rem; height: 34rem; left: calc(50% + var(--px) * 28%); top: calc(50% + var(--py) * 28%); translate: -50% -50%; border-radius: 50%; background: radial-gradient(circle, rgba(245, 215, 120, .16), transparent 62%); pointer-events: none; transition: left .5s ease-out, top .5s ease-out; }
        .lb-hero .casino-hero-chip { translate: calc(var(--px) * -18px) calc(var(--py) * -14px); transition: translate .35s ease-out; }
        .lb-hero .casino-float-chip { translate: calc(var(--px) * var(--k, 1) * 22px) calc(var(--py) * var(--k, 1) * 18px); transition: translate .35s ease-out; }
        .lb-hero .casino-orbit--outer { translate: calc(var(--px) * 10px) calc(var(--py) * 8px); transition: translate .5s ease-out; }
        .lb-swap-line { display: flex; align-items: center; gap: .5rem; font-size: .8rem; color: #9aa7a1; }
        .lb-swap { position: relative; display: inline-grid; min-width: 9rem; height: 1.4rem; }
        .lb-swap b { position: absolute; left: 0; top: 0; font-weight: 800; color: var(--gold); white-space: nowrap; }
        .lb-marquee { overflow: hidden; margin-block: -.4rem; padding: .7rem 0; border-block: 1px solid rgba(245, 215, 120, .15); mask-image: linear-gradient(90deg, transparent, #000 10%, #000 90%, transparent); }
        .lb-marquee__track { display: flex; align-items: center; gap: 1.6rem; width: max-content; white-space: nowrap; animation: lb-marquee 38s linear infinite; }
        .lb-marquee:hover .lb-marquee__track { animation-play-state: paused; }
        .lb-marquee span { font-size: .78rem; font-weight: 800; letter-spacing: .22em; text-transform: uppercase; color: #aab4af; }
        .lb-marquee i { font-style: normal; color: var(--gold); opacity: .7; }
        @keyframes lb-marquee { to { transform: translateX(-50%); } }
        .lb-underline { position: relative; width: fit-content; }
        .lb-underline::after { content: ''; position: absolute; left: 0; bottom: -.35rem; width: 100%; height: 2px; border-radius: 2px; background: linear-gradient(90deg, var(--gold), transparent); transform: scaleX(0); transform-origin: left; transition: transform .9s .25s var(--ease); }
        .is-in .lb-underline::after, .lb-underline.is-in::after { transform: scaleX(1); }
        .lb-bento { display: grid; gap: 1rem; grid-template-columns: 1.35fr 1fr; grid-template-rows: repeat(2, minmax(11rem, auto)); }
        .lb-feat { position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: flex-end; gap: .35rem; min-height: 11rem; padding: 1.4rem; border-radius: 1.4rem; border: 1px solid rgba(255, 255, 255, .09); transition: transform .35s var(--ease), border-color .3s, box-shadow .35s; }
        .lb-feat:first-child { grid-row: 1 / 3; min-height: 24rem; }
        .lb-feat::before, .lb-spot::before { content: ''; position: absolute; inset: 0; border-radius: inherit; pointer-events: none; opacity: 0; transition: opacity .3s; background: radial-gradient(16rem circle at var(--mx, 50%) var(--my, 50%), rgba(245, 215, 120, .18), transparent 65%); }
        .lb-feat:hover::before, .lb-spot:hover::before { opacity: 1; }
        .lb-feat:hover { transform: translateY(-6px); border-color: rgba(245, 215, 120, .45); box-shadow: 0 24px 50px rgba(0, 0, 0, .45), 0 0 40px rgba(245, 215, 120, .12); }
        .lb-feat h3 { font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; }
        .lb-feat:first-child h3 { font-size: clamp(2rem, 4.5vw, 3rem); }
        .lb-feat__num { position: absolute; top: .6rem; right: 1rem; font-size: 4.5rem; font-weight: 900; line-height: 1; color: transparent; -webkit-text-stroke: 1px rgba(245, 215, 120, .25); transition: transform .5s var(--ease), -webkit-text-stroke-color .3s; }
        .lb-feat:hover .lb-feat__num { transform: translateY(6px) scale(1.08); -webkit-text-stroke-color: rgba(245, 215, 120, .6); }
        .lb-feat .casino-featured__cta i { display: inline-block; font-style: normal; transition: transform .3s var(--ease); }
        .lb-feat:hover .casino-featured__cta i { transform: translate(4px, -4px); }
        .lb-tabs { position: relative; }
        .lb-tabs button.is-active { background: transparent; color: #1a1205; position: relative; z-index: 1; }
        .lb-ink { position: absolute; left: 0; top: 0; height: 100%; z-index: 0; border-radius: 9999px; pointer-events: none; background: linear-gradient(135deg, #f5d778, #b8892b); box-shadow: 0 0 18px rgba(245, 215, 120, .35); transition: transform .45s var(--ease), width .45s var(--ease); }
        .lb-search { position: relative; }
        .lb-search kbd { position: absolute; right: .7rem; top: 50%; transform: translateY(-50%); padding: .1rem .45rem; border-radius: .35rem; border: 1px solid rgba(255, 255, 255, .15); font: 700 .65rem ui-monospace, monospace; color: #8b9893; pointer-events: none; }
        .lb-search:focus-within kbd { opacity: 0; }
        .lb-tilewrap { display: contents; }
        .lb-grid .casino-game-card { position: relative; transition: transform .35s var(--ease), box-shadow .35s; }
        .lb-grid .casino-game-card::after { content: ''; position: absolute; inset: 0; border-radius: inherit; pointer-events: none; z-index: 1; opacity: 0; transition: opacity .3s; background: radial-gradient(14rem circle at var(--mx, 50%) var(--my, 50%), rgba(245, 215, 120, .14), transparent 65%); }
        .lb-grid .casino-game-card:hover::after { opacity: 1; }
        .lb-empty { display: grid; justify-items: center; gap: .6rem; padding: 3rem 1rem; text-align: center; color: #9aa7a1; }
        .lb-empty span { font-size: 2.6rem; animation: lb-roll 2.4s ease-in-out infinite; }
        @keyframes lb-roll { 50% { transform: rotate(180deg) translateY(-6px); } }
        .lb-lock { animation: lb-lock 3.2s ease-in-out infinite; }
        @keyframes lb-lock { 0%, 100% { transform: none; } 45% { transform: scale(1.12) rotate(-6deg); } 55% { transform: scale(1.12) rotate(6deg); } }
        .lb-bonus { position: relative; overflow: hidden; }
        .lb-bonus::after { content: ''; position: absolute; top: -50%; left: -40%; width: 30%; height: 200%; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .1), transparent); transform: rotate(18deg); animation: lb-sheen 6s ease-in-out infinite; pointer-events: none; }
        @keyframes lb-sheen { 0%, 60% { transform: translateX(0) rotate(18deg); } 100% { transform: translateX(560%) rotate(18deg); } }
        @media (max-width: 800px) { .lb-bento { grid-template-columns: 1fr; grid-template-rows: none; } .lb-feat:first-child { grid-row: auto; min-height: 15rem; } .lb-hero-glow { display: none; } }
        @media (prefers-reduced-motion: reduce) {
            .lb-reveal { opacity: 1; transform: none; filter: none; transition: none; }
            .lb-aurora i, .lb-marquee__track, .lb-empty span, .lb-lock, .lb-bonus::after { animation: none !important; }
            .lb-hero .casino-hero-chip, .lb-hero .casino-float-chip, .lb-hero .casino-orbit--outer, .lb-hero-glow, .lb-ink, .lb-feat { transition: none; translate: none; }
        }
        </style>
    </div>
@endsection