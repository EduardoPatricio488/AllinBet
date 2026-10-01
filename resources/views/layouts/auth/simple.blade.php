<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="casino-auth-body">
        <main class="casino-auth-shell">
            <aside class="casino-auth-stage" aria-label="AllinBet casino virtual">
                <a href="{{ route('home') }}" class="casino-wordmark" wire:navigate>
                    <span class="casino-mark">A</span>
                    <span>ALLINBET</span>
                </a>

                <div class="casino-auth-copy">
                    <p class="casino-auth-kicker">MESAS VIRTUAIS · SEM DINHEIRO REAL</p>
                    <h1 class="casino-auth-title">A sorte está<br>na <em>sua mão.</em></h1>
                    <p class="casino-auth-description">Jogos, estratégia e uma mesa só sua. Entre no AllinBet com créditos virtuais, sem depósitos, pagamentos ou levantamentos.</p>
                </div>

                <div class="casino-auth-art" aria-hidden="true">
                    <span class="casino-auth-art__card casino-auth-art__card--one" data-suit="♥">A</span>
                    <span class="casino-auth-art__card casino-auth-art__card--two" data-suit="♠">7</span>
                    <span class="casino-auth-art__chip">VIRTUAL</span>
                </div>

                <footer class="casino-auth-stage-footer">
                    <span>CRÉDITOS VIRTUAIS</span>
                    <span>SEM VALOR MONETÁRIO</span>
                    <button type="button" data-casino-sound-toggle aria-pressed="false" class="casino-sound-toggle">Som: desligado</button>
                    <a href="{{ route('home') }}" class="text-inherit underline decoration-casino-gold/50 underline-offset-4" wire:navigate>JOGO RESPONSÁVEL</a>
                </footer>
            </aside>

            <section class="casino-auth-main">
                <div>{{ $slot }}</div>
            </section>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
