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
        .allin-slots{--gold:#f2c14e;--green:#23d99a;--panel:#0a1010;--line:rgba(255,255,255,.08)}
        .slot-cabinet{position:relative;overflow:hidden;border:1px solid rgba(242,193,78,.22);border-radius:2rem;background:radial-gradient(circle at 50% 0%,rgba(35,217,154,.1),transparent 35%),linear-gradient(145deg,#07110f,#101315 52%,#090d0f);box-shadow:0 35px 90px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.06)}
        .slot-cabinet:before{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(115deg,transparent 20%,rgba(255,255,255,.035) 50%,transparent 80%);transform:translateX(-100%);animation:slotSheen 7s ease-in-out infinite}
        .slot-header{position:relative;z-index:2;display:flex;align-items:end;justify-content:space-between;gap:1rem;padding:1.25rem 1.35rem .9rem}.slot-title{font-size:1.55rem;font-weight:1000;letter-spacing:.08em;color:#f7f5ed;text-shadow:0 0 22px rgba(242,193,78,.2)}.slot-title em{font-style:normal;color:var(--gold)}.slot-badge{padding:.42rem .65rem;border:1px solid rgba(242,193,78,.18);border-radius:999px;background:rgba(242,193,78,.05);font-size:.58rem;font-weight:950;letter-spacing:.14em;color:#b8a36a}
        .slot-window{position:relative;margin:0 1.2rem;padding:1.05rem;border:1px solid rgba(255,255,255,.09);border-radius:1.5rem;background:radial-gradient(circle at 50% 40%,rgba(35,217,154,.08),transparent 45%),#030706;box-shadow:inset 0 0 50px rgba(0,0,0,.75),0 15px 40px rgba(0,0,0,.28)}
        .slot-window:before{content:"";position:absolute;inset:.7rem;z-index:6;pointer-events:none;border-radius:1.1rem;box-shadow:inset 0 0 35px rgba(0,0,0,.65),inset 0 0 2px rgba(255,255,255,.1)}.slot-window:after{content:"";position:absolute;inset:0;pointer-events:none;border-radius:1.5rem;box-shadow:inset 0 0 0 1px rgba(255,255,255,.025),inset 0 0 45px rgba(35,217,154,.04)}
        .slot-reels{position:relative;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.65rem;min-height:23rem}
        .slot-reel{position:relative;overflow:hidden;height:23rem;min-height:23rem;perspective:700px;border:1px solid rgba(255,255,255,.09);border-radius:1rem;background:linear-gradient(180deg,#151c1c,#070a0a);box-shadow:inset 0 12px 20px rgba(255,255,255,.025),inset 0 -18px 25px rgba(0,0,0,.5)}
        .slot-reel:before,.slot-reel:after{content:"";position:absolute;left:0;right:0;z-index:4;height:5rem;pointer-events:none}.slot-reel:before{top:0;background:linear-gradient(#020404,transparent)}.slot-reel:after{bottom:0;background:linear-gradient(transparent,#020404)}
        .slot-landed,.slot-strip{position:absolute;inset:0;height:100%;display:grid;grid-template-rows:repeat(3,minmax(0,1fr));grid-auto-flow:row;min-height:0}.slot-landed{will-change:transform,opacity;transform:translateZ(0);opacity:1;transition:opacity .16s ease,transform .16s ease}.slot-landed--hidden{opacity:0;transform:scale(.985);pointer-events:none}.slot-landed--settling{animation:slotLandedSettle .18s cubic-bezier(.2,.9,.25,1)}.slot-strip{position:absolute;inset:0;display:grid;grid-template-rows:repeat(24,minmax(0,1fr));will-change:transform,filter;transform:translate3d(0,0,0);backface-visibility:hidden;opacity:0;transition:opacity .12s ease}.slot-strip--spinning{opacity:1;animation:slotReel 1.15s linear infinite}.slot-strip--settling{animation:slotReelStop .18s cubic-bezier(.16,1,.3,1) forwards}.slot-cell{position:relative;display:grid;place-items:center;min-height:0;height:100%;font-size:clamp(2.4rem,7vw,4.5rem);line-height:1;filter:drop-shadow(0 7px 8px rgba(0,0,0,.35));transition:transform .2s,filter .2s}.slot-strip .slot-cell{font-size:clamp(2rem,5vw,3.4rem);opacity:.58;filter:blur(2.2px);transform:scaleY(1.04);text-shadow:0 0 12px rgba(255,255,255,.12)}.slot-reel:nth-child(1) .slot-strip--spinning{animation-duration:1.08s}.slot-reel:nth-child(2) .slot-strip--spinning{animation-duration:1.14s}.slot-reel:nth-child(3) .slot-strip--spinning{animation-duration:1.2s}
        .slot-cell--win{z-index:5;border-radius:.9rem;background:radial-gradient(circle,rgba(242,193,78,.25),transparent 65%);box-shadow:0 0 28px rgba(242,193,78,.35),inset 0 0 0 1px rgba(242,193,78,.28);animation:slotWinPulse .7s ease-in-out infinite alternate;filter:drop-shadow(0 0 12px rgba(242,193,78,.45))}
        .slot-marker{position:absolute;left:-.15rem;z-index:10;width:1.8rem;height:1.8rem;border-radius:50%;display:grid;place-items:center;background:#121918;border:1px solid rgba(255,255,255,.08);font-size:.58rem;font-weight:1000;color:#68756f}.slot-marker--win{background:var(--gold);color:#382600;box-shadow:0 0 18px rgba(242,193,78,.45)}
        .slot-payline-legend{display:flex;justify-content:center;flex-wrap:wrap;gap:.5rem .9rem;margin:.75rem .2rem 0;color:#7f8b85;font-size:.58rem;font-weight:900;letter-spacing:.05em;text-transform:uppercase}.slot-payline-legend span{padding:.35rem .55rem;border:1px solid rgba(242,193,78,.1);border-radius:999px;background:rgba(242,193,78,.025)}
        .slot-payline{position:absolute;left:1.2%;right:1.2%;top:calc((var(--row) + .5) * 33.333%);height:3px;z-index:7;background:linear-gradient(90deg,transparent,var(--gold),#fff,var(--gold),transparent);box-shadow:0 0 12px rgba(242,193,78,.85);pointer-events:none;animation:slotLine .8s ease-in-out infinite alternate}.slot-payline--vertical{top:1.2%;bottom:1.2%;left:calc((var(--col) + .5) * 33.333%);right:auto;width:3px;height:auto;background:linear-gradient(180deg,transparent,var(--gold),#fff,var(--gold),transparent)}
        .slot-deck{position:relative;z-index:2;display:grid;grid-template-columns:minmax(13rem,1fr) auto minmax(10rem,1fr);align-items:center;gap:1rem;padding:1.1rem 1.25rem 1.3rem}.slot-label{display:block;font-size:.58rem;font-weight:950;letter-spacing:.18em;color:#77837e}.slot-stepper{display:grid;grid-template-columns:2.4rem minmax(5rem,1fr) 2.4rem;margin-top:.4rem;border:1px solid var(--line);border-radius:.8rem;overflow:hidden;background:#080d0c}.slot-stepper button{border:0;background:rgba(255,255,255,.025);color:#d8dfdc;font-size:1.05rem;cursor:pointer}.slot-stepper button:hover:not(:disabled){background:rgba(242,193,78,.1)}.slot-bet-input{width:100%;border:0;border-inline:1px solid var(--line);background:transparent;text-align:center;color:#fff;font-weight:950;outline:0}.slot-hint{display:block;margin-top:.3rem;color:#65716c;font-size:.58rem}.slot-chips{display:flex;flex-wrap:wrap;justify-content:center;gap:.35rem}.slot-chips button{padding:.45rem .6rem;border:1px solid var(--line);border-radius:999px;background:rgba(255,255,255,.025);color:#aeb8b3;font-size:.6rem;font-weight:900;cursor:pointer;transition:.16s}.slot-chips button:hover:not(:disabled){transform:translateY(-2px);border-color:rgba(242,193,78,.4);color:var(--gold)}
        .slot-spin{min-height:3.5rem;border:1px solid rgba(242,193,78,.55);border-radius:1rem;background:linear-gradient(135deg,#ffe39a,#e4ae39 48%,#9c6610);color:#2b1c05;font-size:.9rem;font-weight:1000;letter-spacing:.1em;box-shadow:0 14px 32px rgba(177,116,20,.22),inset 0 1px rgba(255,255,255,.5);cursor:pointer;transition:.18s}.slot-spin:hover:not(:disabled){transform:translateY(-2px);filter:brightness(1.05)}.slot-spin:active:not(:disabled){transform:translateY(1px)}.slot-spin small{display:block;margin-top:.15rem;font-size:.5rem;letter-spacing:.04em;opacity:.65}.slot-spin:disabled{opacity:.6;cursor:not-allowed}
        .slot-prize-overlay{position:absolute;inset:0;z-index:30;display:grid;place-items:center;pointer-events:none;background:radial-gradient(circle,rgba(0,0,0,.06),rgba(0,0,0,.22));backdrop-filter:none;-webkit-backdrop-filter:none}.slot-prize-card{min-width:min(88%,30rem);padding:1.5rem 2rem;border:2px solid rgba(242,193,78,.8);border-radius:1.4rem;background:linear-gradient(145deg,rgba(10,16,14,.98),rgba(43,31,8,.98));box-shadow:0 0 60px rgba(242,193,78,.4),0 30px 80px rgba(0,0,0,.6);text-align:center;animation:slotPrizeIn .45s cubic-bezier(.2,.9,.25,1.2),slotPrizePulse 1s ease-in-out .45s 2}.slot-prize-label{color:var(--gold);font-size:.7rem;font-weight:950;letter-spacing:.2em;text-transform:uppercase}.slot-prize-amount{margin:.25rem 0;color:#fff;font-size:clamp(3rem,8vw,5.5rem);font-weight:1000;line-height:1;text-shadow:0 0 28px rgba(242,193,78,.55)}.slot-prize-sub{color:#aef5d2;font-size:.85rem;font-weight:850}
        .slot-stat{padding:.9rem;border:1px solid var(--line);border-radius:1rem;background:rgba(255,255,255,.02)}.slot-stat strong{font-size:1.2rem;color:#fff}.slot-stat span{display:block;margin-top:.2rem;font-size:.58rem;color:#68756f;text-transform:uppercase;letter-spacing:.12em}
        @keyframes slotReel{0%{transform:translate3d(0,0,0)}100%{transform:translate3d(0,-66.6667%,0)}}@keyframes slotReelStop{0%{transform:translate3d(0,-66.6667%,0) scaleY(1.04);filter:blur(2.2px)}55%{transform:translate3d(0,-69%,0) scaleY(1.015);filter:blur(1px)}100%{transform:translate3d(0,-66.6667%,0) scaleY(1);filter:blur(0)}}@keyframes slotLandedSettle{0%{transform:scale(1.025)}100%{transform:scale(1)}}@keyframes slotWinPulse{to{transform:scale(1.08);filter:drop-shadow(0 0 20px rgba(242,193,78,.7))}}@keyframes slotLine{to{opacity:.45;box-shadow:0 0 5px rgba(242,193,78,.4)}}@keyframes slotPrizeIn{0%{opacity:0;transform:scale(.65) translateY(1rem)}65%{transform:scale(1.06)}100%{opacity:1;transform:scale(1)}}@keyframes slotPrizePulse{50%{transform:scale(1.025);box-shadow:0 0 80px rgba(242,193,78,.6),0 30px 80px rgba(0,0,0,.6)}}@keyframes slotSheen{0%,60%{transform:translateX(-100%)}80%,100%{transform:translateX(100%)}}
        @media(max-width:900px){.slot-deck{grid-template-columns:1fr 1fr}.slot-spin{grid-column:1/-1}.slot-chips{justify-content:flex-start}}@media(max-width:640px){.slot-header{padding:.9rem}.slot-window{margin:0 .7rem;padding:.65rem}.slot-reels{gap:.35rem;min-height:17rem}.slot-reel{height:17rem;min-height:17rem}.slot-cell{min-height:0;font-size:2.3rem}.slot-deck{grid-template-columns:1fr;padding:.8rem}.slot-spin{grid-column:auto}.slot-title{font-size:1.1rem}.slot-badge{font-size:.5rem}}
        @media(prefers-reduced-motion:reduce){.slot-cabinet:before,.slot-strip,.slot-cell--win,.slot-payline,.slot-prize-card{animation:none}}
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

            <div class="slot-window" :class="{ 'slot-window--spinning': busy }">
                <div class="slot-markers" aria-hidden="true">
                    @foreach ([0, 1, 2] as $r)
                        <span class="slot-marker" :class="{ 'slot-marker--win': finalReveal && @js(in_array($r, $horizontalWins, true)) }">{{ $r + 1 }}</span>
                    @endforeach
                </div>

                <div class="slot-reels" role="img" aria-label="Grelha de slots com 9 posições, 3 colunas e 3 linhas">
                    @foreach ([0, 1, 2] as $col)
                        <div class="slot-reel" :class="{ 'slot-reel--settling': settling[{{ $col }}] }">
                            <div class="slot-strip" :class="{ 'slot-strip--spinning': reels[{{ $col }}], 'slot-strip--settling': settling[{{ $col }}] }" aria-hidden="true">
                                @for ($k = 0; $k < 3; $k++)
                                    @foreach ($symbols as $s)
                                        <span class="slot-cell">{{ $s }}</span>
                                    @endforeach
                                @endfor
                            </div>

                            <div class="slot-landed" :class="{ 'slot-landed--hidden': reels[{{ $col }}], 'slot-landed--settling': settling[{{ $col }}] }">
                                @foreach ($grid as $rowIndex => $row)
                                    @php $cellIndex = ($rowIndex * 3) + $col; @endphp
                                    <span class="slot-cell"
                                          :class="{ 'slot-cell--win': finalReveal && @js(in_array($cellIndex, $winningCells, true)) }">
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
                        <button type="button" x-on:click="step(-3)" :disabled="busy || @js($locked)" aria-label="Diminuir aposta">−</button>
                        <input type="number" min="6" step="6" max="{{ $maxBet }}" wire:model="bet"
                               :disabled="busy || @js($locked)" class="slot-bet-input" aria-label="Aposta total em créditos">
                        <button type="button" x-on:click="step(3)" :disabled="busy || @js($locked)" aria-label="Aumentar aposta">+</button>
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
