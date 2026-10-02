@php
    $maxBet = max(0, (int) (auth()->user()?->wallet?->balance ?? 0));
    $locked = $roundPhase === 'prepared';
    $wheelNumbers = [0, 32, 15, 19, 4, 21, 2, 25, 17, 34, 6, 27, 13, 36, 11, 30, 8, 23, 10, 5, 24, 16, 33, 1, 20, 14, 31, 9, 22, 18, 29, 7, 28, 12, 35, 3, 26];
    $redNumbers = [1, 3, 5, 7, 9, 12, 14, 16, 18, 19, 21, 23, 25, 27, 30, 32, 34, 36];
    $multiplier = match ($betType) {
        'straight' => 36,
        'dozen', 'column' => 3,
        default => 2,
    };
    $selectionLabel = match ($betType) {
        'straight' => (string) $selection,
        'color' => $selection === 'red' ? 'Vermelho' : 'Preto',
        'parity' => $selection === 'even' ? 'Par' : 'Ímpar',
        'range' => $selection === 'low' ? '1–18' : '19–36',
        'dozen' => 'Dúzia '.$selection,
        'column' => 'Coluna '.$selection,
        default => '—',
    };
    $resultNumber = $roundResult !== [] ? (int) ($roundResult['outcome'] ?? 0) : 0;
    $resultColor = $roundResult !== [] ? ($roundResult['color'] ?? 'green') : 'green';
    $resultIndex = array_search($resultNumber, $wheelNumbers, true);
    $resultIndex = $resultIndex === false ? 0 : (int) $resultIndex;
@endphp

<div
    class="casino-game-play roulette-page casino-game-screen" data-casino-game="roulette"
    x-data="{
        spinning: false,
        resultVisible: {{ $roundResult !== [] ? 'true' : 'false' }},
        resultNumber: {{ $resultNumber }},
        resultColor: @js($resultColor),
        selectedIndex: {{ $resultIndex }},
        maxBet: {{ $maxBet }},

        pickType(type) {
            this.$wire.betType = type;
            if (type === 'straight') this.$wire.selection = 17;
            if (type === 'color') this.$wire.selection = 'red';
            if (type === 'parity') this.$wire.selection = 'even';
            if (type === 'range') this.$wire.selection = 'low';
            if (type === 'dozen' || type === 'column') this.$wire.selection = 1;
        },

        pickNumber(number) {
            this.$wire.betType = 'straight';
            this.$wire.selection = number;
        },

        stepBet(delta) {
            const current = Number(this.$wire.bet || 1);
            const next = Math.round((current + delta) / 5) * 5;
            this.$wire.bet = Math.min(this.maxBet, Math.max(1, next));
        },

        quickBet(value) {
            this.$wire.bet = value;
        },

        async spinNow() {
            if (this.spinning) return;

            this.spinning = true;
            this.resultVisible = false;

            const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            try {
                await this.$wire.spin();

                const number = Number(this.$wire.roundResult?.outcome ?? 0);
                const color = this.$wire.roundResult?.color ?? 'green';
                const wheel = [0,32,15,19,4,21,2,25,17,34,6,27,13,36,11,30,8,23,10,5,24,16,33,1,20,14,31,9,22,18,29,7,28,12,35,3,26];
                const index = Math.max(0, wheel.indexOf(number));

                this.resultNumber = number;
                this.resultColor = color;
                this.selectedIndex = index;

                if (!calm) {
                    await new Promise(resolve => setTimeout(resolve, 2200));
                }

                const settlementDeadline = Date.now() + 1600;
                while (this.$wire.roundPhase === 'in_progress' && this.$wire.roundResult?.settlement_pending && Date.now() < settlementDeadline) {
                    await new Promise(resolve => setTimeout(resolve, 80));
                }

                if (this.$wire.roundPhase !== 'completed') {
                    this.spinning = false;
                    return;
                }

                this.spinning = false;
                this.resultVisible = true;
            } catch (e) {
                this.spinning = false;
            }
        }
    }"
    x-on:keydown.window="if ($event.code === 'Space' && !['INPUT','TEXTAREA','BUTTON','SELECT','SUMMARY'].includes($event.target.tagName)) { $event.preventDefault(); if (!spinning && $wire.roundPhase !== 'in_progress') spinNow(); }"
>

    <x-casino.how-it-works game-key="roulette" title="Como funciona a Roleta Europeia?" description="Escolhe um número ou um tipo de aposta e gira uma roleta europeia com os números 0 a 36." :rules="[['title'=>'Escolhe a aposta','text'=>'Podes apostar num número, cor, paridade, intervalo, dúzia ou coluna.'], ['title'=>'Define o valor','text'=>'Escolhe quantos créditos virtuais queres apostar.'], ['title'=>'Gira a roleta','text'=>'Carrega em Girar para lançar a ronda e revelar o número vencedor.'], ['title'=>'Confere o resultado','text'=>'A aposta ganha quando o resultado corresponde ao tipo de aposta selecionado. O pagamento depende da aposta.']]" />
    <style>
        .roulette-page{--roulette-green:#18b978;--roulette-red:#c83f47;--roulette-black:#10151c;--roulette-gold:#e5bb59}
        .roulette-layout{display:grid;gap:1.5rem;grid-template-columns:minmax(0,1.45fr) minmax(18rem,.78fr)}
        .roulette-main{min-width:0}.roulette-table{padding:1rem;border:1px solid rgba(255,255,255,.08);border-radius:1.6rem;background:radial-gradient(circle at 50% 12%,rgba(229,187,89,.09),transparent 30%),linear-gradient(145deg,#0b2820,#081814 72%);box-shadow:0 30px 80px rgba(0,0,0,.28)}
        .roulette-stage{position:relative;display:grid;place-items:center;min-height:30rem;overflow:hidden;border:1px solid rgba(255,255,255,.07);border-radius:1.35rem;background:radial-gradient(circle,rgba(24,185,120,.06),transparent 38%),linear-gradient(180deg,rgba(255,255,255,.015),rgba(0,0,0,.12))}
        .roulette-stage-grid{position:absolute;inset:0;opacity:.18;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:36px 36px;mask-image:linear-gradient(to bottom,black,transparent 90%)}
        .roulette-topbar{position:absolute;top:1rem;left:1rem;right:1rem;z-index:8;display:flex;justify-content:space-between;align-items:center;gap:1rem}
        .roulette-kicker{font-size:.65rem;font-weight:950;letter-spacing:.2em;color:#899596;text-transform:uppercase}.roulette-badge{display:flex;align-items:center;gap:.45rem;padding:.45rem .7rem;border:1px solid rgba(255,255,255,.07);border-radius:999px;background:rgba(5,12,10,.72);color:#c8d2ce;font-size:.66rem;font-weight:900}.roulette-dot{width:.42rem;height:.42rem;border-radius:50%;background:var(--roulette-green);box-shadow:0 0 14px rgba(24,185,120,.8)}
        .roulette-wheel-wrap{position:relative;width:23rem;height:23rem;perspective:1000px}.roulette-wheel-shadow{position:absolute;left:50%;bottom:-.7rem;width:18rem;height:2.2rem;transform:translateX(-50%);border-radius:50%;background:rgba(0,0,0,.65);filter:blur(16px)}.roulette-pointer{position:absolute;top:-.55rem;left:50%;z-index:10;transform:translateX(-50%);width:0;height:0;border-left:.65rem solid transparent;border-right:.65rem solid transparent;border-top:1.35rem solid var(--roulette-gold);filter:drop-shadow(0 3px 5px rgba(0,0,0,.5))}
        .roulette-wheel{position:absolute;inset:0;border-radius:50%;transform:rotateX(16deg);transform-style:preserve-3d;box-shadow:0 1.2rem 3rem rgba(0,0,0,.45),inset 0 0 0 1px rgba(255,255,255,.12);transition:transform 2.2s cubic-bezier(.12,.78,.13,1)}.roulette-wheel.is-spinning{animation:rouletteSpin 2.2s cubic-bezier(.1,.68,.13,1) forwards}
        .roulette-rim{position:absolute;inset:.25rem;border-radius:50%;background:linear-gradient(145deg,#f1cb70,#a86f16 46%,#f6d985 70%,#7b4c0a);box-shadow:inset 0 0 0 .4rem rgba(75,38,3,.7),inset 0 0 0 .55rem rgba(255,235,159,.45)}.roulette-inner{position:absolute;inset:1.35rem;border-radius:50%;background:#0d241d;box-shadow:inset 0 0 0 .5rem #143b2d,inset 0 0 0 1.35rem #07120f}
        .roulette-number-ring{position:absolute;inset:.95rem;border-radius:50%}.roulette-pocket{position:absolute;left:50%;top:50%;width:2.45rem;height:2.45rem;margin:-1.225rem;border-radius:.42rem;display:grid;place-items:center;border:1px solid rgba(255,255,255,.2);font-size:.63rem;font-weight:1000;color:#fff;box-shadow:0 3px 7px rgba(0,0,0,.35);transform:rotate(var(--a)) translateY(-9.55rem) rotate(calc(var(--a) * -1));background:var(--pocket-bg)}.roulette-pocket--red{--pocket-bg:linear-gradient(145deg,#d75a61,#862c33)}.roulette-pocket--black{--pocket-bg:linear-gradient(145deg,#27313d,#0c1015)}.roulette-pocket--green{--pocket-bg:linear-gradient(145deg,#29d092,#08764d)}.roulette-center{position:absolute;inset:6.6rem;border-radius:50%;display:grid;place-items:center;background:radial-gradient(circle at 35% 30%,#f6db89,#bd851e 37%,#76470b 72%,#3b2104);border:.35rem solid rgba(247,213,128,.8);box-shadow:inset 0 0 0 .18rem rgba(96,52,6,.48),0 0 2rem rgba(229,187,89,.16)}.roulette-center-logo{width:4.6rem;height:4.6rem;border-radius:50%;display:grid;place-items:center;border:1px solid rgba(255,247,200,.6);background:radial-gradient(circle,#fce9a9,#d99e2b);color:#75490c;font-size:1.45rem;font-weight:1000;box-shadow:inset 0 0 0 .25rem rgba(128,73,9,.2)}
        .roulette-result-card{position:absolute;bottom:1rem;z-index:9;min-width:15rem;padding:.7rem 1rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(4,11,8,.8);backdrop-filter:blur(14px);text-align:center;box-shadow:0 12px 30px rgba(0,0,0,.3)}.roulette-result-label{font-size:.58rem;font-weight:950;letter-spacing:.18em;color:#80918a;text-transform:uppercase}.roulette-result-number{margin-top:.12rem;font-size:1.85rem;font-weight:1000;line-height:1}.roulette-result-sub{margin-top:.16rem;font-size:.68rem;font-weight:850}
        .roulette-table-title{display:flex;align-items:end;justify-content:space-between;gap:1rem;padding:.9rem .35rem .7rem}.roulette-table-title h3{font-size:.78rem;font-weight:950;color:#f0f3f1}.roulette-table-title span{font-size:.58rem;color:#6f7c76}
        .roulette-zero{display:grid;grid-template-columns:4rem minmax(0,1fr);gap:.45rem}.roulette-grid{display:grid;grid-template-columns:repeat(12,minmax(2.1rem,1fr));gap:.35rem}.roulette-number{min-height:2.55rem;border:1px solid rgba(255,255,255,.08);border-radius:.58rem;display:grid;place-items:center;color:#fff;font-size:.68rem;font-weight:950;cursor:pointer;transition:.18s;box-shadow:inset 0 1px 0 rgba(255,255,255,.07)}.roulette-number:hover{transform:translateY(-2px);filter:brightness(1.1)}.roulette-number--red{background:linear-gradient(145deg,#ba4048,#70252c)}.roulette-number--black{background:linear-gradient(145deg,#29323c,#10161d)}.roulette-number--zero{background:linear-gradient(145deg,#27c886,#08734b)}.roulette-number--active{outline:2px solid var(--roulette-gold);outline-offset:1px;box-shadow:0 0 20px rgba(229,187,89,.18)}
        .roulette-externals{display:grid;grid-template-columns:repeat(6,1fr);gap:.35rem;margin-top:.45rem}.roulette-external{min-height:2.45rem;border:1px solid rgba(255,255,255,.08);border-radius:.55rem;background:rgba(255,255,255,.025);color:#bec8c3;font-size:.6rem;font-weight:950;text-transform:uppercase;cursor:pointer;transition:.18s}.roulette-external:hover{transform:translateY(-1px);border-color:rgba(229,187,89,.28);color:#f2d47f}.roulette-external--active{border-color:rgba(229,187,89,.72);background:rgba(229,187,89,.08);color:#f4d582}
        .roulette-bet-panel{padding:1rem;border:1px solid rgba(255,255,255,.07);border-radius:1.25rem;background:rgba(7,10,14,.74);box-shadow:0 18px 40px rgba(0,0,0,.1)}
        .roulette-eyebrow{font-size:.62rem;font-weight:950;letter-spacing:.18em;text-transform:uppercase;color:#7f8c86}.roulette-selection{display:flex;justify-content:space-between;align-items:end;gap:1rem;margin-top:.35rem}.roulette-selection strong{font-size:1.25rem;font-weight:1000;color:#f2f5f3}.roulette-selection span{font-size:.65rem;color:#7c8983}
        .roulette-type-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.4rem;margin-top:.75rem}.roulette-type{padding:.65rem .5rem;border:1px solid rgba(255,255,255,.07);border-radius:.75rem;background:rgba(255,255,255,.02);color:#aeb9b3;font-size:.6rem;font-weight:900;cursor:pointer;transition:.18s}.roulette-type:hover{border-color:rgba(229,187,89,.26);transform:translateY(-1px)}.roulette-type--active{border-color:rgba(229,187,89,.65);background:rgba(229,187,89,.08);color:#f3d57d}
        .roulette-stepper{display:grid;grid-template-columns:2.6rem minmax(5rem,1fr) 2.6rem;margin-top:.85rem;border:1px solid rgba(255,255,255,.08);border-radius:.85rem;overflow:hidden;background:#0b0f14}.roulette-stepper button{border:0;background:rgba(255,255,255,.02);color:#dbe3df;font-size:1.1rem;cursor:pointer}.roulette-stepper button:hover:not(:disabled){background:rgba(229,187,89,.08)}.roulette-input{width:100%;border:0;border-inline:1px solid rgba(255,255,255,.07);background:transparent;padding:.7rem .3rem;text-align:center;color:#fff;font-weight:950;outline:0}.roulette-chips{display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.55rem}.roulette-chip{padding:.4rem .62rem;border:1px solid rgba(255,255,255,.07);border-radius:999px;background:rgba(255,255,255,.02);color:#9eaaa4;font-size:.63rem;font-weight:900;cursor:pointer;transition:.18s}.roulette-chip:hover{border-color:rgba(229,187,89,.28);color:#f0d27c;transform:translateY(-1px)}
        .roulette-payout{display:flex;justify-content:space-between;gap:1rem;align-items:center;margin-top:.8rem;padding:.7rem .75rem;border:1px solid rgba(229,187,89,.1);border-radius:.75rem;background:rgba(229,187,89,.045)}.roulette-payout strong{font-size:.95rem;color:#f1d27a}.roulette-payout span{font-size:.58rem;color:#747f7a}.roulette-action{display:flex;gap:.6rem;align-items:center;margin-top:.75rem}.roulette-primary{flex:1;min-height:3.15rem;border:0;border-radius:1rem;background:linear-gradient(135deg,#efcf78,#bd8320 55%,#80500b);color:#2e1d05;font-size:.8rem;font-weight:1000;letter-spacing:.05em;text-transform:uppercase;cursor:pointer;box-shadow:0 13px 28px rgba(174,114,20,.2);transition:.18s}.roulette-primary:hover:not(:disabled){transform:translateY(-2px);filter:brightness(1.04)}.roulette-primary:disabled{opacity:.5;cursor:not-allowed}.roulette-space{font-size:.58rem;color:#697670}
        .roulette-fairness{margin-top:.9rem;padding:1rem;border:1px solid rgba(255,255,255,.07);border-radius:1.25rem;background:rgba(7,10,14,.7)}.roulette-fairness summary{cursor:pointer;list-style:none}.roulette-fairness summary::-webkit-details-marker{display:none}.roulette-fairness summary::after{content:'+';float:right;color:#69756f}.roulette-fairness[open] summary::after{content:'−'}.roulette-seed{width:100%;margin-top:.6rem;border:1px solid rgba(255,255,255,.08);border-radius:.7rem;background:#0b0f14;padding:.6rem .7rem;color:#dae3df;font:600 .66rem ui-monospace,SFMono-Regular,Menlo,monospace}
        @keyframes rouletteSpin{0%{transform:rotateX(16deg) rotateZ(0deg)}100%{transform:rotateX(16deg) rotateZ(1440deg)}}@keyframes rouletteWin{0%{transform:scale(.45);opacity:0}25%{opacity:1}100%{transform:scale(1.55);opacity:0}}
        @media(max-width:1100px){.roulette-layout{grid-template-columns:1fr}}@media(max-width:720px){.roulette-stage{min-height:25rem}.roulette-wheel-wrap{width:19rem;height:19rem}.roulette-pocket{width:2.05rem;height:2.05rem;margin:-1.025rem;font-size:.55rem;transform:rotate(var(--a)) translateY(-7.7rem) rotate(calc(var(--a) * -1))}.roulette-center{inset:5.45rem}.roulette-grid{grid-template-columns:repeat(6,minmax(2rem,1fr))}.roulette-zero{grid-template-columns:1fr}.roulette-externals{grid-template-columns:repeat(2,1fr)}.roulette-type-grid{grid-template-columns:repeat(2,1fr)}.roulette-topbar{align-items:flex-start}.roulette-result-card{min-width:13rem}}
        @media(prefers-reduced-motion:reduce){.roulette-wheel,.roulette-wheel.is-spinning,.roulette-pocket,.roulette-primary,.roulette-number,.roulette-external,.roulette-type,.roulette-chip{animation:none;transition:none}}
    </style>

    <div class="roulette-layout">
        <section class="roulette-main">
            <div class="roulette-table">
                <div class="roulette-stage">
                    <div class="roulette-stage-grid" aria-hidden="true"></div>
                    <div class="roulette-win-glow" :class="{ 'is-win': resultVisible && {{ ($roundResult['won'] ?? false) ? 'true' : 'false' }} }" aria-hidden="true"></div>

                    <div class="roulette-topbar">
                        <div>
                            <p class="roulette-kicker">JOGO 03 · MESA</p>
                            <h2 class="mt-1 text-2xl font-black tracking-tight text-white">Roleta europeia</h2>
                        </div>
                        <div class="roulette-badge">
                            <span class="roulette-dot"></span>
                            <span x-text="spinning ? 'A roda está a girar…' : ({{ $locked ? 'true' : 'false' }} ? 'Aposta confirmada' : 'Mesa aberta')"></span>
                        </div>
                    </div>

                    <div class="roulette-wheel-wrap">
                        <div class="roulette-pointer" aria-hidden="true"></div>
                        <div class="roulette-wheel-shadow" aria-hidden="true"></div>

                        <div class="roulette-wheel" :class="{ 'is-spinning': spinning }" :style="!spinning ? 'transform:rotateX(16deg) rotateZ(' + ((-selectedIndex * (360 / 37)) + 720) + 'deg)' : ''">
                            <div class="roulette-rim"></div>
                            <div class="roulette-inner"></div>

                            <div class="roulette-number-ring">
                                @foreach ($wheelNumbers as $index => $number)
                                    @php
                                        $angle = $index * (360 / count($wheelNumbers));
                                        $pocketColor = $number === 0 ? 'green' : (in_array($number, $redNumbers, true) ? 'red' : 'black');
                                    @endphp
                                    <span class="roulette-pocket roulette-pocket--{{ $pocketColor }}" style="--a: {{ $angle }}deg">{{ $number }}</span>
                                @endforeach
                            </div>

                            <div class="roulette-center">
                                <div class="roulette-center-logo">0</div>
                            </div>
                        </div>
                    </div>

                    <div x-show="resultVisible" x-cloak x-transition.opacity class="roulette-result-card">
                        <p class="roulette-result-label">Resultado confirmado</p>
                        <p class="roulette-result-number" :class="resultColor === 'red' ? 'text-rose-400' : (resultColor === 'black' ? 'text-white' : 'text-emerald-300')" x-text="resultNumber"></p>
                        <p class="roulette-result-sub" :class="resultColor === 'red' ? 'text-rose-300' : (resultColor === 'black' ? 'text-zinc-400' : 'text-emerald-300')" x-text="resultColor === 'red' ? 'VERMELHO' : (resultColor === 'black' ? 'PRETO' : 'ZERO · VERDE')"></p>
                    </div>
                </div>

                <div class="roulette-table-title">
                    <h3>PARTICIPA NA MESA</h3>
                    <span>37 casas · zero único</span>
                </div>

                <div class="roulette-zero">
                    <button type="button" class="roulette-number roulette-number--zero" :class="{ 'roulette-number--active': $wire.betType === 'straight' && Number($wire.selection) === 0 }" x-on:click="pickNumber(0)" :disabled="spinning || @js($locked)">0</button>

                    <div class="roulette-grid">
                        @foreach (range(1, 36) as $number)
                            @php $isRed = in_array($number, $redNumbers, true); @endphp
                            <button type="button" class="roulette-number {{ $isRed ? 'roulette-number--red' : 'roulette-number--black' }}" :class="{ 'roulette-number--active': $wire.betType === 'straight' && Number($wire.selection) === {{ $number }} }" x-on:click="pickNumber({{ $number }})" :disabled="spinning || @js($locked)">{{ $number }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="roulette-externals">
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'color' && $wire.selection === 'red' }" x-on:click="$wire.betType='color';$wire.selection='red'" :disabled="spinning || @js($locked)">Vermelho</button>
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'color' && $wire.selection === 'black' }" x-on:click="$wire.betType='color';$wire.selection='black'" :disabled="spinning || @js($locked)">Preto</button>
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'parity' && $wire.selection === 'odd' }" x-on:click="$wire.betType='parity';$wire.selection='odd'" :disabled="spinning || @js($locked)">Ímpar</button>
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'parity' && $wire.selection === 'even' }" x-on:click="$wire.betType='parity';$wire.selection='even'" :disabled="spinning || @js($locked)">Par</button>
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'range' && $wire.selection === 'low' }" x-on:click="$wire.betType='range';$wire.selection='low'" :disabled="spinning || @js($locked)">1–18</button>
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'range' && $wire.selection === 'high' }" x-on:click="$wire.betType='range';$wire.selection='high'" :disabled="spinning || @js($locked)">19–36</button>
                </div>

                <div class="roulette-externals">
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'dozen' && Number($wire.selection) === 1 }" x-on:click="$wire.betType='dozen';$wire.selection=1" :disabled="spinning || @js($locked)">1ª dúzia</button>
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'dozen' && Number($wire.selection) === 2 }" x-on:click="$wire.betType='dozen';$wire.selection=2" :disabled="spinning || @js($locked)">2ª dúzia</button>
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'dozen' && Number($wire.selection) === 3 }" x-on:click="$wire.betType='dozen';$wire.selection=3" :disabled="spinning || @js($locked)">3ª dúzia</button>
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'column' && Number($wire.selection) === 1 }" x-on:click="$wire.betType='column';$wire.selection=1" :disabled="spinning || @js($locked)">Coluna 1</button>
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'column' && Number($wire.selection) === 2 }" x-on:click="$wire.betType='column';$wire.selection=2" :disabled="spinning || @js($locked)">Coluna 2</button>
                    <button type="button" class="roulette-external" :class="{ 'roulette-external--active': $wire.betType === 'column' && Number($wire.selection) === 3 }" x-on:click="$wire.betType='column';$wire.selection=3" :disabled="spinning || @js($locked)">Coluna 3</button>
                </div>
            </div>
        </section>

        <aside>
            <div class="roulette-bet-panel">
                <p class="roulette-eyebrow">Aposta atual</p>
                <div class="roulette-selection">
                    <div>
                        <strong>{{ $selectionLabel }}</strong>
                        <span class="block mt-1">{{ ucfirst($betType) }}</span>
                    </div>
                    <div class="text-right">
                        <strong class="text-amber-200">{{ $multiplier }}×</strong>
                        <span class="block mt-1">pagamento bruto</span>
                    </div>
                </div>

                <div class="roulette-type-grid">
                    @foreach ([
                        'straight' => 'Número',
                        'color' => 'Cor',
                        'parity' => 'Paridade',
                        'range' => 'Baixo/alto',
                        'dozen' => 'Dúzia',
                        'column' => 'Coluna',
                    ] as $type => $label)
                        <button type="button" class="roulette-type {{ $betType === $type ? 'roulette-type--active' : '' }}" :class="{ 'roulette-type--active': $wire.betType === @js($type) }" x-on:click="pickType(@js($type))" :disabled="spinning || @js($locked)">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="roulette-stepper">
                    <button type="button" x-on:click="stepBet(-5)" :disabled="spinning || @js($locked)">−</button>
                    <input type="number" min="1" max="{{ $maxBet }}" step="1" wire:model="bet" :disabled="spinning || @js($locked)" class="roulette-input" aria-label="Aposta em créditos">
                    <button type="button" x-on:click="stepBet(5)" :disabled="spinning || @js($locked)">+</button>
                </div>

                <div class="roulette-chips">
                    @foreach ([25, 50, 100, 250, 500, 1000] as $chip)
                        @if ($chip <= $maxBet)
                            <button type="button" class="roulette-chip" x-on:click="quickBet({{ $chip }})" :disabled="spinning || @js($locked)">{{ $chip }}</button>
                        @endif
                    @endforeach
                    <button type="button" class="roulette-chip" x-on:click="quickBet({{ $maxBet }})" :disabled="spinning || @js($locked) || {{ $maxBet < 1 ? 'true' : 'false' }}">ALL IN · {{ number_format($maxBet, 0, ',', ' ') }}</button>
                </div>

                <div class="roulette-payout">
                    <div>
                        <strong>+{{ number_format(max(0, ((int) $bet) * $multiplier)) }}</strong>
                        <span class="block mt-1">payout se acertar</span>
                    </div>
                    <div class="text-right">
                        <strong>{{ $multiplier }}×</strong>
                        <span class="block mt-1">multiplicador</span>
                    </div>
                </div>

                <div class="roulette-action">
                    <button type="button" class="roulette-primary" x-on:click="spinNow()" :disabled="spinning || $wire.roundPhase === 'in_progress'">
                        <span x-show="!spinning">Girar roleta</span>
                        <span x-show="spinning" x-cloak>A roda está a girar…</span>
                    </button>
                </div>
                <p class="mt-2 text-center roulette-space">Espaço = girar · a aposta fecha automaticamente ao iniciar</p>

                @error('bet')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                @error('selection')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                @error('game')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
            </div>

            @if ($roundResult !== [])
                <div x-show="resultVisible" x-cloak x-transition.opacity class="roulette-bet-panel mt-3">
                    <p class="roulette-eyebrow">Última ronda</p>
                    <div class="mt-2 flex items-end justify-between gap-3">
                        <div>
                            <strong class="text-3xl font-black {{ $resultColor === 'red' ? 'text-rose-400' : ($resultColor === 'black' ? 'text-white' : 'text-emerald-300') }}">{{ $resultNumber }}</strong>
                            <span class="block mt-1 text-xs uppercase tracking-wider text-zinc-500">{{ $resultColor === 'green' ? 'Zero · verde' : $resultColor }}</span>
                        </div>
                        <span class="text-sm font-black {{ ($roundResult['won'] ?? false) ? 'text-emerald-300' : 'text-rose-300' }}">{{ ($roundResult['won'] ?? false) ? '+'.$roundPayout : 'Sem prémio' }}</span>
                    </div>
                </div>
            @endif

            <div class="roulette-fairness">
                <details>
                    <summary class="roulette-eyebrow">🔐 Jogo transparente</summary>
                    <p class="mt-3 text-xs leading-5 text-zinc-500">O hash do servidor é fixado antes do resultado e a ronda pode ser verificada.</p>
                    @if ($serverSeedHash)
                        <p class="mt-3 text-[0.6rem] font-black uppercase tracking-[0.16em] text-zinc-600">Hash do servidor</p>
                        <p class="mt-1 break-all font-mono text-[0.66rem] leading-5 text-zinc-300">{{ $serverSeedHash }}</p>
                    @endif
                    <label class="mt-4 block text-[0.64rem] font-bold text-zinc-500">Seed do cliente
                        <input type="text" maxlength="128" wire:model="clientSeed" :disabled="spinning || @js($locked)" class="roulette-seed">
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
