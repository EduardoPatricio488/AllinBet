<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="casino-app-body min-h-screen">
        <flux:sidebar sticky collapsible="mobile" class="casino-sidebar border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="casino-wordmark" wire:navigate>
                    <span class="casino-mark">A</span>
                    <span>ALLINBET</span>
                </a>
                <button type="button" data-casino-sound-toggle aria-pressed="false" class="casino-sound-toggle">Som: desligado</button>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Casino" class="grid">
                    <flux:sidebar.item icon="squares-2x2" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        Lobby
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('casino.history')" :current="request()->routeIs('casino.history')" wire:navigate>Histórico</flux:sidebar.item>
                    <flux:sidebar.item icon="heart" :href="route('casino.help')" :current="request()->routeIs('casino.help')" wire:navigate>Jogo responsável</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <div class="px-3 py-3">
                @livewire('casino.wallet-balance')
                <p class="mt-2 text-[10px] text-zinc-500">Créditos virtuais — sem valor monetário</p>
            </div>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.*', 'security.*', 'appearance.*')" wire:navigate>
                    Configurações
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="casino-mobile-header lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="casino-wordmark" wire:navigate>AllinBet</a>

            <flux:spacer />

            @livewire('casino.wallet-balance')

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
