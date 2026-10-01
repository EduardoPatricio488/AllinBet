@php
    $symbols = ['🍒', '🍋', '🍊', '🔔', '⭐', '🍀', '💎', '7️⃣'];
    $glyph = fn (int $s) => $symbols[$s] ?? chr(65 + $s);

    $grid = $roundResult['grid'] ?? [[0, 1, 2], [3, 4, 5], [2, 1, 0]];
    $maxBet = (int) config('casino.bet_limits.max');
    $locked = $roundPhase === 'prepared';

    $winningLines = $roundPhase === 'completed'
        ? collect($roundResult['winning_lines'] ?? [])->values()->all()
        : [];

    $horizontalWins = collect($winningLines)
        ->filter(fn ($line) => ($line['direction'] ?? '') === 'horizontal')
        ->pluck('line')
        ->map(fn ($line) => (int) $line)
        ->all();

    $verticalWins = collect($winningLines)
        ->filter(fn ($line) => ($line['direction'] ?? '') === 'vertical')
        ->pluck('line')
        ->map(fn ($line) => (int) $line)
        ->all();

    $winningCells = collect($winningLines)->flatMap(function ($line) {
        if (($line['direction'] ?? '') === 'horizontal') {
            return collect(range(0, 2))->map(fn ($column) => ($line['line'] * 3) + $column);
        }

        return collect(range(0, 2))->map(fn ($row) => ($row * 3) + $line['line']);
    })->unique()->values()->all();
@endphp

<div class="casino-game-play grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,20rem)]"
     x-data="{
        reels: [false, false, false],
        busy: false,
        prizeVisible: false,
        prizeAmount: 0,
        maxBet: {{ $maxBet }},
        step(d) {
            const v = Math.round((Number(this.$wire.bet || 6) + d) / 6) * 6;
            this.$wire.bet = Math.min(this.maxBet, Math.max(6, v));
        },
        async go() {
            if (this.busy) return;
            const w = this.$wire;
            let spun = false;
            this.busy = true;
            this.reels = [true, true, true];
            const t0 = Date.now();

            try {
                if (w.roundPhase !== 'prepared') await w.prepare();
                if (w.roundPhase === 'prepared') {
                    await w.spin();
                    spun = true;
                }
            } catch (e) {}

            const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            await new Promise(r => setTimeout(r, calm ? 0 : Math.max(0, 1000 - (Date.now() - t0))));

            for (let i = 0; i < 3; i++) {
                this.reels[i] = false;
                if (!calm) await new Promise(r => setTimeout(r, 320));
            }

            this.busy = false;

            const payout = Number(w.roundPayout || 0);
            if (spun && w.roundPhase === 'completed' && payout > 0) {
                this.prizeAmount = payout;
                this.prizeVisible = true;
                window.setTimeout(() => { this.prizeVisible = false; }, 3200);
            }
            if (spun && w.roundPhase === 'completed' && payout > 0) {
                this.$dispatch('casino-toast', {
                    title: 'Vitória!',
                    message: '+' + payout + ' créditos virtuais'
                });

                if (payout >= Number(w.bet || 0) * 10) {
                    this.$dispatch('casino-big-win', { amount: payout });
                }
            }
        }
     }"
     x-on:keydown.window="if ($event.code === 'Space' && !['INPUT','TEXTAREA','BUTTON','SUMMARY'].includes($event.target.tagName)) { $event.preventDefault(); go(); }">

    <style>
        .slot-prize-overlay{position:absolute;inset:0;z-index:30;display:grid;place-items:center;pointer-events:none;background:radial-gradient(circle,rgba(0,0,0,.2),rgba(0,0,0,.58));backdrop-filter:blur(2px)}
        .slot-prize-card{min-width:min(88%,28rem);padding:1.4rem 2rem;border:2px solid rgba(242,193,78,.8);border-radius:1.25rem;background:linear-gradient(145deg,rgba(15,18,22,.97),rgba(38,29,10,.96));box-shadow:0 0 45px rgba(242,193,78,.35),0 24px 60px rgba(0,0,0,.55);text-align:center;animation:slotPrizeIn .45s cubic-bezier(.2,.9,.25,1.2)}
        .slot-prize-label{margin:0;color:#f2c14e;font-size:.7rem;font-weight:900;letter-spacing:.2em;text-transform:uppercase}.slot-prize-amount{margin:.2rem 0 0;color:#fff;font-size:clamp(2.4rem,7vw,4.5rem);font-weight:950;line-height:1;text-shadow:0 0 22px rgba(242,193,78,.45)}.slot-prize-sub{margin:.55rem 0 0;color:#b8f7d8;font-size:.85rem;font-weight:800}
        @keyframes slotPrizeIn{0%{opacity:0;transform:scale(.65) translateY(1rem)}60%{transform:scale(1.06)}100%{opacity:1;transform:scale(1) translateY(0)}}
        @keyframes slotPrizePulse{50%{transform:scale(1.03);box-shadow:0 0 65px rgba(242,193,78,.55),0 24px 60px rgba(0,0,0,.55)}}
        .slot-prize-card{animation:slotPrizeIn .45s cubic-bezier(.2,.9,.25,1.2),slotPrizePulse 1.1s ease-in-out .45s 2}
        @media(prefers-reduced-motion:reduce){.slot-prize-card{animation:none}}
    </style>

    <section class="slot-machine relative" :class="{ 'is-busy': busy }">
        <div class="slot-cabinet">
            <div x-show="prizeVisible" x-cloak x-transition.opacity class="slot-prize-overlay" role="status" aria-live="assertive">
                <div class="slot-prize-card">
                    <p class="slot-prize-label">🎉 Prémio ganho</p>
                    <p class="slot-prize-amount">+<span x-text="Number(prizeAmount).toLocaleString('pt-PT')"></span></p>
                    <p class="slot-prize-sub">créditos virtuais</p>
                </div>
            </div>
            <span class="casino-bulbs" aria-hidden="true"></span>

            <div class="slot-header">
                <span class="slot-title">ALLINBET <em class="casino-shimmer-text">SLOTS</em></span>
                <span class="slot-badge">6 LINHAS · 3×3</span>
            </div>

            <div class="slot-window">
                <div class="slot-markers" aria-hidden="true">
                    @foreach ([0, 1, 2] as $r)
                        <span class="slot-marker {{ in_array($r, $horizontalWins, true) ? 'slot-marker--win' : '' }}">{{ $r + 1 }}</span>
                    @endforeach
                </div>

                <div class="slot-reels" role="img" aria-label="Rolos da máquina de slots">
                    @foreach ([0, 1, 2] as $col)
                        <div class="slot-reel">
                            <div class="slot-strip" x-show="reels[{{ $col }}]" x-cloak aria-hidden="true">
                                @for ($k = 0; $k < 2; $k++)
                                    @foreach ($symbols as $s)
                                        <span class="slot-cell">{{ $s }}</span>
                                    @endforeach
                                @endfor
                            </div>

                            <div class="slot-landed" x-show="!reels[{{ $col }}]">
                                @foreach ($grid as $rowIndex => $row)
                                    @php $cellIndex = ($rowIndex * 3) + $col; @endphp
                                    <span class="slot-cell {{ in_array($cellIndex, $winningCells, true) ? 'slot-cell--win' : '' }}">
                                        {{ $glyph((int) ($row[$col] ?? 0)) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @foreach ($horizontalWins as $r)
                        <i class="slot-payline" style="--row: {{ $r }}" aria-label="Linha horizontal vencedora"></i>
                    @endforeach

                    @foreach ($verticalWins as $c)
                        <i class="slot-payline slot-payline--vertical" style="--column: {{ $c }}" aria-label="Linha vertical vencedora"></i>
                    @endforeach
                </div>
            </div>

            <div class="slot-deck">
                <div class="slot-bet">
                    <span class="slot-label">APOSTA TOTAL</span>
                    <div class="slot-stepper">
                        <button type="button" x-on:click="step(-6)" :disabled="busy || @js($locked)" aria-label="Diminuir aposta">−</button>
                        <input type="number" min="6" step="6" max="{{ $maxBet }}" wire:model="bet"
                               :disabled="busy || @js($locked)" class="slot-bet-input" aria-label="Aposta total em créditos">
                        <button type="button" x-on:click="step(6)" :disabled="busy || @js($locked)" aria-label="Aumentar aposta">+</button>
                    </div>
                    <small class="slot-hint">por linha: <b x-text="Math.floor(Number($wire.bet || 0) / 6)"></b> · 6 linhas</small>
                </div>

                <div class="slot-chips" aria-label="Apostas rápidas">
                    @foreach ([6, 12, 30, 60, 150] as $chip)
                        @if ($chip <= $maxBet)
                            <button type="button" x-on:click="$wire.bet = {{ $chip }}" :disabled="busy || @js($locked)">{{ $chip }}</button>
                        @endif
                    @endforeach
                    <button type="button" x-on:click="$wire.bet = Math.floor(maxBet / 6) * 6" :disabled="busy || @js($locked)">MAX</button>
                </div>

                <button type="button" class="slot-spin" x-on:click="go()" :disabled="busy">
                    <span x-show="!busy">GIRAR</span>
                    <span x-show="busy" x-cloak>A GIRAR…</span>
                    <small>barra de espaço</small>
                </button>
            </div>

            @error('bet') <p role="alert" class="mt-3 text-sm text-rose-300">{{ $message }}</p> @enderror
            @error('game') <p role="alert" class="mt-3 text-sm text-rose-300">{{ $message }}</p> @enderror
        </div>
    </section>

    <aside class="space-y-4">
        <div class="casino-card" aria-live="polite">
            <p class="casino-eyebrow">ÚLTIMA RONDA</p>
            <p x-show="busy" x-cloak class="mt-2 text-sm text-zinc-400">Os rolos estão a girar…</p>

            <div x-show="!busy" class="mt-2">
                @if ($roundResult !== [])
                    @if ((int) $roundPayout > 0)
                        <p class="slot-payout slot-payout--win">+{{ number_format((int) $roundPayout) }}</p>
                        <p class="text-sm text-zinc-400">
                            {{ count($winningLines) }} linha(s) vencedora(s) · apenas triplos pagam
                        </p>
                    @else
                        <p class="slot-payout">Sem prémio</p>
                        <p class="text-sm text-zinc-400">Só 3 símbolos iguais na horizontal ou vertical dão prémio.</p>
                    @endif

                    @if ($roundPhase === 'completed' && $roundId)
                        <a href="{{ route('fairness.verify', $roundId) }}" wire:navigate
                           class="mt-3 inline-block text-sm text-emerald-300 underline underline-offset-4">Verificar esta ronda ↗</a>
                    @endif
                @else
                    <p class="text-sm text-zinc-400">6 linhas de prémio: 3 horizontais + 3 verticais.</p>
                @endif
            </div>
        </div>

        <div class="casino-card">
            <p class="casino-eyebrow">COMBINAÇÕES QUE PAGAM</p>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-zinc-300">
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">→ 3 iguais</div>
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">↓ 3 iguais</div>
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">3 horizontais</div>
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">3 verticais</div>
            </div>
            <p class="mt-3 text-xs leading-5 text-zinc-500">Podem existir várias linhas vencedoras no mesmo giro e os prémios acumulam.</p>
        </div>

        <details class="casino-card slot-fair">
            <summary>🔐 Jogo transparente</summary>
            <p class="mt-3 text-xs text-zinc-400">O hash do servidor é fixado antes do resultado e a ronda pode ser verificada.</p>

            @if ($serverSeedHash)
                <p class="mt-3 text-[0.65rem] uppercase text-zinc-500">Hash do servidor</p>
                <p class="break-all font-mono text-xs text-zinc-300">{{ $serverSeedHash }}</p>
            @endif

            <label class="mt-3 block space-y-1 text-xs">Seed do cliente
                <input type="text" maxlength="128" wire:model="clientSeed" :disabled="busy || @js($locked)"
                       class="block w-full rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 font-mono text-xs text-white">
            </label>
        </details>

        <p class="px-1 text-center text-[0.7rem] text-zinc-500">Créditos virtuais — sem valor monetário</p>
    </aside>
</div>