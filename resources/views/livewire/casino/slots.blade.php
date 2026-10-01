@php
    $symbols = ['🍒', '🍋', '🍊', '🔔', '⭐', '🍀', '💎', '7️⃣'];
    $glyph = fn (int $s) => $symbols[$s] ?? chr(65 + $s);

    $grid = $roundResult['grid'] ?? [[0, 1, 2], [3, 4, 5], [2, 1, 0]];
    $maxBet = (int) config('casino.bet_limits.max');
    $locked = $roundPhase === 'prepared';

    $winRows = $roundPhase === 'completed'
        ? collect($roundResult['winning_lines'] ?? [])
            ->map(fn ($l) => is_array($l) ? ($l['line'] ?? $l['row'] ?? null) : $l)
            ->filter(fn ($v) => is_int($v))
            ->values()
            ->all()
        : [];
@endphp

<div class="casino-game-play grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,20rem)]"
     x-data="{
        reels: [false, false, false],
        busy: false,
        maxBet: {{ $maxBet }},
        step(d) {
            const v = Math.round((Number(this.$wire.bet || 3) + d) / 3) * 3;
            this.$wire.bet = Math.min(this.maxBet, Math.max(3, v));
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
            } catch (e) {
                // O Livewire continua a ser a autoridade do resultado.
            }

            const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            await new Promise(r => setTimeout(r, calm ? 0 : Math.max(0, 1000 - (Date.now() - t0))));

            for (let i = 0; i < 3; i++) {
                this.reels[i] = false;
                if (!calm) await new Promise(r => setTimeout(r, 320));
            }

            this.busy = false;

            const payout = Number(w.roundPayout || 0);
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

    <section class="slot-machine" :class="{ 'is-busy': busy }">
        <div class="slot-cabinet">
            <span class="casino-bulbs" aria-hidden="true"></span>

            <div class="slot-header">
                <span class="slot-title">ALLINBET <em class="casino-shimmer-text">SLOTS</em></span>
                <span class="slot-badge">3 LINHAS · 3 ROLOS</span>
            </div>

            <div class="slot-window">
                <div class="slot-markers" aria-hidden="true">
                    @foreach ([0, 1, 2] as $r)
                        <span class="slot-marker {{ in_array($r, $winRows, true) ? 'slot-marker--win' : '' }}">{{ $r + 1 }}</span>
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
                                    <span class="slot-cell {{ in_array($rowIndex, $winRows, true) ? 'slot-cell--win' : '' }}">{{ $glyph((int) ($row[$col] ?? 0)) }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @foreach ($winRows as $r)
                        <i class="slot-payline" style="--row: {{ $r }}" aria-hidden="true"></i>
                    @endforeach
                </div>
            </div>

            <div class="slot-deck">
                <div class="slot-bet">
                    <span class="slot-label">APOSTA TOTAL</span>
                    <div class="slot-stepper">
                        <button type="button" x-on:click="step(-3)" :disabled="busy || @js($locked)" aria-label="Diminuir aposta">−</button>
                        <input type="number" min="3" step="3" max="{{ $maxBet }}" wire:model="bet"
                               :disabled="busy || @js($locked)" class="slot-bet-input" aria-label="Aposta total em créditos">
                        <button type="button" x-on:click="step(3)" :disabled="busy || @js($locked)" aria-label="Aumentar aposta">+</button>
                    </div>
                    <small class="slot-hint">por linha: <b x-text="Math.floor(Number($wire.bet || 0) / 3)"></b> · múltiplos de 3</small>
                </div>

                <div class="slot-chips" aria-label="Apostas rápidas">
                    @foreach ([3, 15, 30, 60, 150] as $chip)
                        @if ($chip <= $maxBet)
                            <button type="button" x-on:click="$wire.bet = {{ $chip }}" :disabled="busy || @js($locked)">{{ $chip }}</button>
                        @endif
                    @endforeach
                    <button type="button" x-on:click="$wire.bet = Math.floor(maxBet / 3) * 3" :disabled="busy || @js($locked)">MAX</button>
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
                        <p class="text-sm text-zinc-400">{{ count($roundResult['winning_lines'] ?? []) }} linha(s) vencedora(s) · créditos virtuais</p>
                    @else
                        <p class="slot-payout">Sem prémio</p>
                        <p class="text-sm text-zinc-400">Cada ronda é independente das anteriores.</p>
                    @endif

                    @if ($roundPhase === 'completed' && $roundId)
                        <a href="{{ route('fairness.verify', $roundId) }}" wire:navigate
                           class="mt-3 inline-block text-sm text-emerald-300 underline underline-offset-4">Verificar esta ronda ↗</a>
                    @endif
                @else
                    <p class="text-sm text-zinc-400">Três linhas horizontais. Prémios pagos em créditos inteiros.</p>
                @endif
            </div>
        </div>

        <details class="casino-card slot-fair">
            <summary>🔐 Jogo transparente</summary>
            <p class="mt-3 text-xs text-zinc-400">O hash do servidor é fixado antes do resultado. Pode alterar a sua seed para influenciar o sorteio.</p>

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
