<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
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
                    <a href="{{ route('home') }}" class="hidden text-sm text-zinc-300 transition hover:text-casino-gold-bright sm:inline" wire:navigate>Lobby</a>
                    @auth
                        <a href="{{ route('casino.history') }}" class="hidden text-sm text-zinc-300 transition hover:text-casino-gold-bright sm:inline" wire:navigate>Histórico</a>
                        <a href="{{ route('casino.help') }}" class="hidden text-sm text-zinc-300 transition hover:text-casino-gold-bright lg:inline" wire:navigate>Jogo responsável</a>
                        @livewire('casino.wallet-balance')
                        <details class="casino-user-menu">
                            <summary aria-label="Menu do utilizador">{{ auth()->user()->initials() }}</summary>
                            <div class="casino-user-menu__panel">
                                <p class="casino-user-menu__name">{{ auth()->user()->name }}</p>
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

        <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
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

        @fluxScripts
        @livewireScripts
    </body>
</html>
