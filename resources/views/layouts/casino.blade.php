<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php($pageTitle = $__env->yieldContent('page-title', 'Casino'))
        @include('partials.head', ['title' => $pageTitle])
        @livewireStyles
    </head>
    <body class="casino-body min-h-screen antialiased">
        <header class="casino-topbar">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="casino-wordmark" wire:navigate>
                    <span class="casino-mark">A</span>
                    <span>ALLINBET</span>
                </a>
                <nav aria-label="Navegação principal" class="flex items-center gap-3 sm:gap-5">
                    <button type="button" data-casino-sound-toggle aria-pressed="false" class="casino-sound-toggle hidden sm:inline-flex">Som: desligado</button>
                    <button type="button" x-data x-on:click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'" class="casino-theme-toggle" aria-label="Alternar tema">
                        <span x-show="$flux.appearance === 'dark'">☀️ Tema claro</span>
                        <span x-show="$flux.appearance !== 'dark'">🌙 Tema escuro</span>
                    </button>
                    <a href="{{ route('home') }}" class="hidden text-sm text-zinc-300 transition hover:text-casino-gold-bright sm:inline" wire:navigate>Lobby</a>
                    @auth
                        <a href="{{ route('casino.history') }}" class="hidden text-sm text-zinc-300 transition hover:text-casino-gold-bright sm:inline" wire:navigate>Histórico</a>
                        <a href="{{ route('casino.help') }}" class="hidden text-sm text-zinc-300 transition hover:text-casino-gold-bright lg:inline" wire:navigate>Jogo responsável</a>
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
        </footer>

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
                items: [
                    { n: 'Coinflip', u: '{{ route('casino.coinflip') }}' },
                    { n: 'Dados', u: '{{ route('casino.dice') }}' },
                    { n: 'Roleta', u: '{{ route('casino.roulette') }}' },
                    { n: 'Blackjack', u: '{{ route('casino.blackjack') }}' },
                    { n: 'Slots', u: '{{ route('casino.slots') }}' },
                    { n: 'Histórico', u: '{{ route('casino.history') }}' },
                    { n: 'Carteira', u: '{{ route('casino.wallet') }}' },
                ],
                get results() { return this.items.filter(i => i.n.toLowerCase().includes(this.q.toLowerCase())); }
            }"
            x-on:keydown.window.ctrl.k.prevent="open = true; $nextTick(() => $refs.q.focus())"
            x-on:keydown.escape.window="open = false"
            x-cloak
            x-show="open"
            class="casino-modal"
            x-on:click.self="open = false"
        >
            <div class="casino-modal__panel casino-card">
                <input x-ref="q" x-model="q" type="search" placeholder="Procurar jogo… (Esc fecha)" class="casino-input w-full">
                <ul class="mt-3 space-y-1">
                    <template x-for="i in results" :key="i.u">
                        <li><a :href="i.u" class="casino-cmd-item" wire:navigate x-on:click="open = false" x-text="i.n"></a></li>
                    </template>
                </ul>
            </div>
        </div>
        @fluxScripts
        @livewireScripts
    </body>
</html>
