<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $pageTitle = $__env->yieldContent('page-title', 'Casino');
            $casinoGames = $casinoGames ?? \App\Support\CasinoCatalog::games();
        @endphp
        @include('partials.head', ['title' => $pageTitle])
        @livewireStyles
    </head>
    <body class="casino-body min-h-screen antialiased">
        <div class="casino-atmosphere" aria-hidden="true">
            <span class="casino-atmosphere__vignette"></span>
            <span class="casino-atmosphere__felt"></span>
        </div>

        <div class="casino-shell">
            <aside class="casino-rail" aria-label="Jogos e secções">
                <a href="{{ route('home') }}" class="casino-wordmark casino-rail__brand" >
                    <span class="casino-mark">A</span>
                    <span>ALLINBET</span>
                </a>

                <a href="{{ route('home') }}" class="casino-rail__item {{ request()->routeIs('home', 'dashboard') ? 'is-current' : '' }}" wire:navigate>
                    <span class="casino-rail__glyph">▣</span>
                    Casino
                </a>

                <a href="{{ route('casino.sports') }}" class="casino-rail__item {{ request()->routeIs('casino.sports') ? 'is-current' : '' }}" wire:navigate>
                    <span class="casino-rail__glyph">⚽</span>
                    Apostas desportivas
                </a>


                <p class="casino-rail__label">ORIGINALS</p>
                @foreach ($casinoGames as $game)
                    <a href="{{ auth()->check() ? route($game['route']) : route('login') }}" class="casino-rail__item {{ request()->routeIs($game['route']) ? 'is-current' : '' }}" wire:navigate>
                        <span class="casino-rail__glyph">{{ $game['icon'] }}</span>
                        {{ $game['name'] }}
                    </a>
                @endforeach

                <p class="casino-rail__label">CONTA</p>
                @auth
                    <a href="{{ route('casino.wallet') }}" class="casino-rail__item {{ request()->routeIs('casino.wallet') ? 'is-current' : '' }}" wire:navigate>
                        <span class="casino-rail__glyph">◎</span>
                        Carteira
                    </a>
                    <a href="{{ route('casino.history') }}" class="casino-rail__item {{ request()->routeIs('casino.history') ? 'is-current' : '' }}" wire:navigate>
                        <span class="casino-rail__glyph">☰</span>
                        Histórico
                    </a>
                @endauth
                <a href="{{ route('casino.help') }}" class="casino-rail__item {{ request()->routeIs('casino.help') ? 'is-current' : '' }}" wire:navigate>
                    <span class="casino-rail__glyph">♥</span>
                    Jogo responsável
                </a>

                <p class="casino-rail__meta">Créditos virtuais — sem valor monetário. Sem depósitos, pagamentos ou levantamentos.</p>
            </aside>

            <div class="casino-shell__body">
                <header class="casino-topbar">
                    <div class="casino-topbar__inner mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <a href="{{ route('home') }}" class="casino-wordmark lg:hidden" >
                            <span class="casino-mark">A</span>
                            <span>ALLINBET</span>
                        </a>
                        <button type="button" class="casino-search-trigger" x-data x-on:click="$dispatch('casino-open-search')">
                            <span>Procurar jogos, histórico…</span>
                            <kbd>Ctrl K</kbd>
                        </button>
                        <nav aria-label="Navegação principal" class="casino-topbar__actions">
                            <button type="button" data-casino-sound-toggle aria-pressed="false" class="casino-sound-toggle hidden sm:inline-flex">Som: desligado</button>
                            <button type="button" x-data x-on:click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'" class="casino-theme-toggle" aria-label="Alternar tema">
                                <span x-show="$flux.appearance === 'dark'">☀️ Tema claro</span>
                                <span x-show="$flux.appearance !== 'dark'">🌙 Tema escuro</span>
                            </button>
                            @auth
                                <a href="{{ route('casino.wallet') }}" class="casino-wallet-link" wire:navigate aria-label="Abrir carteira">@livewire('casino.wallet-balance')</a>
                                <details class="casino-user-menu">
                                    <summary aria-label="Menu do utilizador">{{ auth()->user()->initials() }}</summary>
                                    <div class="casino-user-menu__panel">
                                        <p class="casino-user-menu__name">{{ auth()->user()->name }}</p>
                                        <a href="{{ route('casino.wallet') }}" wire:navigate>Carteira de créditos</a>
                                        <a href="{{ route('profile.edit') }}" wire:navigate>Perfil e configurações</a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit">Terminar sessão</button>
                                        </form>
                                    </div>
                                </details>
                            @else
                                <span class="hidden text-xs text-zinc-300 sm:inline">Créditos virtuais — sem valor monetário</span>
                                <a href="{{ route('login') }}" class="text-sm text-zinc-200 transition hover:text-casino-gold-bright">Entrar</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="casino-button casino-button--primary min-h-10 px-3 py-2 text-xs sm:text-sm">Criar conta</a>
                                @endif
                            @endauth
                        </nav>
                    </div>
                </header>

                @php
                    $isCasinoGameRoute = collect($casinoGames)->contains(fn ($game) => request()->routeIs($game['route']));
                    $currentCasinoGame = $isCasinoGameRoute
                        ? collect($casinoGames)->first(fn ($game) => request()->routeIs($game['route']))
                        : null;
                @endphp

                <main class="casino-main mx-auto w-full max-w-7xl px-4 py-5 pb-24 sm:px-6 sm:py-7 md:pb-8 lg:px-8">
                    @if ($isCasinoGameRoute && $currentCasinoGame)
                        <div class="casino-game-chrome" data-casino-game-chrome>
                            <div class="casino-game-chrome__head">
                                <div>
                                    <p class="casino-eyebrow">ALLINBET · ORIGINALS</p>
                                    <div class="casino-game-chrome__title">
                                        <strong>{{ $currentCasinoGame['name'] }}</strong>
                                        <span>{{ $currentCasinoGame['label'] }}</span>
                                    </div>
                                </div>
                                <div class="casino-game-chrome__meta">
                                    @auth
                                        <a href="{{ route('casino.wallet') }}" wire:navigate>Carteira <b>@livewire('casino.wallet-balance')</b></a>
                                    @endauth
                                    <span class="casino-game-chrome__live"><i></i> AO VIVO</span>
                                </div>
                            </div>

                            <nav class="casino-game-chrome__switch" aria-label="Jogos">
                                @foreach ($casinoGames as $game)
                                    <a href="{{ auth()->check() ? route($game['route']) : route('login') }}"
                                       class="{{ request()->routeIs($game['route']) ? 'is-current' : '' }}"
                                       wire:navigate>
                                        <span>{{ $game['icon'] }}</span>
                                        {{ $game['name'] }}
                                    </a>
                                @endforeach
                                <a href="{{ route('casino.history') }}" wire:navigate>☰ Histórico</a>
                                <a href="{{ route('casino.help') }}" wire:navigate>♥ Responsável</a>
                            </nav>

                            <div class="casino-game-chrome__ambient" aria-hidden="true">
                                <span></span><span></span><span></span><span></span><span></span>
                            </div>
                        </div>
                    @endif

                    <div class="casino-main__content">
                        @yield('content')
                    </div>
                </main>

                <footer class="border-t border-casino-gold/15 px-4 py-5 text-center text-xs text-zinc-400">
                    <span>AllinBet</span>
                    <span class="mx-2 text-casino-gold/60">·</span>
                    <span>Créditos virtuais — sem valor monetário</span>
                    <span class="mx-2 text-casino-gold/60">·</span>
                    <a href="{{ route('casino.help') }}" class="hover:text-casino-gold-bright" wire:navigate>Jogo responsável</a>
                </footer>
            </div>
        </div>

        <div
            class="casino-feedback-root"
            x-data="{
                toast: null,
                toastTimer: null,
                celebration: null,
                celebrationTimer: null,
                coins: [-230, -180, -130, -80, -30, 20, 70, 120, 170, 220],
                notify(detail) {
                    this.toast = detail;
                    window.clearTimeout(this.toastTimer);
                    this.toastTimer = window.setTimeout(() => { this.toast = null; }, 3600);
                },
                celebrate(detail) {
                    this.celebration = detail;
                    window.clearTimeout(this.celebrationTimer);
                    this.celebrationTimer = window.setTimeout(() => { this.celebration = null; }, 2200);
                }
            }"
            x-on:casino-toast.window="notify($event.detail)"
            x-on:casino-big-win.window="celebrate($event.detail)"
        >
            <div x-cloak x-show="toast" x-transition.opacity class="casino-toast" role="status" aria-live="polite">
                <span class="casino-toast__spark" aria-hidden="true">✦</span>
                <span class="casino-toast__copy"><strong x-text="toast?.title"></strong><small x-text="toast?.message"></small></span>
                <button type="button" class="casino-toast__close" aria-label="Fechar notificação" x-on:click="toast = null">×</button>
            </div>

            <div x-cloak x-show="celebration" x-transition.opacity class="casino-win-celebration" role="status" aria-live="assertive">
                <div class="casino-win-celebration__coins" aria-hidden="true">
                    <template x-for="(coin, index) in coins" :key="index">
                        <span class="casino-confetti-coin" x-bind:style="`--coin-x:${coin}px; --coin-delay:${index * 24}ms`">CR</span>
                    </template>
                </div>
                <div class="casino-win-celebration__message"><span>GRANDE VITÓRIA</span><strong x-text="`+${celebration?.amount ?? 0}`"></strong><small>créditos virtuais</small></div>
            </div>
        </div>

        @auth
            <nav class="casino-bottomnav md:hidden" aria-label="Navegação rápida">
                <a href="{{ route('home') }}" >🎰<span>Casino</span></a>
                <a href="{{ route('casino.history') }}" wire:navigate>📜<span>Histórico</span></a>
                <a href="{{ route('casino.help') }}" wire:navigate>🛟<span>Ajuda</span></a>
                <a href="{{ route('casino.wallet') }}" wire:navigate>💳<span>Carteira</span></a>
                <a href="{{ route('profile.edit') }}" wire:navigate>👤<span>Perfil</span></a>
            </nav>
        @endauth

        <div
            x-data="{
                open: false,
                started: Number(sessionStorage.getItem('casino-start') || Date.now()),
                timer: null,
                now: Date.now(),
                nextBreak: Number(sessionStorage.getItem('casino-next') || 0),
                init() {
                    sessionStorage.setItem('casino-start', this.started);
                    this.nextBreak = this.nextBreak || (this.started + 1800000);
                    sessionStorage.setItem('casino-next', String(this.nextBreak));

                    this.timer = window.setInterval(() => {
                        this.now = Date.now();

                        if (this.now >= this.nextBreak) {
                            this.open = true;
                            this.nextBreak = this.now + 1800000;
                            sessionStorage.setItem('casino-next', String(this.nextBreak));
                        }
                    }, 1000);
                },
                destroy() { window.clearInterval(this.timer); },
                dismiss() { this.open = false; this.now = Date.now(); },
                get minutes() { return Math.max(0, Math.floor((this.now - this.started) / 60000)); },
                get elapsedLabel() {
                    const h = Math.floor(this.minutes / 60);
                    const m = this.minutes % 60;
                    return h > 0 ? h + 'h ' + String(m).padStart(2, '0') + 'min' : m + 'min';
                },
                get nextBreakMinutes() { return Math.max(0, Math.ceil((this.nextBreak - this.now) / 60000)); },
                get progress() {
                    const cycle = 1800000;
                    const sinceLast = Math.max(0, this.now - (this.nextBreak - cycle));
                    return Math.min(100, (sinceLast / cycle) * 100);
                },
                get message() {
                    if (this.minutes >= 120) return 'Já passou bastante tempo. Uma pausa mais longa pode ser uma boa altura para desligar por uns minutos.';
                    if (this.minutes >= 60) return 'Uma hora de jogo já passou. Faz uma pausa, bebe água e decide com calma se queres continuar.';
                    return 'Estás há algum tempo no jogo. Levanta-te, estica as pernas e decide com calma se queres continuar.';
                }
            }"
            x-cloak
            x-show="open"
            x-transition:enter="casino-pause-enter"
            x-transition:enter-start="casino-pause-enter-start"
            x-transition:enter-end="casino-pause-enter-end"
            x-transition:leave="casino-pause-leave"
            x-transition:leave-start="casino-pause-leave-start"
            x-transition:leave-end="casino-pause-leave-end"
            class="casino-modal casino-pause-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="rc-title"
            x-on:keydown.escape.window="dismiss()"
        >
            <div class="casino-pause-card" @click.stop>
                <div class="casino-pause-glow" aria-hidden="true"></div>

                <div class="casino-pause-top">
                    <div class="casino-pause-icon" aria-hidden="true">☕</div>
                    <div>
                        <p class="casino-eyebrow">LEMBRETE DE PAUSA</p>
                        <span class="casino-pause-live"><i></i> JOGA COM CALMA</span>
                    </div>
                    <button type="button" class="casino-pause-close" aria-label="Fechar lembrete" x-on:click="dismiss()">×</button>
                </div>

                <div class="casino-pause-heading">
                    <h2 id="rc-title">Está na hora de fazer uma pausa?</h2>
                    <p x-text="message"></p>
                </div>

                <div class="casino-pause-stats">
                    <div>
                        <span>TEMPO DE JOGO</span>
                        <strong x-text="elapsedLabel"></strong>
                    </div>
                    <div>
                        <span>PRÓXIMO LEMBRETE</span>
                        <strong x-text="nextBreakMinutes > 0 ? nextBreakMinutes + ' min' : 'agora'"></strong>
                    </div>
                </div>

                <div class="casino-pause-progress" aria-hidden="true">
                    <span :style="'width:' + progress + '%'"></span>
                </div>
                <p class="casino-pause-caption">O tempo é contado nesta sessão e serve apenas como lembrete.</p>

                <div class="casino-pause-actions">
                    <a class="casino-button casino-button--primary" href="{{ route('casino.help') }}" wire:navigate>
                        Ver jogo responsável
                    </a>
                    <button type="button" class="casino-button casino-button--secondary" x-on:click="dismiss()">
                        Fechar por agora
                    </button>
                </div>

                <div class="casino-pause-footer">
                    <span>◷</span>
                    <span>Fazer pausas regulares pode ajudar a manter o jogo sob controlo.</span>
                </div>
            </div>
        </div>

        <div
            x-data="{
                open: false,
                q: '',
                items: {{ Js::from(collect($casinoGames)->map(fn ($game) => ['n' => $game['name'], 'u' => route($game['route'])])->concat([
                    ['n' => 'Histórico', 'u' => route('casino.history')],
                    ['n' => 'Carteira', 'u' => route('casino.wallet')],
                    ['n' => 'Jogo responsável', 'u' => route('casino.help')],
                    ['n' => 'As minhas apostas', 'u' => '#sports-bets'],
                ])->values()) }},
                get results() { return this.items.filter(i => i.n.toLowerCase().includes(this.q.toLowerCase())); }
            }"
            x-on:keydown.window.ctrl.k.prevent="open = true; $nextTick(() => $refs.q.focus())"
            x-on:casino-open-search.window="open = true; $nextTick(() => $refs.q.focus())"
            x-on:keydown.escape.window="open = false"
            x-cloak
            x-show="open"
            class="casino-modal"
            x-on:click.self="open = false"
        >
            <div class="casino-modal__panel casino-card p-4">
                <input x-ref="q" x-model="q" type="search" placeholder="Procurar jogo… (Esc fecha)" class="casino-field w-full">
                <ul class="mt-3 space-y-1">
                    <template x-for="i in results" :key="i.u">
                        <li>
                            <a :href="i.u"
                               class="casino-cmd-item"
                               x-on:click="if (i.n === 'As minhas apostas') { $event.preventDefault(); $dispatch('casino-open-sports-bets'); open = false; } else { open = false; }"
                               x-text="i.n"></a>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
        @auth
            <div
                id="casino-sports-bets-modal"
                x-data="{ open: false }"
                x-on:casino-open-sports-bets.window="open = true; $dispatch('sports-bets-refresh')"
                x-on:keydown.escape.window="open = false"
                x-cloak
                x-show="open"
                x-transition.opacity
                class="casino-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="casino-sports-bets-title"
                x-on:click.self="open = false"
            >
                <div class="casino-modal__panel casino-card w-full max-w-2xl overflow-hidden p-0">
                    @livewire('casino.sports-bets')
                </div>
            </div>
        @endauth

        @livewire('casino.fairness-modal')

        @fluxScripts
        @livewireScripts
    </body>
</html>
