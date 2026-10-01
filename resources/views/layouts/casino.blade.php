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
                <a href="{{ route('home') }}" class="casino-wordmark casino-rail__brand" wire:navigate>
                    <span class="casino-mark">A</span>
                    <span>ALLINBET</span>
                </a>

                <a href="{{ route('home') }}" class="casino-rail__item {{ request()->routeIs('home', 'dashboard') ? 'is-current' : '' }}" wire:navigate>
                    <span class="casino-rail__glyph">▣</span>
                    Lobby
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
                        <a href="{{ route('home') }}" class="casino-wordmark lg:hidden" wire:navigate>
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

                <main class="mx-auto w-full max-w-7xl px-4 py-6 pb-24 sm:px-6 sm:py-8 md:pb-8 lg:px-8">
                    @yield('content')
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
                <a href="{{ route('home') }}" wire:navigate>🎰<span>Lobby</span></a>
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
                init() {
                    sessionStorage.setItem('casino-start', this.started);
                    this.timer = window.setInterval(() => {
                        const next = Number(sessionStorage.getItem('casino-next') || (this.started + 1800000));
                        if (Date.now() >= next) {
                            this.open = true;
                            sessionStorage.setItem('casino-next', String(Date.now() + 1800000));
                        }
                    }, 15000);
                },
                destroy() { window.clearInterval(this.timer); },
                get minutes() { return Math.floor((Date.now() - this.started) / 60000); }
            }"
            x-cloak
            x-show="open"
            x-transition.opacity
            class="casino-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="rc-title"
        >
            <div class="casino-modal__panel casino-card">
                <p class="casino-eyebrow">PAUSA</p>
                <h2 id="rc-title">Já joga há <span x-text="minutes"></span> minutos</h2>
                <p>Que tal esticar as pernas? Pode continuar quando quiser.</p>
                <div class="flex gap-3">
                    <button type="button" class="casino-button casino-button--secondary" x-on:click="open = false">Continuar a jogar</button>
                    <a class="casino-button casino-button--secondary" href="{{ route('casino.help') }}" wire:navigate>Fazer uma pausa</a>
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
                        <li><a :href="i.u" class="casino-cmd-item" wire:navigate x-on:click="open = false" x-text="i.n"></a></li>
                    </template>
                </ul>
            </div>
        </div>
        @livewire('casino.fairness-modal')

        @fluxScripts
        @livewireScripts
    </body>
</html>
