@php
    $maxBet = (int) config('casino.bet_limits.max', 10000);
    $houseEdge = (int) config('casino.games.dice.house_edge_bps', 500);
    $threshold = (int) $threshold;
    $chance = $direction === 'under' ? $threshold : 100 - $threshold;
    $grossMultiplier = $chance > 0 ? (100 - ($houseEdge / 100)) / $chance : 0;
    $potentialPayout = (int) floor(((int) $bet * (10000 - $houseEdge)) / max(1, $chance * 100));
    $locked = $roundPhase === 'prepared';
    $hasResult = $roundResult !== [];
    $rollValue = $hasResult ? (int) ($roundResult['roll'] ?? 0) : 5000;
    $displayNumber = intdiv($rollValue, 100).'.'.str_pad((string) ($rollValue % 100), 2, '0', STR_PAD_LEFT);
    $dieFace = $hasResult ? (($rollValue % 6) + 1) : 6;
@endphp

<div
    class="casino-game-play dice-page"
    x-data="{
        busy: false,
        rolling: false,
        resultVisible: {{ $hasResult ? 'true' : 'false' }},
        outcome: {{ $hasResult ? 'true' : 'false' }},
        displayedRoll: @js($displayNumber),
        displayedPayout: {{ (int) $roundPayout }},
        dieFace: {{ $dieFace }},
        previewThreshold: {{ $threshold }},
        maxBet: {{ $maxBet }},

        setThreshold(value) {
            const v = Math.min(99, Math.max(1, Number(value) || 50));
            this.previewThreshold = v;
            this.$wire.threshold = v;
        },

        chance() {
            return this.$wire.direction === 'under'
                ? Number(this.previewThreshold)
                : 100 - Number(this.previewThreshold);
        },

        multiplier() {
            const c = this.chance();
            return c > 0 ? (100 - 0.5) / c : 0;
        },

        stepBet(d) {
            const current = Number(this.$wire.bet || 1);
            const next = Math.round((current + d) / 5) * 5;
            this.$wire.bet = Math.min(this.maxBet, Math.max(1, next));
        },

        async rollNow() {
            if (this.busy || this.$wire.roundPhase !== 'prepared') return;

            this.busy = true;
            this.rolling = true;
            this.resultVisible = false;

            const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            try {
                await this.$wire.roll();

                const raw = Number(this.$wire.roundResult?.roll ?? 5000);
                const whole = Math.floor(raw / 100);
                const decimal = String(raw % 100).padStart(2, '0');

                this.outcome = Boolean(this.$wire.roundResult?.won);
                this.displayedRoll = whole + '.' + decimal;
                this.dieFace = (raw % 6) + 1;

                if (!calm) {
                    await new Promise(resolve => setTimeout(resolve, 1650));
                }

                this.rolling = false;
                this.resultVisible = true;
                this.displayedPayout = Number(this.$wire.roundPayout || 0);
            } catch (e) {
                this.rolling = false;
            } finally {
                this.busy = false;
            }
        }
    }"
    x-on:keydown.window="if ($event.code === 'Space' && !['INPUT','TEXTAREA','BUTTON','SELECT','SUMMARY'].includes($event.target.tagName)) { $event.preventDefault(); if (!busy && $wire.roundPhase === 'prepared') rollNow(); }"
    x-on:livewire:navigated.window="resultVisible = {{ $hasResult ? 'true' : 'false' }}"
>

    <x-casino.how-it-works game-key="dice" title="Como funciona o Dados?" description="Define um limite e escolhe se o lançamento deve ficar abaixo ou acima desse valor. O resultado é gerado de forma verificável." :rules="[['title'=>'Define o limite','text'=>'Escolhe o valor de referência entre 1 e 99.'], ['title'=>'Escolhe a direção','text'=>'Seleciona Abaixo ou Acima para definir a condição vencedora.'], ['title'=>'Define a aposta','text'=>'Escolhe a quantidade de créditos virtuais para esta ronda.'], ['title'=>'Lança os dados','text'=>'Carrega em Lançar. Se o resultado cumprir a condição escolhida, a ronda é vencedora.']]" />
    <style>
        .dice-page{--dice-gold:#f4c45f;--dice-green:#44e0a6;--dice-red:#fa6872;--dice-blue:#55a9ff}
        .dice-layout{display:grid;gap:1.5rem;grid-template-columns:minmax(0,1.5fr) minmax(18rem,.72fr)}
        .dice-stage{position:relative;overflow:hidden;min-height:35rem;border:1px solid rgba(255,255,255,.08);border-radius:1.75rem;background:radial-gradient(circle at 50% 22%,rgba(85,169,255,.1),transparent 28%),radial-gradient(circle at 50% 88%,rgba(244,196,95,.09),transparent 36%),linear-gradient(145deg,#0d121a,#080b10 58%,#11100d);box-shadow:0 30px 80px rgba(0,0,0,.28)}
        .dice-grid{position:absolute;inset:0;opacity:.3;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:40px 40px;mask-image:linear-gradient(to bottom,black,transparent 92%)}
        .dice-light{position:absolute;left:50%;top:48%;width:26rem;height:26rem;transform:translate(-50%,-50%);border-radius:50%;background:radial-gradient(circle,rgba(85,169,255,.14),rgba(85,169,255,.035) 38%,transparent 72%);filter:blur(4px);animation:diceLight 3.5s ease-in-out infinite}
        .dice-topbar{position:relative;z-index:3;display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.15rem 1.25rem 0}
        .dice-kicker{font-size:.67rem;font-weight:900;letter-spacing:.2em;text-transform:uppercase;color:#909aa8}
        .dice-status{display:flex;align-items:center;gap:.45rem;padding:.45rem .7rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(7,10,14,.62);font-size:.68rem;font-weight:850;color:#c1c8d2}
        .dice-status-dot{width:.45rem;height:.45rem;border-radius:50%;background:var(--dice-green);box-shadow:0 0 15px rgba(68,224,166,.8)}
        .dice-stage-content{position:relative;z-index:2;display:grid;place-items:center;min-height:27rem;padding:1rem}
        .dice-table-shadow{position:absolute;bottom:3.9rem;width:17rem;height:2.8rem;border-radius:50%;background:rgba(0,0,0,.62);filter:blur(17px);transition:.55s ease}.dice-table-shadow.is-rolling{width:8rem;opacity:.38;filter:blur(22px)}
        .dice-orbit{position:absolute;width:21rem;height:9rem;border:1px solid rgba(85,169,255,.1);border-radius:50%;transform:rotateX(64deg) rotateZ(-8deg);animation:diceOrbit 11s linear infinite}.dice-orbit::after{content:"";position:absolute;inset:.45rem;border:1px dashed rgba(244,196,95,.08);border-radius:50%}
        .dice-cube-wrap{position:relative;width:12rem;height:12rem;display:grid;place-items:center;perspective:1000px}
        .dice-cube{position:relative;width:8.6rem;height:8.6rem;transform-style:preserve-3d;transform:rotateX(-18deg) rotateY(-28deg) rotateZ(3deg);transition:transform .4s ease;filter:drop-shadow(0 24px 22px rgba(0,0,0,.36))}
        .dice-cube.is-rolling{animation:diceRoll 1.65s cubic-bezier(.15,.8,.18,1) both}
        .dice-face{position:absolute;inset:0;display:grid;place-items:center;border:2px solid rgba(36,43,53,.95);border-radius:1.05rem;background:linear-gradient(145deg,#f8fbff,#cfd8e5 52%,#9aa8ba);box-shadow:inset 0 2px 0 rgba(255,255,255,.9),inset 0 -8px 18px rgba(60,72,89,.22),0 0 0 1px rgba(255,255,255,.08)}
        .dice-face::after{content:"";position:absolute;inset:.35rem;border:1px solid rgba(255,255,255,.55);border-radius:.78rem}
        .dice-front{transform:translateZ(4.3rem)}.dice-back{transform:rotateY(180deg) translateZ(4.3rem)}.dice-right{transform:rotateY(90deg) translateZ(4.3rem)}.dice-left{transform:rotateY(-90deg) translateZ(4.3rem)}.dice-top{transform:rotateX(90deg) translateZ(4.3rem)}.dice-bottom{transform:rotateX(-90deg) translateZ(4.3rem)}
        .pip-grid{position:relative;z-index:2;display:grid;grid-template-columns:repeat(3,1fr);grid-template-rows:repeat(3,1fr);gap:.35rem;width:78%;height:78%;place-items:center}.pip{width:1rem;height:1rem;border-radius:50%;background:#263140;box-shadow:inset 0 2px 3px rgba(0,0,0,.28),0 1px 0 rgba(255,255,255,.7)}
        .dice-face-label{position:absolute;left:.7rem;bottom:.55rem;z-index:3;font-size:.52rem;font-weight:1000;letter-spacing:.14em;color:#637083;text-transform:uppercase}
        .dice-floor{position:absolute;bottom:3.15rem;width:18rem;height:3.8rem;border:1px solid rgba(255,255,255,.045);border-radius:50%;background:radial-gradient(ellipse,rgba(85,169,255,.06),transparent 68%);filter:blur(1px)}
        .dice-roll-readout{position:absolute;left:50%;bottom:.95rem;z-index:4;transform:translateX(-50%);min-width:15rem;padding:.75rem 1rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(5,8,12,.78);backdrop-filter:blur(15px);text-align:center;box-shadow:0 14px 30px rgba(0,0,0,.25)}
        .dice-readout-label{font-size:.6rem;font-weight:950;letter-spacing:.18em;text-transform:uppercase;color:#7f8997}.dice-readout-value{margin-top:.08rem;font-size:1.7rem;font-weight:1000;line-height:1;color:#f4f7fb;font-variant-numeric:tabular-nums}.dice-readout-sub{margin-top:.22rem;font-size:.69rem;font-weight:850}
        .dice-panel{padding:1rem;border:1px solid rgba(255,255,255,.07);border-radius:1.25rem;background:rgba(7,10,14,.74);box-shadow:0 18px 40px rgba(0,0,0,.12)}.dice-panel+.dice-panel{margin-top:.9rem}
        .dice-eyebrow{font-size:.64rem;font-weight:950;letter-spacing:.18em;text-transform:uppercase;color:#7d8897}
        .dice-mode-grid{display:grid;grid-template-columns:1fr 1fr;gap:.55rem;margin-top:.75rem}.dice-mode{padding:.78rem;border:1px solid rgba(255,255,255,.08);border-radius:.9rem;background:rgba(255,255,255,.025);color:#bbc3ce;text-align:left;transition:.2s;cursor:pointer}.dice-mode:hover{border-color:rgba(85,169,255,.25);transform:translateY(-1px)}.dice-mode--active{border-color:rgba(85,169,255,.7);background:linear-gradient(145deg,rgba(85,169,255,.12),rgba(85,169,255,.035));box-shadow:0 0 0 1px rgba(85,169,255,.1)}.dice-mode__title{font-size:.79rem;font-weight:950;color:#eef2f7}.dice-mode__sub{margin-top:.15rem;font-size:.6rem;color:#798492}
        .dice-bet-box{margin-top:.9rem;padding:.9rem;border:1px solid rgba(255,255,255,.07);border-radius:1rem;background:rgba(255,255,255,.018)}.dice-bet-stepper{display:grid;grid-template-columns:2.6rem minmax(5rem,1fr) 2.6rem;margin-top:.55rem;border:1px solid rgba(255,255,255,.08);border-radius:.85rem;overflow:hidden;background:#0b0f14}.dice-bet-stepper button{border:0;background:rgba(255,255,255,.025);color:#dde4ec;font-size:1.15rem;cursor:pointer}.dice-bet-stepper button:hover:not(:disabled){background:rgba(85,169,255,.09)}.dice-bet-input{width:100%;border:0;border-inline:1px solid rgba(255,255,255,.07);background:transparent;padding:.7rem .35rem;text-align:center;font-size:1rem;font-weight:950;color:#fff;outline:0}
        .dice-chips{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.6rem}.dice-chip{padding:.42rem .65rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(255,255,255,.025);color:#aeb7c4;font-size:.67rem;font-weight:900;cursor:pointer;transition:.2s}.dice-chip:hover{border-color:rgba(85,169,255,.3);color:#beddff;transform:translateY(-1px)}
        .dice-range-wrap{margin-top:1rem}.dice-range-head{display:flex;justify-content:space-between;align-items:end;gap:1rem}.dice-range-value{font-size:1.5rem;font-weight:1000;color:#f3f6fa}.dice-range-value span{font-size:.7rem;color:#8993a2;font-weight:800}.dice-range{width:100%;margin-top:.7rem;accent-color:#55a9ff;cursor:pointer}
        .dice-range-scale{display:flex;justify-content:space-between;margin-top:.3rem;color:#687383;font-size:.57rem;font-weight:800}.dice-probability{margin-top:.8rem;padding:.7rem .8rem;border:1px solid rgba(255,255,255,.06);border-radius:.8rem;background:rgba(85,169,255,.045)}.dice-probability-row{display:flex;justify-content:space-between;gap:1rem}.dice-probability-row strong{font-size:1rem;color:#bfe0ff}.dice-probability-row span{font-size:.61rem;color:#768392}.dice-probability-meter{height:.4rem;margin-top:.55rem;overflow:hidden;border-radius:999px;background:#1a222c}.dice-probability-meter>span{display:block;height:100%;width:50%;background:linear-gradient(90deg,#4d98e7,#7fc2ff);transition:width .3s ease}
        .dice-payout-box{display:flex;justify-content:space-between;gap:1rem;align-items:center;margin-top:.8rem;padding:.7rem .8rem;border-radius:.8rem;background:rgba(244,196,95,.05);border:1px solid rgba(244,196,95,.1)}.dice-payout-box strong{font-size:1rem;color:#f4d17c}.dice-payout-box span{font-size:.6rem;color:#7a8492}
        .dice-action{display:flex;gap:.65rem;align-items:center;margin-top:.9rem}.dice-primary{flex:1;min-height:3.1rem;border:0;border-radius:1rem;background:linear-gradient(135deg,#7dc4ff,#3d88da 58%,#255d9c);color:#06121f;font-size:.8rem;font-weight:1000;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;box-shadow:0 12px 28px rgba(61,136,218,.22);transition:.2s}.dice-primary:hover:not(:disabled){transform:translateY(-2px);filter:brightness(1.05)}.dice-primary:disabled{opacity:.5;cursor:not-allowed}.dice-space{font-size:.59rem;color:#6d7887}
        .dice-result-card{margin-top:.9rem;padding:.9rem;border:1px solid rgba(255,255,255,.07);border-radius:1rem;background:rgba(255,255,255,.02)}.dice-result-card--win{border-color:rgba(68,224,166,.22);background:rgba(68,224,166,.035)}.dice-result-card--loss{border-color:rgba(250,104,114,.16)}.dice-result-title{font-size:.62rem;font-weight:950;letter-spacing:.16em;text-transform:uppercase;color:#7f8998}.dice-result-main{display:flex;justify-content:space-between;align-items:end;gap:1rem;margin-top:.35rem}.dice-result-number{font-size:1.7rem;font-weight:1000;color:#f1f4f8}.dice-result-status{font-size:.82rem;font-weight:950}.dice-result-status--win{color:#6feeb2}.dice-result-status--loss{color:#ff9098}.dice-result-payout{margin-top:.25rem;font-size:.7rem;color:#8993a2}
        .dice-fairness{margin-top:.9rem}.dice-fairness summary{cursor:pointer;list-style:none}.dice-fairness summary::-webkit-details-marker{display:none}.dice-fairness summary::after{content:'+';float:right;color:#6e7885}.dice-fairness[open] summary::after{content:'−'}.dice-seed{width:100%;margin-top:.65rem;border:1px solid rgba(255,255,255,.08);border-radius:.75rem;background:#0b0f14;padding:.65rem .7rem;color:#dce2e8;font:600 .67rem ui-monospace,SFMono-Regular,Menlo,monospace}
        @keyframes diceLight{0%,100%{transform:translate(-50%,-50%) scale(.95);opacity:.7}50%{transform:translate(-50%,-50%) scale(1.06);opacity:1}}@keyframes diceOrbit{to{transform:rotateX(64deg) rotateZ(352deg)}}@keyframes diceRoll{0%{transform:translateY(1.2rem) rotateX(-18deg) rotateY(-28deg) rotateZ(3deg)}16%{transform:translateY(-3.6rem) rotateX(180deg) rotateY(210deg) rotateZ(18deg)}34%{transform:translateY(-6.4rem) rotateX(420deg) rotateY(530deg) rotateZ(-14deg)}53%{transform:translateY(-2.4rem) rotateX(680deg) rotateY(820deg) rotateZ(12deg)}72%{transform:translateY(-.2rem) rotateX(860deg) rotateY(1010deg) rotateZ(-5deg)}88%{transform:translateY(.35rem) rotateX(980deg) rotateY(1160deg) rotateZ(3deg)}100%{transform:translateY(0) rotateX(900deg) rotateY(1080deg) rotateZ(0deg)}}
        @media(max-width:1024px){.dice-layout{grid-template-columns:1fr}.dice-stage{min-height:32rem}}@media(max-width:640px){.dice-topbar{align-items:flex-start}.dice-stage{min-height:30rem;border-radius:1.25rem}.dice-cube-wrap{transform:scale(.88)}.dice-orbit{width:18rem}.dice-roll-readout{min-width:13rem}.dice-mode-grid{grid-template-columns:1fr}.dice-action{align-items:stretch;flex-direction:column}}
        @media(prefers-reduced-motion:reduce){.dice-light,.dice-orbit{animation:none}.dice-cube.is-rolling{animation:none}.dice-primary,.dice-chip,.dice-mode{transition:none}}
    </style>

    <div class="dice-layout">
        <section class="dice-stage">
            <div class="dice-grid" aria-hidden="true"></div>
            <div class="dice-light" aria-hidden="true"></div>

            <div class="dice-topbar">
                <div>
                    <p class="dice-kicker">JOGO 02 · ORIGINAL</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-white">Dados</h2>
                </div>
                <div class="dice-status">
                    <span class="dice-status-dot"></span>
                    <span x-text="busy ? 'A lançar…' : ({{ $locked ? 'true' : 'false' }} ? 'Ronda preparada' : 'Pronto a jogar')"></span>
                </div>
            </div>

            <div class="dice-stage-content" aria-live="polite">
                <div class="dice-orbit" aria-hidden="true"></div>
                <div class="dice-floor" aria-hidden="true"></div>
                <div class="dice-table-shadow" :class="{ 'is-rolling': rolling }" aria-hidden="true"></div>

                <div class="dice-cube-wrap">
                    <div class="dice-cube" :class="{ 'is-rolling': rolling }" role="img" :aria-label="'Dado com resultado ' + dieFace">
                        <div class="dice-face dice-front">
                            @php
                                $facePips = [
                                    1 => [4],
                                    2 => [0,8],
                                    3 => [0,4,8],
                                    4 => [0,2,6,8],
                                    5 => [0,2,4,6,8],
                                    6 => [0,2,3,5,6,8],
                                ];
                                $activePips = $facePips[$dieFace] ?? $facePips[6];
                            @endphp
                            <div class="pip-grid">
                                @for ($p = 0; $p < 9; $p++)
                                    <span class="pip" style="opacity: {{ in_array($p, $activePips, true) ? '1' : '.0' }}"></span>
                                @endfor
                            </div>
                            <span class="dice-face-label">ALLIN · {{ $dieFace }}</span>
                        </div>
                        <div class="dice-face dice-back">
                            <div class="pip-grid">
                                @for ($p = 0; $p < 9; $p++)
                                    <span class="pip" style="opacity: {{ in_array($p, [2,4,6], true) ? '1' : '.0' }}"></span>
                                @endfor
                            </div>
                        </div>
                        <div class="dice-face dice-right">
                            <div class="pip-grid">
                                @for ($p = 0; $p < 9; $p++)
                                    <span class="pip" style="opacity: {{ in_array($p, [0,2,4,6,8], true) ? '1' : '.0' }}"></span>
                                @endfor
                            </div>
                        </div>
                        <div class="dice-face dice-left">
                            <div class="pip-grid">
                                @for ($p = 0; $p < 9; $p++)
                                    <span class="pip" style="opacity: {{ in_array($p, [0,1,2], true) ? '1' : '.0' }}"></span>
                                @endfor
                            </div>
                        </div>
                        <div class="dice-face dice-top">
                            <div class="pip-grid">
                                @for ($p = 0; $p < 9; $p++)
                                    <span class="pip" style="opacity: {{ in_array($p, [0,2,4,6,8], true) ? '1' : '.0' }}"></span>
                                @endfor
                            </div>
                        </div>
                        <div class="dice-face dice-bottom">
                            <div class="pip-grid">
                                @for ($p = 0; $p < 9; $p++)
                                    <span class="pip" style="opacity: {{ in_array($p, [0,4,8], true) ? '1' : '.0' }}"></span>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="resultVisible" x-cloak x-transition.opacity class="dice-roll-readout">
                <p class="dice-readout-label">Resultado</p>
                <p class="dice-readout-value" x-text="displayedRoll"></p>
                <p class="dice-readout-sub" :class="outcome ? 'text-emerald-300' : 'text-rose-300'" x-text="outcome ? '▲ GANHOU' : '▼ NÃO GANHOU'"></p>
            </div>
        </section>

        <aside>
            <div class="dice-panel">
                <p class="dice-eyebrow">Configurar lançamento</p>

                <div class="dice-mode-grid">
                    <button type="button" class="dice-mode" :class="{ 'dice-mode--active': $wire.direction === 'under' }" x-on:click="$wire.direction = 'under'" :disabled="busy || @js($locked)">
                        <span class="dice-mode__title block">Abaixo</span>
                        <span class="dice-mode__sub block">Ganhar se o resultado for menor</span>
                    </button>
                    <button type="button" class="dice-mode" :class="{ 'dice-mode--active': $wire.direction === 'over' }" x-on:click="$wire.direction = 'over'" :disabled="busy || @js($locked)">
                        <span class="dice-mode__title block">Acima</span>
                        <span class="dice-mode__sub block">Ganhar se o resultado for maior</span>
                    </button>
                </div>

                <div class="dice-bet-box">
                    <p class="dice-eyebrow">Aposta em créditos virtuais</p>
                    <div class="dice-bet-stepper">
                        <button type="button" x-on:click="stepBet(-5)" :disabled="busy || @js($locked)" aria-label="Diminuir aposta">−</button>
                        <input type="number" min="1" step="1" max="{{ $maxBet }}" wire:model="bet" :disabled="busy || @js($locked)" class="dice-bet-input" aria-label="Aposta em créditos">
                        <button type="button" x-on:click="stepBet(5)" :disabled="busy || @js($locked)" aria-label="Aumentar aposta">+</button>
                    </div>
                    <div class="dice-chips">
                        @foreach ([25,50,100,250,500,1000] as $chip)
                            @if ($chip <= $maxBet)
                                <button type="button" class="dice-chip" x-on:click="$wire.bet = {{ $chip }}" :disabled="busy || @js($locked)">{{ $chip }}</button>
                            @endif
                        @endforeach
                        <button type="button" class="dice-chip" x-on:click="$wire.bet = {{ $maxBet }}" :disabled="busy || @js($locked)">MAX</button>
                    </div>
                </div>

                <div class="dice-range-wrap">
                    <div class="dice-range-head">
                        <div>
                            <p class="dice-eyebrow">Limite</p>
                            <p class="dice-range-value"><span x-text="previewThreshold"></span><span> / 99</span></p>
                        </div>
                        <span class="text-xs font-bold text-zinc-500" x-text="$wire.direction === 'under' ? 'Abaixo deste valor' : 'Acima deste valor'"></span>
                    </div>

                    <input
                        class="dice-range"
                        type="range"
                        min="1"
                        max="99"
                        step="1"
                        x-model.number="previewThreshold"
                        x-on:input="$wire.threshold = previewThreshold"
                        :disabled="busy || @js($locked)"
                        aria-label="Limite do jogo"
                    >

                    <div class="dice-range-scale">
                        <span>1</span><span>25</span><span>50</span><span>75</span><span>99</span>
                    </div>

                    <div class="dice-chips">
                        @foreach ([25,50,75] as $target)
                            <button type="button" class="dice-chip" x-on:click="setThreshold({{ $target }})" :disabled="busy || @js($locked)">{{ $target }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="dice-probability">
                    <div class="dice-probability-row">
                        <span>Probabilidade teórica</span>
                        <strong x-text="chance() + '%'"></strong>
                    </div>
                    <div class="dice-probability-meter">
                        <span :style="'width:' + chance() + '%'"></span>
                    </div>
                </div>

                <div class="dice-payout-box">
                    <div>
                        <strong x-text="multiplier().toFixed(2) + '×'"></strong>
                        <span class="block">multiplicador bruto</span>
                    </div>
                    <div class="text-right">
                        <strong x-text="'+' + Math.floor(Number($wire.bet || 0) * multiplier()).toLocaleString('pt-PT')"></strong>
                        <span class="block">payout potencial</span>
                    </div>
                </div>

                <div class="dice-action">
                    @if ($roundPhase === 'prepared')
                        <button type="button" class="dice-primary" x-on:click="rollNow()" :disabled="busy">
                            <span x-show="!busy">Lançar dados</span>
                            <span x-show="busy" x-cloak>Os dados estão no ar…</span>
                        </button>
                    @else
                        <button type="button" class="dice-primary" wire:click="prepare" wire:loading.attr="disabled">
                            {{ $roundPhase === 'completed' ? 'Preparar nova ronda' : 'Preparar ronda' }}
                        </button>
                    @endif
                </div>
                <p class="mt-2 text-center dice-space">Espaço = lançar quando a ronda estiver preparada</p>

                @error('bet')<p role="alert" class="mt-3 text-xs text-rose-300">{{ $message }}</p>@enderror
                @error('threshold')<p role="alert" class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                @error('game')<p role="alert" class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
            </div>

            @if ($roundResult !== [])
                <div x-show="resultVisible" x-cloak x-transition.opacity class="dice-result-card {{ ($roundResult['won'] ?? false) ? 'dice-result-card--win' : 'dice-result-card--loss' }}">
                    <p class="dice-result-title">Último lançamento</p>
                    <div class="dice-result-main">
                        <span class="dice-result-number">{{ $displayNumber }}</span>
                        <span class="dice-result-status {{ ($roundResult['won'] ?? false) ? 'dice-result-status--win' : 'dice-result-status--loss' }}">
                            {{ ($roundResult['won'] ?? false) ? 'Ganhou' : 'Não ganhou' }}
                        </span>
                    </div>
                    <p class="dice-result-payout">{{ number_format((int) $roundPayout) }} créditos virtuais</p>
                </div>
            @endif

            <div class="dice-panel dice-fairness">
                <details>
                    <summary class="dice-eyebrow">🔐 Jogo transparente</summary>
                    <p class="mt-3 text-xs leading-5 text-zinc-500">O hash do servidor é fixado antes do resultado e a ronda pode ser verificada.</p>
                    @if ($serverSeedHash)
                        <p class="mt-3 text-[0.61rem] font-black uppercase tracking-[0.16em] text-zinc-600">Hash do servidor</p>
                        <p class="mt-1 break-all font-mono text-[0.68rem] leading-5 text-zinc-300">{{ $serverSeedHash }}</p>
                    @endif
                    <label class="mt-4 block text-[0.66rem] font-bold text-zinc-500">Seed do cliente
                        <input type="text" maxlength="128" wire:model="clientSeed" :disabled="busy || @js($locked)" class="dice-seed">
                    </label>
                </details>
            </div>

            @if ($roundPhase === 'completed' && $roundId)
                <button type="button" class="mt-3 w-full px-3 py-2 text-xs font-bold text-emerald-300 transition hover:text-emerald-200" x-data x-on:click="$dispatch('casino-open-fairness', { roundId: {{ $roundId }} })">
                    Verificar esta ronda →
                </button>
            @endif

            <p class="mt-4 px-1 text-center text-[0.68rem] text-zinc-600">Créditos virtuais — sem valor monetário</p>
        </aside>
    </div>
</div>
