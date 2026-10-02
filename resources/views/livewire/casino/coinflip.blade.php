@php
    $maxBet = (int) config('casino.bet_limits.max', 10000);
    $minimumBet = (int) config('casino.bet_limits.min', 1);
    $houseEdge = (int) config('casino.games.coinflip.house_edge_bps', 250);
    $multiplier = (10000 - $houseEdge) / 5000;
    $historyTotal = $headsCount + $tailsCount;
    $headsPercent = $historyTotal > 0 ? (int) round(($headsCount / $historyTotal) * 100) : 50;
    $tailsPercent = 100 - $headsPercent;
    $effectiveMaxBet = min($maxBet, max(0, (int) $walletBalance));
    $previewBet = min(max(0, (int) $bet), $effectiveMaxBet);
    $potentialPayout = $previewBet > 0 ? (int) floor($previewBet * $multiplier) : 0;
    $potentialProfit = max(0, $potentialPayout - $previewBet);
    $balanceAfterBet = max(0, (int) $walletBalance - $previewBet);
    $canBet = $walletBalance >= $minimumBet;
    $locked = $roundPhase === 'prepared';
    $hasResult = $roundResult !== [];
@endphp

<div
    class="casino-game-play coinflip-page"
    x-data="{
        busy: false,
        flipping: false,
        resultVisible: {{ $hasResult ? 'true' : 'false' }},
        burst: false,
        displayedOutcome: @js($roundResult['outcome'] ?? 'heads'),
        displayedWon: {{ ($roundResult['won'] ?? false) ? 'true' : 'false' }},
        displayedPayout: {{ (int) $roundPayout }},
        displayedBet: {{ (int) ($roundResult['bet'] ?? $previewBet) }},
        stageMessage: {{ $locked ? "'Ronda preparada'" : ($hasResult ? "'Resultado revelado'" : "'Pronto para lançar'") }},
        progress: 0,
        progressTimer: null,
        maxBet: {{ $maxBet }},

        setFraction(fraction) {
            const balance = Number({{ (int) $walletBalance }});
            const maximum = Math.min(this.maxBet, balance);
            this.$wire.bet = Math.max(1, Math.floor(maximum * fraction));
        },

        sideLabel() {
            return this.$wire.side === 'heads' ? 'Cara' : 'Coroa';
        },

        step(d) {
            const current = Number(this.$wire.bet || 1);
            const next = Math.round((current + d) / 5) * 5;
            this.$wire.bet = Math.min(this.maxBet, Math.max(1, next));
        },

        animatePayout(target) {
            const duration = 700;
            const startedAt = performance.now();

            const tick = (now) => {
                const progress = Math.min(1, (now - startedAt) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                this.displayedPayout = Math.round(target * eased);

                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            };

            requestAnimationFrame(tick);
        },

        async launch() {
            if (this.busy || this.$wire.roundPhase !== 'prepared') return;

            this.busy = true;
            this.resultVisible = false;
            this.burst = false;
            this.progress = 0;
            this.stageMessage = 'A validar a ronda…';

            const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            try {
                await this.$wire.flip();

                const outcome = this.$wire.roundResult?.outcome === 'tails' ? 'tails' : 'heads';
                const won = Boolean(this.$wire.roundResult?.won);
                const payout = Number(this.$wire.roundPayout || 0);

                this.displayedOutcome = outcome;
                this.displayedWon = won;
                this.displayedPayout = 0;
                this.displayedBet = Number(this.$wire.bet || 0);
                this.flipping = true;
                this.stageMessage = 'A moeda está no ar…';

                if (!calm) {
                    const startedAt = performance.now();
                    this.progressTimer = window.setInterval(() => {
                        this.progress = Math.min(100, ((performance.now() - startedAt) / 1850) * 100);
                    }, 32);

                    await new Promise(resolve => setTimeout(resolve, 1850));
                }

                window.clearInterval(this.progressTimer);
                this.progressTimer = null;
                this.progress = 100;
                this.flipping = false;
                this.resultVisible = true;
                this.stageMessage = won ? 'Vitória confirmada' : 'Resultado revelado';

                if (won && payout > 0) {
                    this.burst = true;
                    this.animatePayout(payout);

                    window.setTimeout(() => {
                        this.burst = false;
                    }, 1100);
                } else {
                    this.displayedPayout = payout;
                }
            } catch (e) {
                this.flipping = false;
                this.stageMessage = 'Pronto para tentar novamente';
            } finally {
                window.clearInterval(this.progressTimer);
                this.progressTimer = null;
                this.busy = false;
            }
        },

        destroy() {
            window.clearInterval(this.progressTimer);
        }
    }"
    x-on:keydown.window="
        if (['INPUT','TEXTAREA','BUTTON','SELECT','SUMMARY'].includes($event.target.tagName)) return;
        if ($event.code === 'Space') {
            $event.preventDefault();
            if (!busy && $wire.roundPhase === 'prepared') launch();
        }
        if ($event.key === '1' && !busy && $wire.roundPhase !== 'prepared') $wire.side = 'heads';
        if ($event.key === '2' && !busy && $wire.roundPhase !== 'prepared') $wire.side = 'tails';
    "
>

    <x-casino.how-it-works game-key="coinflip" title="Como funciona o Coinflip?" description="Escolhe Cara ou Coroa e lança a moeda. Se o resultado coincidir com a tua escolha, recebes o pagamento definido para a ronda." :rules="[['title'=>'Escolhe o lado','text'=>'Seleciona Cara ou Coroa antes de preparar a ronda.'], ['title'=>'Define a aposta','text'=>'Escolhe quantos créditos virtuais queres colocar na ronda.'], ['title'=>'Lança a moeda','text'=>'Quando a ronda estiver preparada, carrega em Lançar para revelar o resultado.'], ['title'=>'Ganha se acertares','text'=>'Se a moeda cair no lado escolhido, a ronda é vencedora e recebes os créditos correspondentes.']]" />
    <style>
        .coinflip-page{--cf-gold:#f4c45f;--cf-gold-deep:#b77a18;--cf-red:#f05d62;--cf-green:#42e3a2}
        .coinflip-layout{display:grid;gap:1.5rem;grid-template-columns:minmax(0,1.45fr) minmax(18rem,.75fr)}
        .coinflip-stage{position:relative;overflow:hidden;min-height:34rem;border:1px solid rgba(255,255,255,.08);border-radius:1.75rem;background:radial-gradient(circle at 50% 20%,rgba(244,196,95,.13),transparent 31%),radial-gradient(circle at 50% 90%,rgba(53,211,147,.08),transparent 35%),linear-gradient(145deg,#11151b,#080b0f 65%,#12100a);box-shadow:0 30px 80px rgba(0,0,0,.3)}
        .coinflip-grid{position:absolute;inset:0;opacity:.24;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:38px 38px;mask-image:linear-gradient(to bottom,black,transparent 88%)}
        .coinflip-topbar{position:relative;z-index:2;display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.15rem 1.25rem 0}
        .coinflip-kicker{font-size:.67rem;font-weight:900;letter-spacing:.2em;color:#8e98a8;text-transform:uppercase}
        .coinflip-status{display:flex;align-items:center;gap:.5rem;padding:.45rem .7rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(9,12,16,.62);font-size:.7rem;font-weight:800;color:#bac2ce}
        .coinflip-status-dot{width:.45rem;height:.45rem;border-radius:999px;background:var(--cf-green);box-shadow:0 0 15px rgba(66,227,162,.8)}
        .coinflip-stage__content{position:relative;z-index:2;display:grid;place-items:center;min-height:25rem;padding:1.25rem}
        .coin-aura{position:absolute;width:22rem;height:22rem;border-radius:50%;background:radial-gradient(circle,rgba(244,196,95,.17),rgba(244,196,95,.05) 32%,transparent 70%);filter:blur(3px);animation:coinAura 3s ease-in-out infinite}
        .coin-orbit{position:absolute;width:25rem;height:11rem;border:1px solid rgba(244,196,95,.11);border-radius:50%;transform:rotateX(62deg) rotateZ(-7deg);box-shadow:0 0 60px rgba(244,196,95,.06);animation:coinOrbit 10s linear infinite}
        .coin-orbit::after{content:"";position:absolute;inset:.45rem;border:1px dashed rgba(244,196,95,.07);border-radius:50%}
        .coin-pedestal{position:absolute;bottom:3.5rem;width:13rem;height:2rem;border-radius:50%;background:radial-gradient(ellipse,rgba(0,0,0,.75),rgba(0,0,0,0) 72%);filter:blur(3px)}
        .coin-shadow{position:absolute;bottom:4.25rem;width:9rem;height:1.8rem;border-radius:50%;background:rgba(0,0,0,.58);filter:blur(14px);transform:scaleX(1.1);transition:all .6s ease}
        .coin-shadow.is-flipping{transform:scaleX(.5);opacity:.45;filter:blur(20px)}
        .coin-wrap{position:relative;width:12.5rem;height:12.5rem;display:grid;place-items:center;perspective:1100px;filter:drop-shadow(0 25px 25px rgba(0,0,0,.3))}
        .coin-rotator{position:relative;width:10.4rem;height:10.4rem;transform-style:preserve-3d;transition:transform .45s ease;transform:rotateY(0deg) rotateZ(-3deg)}
        .coin-rotator.show-tails{transform:rotateY(180deg) rotateZ(3deg)}
        .coin-rotator.is-flipping.to-heads{animation:coinFlipHeads 1.85s cubic-bezier(.16,.8,.18,1) both}
        .coin-rotator.is-flipping.to-tails{animation:coinFlipTails 1.85s cubic-bezier(.16,.8,.18,1) both}
        .coin-face{position:absolute;inset:0;border-radius:50%;backface-visibility:hidden;display:grid;place-items:center;overflow:hidden;border:8px solid #d49a31;background:radial-gradient(circle at 34% 27%,#fff4bd 0,#f4cf77 9%,#d99625 27%,#b86f0d 66%,#713d08 100%);box-shadow:inset 0 0 0 2px rgba(255,255,255,.5),inset 0 -12px 18px rgba(70,30,0,.28),0 0 0 3px rgba(255,255,255,.08),0 14px 28px rgba(0,0,0,.45)}
        .coin-face::before{content:"";position:absolute;inset:.55rem;border-radius:50%;border:2px solid rgba(255,246,196,.46);box-shadow:inset 0 0 0 1px rgba(90,47,4,.28)}
        .coin-face::after{content:"";position:absolute;top:9%;left:16%;width:34%;height:17%;border-radius:50%;background:rgba(255,255,255,.23);filter:blur(8px);transform:rotate(-20deg)}
        .coin-face--tails{transform:rotateY(180deg)}
        .coin-mark{position:relative;z-index:1;width:5.6rem;height:5.6rem;display:grid;place-items:center;border:2px solid rgba(107,58,4,.42);border-radius:50%;background:rgba(255,235,166,.12);color:#71420a;text-shadow:0 1px 0 rgba(255,244,187,.65);font-size:3.15rem;font-weight:1000;line-height:1}
        .coin-edge{position:absolute;right:-.5rem;top:6%;width:1rem;height:88%;border-radius:999px;background:repeating-linear-gradient(to bottom,#7e4708 0 4px,#e2aa3c 4px 8px);transform:rotate(9deg);filter:blur(.2px);opacity:.8;z-index:-1}
        .coin-pulse{position:absolute;inset:1.8rem;border:1px solid rgba(244,196,95,.15);border-radius:50%;animation:coinPulse 2.6s ease-in-out infinite}
        .coin-particle{position:absolute;left:50%;top:50%;width:.45rem;height:.45rem;border-radius:50%;background:#f8d47e;box-shadow:0 0 16px rgba(244,196,95,.85);opacity:0;pointer-events:none}
        .coin-particle.burst{animation:coinBurst 950ms cubic-bezier(.17,.86,.22,1) forwards;animation-delay:calc(var(--i) * -15ms)}
        .coin-result{position:absolute;bottom:1.1rem;left:50%;z-index:4;min-width:14rem;transform:translateX(-50%);padding:.75rem 1rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(5,7,10,.78);backdrop-filter:blur(14px);text-align:center;box-shadow:0 14px 30px rgba(0,0,0,.28)}
        .coin-result__label{font-size:.62rem;font-weight:900;letter-spacing:.18em;text-transform:uppercase;color:#929aa7}
        .coin-result__value{margin-top:.15rem;font-size:1.05rem;font-weight:950}.coin-result__value--win{color:#78f2b9}.coin-result__value--loss{color:#ff9499}
        .coin-result__payout{margin-top:.1rem;font-size:.78rem;font-weight:800;color:#d7dde5}
        .coin-burst-glow{position:absolute;width:8rem;height:8rem;border-radius:50%;background:radial-gradient(circle,rgba(255,215,113,.28),transparent 70%);filter:blur(8px);opacity:0}
        .coin-stage--win .coin-burst-glow{opacity:1;animation:winGlow 1.2s ease-out}.coin-stage--win .coin-rotator{filter:drop-shadow(0 0 28px rgba(244,196,95,.28))}.coin-stage--loss .coin-rotator{filter:drop-shadow(0 0 20px rgba(240,93,98,.12))}
        .coinflip-panel{padding:1rem;border:1px solid rgba(255,255,255,.07);border-radius:1.25rem;background:rgba(7,10,14,.72);box-shadow:0 18px 40px rgba(0,0,0,.12)}
        .coinflip-panel + .coinflip-panel{margin-top:.9rem}.coinflip-eyebrow{font-size:.64rem;font-weight:950;letter-spacing:.18em;text-transform:uppercase;color:#7e8897}
        .coinflip-statline{display:flex;justify-content:space-between;gap:1rem;align-items:end;margin-top:.45rem}.coinflip-statline strong{font-size:1.2rem;font-weight:1000;color:#f1f4f8}.coinflip-statline span{font-size:.68rem;color:#7d8795}
        .coinflip-meter{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);height:.5rem;margin-top:.75rem;border-radius:999px;overflow:hidden;background:#1a2028}.coinflip-meter__heads{background:linear-gradient(90deg,#f4c45f,#d48c1e);transition:width .5s ease}.coinflip-meter__tails{background:linear-gradient(90deg,#b74d58,#e77a80);transition:width .5s ease}
        .coinflip-meter-labels{display:flex;justify-content:space-between;margin-top:.45rem;font-size:.63rem;font-weight:850}.coinflip-meter-labels span:first-child{color:#f0c86d}.coinflip-meter-labels span:last-child{color:#ed7b82}
        .coinflip-streak{display:flex;align-items:center;justify-content:space-between;gap:.8rem;margin-top:.8rem;padding:.72rem;border:1px solid rgba(255,255,255,.07);border-radius:.9rem;background:rgba(255,255,255,.02)}
        .coinflip-streak__orb{width:2.15rem;height:2.15rem;display:grid;place-items:center;border-radius:50%;background:radial-gradient(circle at 32% 28%,#fff3bf,#f0bd52 34%,#a86a16 100%);color:#69400a;font-weight:1000;box-shadow:0 0 22px rgba(244,196,95,.15)}
        .coinflip-history{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.75rem}.coin-history-dot{width:2rem;height:2rem;display:grid;place-items:center;border-radius:50%;border:1px solid rgba(255,255,255,.08);font-size:.69rem;font-weight:1000;box-shadow:inset 0 1px 0 rgba(255,255,255,.08);transition:transform .2s ease}.coin-history-dot:hover{transform:translateY(-2px)}.coin-history-dot--heads{background:rgba(244,196,95,.11);color:#f1ca6d;border-color:rgba(244,196,95,.25)}.coin-history-dot--tails{background:rgba(231,113,121,.1);color:#ef8d94;border-color:rgba(231,113,121,.22)}
        .coinflip-choice-box{display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:center}.coinflip-choice-box__value{font-size:1.35rem;font-weight:1000;color:#f2f4f7}.coinflip-choice-box__odds{text-align:right}.coinflip-choice-box__odds strong{display:block;font-size:.9rem;color:#f4d17a}.coinflip-choice-box__odds span{font-size:.61rem;color:#76808d}
        .coinflip-controls{margin-top:-.25rem;padding:1rem;border:1px solid rgba(255,255,255,.07);border-radius:1.35rem;background:rgba(6,9,13,.7)}
        .coinflip-label{display:block;margin-bottom:.55rem;font-size:.68rem;font-weight:900;letter-spacing:.16em;text-transform:uppercase;color:#8f98a7}
        .coinflip-side-grid{display:grid;grid-template-columns:1fr 1fr;gap:.65rem}.coin-choice{position:relative;display:flex;align-items:center;justify-content:space-between;gap:.7rem;padding:.85rem .9rem;border:1px solid rgba(255,255,255,.08);border-radius:1rem;background:rgba(255,255,255,.025);color:#c9d0da;transition:transform .2s ease,border-color .2s ease,background .2s ease,box-shadow .2s ease}.coin-choice:hover{transform:translateY(-2px);border-color:rgba(244,196,95,.28);background:rgba(244,196,95,.055)}.coin-choice--active{border-color:rgba(244,196,95,.72);background:linear-gradient(145deg,rgba(244,196,95,.14),rgba(244,196,95,.035));box-shadow:0 0 0 1px rgba(244,196,95,.14),0 12px 25px rgba(0,0,0,.18)}
        .coin-choice__icon{width:2.6rem;height:2.6rem;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#f7d47f,#af7015);color:#6d3d08;font-size:1rem;font-weight:1000;box-shadow:inset 0 1px 0 rgba(255,255,255,.52)}.coin-choice__copy{flex:1}.coin-choice__title{font-size:.84rem;font-weight:950;color:#f0f3f7}.coin-choice__sub{margin-top:.08rem;font-size:.62rem;color:#858f9e}.coin-choice__check{font-size:.75rem;color:#f6d17d;opacity:0;transform:scale(.7);transition:.2s ease}.coin-choice--active .coin-choice__check{opacity:1;transform:scale(1)}
        .coinflip-stepper{display:grid;grid-template-columns:2.7rem minmax(5rem,1fr) 2.7rem;border:1px solid rgba(255,255,255,.08);border-radius:1rem;overflow:hidden;background:#0c1015}.coinflip-stepper button{border:0;background:rgba(255,255,255,.025);color:#d9e0e8;font-size:1.15rem;cursor:pointer;transition:background .2s ease}.coinflip-stepper button:hover:not(:disabled){background:rgba(244,196,95,.1)}.coinflip-stepper button:disabled{cursor:not-allowed;opacity:.35}.coinflip-bet-input{width:100%;border:0;border-inline:1px solid rgba(255,255,255,.07);background:transparent;padding:.72rem .4rem;text-align:center;font-size:1rem;font-weight:950;color:#fff;outline:0}
        .coinflip-chips{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.65rem}.coinflip-chip{padding:.45rem .7rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(255,255,255,.025);color:#aeb7c4;font-size:.7rem;font-weight:900;transition:.2s ease;cursor:pointer}.coinflip-chip:hover:not(:disabled){transform:translateY(-1px);border-color:rgba(244,196,95,.3);color:#f7d681;background:rgba(244,196,95,.07)}.coinflip-chip:disabled{opacity:.35;cursor:not-allowed}
        .coinflip-action{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:.85rem}.coinflip-primary{flex:1;min-height:3.1rem;border:0;border-radius:1rem;background:linear-gradient(135deg,#f2cf78,#c28722 54%,#8d590f);color:#2c1d07;font-size:.82rem;font-weight:1000;letter-spacing:.07em;text-transform:uppercase;box-shadow:0 12px 28px rgba(178,118,24,.23),inset 0 1px 0 rgba(255,255,255,.46);cursor:pointer;transition:transform .2s ease,filter .2s ease,box-shadow .2s ease}.coinflip-primary:hover:not(:disabled){transform:translateY(-2px);filter:brightness(1.04);box-shadow:0 18px 32px rgba(178,118,24,.28),inset 0 1px 0 rgba(255,255,255,.48)}.coinflip-primary:disabled{cursor:not-allowed;opacity:.5;transform:none}.coinflip-space{font-size:.62rem;color:#76808e}
        .coinflip-result-pill{display:inline-flex;align-items:center;gap:.4rem;margin-top:.8rem;padding:.42rem .65rem;border-radius:999px;background:rgba(255,255,255,.035);font-size:.64rem;font-weight:900;color:#b8c0ca}.coinflip-result-pill::before{content:"";width:.42rem;height:.42rem;border-radius:50%;background:#42e3a2;box-shadow:0 0 12px rgba(66,227,162,.7)}
        .coinflip-fairness{margin-top:.9rem}.coinflip-fairness summary{cursor:pointer;list-style:none}.coinflip-fairness summary::-webkit-details-marker{display:none}.coinflip-fairness summary::after{content:"+";float:right;color:#6f7988;font-size:1rem}.coinflip-fairness[open] summary::after{content:"−"}.coinflip-seed{margin-top:.7rem;width:100%;border:1px solid rgba(255,255,255,.08);border-radius:.75rem;background:#0b0f14;padding:.65rem .75rem;color:#dce2e8;font:600 .68rem ui-monospace,SFMono-Regular,Menlo,monospace}
        .coinflip-topbar__right{display:flex;align-items:center;gap:.5rem}.coinflip-live-tag{display:flex;align-items:center;gap:.45rem;padding:.42rem .65rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(7,10,14,.62);font-size:.61rem;font-weight:950;letter-spacing:.12em;color:#aeb6c2}.coinflip-balance-pill{display:flex;align-items:center;gap:.5rem;padding:.42rem .7rem;border:1px solid rgba(244,196,95,.18);border-radius:999px;background:rgba(244,196,95,.06);font-size:.62rem;color:#8f98a7}.coinflip-balance-pill strong{color:#f7d783;font-size:.72rem;font-variant-numeric:tabular-nums}.coinflip-live-hud{position:absolute;top:1rem;left:50%;z-index:5;width:min(23rem,78%);transform:translateX(-50%) translateY(-.35rem);padding:.5rem .65rem;border:1px solid rgba(255,255,255,.07);border-radius:999px;background:rgba(5,8,12,.7);backdrop-filter:blur(12px);opacity:.72;box-shadow:0 12px 28px rgba(0,0,0,.18);transition:opacity .25s ease,transform .25s ease,border-color .25s ease}.coinflip-live-hud.is-active{opacity:1;transform:translateX(-50%) translateY(0);border-color:rgba(244,196,95,.25);box-shadow:0 0 28px rgba(244,196,95,.08)}.coinflip-live-hud.is-done{opacity:.94}.coinflip-live-hud__dot{display:inline-block;width:.42rem;height:.42rem;margin:0 .42rem 0 .1rem;border-radius:50%;background:#6ff0b6;box-shadow:0 0 12px rgba(111,240,182,.85)}.coinflip-live-hud.is-active .coinflip-live-hud__dot{animation:coinLiveDot .65s ease-in-out infinite alternate}.coinflip-live-hud__label{display:inline-block;min-width:8rem;font-size:.62rem;font-weight:900;letter-spacing:.08em;color:#d7dde4}.coinflip-live-hud__progress{display:block;height:.16rem;margin:.35rem .1rem 0;overflow:hidden;border-radius:999px;background:rgba(255,255,255,.06)}.coinflip-live-hud__progress i{display:block;height:100%;width:0;border-radius:inherit;background:linear-gradient(90deg,#9b6514,#f7d783,#fff0b4);box-shadow:0 0 12px rgba(244,196,95,.45);transition:width .05s linear}.coinflip-balance-card{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.85rem;padding:.78rem .8rem;border:1px solid rgba(255,255,255,.07);border-radius:.9rem;background:linear-gradient(135deg,rgba(244,196,95,.055),rgba(255,255,255,.018))}.coinflip-balance-card>div{min-width:0}.coinflip-balance-card__label{display:block;font-size:.58rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#707b8b}.coinflip-balance-card strong{display:block;margin-top:.16rem;font-size:1rem;font-variant-numeric:tabular-nums;color:#eef2f6}.coinflip-balance-card__after{color:#d8b45b!important}.coinflip-label-row{display:flex;align-items:center;justify-content:space-between;gap:1rem}.coinflip-bet-limit{font-size:.58rem;font-weight:800;color:#677280}.coinflip-chip--gold{border-color:rgba(244,196,95,.24);background:rgba(244,196,95,.05);color:#f0c969}.coinflip-percentages{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-top:.65rem}.coinflip-percentages>span{font-size:.58rem;font-weight:800;color:#6f7a89}.coinflip-percentages>div{display:flex;gap:.3rem}.coinflip-percent{padding:.34rem .48rem;border:1px solid rgba(255,255,255,.07);border-radius:.5rem;background:rgba(255,255,255,.02);color:#9ea8b5;font-size:.57rem;font-weight:900;cursor:pointer;transition:.18s ease}.coinflip-percent:hover:not(:disabled){transform:translateY(-1px);border-color:rgba(244,196,95,.25);color:#f2cf78}.coinflip-percent:disabled{opacity:.35;cursor:not-allowed}.coinflip-payout-preview{display:flex;justify-content:space-between;gap:1rem;align-items:center;margin-top:.85rem;padding:.78rem .8rem;border:1px solid rgba(244,196,95,.12);border-radius:.9rem;background:rgba(244,196,95,.035)}.coinflip-payout-preview__label{display:block;font-size:.58rem;font-weight:850;color:#7c8795}.coinflip-payout-preview strong{display:inline-block;margin-top:.12rem;font-size:1.15rem;color:#f5d27b}.coinflip-payout-preview small{margin-left:.35rem;color:#677282;font-size:.58rem}.coinflip-payout-preview__multiplier{text-align:right}.coinflip-payout-preview__multiplier strong{font-size:.92rem}.coinflip-payout-preview__multiplier span{display:block;font-size:.56rem;color:#687484}.coin-result__net{margin-top:.16rem;font-size:.61rem;font-weight:850}.coin-result__net.is-win{color:#68e7ad}.coin-result__net.is-loss{color:#ff9098}.coinflip-result-pill{display:flex;align-items:center;justify-content:space-between;gap:.8rem}.coinflip-result-pill strong{font-size:.58rem;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:#5f6977}.coinflip-empty-balance{margin-top:.7rem;padding:.65rem .75rem;border:1px solid rgba(250,104,114,.14);border-radius:.75rem;background:rgba(250,104,114,.04);font-size:.65rem;font-weight:800;color:#e99399}.coinflip-fairness__headline{display:flex;align-items:center;justify-content:space-between;gap:1rem}.coinflip-lock{width:2rem;height:2rem;display:grid;place-items:center;border:1px solid rgba(255,255,255,.07);border-radius:.65rem;background:rgba(255,255,255,.02);font-size:.86rem}.coinflip-hash-row{display:flex;align-items:flex-start;justify-content:space-between;gap:.8rem;margin-top:.7rem}.coinflip-copy{flex:none;padding:.4rem .55rem;border:1px solid rgba(255,255,255,.08);border-radius:.55rem;background:rgba(255,255,255,.025);color:#aeb8c5;font-size:.58rem;font-weight:900;cursor:pointer;transition:.18s ease}.coinflip-copy:hover{border-color:rgba(244,196,95,.28);color:#f2d17c;transform:translateY(-1px)}.coinflip-primary{position:relative;overflow:hidden}.coinflip-primary::after{content:"";position:absolute;inset:0;width:35%;transform:translateX(-180%) skewX(-18deg);background:linear-gradient(90deg,transparent,rgba(255,255,255,.35),transparent);animation:coinPrimaryShine 3.2s ease-in-out infinite}.coinflip-primary:disabled::after{display:none}
        @keyframes coinLiveDot{from{transform:scale(.72);opacity:.55}to{transform:scale(1.12);opacity:1}}@keyframes coinPrimaryShine{0%,55%,100%{transform:translateX(-180%) skewX(-18deg)}72%{transform:translateX(350%) skewX(-18deg)}}
        @keyframes coinAura{0%,100%{transform:scale(.96);opacity:.72}50%{transform:scale(1.04);opacity:1}}
        @keyframes coinOrbit{to{transform:rotateX(62deg) rotateZ(353deg)}}@keyframes coinPulse{0%,100%{transform:scale(.97);opacity:.34}50%{transform:scale(1.08);opacity:.72}}
        @keyframes coinFlipHeads{0%{transform:translateY(1rem) rotateZ(-5deg) rotateY(0)}12%{transform:translateY(-3.2rem) rotateZ(7deg) rotateY(360deg)}34%{transform:translateY(-7.4rem) rotateZ(-9deg) rotateY(900deg)}56%{transform:translateY(-3.1rem) rotateZ(8deg) rotateY(1260deg)}78%{transform:translateY(-.35rem) rotateZ(-2deg) rotateY(1620deg)}92%{transform:translateY(.4rem) rotateZ(2deg) rotateY(1760deg)}100%{transform:translateY(0) rotateZ(-2deg) rotateY(1800deg)}}
        @keyframes coinFlipTails{0%{transform:translateY(1rem) rotateZ(-5deg) rotateY(0)}12%{transform:translateY(-3.2rem) rotateZ(7deg) rotateY(360deg)}34%{transform:translateY(-7.4rem) rotateZ(-9deg) rotateY(1080deg)}56%{transform:translateY(-3.1rem) rotateZ(8deg) rotateY(1440deg)}78%{transform:translateY(-.35rem) rotateZ(-2deg) rotateY(1800deg)}92%{transform:translateY(.4rem) rotateZ(2deg) rotateY(1940deg)}100%{transform:translateY(0) rotateZ(3deg) rotateY(1980deg)}}
        @keyframes coinBurst{0%{opacity:0;transform:rotate(calc(var(--i) * 22.5deg)) translateY(0) scale(.4)}12%{opacity:1}100%{opacity:0;transform:rotate(calc(var(--i) * 22.5deg)) translateY(145px) scale(.15)}}@keyframes winGlow{0%{transform:scale(.4);opacity:0}30%{opacity:1}100%{transform:scale(1.7);opacity:0}}
        @media(max-width:1024px){.coinflip-layout{grid-template-columns:1fr}.coinflip-stage{min-height:31rem}}@media(max-width:640px){.coinflip-topbar{align-items:flex-start}.coinflip-side-grid{grid-template-columns:1fr}.coinflip-action{align-items:stretch;flex-direction:column}.coinflip-stage{min-height:29rem;border-radius:1.25rem}.coin-wrap{width:11rem;height:11rem}.coin-rotator{width:9.2rem;height:9.2rem}.coin-mark{width:5rem;height:5rem;font-size:2.7rem}.coin-result{min-width:12rem}.coin-aura{width:18rem;height:18rem}.coin-orbit{width:20rem}}
        @media(prefers-reduced-motion:reduce){.coin-aura,.coin-orbit,.coin-pulse,.coinflip-chip,.coin-choice,.coinflip-primary,.coin-history-dot{animation:none!important;transition:none!important}.coin-rotator.is-flipping.to-heads,.coin-rotator.is-flipping.to-tails{animation:none}.coin-burst-glow{display:none}.coin-particle.burst{animation:none}}
    </style>

    <div class="coinflip-layout">
        <section class="coinflip-stage" :class="{
            'coin-stage--win': resultVisible && displayedWon,
            'coin-stage--loss': resultVisible && !displayedWon
        }">
            <div class="coinflip-grid" aria-hidden="true"></div>
            @for ($i = 0; $i < 16; $i++)
                <i class="coin-particle" :class="{ 'burst': burst }" style="--i: {{ $i }}" aria-hidden="true"></i>
            @endfor

            <div class="coin-burst-glow" aria-hidden="true"></div>

            <div class="coinflip-topbar">
                <div>
                    <p class="coinflip-kicker">JOGO 01 · ORIGINAL</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-white">Coinflip</h2>
                </div>
                <div class="coinflip-topbar__right">
                    <div class="coinflip-live-tag">
                        <span class="coinflip-status-dot"></span>
                        <span x-text="busy ? 'LANÇAMENTO' : 'AO VIVO'"></span>
                    </div>
                    <div class="coinflip-balance-pill">
                        <span>Saldo</span>
                        <strong>{{ number_format((int) $walletBalance, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>

            <div class="coinflip-stage__content" aria-live="polite">
                <div class="coin-aura" aria-hidden="true"></div>
                <div class="coin-orbit" aria-hidden="true"></div>
                <div class="coin-pulse" aria-hidden="true"></div>

                <div class="coinflip-live-hud" :class="{ 'is-active': busy, 'is-done': resultVisible && !busy }">
                    <span class="coinflip-live-hud__dot"></span>
                    <span class="coinflip-live-hud__label" x-text="stageMessage"></span>
                    <span class="coinflip-live-hud__progress"><i :style="'width:' + progress + '%'"></i></span>
                </div>

                <div class="coin-wrap">
                    <div class="coin-shadow" :class="{ 'is-flipping': flipping }" aria-hidden="true"></div>

                    <div
                        class="coin-rotator"
                        :class="{
                            'is-flipping': flipping,
                            'to-heads': displayedOutcome === 'heads',
                            'to-tails': displayedOutcome === 'tails',
                            'show-tails': !flipping && displayedOutcome === 'tails'
                        }"
                        role="img"
                        :aria-label="displayedOutcome === 'heads' ? 'Moeda: Cara' : 'Moeda: Coroa'"
                    >
                        <div class="coin-face">
                            <div class="coin-mark">C</div>
                            <span class="coin-edge" aria-hidden="true"></span>
                        </div>
                        <div class="coin-face coin-face--tails">
                            <div class="coin-mark">T</div>
                            <span class="coin-edge" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>

                <div class="coin-pedestal" aria-hidden="true"></div>
            </div>

            <div x-show="resultVisible" x-cloak x-transition.opacity class="coin-result">
                <p class="coin-result__label">Resultado confirmado</p>
                <p class="coin-result__value" :class="displayedWon ? 'coin-result__value--win' : 'coin-result__value--loss'">
                    <span x-text="displayedOutcome === 'heads' ? 'CARA' : 'COROA'"></span>
                    <span class="mx-1">·</span>
                    <span x-text="displayedWon ? 'GANHOU' : 'NÃO GANHOU'"></span>
                </p>
                <p class="coin-result__payout" x-text="displayedPayout > 0 ? '+' + Number(displayedPayout).toLocaleString('pt-PT') + ' créditos virtuais' : '0 créditos virtuais'"></p>
                <p class="coin-result__net" :class="displayedWon ? 'is-win' : 'is-loss'" x-text="displayedWon ? 'Lucro líquido · +' + Math.max(0, displayedPayout - displayedBet).toLocaleString('pt-PT') : 'Perda líquida · -' + Number(displayedBet).toLocaleString('pt-PT')"></p>
            </div>
        </section>

        <aside>
            <div class="coinflip-panel">
                <div class="coinflip-choice-box">
                    <div>
                        <p class="coinflip-eyebrow">A tua escolha</p>
                        <p class="coinflip-choice-box__value" x-text="sideLabel()"></p>
                    </div>
                    <div class="coinflip-choice-box__odds">
                        <strong>{{ number_format($multiplier, 2, '.', '') }}×</strong>
                        <span>pagamento bruto</span>
                    </div>
                </div>

                <div class="coinflip-side-grid mt-4">
                    <button type="button" class="coin-choice" :class="{ 'coin-choice--active': $wire.side === 'heads' }" x-on:click="$wire.side = 'heads'" :disabled="busy || @js($locked)">
                        <span class="coin-choice__icon">C</span><span class="coin-choice__copy"><span class="coin-choice__title block">Cara</span><span class="coin-choice__sub block">Heads · 50%</span></span><span class="coin-choice__check">●</span>
                    </button>
                    <button type="button" class="coin-choice" :class="{ 'coin-choice--active': $wire.side === 'tails' }" x-on:click="$wire.side = 'tails'" :disabled="busy || @js($locked)">
                        <span class="coin-choice__icon">T</span><span class="coin-choice__copy"><span class="coin-choice__title block">Coroa</span><span class="coin-choice__sub block">Tails · 50%</span></span><span class="coin-choice__check">●</span>
                    </button>
                </div>

                <div class="coinflip-controls">
                    <div class="coinflip-balance-card">
                        <div>
                            <span class="coinflip-balance-card__label">Saldo disponível</span>
                            <strong>{{ number_format((int) $walletBalance, 0, ',', '.') }}</strong>
                        </div>
                        <div class="text-right">
                            <span class="coinflip-balance-card__label">Após a aposta</span>
                            <strong class="coinflip-balance-card__after">{{ number_format((int) $balanceAfterBet, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="coinflip-label-row">
                        <label class="coinflip-label !mb-0">Aposta em créditos virtuais</label>
                        <span class="coinflip-bet-limit">1 – {{ number_format($maxBet, 0, ',', '.') }}</span>
                    </div>
                    <div class="coinflip-stepper">
                        <button type="button" x-on:click="step(-5)" :disabled="busy || @js($locked)" aria-label="Diminuir aposta">−</button>
                        <input type="number" min="{{ $minimumBet }}" step="1" max="{{ max(1, $effectiveMaxBet) }}" wire:model="bet" :disabled="busy || @js($locked)" class="coinflip-bet-input" aria-label="Aposta em créditos virtuais">
                        <button type="button" x-on:click="step(5)" :disabled="busy || @js($locked)" aria-label="Aumentar aposta">+</button>
                    </div>

                    <div class="coinflip-chips">
                        @foreach ([25, 50, 100, 250, 500, 1000] as $chip)
                            @if ($chip <= $maxBet)
                                <button type="button" class="coinflip-chip" x-on:click="$wire.bet = {{ min($chip, $effectiveMaxBet) }}" :disabled="busy || @js($locked) || {{ $effectiveMaxBet }} < {{ $minimumBet }}">{{ $chip }}</button>
                            @endif
                        @endforeach
                        <button type="button" class="coinflip-chip coinflip-chip--gold" x-on:click="$wire.bet = {{ max(0, $effectiveMaxBet) }}" :disabled="busy || @js($locked) || {{ $effectiveMaxBet }} < {{ $minimumBet }}">MAX</button>
                    </div>

                    <div class="coinflip-percentages">
                        <span>Percentagem do saldo</span>
                        <div>
                            <button type="button" class="coinflip-percent" x-on:click="setFraction(.25)" :disabled="busy || @js($locked) || {{ $effectiveMaxBet }} < {{ $minimumBet }}">25%</button>
                            <button type="button" class="coinflip-percent" x-on:click="setFraction(.5)" :disabled="busy || @js($locked) || {{ $effectiveMaxBet }} < {{ $minimumBet }}">50%</button>
                            <button type="button" class="coinflip-percent" x-on:click="setFraction(.75)" :disabled="busy || @js($locked) || {{ $effectiveMaxBet }} < {{ $minimumBet }}">75%</button>
                        </div>
                    </div>

                    @error('bet')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror

                    <div class="coinflip-payout-preview">
                        <div>
                            <span class="coinflip-payout-preview__label">Pagamento potencial</span>
                            <strong>+{{ number_format($potentialProfit, 0, ',', '.') }}</strong>
                            <small>lucro líquido</small>
                        </div>
                        <div class="coinflip-payout-preview__multiplier">
                            <strong>{{ number_format($multiplier, 2, '.', '') }}×</strong>
                            <span>se acertares</span>
                        </div>
                    </div>
                </div>

                <div class="coinflip-action">
                    @if ($roundPhase === 'prepared')
                        <button type="button" class="coinflip-primary" x-on:click="launch()" :disabled="busy || !@js($canBet)">
                            <span x-show="!busy">Lançar moeda</span>
                            <span x-show="busy" x-cloak>A moeda está no ar…</span>
                        </button>
                    @else
                        <button type="button" class="coinflip-primary" wire:click="prepare" wire:loading.attr="disabled" @disabled(! $canBet)>
                            {{ $roundPhase === 'completed' ? 'Preparar nova ronda' : 'Preparar ronda' }}
                        </button>
                    @endif
                    <span class="coinflip-space">1 = Cara · 2 = Coroa · Espaço = lançar</span>
                </div>

                @error('game')<p role="alert" class="mt-3 text-xs text-rose-300">{{ $message }}</p>@enderror

                @if ($roundPhase === 'prepared')
                    <div class="coinflip-result-pill"><span>Hash fixado antes do lançamento</span><strong>#{{ $roundId }}</strong></div>
                @elseif (! $canBet)
                    <div class="coinflip-empty-balance">Saldo insuficiente para a aposta mínima.</div>
                @endif
            </div>

            <div class="coinflip-panel">
                <p class="coinflip-eyebrow">Ritmo recente</p>
                <div class="coinflip-statline">
                    <strong>Últimas {{ $historyTotal }} rondas</strong>
                    <span>{{ $winsCount }} vitória(s)</span>
                </div>

                <div class="coinflip-meter" aria-label="Distribuição das últimas rondas">
                    <span class="coinflip-meter__heads" style="width: {{ $headsPercent }}%"></span>
                    <span class="coinflip-meter__tails" style="width: {{ $tailsPercent }}%"></span>
                </div>

                <div class="coinflip-meter-labels">
                    <span>C Cara {{ $headsPercent }}%</span>
                    <span>Coroa {{ $tailsPercent }}% T</span>
                </div>

                <div class="coinflip-streak">
                    <div class="flex items-center gap-3">
                        <span class="coinflip-streak__orb">{{ $streakSide === 'tails' ? 'T' : 'C' }}</span>
                        <span>
                            <span class="block text-xs font-black text-zinc-200">{{ $currentStreak > 0 ? $currentStreak.' seguida(s)' : 'Sem sequência' }}</span>
                            <span class="mt-0.5 block text-[0.62rem] text-zinc-500">{{ $currentStreak > 0 ? (($streakSide === 'tails' ? 'Coroa' : 'Cara').' na sequência atual') : 'Nenhuma sequência ainda' }}</span>
                        </span>
                    </div>
                    <span class="text-[0.62rem] font-bold text-zinc-500">50/50 teórico</span>
                </div>

                <div class="coinflip-history" aria-label="Últimas rondas">
                    @forelse (array_slice($recentFlips, 0, 12) as $flip)
                        <span class="coin-history-dot {{ $flip['outcome'] === 'heads' ? 'coin-history-dot--heads' : 'coin-history-dot--tails' }}" title="{{ $flip['outcome'] === 'heads' ? 'Cara' : 'Coroa' }} · {{ $flip['won'] ? 'Vitória' : 'Sem prémio' }} · {{ $flip['time'] }}">
                            {{ $flip['outcome'] === 'heads' ? 'C' : 'T' }}
                        </span>
                    @empty
                        <span class="text-xs text-zinc-500">As tuas últimas jogadas aparecem aqui.</span>
                    @endforelse
                </div>
            </div>

            <div class="coinflip-panel coinflip-fairness">
                <div class="coinflip-fairness__headline">
                    <div>
                        <p class="coinflip-eyebrow">Integridade da ronda</p>
                        <p class="mt-1 text-xs text-zinc-500">Verificável por hash antes do lançamento.</p>
                    </div>
                    <span class="coinflip-lock">🔐</span>
                </div>
                <details class="mt-3">
                    <summary class="coinflip-eyebrow">🔐 Jogo transparente</summary>
                    <p class="mt-3 text-xs leading-5 text-zinc-500">O hash do servidor é fixado antes do resultado. Depois da ronda, o resultado pode ser verificado.</p>
                    @if ($serverSeedHash)
                        <div class="coinflip-hash-row">
                            <div>
                                <p class="text-[0.61rem] font-black uppercase tracking-[0.16em] text-zinc-600">Hash do servidor</p>
                                <p class="mt-1 break-all font-mono text-[0.68rem] leading-5 text-zinc-300">{{ $serverSeedHash }}</p>
                            </div>
                            <button type="button" class="coinflip-copy" x-data="{ copied: false }" x-on:click="navigator.clipboard?.writeText(@js($serverSeedHash)); copied = true; setTimeout(() => copied = false, 1400)" x-text="copied ? 'Copiado' : 'Copiar'"></button>
                        </div>
                    @endif
                    <label class="mt-4 block text-[0.66rem] font-bold text-zinc-500">Seed do cliente
                        <input type="text" maxlength="128" wire:model="clientSeed" :disabled="busy || @js($locked)" class="coinflip-seed">
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
