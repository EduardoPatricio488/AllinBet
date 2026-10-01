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
        $index = (int) ($line['line'] ?? 0);

        if (($line['direction'] ?? '') === 'vertical') {
            return collect(range(0, 2))->map(fn ($row) => ($row * 3) + $index);
        }

        return collect(range(0, 2))->map(fn ($column) => ($index * 3) + $column);
    })->unique()->values()->all();
@endphp

<div class="casino-game-play grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,20rem)]"
     x-data="{
        reels: [false, false, false],
        settling: [false, false, false],
        finalReveal: true,
        busy: false,
        resultVisible: true,
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
            this.resultVisible = false;
            this.prizeVisible = false;
            this.finalReveal = false;
            let spun = false;
            this.busy = true;
            this.reels = [true, true, true];
            this.settling = [false, false, false];
            const t0 = Date.now();

            try {
                if (w.roundPhase !== 'prepared') await w.prepare();
                if (w.roundPhase === 'prepared') {
                    await w.spin();
                    spun = true;
                }
            } catch (e) {}

            const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (calm) {
                this.reels = [false, false, false];
                this.settling = [false, false, false];
            } else {
                await new Promise(r => setTimeout(r, Math.max(0, 1250 - (Date.now() - t0))));
                this.settling[0] = true;
                await new Promise(r => setTimeout(r, 180));
                this.reels[0] = false;
                await new Promise(r => setTimeout(r, 80));
                this.settling[0] = false;

                await new Promise(r => setTimeout(r, 330));
                this.settling[1] = true;
                await new Promise(r => setTimeout(r, 180));
                this.reels[1] = false;
                await new Promise(r => setTimeout(r, 80));
                this.settling[1] = false;

                await new Promise(r => setTimeout(r, 330));
                this.settling[2] = true;
                await new Promise(r => setTimeout(r, 180));
                this.reels[2] = false;
                await new Promise(r => setTimeout(r, 80));
                this.settling[2] = false;
            }

            this.finalReveal = true;
            this.resultVisible = true;
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

    <x-casino.how-it-works game-key="slots" title="Como funcionam as Slots?" description="Faz girar uma grelha 3×3 com 6 linhas de pagamento: 3 horizontais e 3 verticais. Só combinações de três símbolos iguais numa dessas linhas pagam." :rules="[['title'=>'Escolhe a aposta','text'=>'A aposta total é distribuída pelas 6 linhas de pagamento.'], ['title'=>'Gira os rolos','text'=>'Carrega em Girar para revelar os 9 símbolos da grelha.'], ['title'=>'6 linhas de pagamento','text'=>'Existem três linhas horizontais e três linhas verticais.'], ['title'=>'Só 3 iguais pagam','text'=>'Uma linha só paga quando os seus 3 símbolos são iguais. Os prémios das linhas vencedoras acumulam.']]" />

<style>
        .slot-machine { --cell: clamp(4.2rem, 16vw, 6.6rem); --gold: #f2c14e; --gold-hi: #ffe39a; }
        .allin-slots { --green: #23d99a; --line: rgba(255,255,255,.08); }

        /* Cabinet */
        .slot-cabinet {
            position: relative; overflow: hidden; padding: 1.1rem; border-radius: 1.75rem;
            border: 1px solid rgba(242, 193, 78, .3);
            background: radial-gradient(120% 60% at 50% 0, rgba(35, 217, 154, .12), transparent 60%), linear-gradient(160deg, #0b1714, #0c0f12 60%, #070a0b);
            box-shadow: 0 40px 80px rgba(0, 0, 0, .5), inset 0 1px 0 rgba(255, 255, 255, .07);
        }
        .slot-cabinet .casino-bulbs { inset: 5px; border-width: 3px; opacity: .5; }
        .slot-header { position: relative; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: 1rem; }
        .slot-title { font-size: clamp(1.1rem, 3vw, 1.5rem); font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
        .slot-title em { font-style: normal; color: var(--gold); }
        .slot-badge { padding: .35rem .7rem; border: 1px solid rgba(242, 193, 78, .25); border-radius: 9999px; font-size: .7rem; color: #cdb878; }

        /* Janela dos rolos */
        .slot-window {
            position: relative; display: grid; grid-template-columns: auto 1fr auto; gap: .5rem; padding: .8rem;
            border-radius: 1.2rem; background: #030706;
            box-shadow: inset 0 0 40px #000, 0 0 0 2px rgba(242, 193, 78, .45), 0 0 0 6px rgba(242, 193, 78, .1);
        }
        .slot-markers { display: grid; grid-template-rows: repeat(3, var(--cell)); align-items: center; }
        .slot-markers span {
            display: grid; place-items: center; width: 1.5rem; height: 1.5rem; border-radius: 9999px;
            font-size: .7rem; font-weight: 800; color: #6f7c76; background: #101816; border: 1px solid rgba(255, 255, 255, .08);
        }
        .slot-window.is-done .slot-markers .is-win { color: #382600; background: var(--gold); box-shadow: 0 0 14px rgba(242, 193, 78, .8); }

        .slot-reels { position: relative; display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
        .slot-reel {
            position: relative; overflow: hidden; height: calc(var(--cell) * 3); border-radius: .8rem;
            background: linear-gradient(180deg, #19221f, #0a0f0e);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .07);
        }
        .slot-reel::after {
            content: ''; position: absolute; inset: 0; z-index: 3; pointer-events: none;
            background: linear-gradient(180deg, rgba(0, 0, 0, .7), transparent 28%, transparent 72%, rgba(0, 0, 0, .7));
        }
        .slot-sym { display: block; width: 100%; height: var(--cell); padding: 14%; filter: drop-shadow(0 5px 6px rgba(0, 0, 0, .45)); }
        .slot-spinner { display: none; }
        .slot-landing { display: grid; }
        .slot-reel.is-spin .slot-landing { display: none; }
        .slot-reel.is-spin .slot-spinner { display: grid; animation: slot-loop var(--t, .5s) linear infinite; filter: blur(2.2px); }
        .slot-reel:nth-child(2) { --t: .46s; }
        .slot-reel:nth-child(3) { --t: .42s; }
        .slot-reel.is-land .slot-landing { animation: slot-land .55s cubic-bezier(.22, .8, .3, 1) both; }

        @keyframes slot-loop {
            from { transform: translateY(calc(var(--cell) * -8)); }
            to { transform: translateY(0); }
        }
        @keyframes slot-land {
            from { transform: translateY(calc(var(--cell) * -6)); }
            65% { transform: translateY(calc(var(--cell) * .14)); }
            82% { transform: translateY(calc(var(--cell) * -.05)); }
            to { transform: translateY(0); }
        }

        /* Vitória: só visível quando todos os rolos terminaram */
        .slot-payline {
            position: absolute; left: -.3rem; right: -.3rem; top: calc((var(--row) + .5) * var(--cell)); z-index: 4;
            height: 3px; border-radius: 2px; opacity: 0; pointer-events: none;
            background: linear-gradient(90deg, transparent, var(--gold), #fff, var(--gold), transparent);
            box-shadow: 0 0 14px rgba(242, 193, 78, .9);
        }
        .slot-payline--vertical {
            top: -.3rem; bottom: -.3rem; left: calc((var(--col) + .5) * 33.333%); right: auto;
            width: 3px; height: auto;
            background: linear-gradient(180deg, transparent, var(--gold), #fff, var(--gold), transparent);
        }
        .slot-window.is-done .slot-payline { opacity: 1; animation: slot-line .9s ease-in-out infinite alternate; }
        .slot-window.is-done.has-win .slot-sym:not(.is-win) { opacity: .4; }
        .slot-window.is-done .slot-sym.is-win {
            animation: slot-pulse .7s ease-in-out 4 alternate;
            filter: drop-shadow(0 0 12px rgba(242, 193, 78, .9));
        }
        @keyframes slot-pulse { to { transform: scale(1.1); } }
        @keyframes slot-line { to { opacity: .55; } }

        /* Painel de controlo */
        .slot-deck {
            position: relative; display: grid; grid-template-columns: minmax(12rem, 1fr) auto minmax(10rem, 1fr); align-items: center; gap: 1rem;
            margin-top: 1rem; padding: 1rem; border-radius: 1rem; border: 1px solid rgba(255, 255, 255, .08); background: rgba(255, 255, 255, .025);
        }
        .slot-label { display: block; font-size: .7rem; color: #8b9893; }
        .slot-stepper { display: grid; grid-template-columns: 2.5rem minmax(4rem, 1fr) 2.5rem; margin-top: .35rem; overflow: hidden; border-radius: .8rem; border: 1px solid rgba(255, 255, 255, .1); background: #070c0b; }
        .slot-stepper button { background: rgba(255, 255, 255, .03); color: #dfe6e3; font-size: 1.1rem; }
        .slot-stepper button:hover:not(:disabled) { background: rgba(242, 193, 78, .12); }
        .slot-bet-input { width: 100%; padding: .55rem 0; text-align: center; font-size: 1.15rem; font-weight: 800; color: #fff; background: transparent; border: 0; border-inline: 1px solid rgba(255, 255, 255, .1); outline: 0; font-variant-numeric: tabular-nums; }
        .slot-bet-input:focus-visible, .slot-stepper button:focus-visible, .slot-chips button:focus-visible, .slot-spin:focus-visible { outline: 2px solid var(--gold-hi); outline-offset: 2px; }
        .slot-hint { display: block; margin-top: .3rem; font-size: .7rem; color: #78857f; }
        .slot-chips { display: flex; flex-wrap: wrap; justify-content: center; gap: .35rem; }
        .slot-chips button { padding: .45rem .75rem; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, .1); background: rgba(255, 255, 255, .03); color: #b7c1bc; font-size: .75rem; font-weight: 700; transition: transform .15s, color .15s, border-color .15s; }
        .slot-chips button:hover:not(:disabled) { transform: translateY(-2px); color: var(--gold); border-color: rgba(242, 193, 78, .45); }
        .slot-spin {
            min-height: 3.6rem; padding: .5rem 1.5rem; border-radius: 1rem; border: 1px solid rgba(242, 193, 78, .6);
            font-size: 1.05rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: #2b1c05;
            background: linear-gradient(180deg, #ffe9a8, #e4ae39 55%, #9c6610);
            box-shadow: 0 5px 0 #6b470b, 0 14px 30px rgba(177, 116, 20, .25), inset 0 1px rgba(255, 255, 255, .5);
            transition: transform .08s, box-shadow .08s, filter .15s;
        }
        .slot-spin small { display: block; font-size: .6rem; font-weight: 600; letter-spacing: .04em; text-transform: none; opacity: .65; }
        .slot-spin:hover:not(:disabled) { filter: brightness(1.07); }
        .slot-spin:active:not(:disabled) { transform: translateY(4px); box-shadow: 0 1px 0 #6b470b, 0 6px 16px rgba(177, 116, 20, .25); }
        .slot-spin:disabled, .slot-stepper button:disabled, .slot-chips button:disabled, .slot-bet-input:disabled { opacity: .55; cursor: not-allowed; }
        .slot-error { margin-top: .75rem; font-size: .85rem; color: #fda4af; }

        /* Prémio */
        .slot-prize-overlay {
            position: absolute; inset: 0; z-index: 20; display: grid; place-items: center; pointer-events: none;
            background: radial-gradient(circle, rgba(0, 0, 0, .06), rgba(0, 0, 0, .22));
            backdrop-filter: none; -webkit-backdrop-filter: none;
        }
        .slot-prize__card, .slot-prize-card {
            min-width: min(88%, 26rem); padding: 1.4rem 2rem; text-align: center; border-radius: 1.4rem; border: 2px solid rgba(242, 193, 78, .8);
            background: linear-gradient(145deg, rgba(10, 16, 14, .97), rgba(43, 31, 8, .97));
            box-shadow: 0 0 60px rgba(242, 193, 78, .4), 0 30px 80px rgba(0, 0, 0, .6); animation: slot-prize-in .45s cubic-bezier(.2, .9, .25, 1.2);
        }
        .slot-prize__card p, .slot-prize-card p { font-size: .9rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--gold); }
        .slot-prize__card strong, .slot-prize-amount { display: block; font-size: clamp(2.8rem, 9vw, 5rem); line-height: 1.05; color: #fff; text-shadow: 0 0 28px rgba(242, 193, 78, .55); font-variant-numeric: tabular-nums; }
        .slot-prize__card small, .slot-prize-sub { color: #aef5d2; font-weight: 700; }
        @keyframes slot-prize-in { from { opacity: 0; transform: scale(.65) translateY(1rem); } to { opacity: 1; transform: none; } }

        /* Lateral */
        .slot-payout { font-size: 1.9rem; font-weight: 800; line-height: 1.1; }
        .slot-payout--win { color: var(--gold); text-shadow: 0 0 18px rgba(242, 193, 78, .5); }

        @media (max-width: 900px) { .slot-deck { grid-template-columns: 1fr 1fr; } .slot-spin { grid-column: 1 / -1; } .slot-chips { justify-content: flex-start; } }
        @media (max-width: 560px) {
            .slot-deck { grid-template-columns: 1fr; } .slot-spin { grid-column: auto; }
            .slot-markers span { width: 1.2rem; height: 1.2rem; font-size: .6rem; }
            .slot-window { padding: .55rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .slot-reel.is-spin .slot-spinner, .slot-reel.is-land .slot-landing, .slot-window.is-done .slot-payline,
            .slot-window.is-done .slot-sym.is-win, .slot-prize__card, .slot-prize-card { animation: none; }
            .slot-reel.is-spin .slot-spinner { filter: none; }
        }
    </style>

    <section class="slot-machine relative allin-slots" :class="{ 'is-busy': busy }">
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
                <span class="slot-badge">9 POSIÇÕES · 3×3</span>
            </div>

            <div class="slot-window"
                 :class="{
                    'is-done': finalReveal,
                    'has-win': finalReveal && @js(count($winningLines) > 0)
                 }">
                <div class="slot-markers" aria-hidden="true">
                    @foreach ([0, 1, 2] as $r)
                        <span class="slot-marker" :class="{ 'is-win': finalReveal && @js(in_array($r, $horizontalWins, true)) }">{{ $r + 1 }}</span>
                    @endforeach
                </div>

                <div class="slot-reels" role="img" aria-label="Grelha de slots com 9 posições, 3 colunas e 3 linhas">
                    @foreach ([0, 1, 2] as $col)
                        <div class="slot-reel"
                             :class="{
                                'is-spin': reels[{{ $col }}],
                                'is-land': settling[{{ $col }}]
                             }">
                            <div class="slot-spinner" aria-hidden="true">
                                @for ($k = 0; $k < 3; $k++)
                                    @foreach ($symbols as $s)
                                        <span class="slot-sym">{{ $s }}</span>
                                    @endforeach
                                @endfor
                            </div>

                            <div class="slot-landing">
                                @foreach ($grid as $rowIndex => $row)
                                    @php $cellIndex = ($rowIndex * 3) + $col; @endphp
                                    <span class="slot-sym"
                                          :class="{ 'is-win': finalReveal && @js(in_array($cellIndex, $winningCells, true)) }">
                                        {{ $glyph((int) ($row[$col] ?? 0)) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <template x-if="finalReveal">
                        <div aria-hidden="true">
                            @foreach ($horizontalWins as $r)
                                <i class="slot-payline" style="--row: {{ $r }}" aria-label="Linha horizontal vencedora"></i>
                            @endforeach
                            @foreach ($verticalWins as $col)
                                <i class="slot-payline slot-payline--vertical" style="--col: {{ $col }}" aria-label="Linha vertical vencedora"></i>
                            @endforeach
                        </div>
                    </template>
                </div>
            </div>

            <div class="slot-payline-legend" aria-label="Linhas de pagamento">
                <span>9 posições · 3×3</span><span>↔ 3 horizontais + ↕ 3 verticais</span>
                <span>3 símbolos iguais = prémio</span>
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
                    @foreach ([6, 12, 30, 60, 120] as $chip)
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
            <p x-show="!resultVisible" class="mt-2 text-sm text-zinc-400">Os rolos estão a girar…</p>

            <div x-show="resultVisible" x-cloak class="mt-2">
                @if ($roundResult !== [])
                    @if ((int) $roundPayout > 0)
                        <p class="slot-payout slot-payout--win">+{{ number_format((int) $roundPayout) }}</p>
                        <p class="text-sm text-zinc-400">
                            {{ count($winningLines) }} linha(s) vencedora(s) · apenas triplos pagam
                        </p>
                    @else
                        <p class="slot-payout">Sem prémio</p>
                        <p class="text-sm text-zinc-400">Só 3 símbolos iguais na mesma linha horizontal ou vertical dão prémio.</p>
                    @endif

                    @if ($roundPhase === 'completed' && $roundId)
                        <button type="button" class="mt-3 inline-block text-sm text-emerald-300 underline underline-offset-4 hover:text-emerald-200" x-data x-on:click="$dispatch('casino-open-fairness', { roundId: {{ $roundId }} })">Verificar esta ronda</button>
                    @endif
                @else
                    <p class="text-sm text-zinc-400">6 linhas de prémio: 3 horizontais e 3 verticais.</p>
                @endif
            </div>
        </div>

        <div class="casino-card">
            <p class="casino-eyebrow">COMBINAÇÕES QUE PAGAM</p>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-zinc-300">
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">3 iguais</div>
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">3 horizontais</div>
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">3 verticais</div>
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">3 símbolos iguais</div>
            </div>
            <p class="mt-3 text-xs leading-5 text-zinc-500">Existem 6 linhas de pagamento: 3 horizontais e 3 verticais. Só três símbolos iguais na mesma linha pagam; várias linhas vencedoras acumulam.</p>
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
