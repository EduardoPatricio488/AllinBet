@php
    $maxBet = max(0, (int) $walletBalance);
    $houseEdge = (int) config('casino.games.coinflip.house_edge_bps', 250);
    $multiplier = (10000 - $houseEdge) / 5000;
    $historyTotal = $headsCount + $tailsCount;
    $headsPercent = $historyTotal > 0 ? (int) round(($headsCount / $historyTotal) * 100) : 50;
    $tailsPercent = 100 - $headsPercent;
    $has = $roundResult !== [];
    $initOutcome = ($roundResult['outcome'] ?? 'heads') === 'tails' ? 'tails' : 'heads';
@endphp

<div class="casino-game-play coinflip-page cf casino-game-screen" data-casino-game="coinflip"
     x-data="{
        busy: false, charge: false, tossing: false, landed: false, burst: false,
        revealFace: {{ $has ? 'true' : 'false' }}, resultVisible: {{ $has ? 'true' : 'false' }},
        rot: {{ $initOutcome === 'tails' ? 180 : 0 }}, dur: 0,
        outcome: @js($initOutcome), won: {{ ($roundResult['won'] ?? false) ? 'true' : 'false' }},
        payout: {{ (int) $roundPayout }}, shown: {{ (int) $roundPayout }}, maxBet: {{ $maxBet }}, mult: {{ $multiplier }},
        phase: 'ready', countdown: 0, countdownPct: 0, payoutReleased: true,
        spinStartedAt: 0, spinFrame: 0, backendReady: false, backendError: false,
        get off() { return this.busy || !this.payoutReleased || ['prepared', 'in_progress'].includes(this.$wire.roundPhase); },
        get label() { return this.$wire.side === 'heads' ? 'Cara' : 'Coroa'; },
        get potential() { return Math.floor(Number(this.$wire.bet || 0) * this.mult); },
        get statusText() {
            if (this.busy && this.tossing) return 'A RODAR';
            if (this.busy && this.phase === 'result') return 'A DISTRIBUIR O PRÉMIO';
            if (this.busy) return this.backendError ? 'ERRO' : 'A PREPARAR';
            if (this.resultVisible) return this.won ? 'VITÓRIA' : 'RESULTADO';
            return this.$wire.roundPhase === 'prepared' ? 'MOEDA PREPARADA' : 'PRONTO A JOGAR';
        },
        get countdownText() {
            return this.countdown > 0 ? this.countdown.toFixed(1) + 's' : '0,0s';
        },
        sfx(name) { this.$dispatch('casino-sfx', { name }); },
        wait(ms) { return new Promise((r) => setTimeout(r, ms)); },
        pick(s) { if (this.off) return; this.$wire.side = s; this.sfx('chip'); },
        step(d) { if (this.off) return; this.$wire.bet = Math.min(this.maxBet, Math.max(1, Math.round((Number(this.$wire.bet || 1) + d) / 5) * 5)); },
        count(p) {
            const t0 = performance.now();
            const tick = (t) => {
                const k = Math.min(1, (t - t0) / 800);
                this.shown = Math.round(p * (1 - Math.pow(1 - k, 3)));
                if (k < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        },
        mod(a) { return ((a % 360) + 360) % 360; },
        async spinCoin(startTime) {
            const startAngle = this.rot;
            const minSpinMs = 4000;
            const cruiseMs = 3300;
            this.spinStartedAt = startTime;
            this.backendReady = false;
            this.backendError = false;
            this.phase = 'spinning';

            const frame = (now) => {
                const elapsed = now - startTime;
                const clamped = Math.min(elapsed, cruiseMs);
                const cruiseAngle = startAngle + (clamped * 0.75);

                if (this.backendReady && elapsed >= cruiseMs) {
                    const target = this.outcome === 'tails' ? 180 : 0;
                    const current = cruiseAngle;
                    const distance = 360 + this.mod(target - this.mod(current));
                    const settleProgress = Math.min(1, (elapsed - cruiseMs) / (minSpinMs - cruiseMs));
                    const eased = 1 - Math.pow(1 - settleProgress, 3);
                    this.rot = current + (distance * eased);
                } else {
                    this.rot = startAngle + (elapsed * 0.75);
                }

                this.countdown = Math.max(0, (minSpinMs - elapsed) / 1000);
                this.countdownPct = Math.min(100, Math.max(0, (elapsed / minSpinMs) * 100));

                if (elapsed < minSpinMs || !this.backendReady) {
                    this.spinFrame = requestAnimationFrame(frame);
                    return;
                }

                this.rot = startAngle + (cruiseMs * 0.75) + 360 + this.mod((this.outcome === 'tails' ? 180 : 0) - this.mod(startAngle + (cruiseMs * 0.75)));
                this.tossing = false;
                this.revealFace = true;
                this.phase = 'revealed';
                this.landed = true;
                this.sfx('land');
                setTimeout(() => { this.landed = false; }, 650);
            };

            this.tossing = true;
            this.sfx('toss');
            this.spinFrame = requestAnimationFrame(frame);
        },
        async play() {
            if (this.busy || !this.payoutReleased) return;

            const w = this.$wire;
            const startTime = performance.now();
            const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;

            this.busy = true;
            this.payoutReleased = false;
            this.charge = false;
            this.tossing = !calm;
            this.resultVisible = false;
            this.revealFace = false;
            this.burst = false;
            this.backendReady = false;
            this.backendError = false;
            this.countdown = calm ? 0 : 4;
            this.countdownPct = 0;
            this.phase = calm ? 'revealing' : 'spinning';
            this.sfx('charge');

            const backend = (async () => {
                try {
                    if (w.roundPhase !== 'prepared') await w.prepare();

                    if (w.roundPhase !== 'prepared') {
                        this.backendError = true;
                        return false;
                    }

                    await w.flip();

                    const pendingSettlement = w.roundPhase === 'in_progress'
                        && !!w.roundResult?.settlement_pending;

                    if (w.roundPhase !== 'completed' && !pendingSettlement) {
                        this.backendError = true;
                        return false;
                    }

                    const out = w.roundResult?.outcome === 'tails' ? 'tails' : 'heads';
                    this.outcome = out;
                    this.won = !!w.roundResult?.won;
                    this.payout = Number(w.roundPayout || 0);
                    this.shown = 0;
                    this.backendReady = true;
                    return true;
                } catch (e) {
                    this.backendError = true;
                    return false;
                }
            })();

            if (calm) {
                await backend;
                if (!this.backendReady) {
                    this.busy = false;
                    this.payoutReleased = true;
                    this.tossing = false;
                    this.phase = 'ready';
                    return;
                }

                this.rot = this.outcome === 'tails' ? 180 : 0;
                this.revealFace = true;
            } else {
                await this.spinCoin(startTime);
                const ok = await backend;

                if (!ok) {
                    cancelAnimationFrame(this.spinFrame);
                    this.tossing = false;
                    this.countdown = 0;
                    this.phase = 'ready';
                    this.busy = false;
                    this.payoutReleased = true;
                    return;
                }

                if (this.tossing) {
                    await new Promise((resolve) => {
                        const waitForReveal = () => {
                            if (!this.tossing) return resolve();
                            requestAnimationFrame(waitForReveal);
                        };
                        waitForReveal();
                    });
                }
            }

            const settlementDeadline = Date.now() + 1800;
            while (w.roundPhase === 'in_progress' && Date.now() < settlementDeadline) {
                await this.wait(80);
            }

            if (w.roundPhase !== 'completed') {
                this.backendError = true;
                this.busy = false;
                this.payoutReleased = true;
                this.phase = 'ready';
                return;
            }

            this.payout = Number(w.roundPayout || 0);

            this.resultVisible = true;
            this.phase = 'result';

            if (this.won && this.payout > 0) {
                this.burst = true;
                this.count(this.payout);
                this.sfx('win');
                setTimeout(() => { this.burst = false; }, 1400);
                this.$dispatch('casino-toast', { title: 'Vitória!', message: '+' + this.payout + ' créditos virtuais' });
                if (this.payout >= Number(w.bet || 1) * 10) this.$dispatch('casino-big-win', { amount: this.payout });
                await this.wait(850);
            } else {
                this.shown = this.payout;
                this.sfx('lose');
                await this.wait(250);
            }

            this.payoutReleased = true;
            this.busy = false;
        }
    }"
     x-on:keydown.window="if (!['INPUT','TEXTAREA','BUTTON','SELECT','SUMMARY'].includes($event.target.tagName) && !$event.ctrlKey && !$event.metaKey) { if ($event.code === 'Space') { $event.preventDefault(); play(); } else if ($event.key.toLowerCase() === 'c') pick('heads'); else if ($event.key.toLowerCase() === 't') pick('tails'); }">

    <svg width="0" height="0" style="position:absolute" aria-hidden="true">
        <defs>
            <path id="cfArc" d="M50 50m-37 0a37 37 0 1 1 74 0a37 37 0 1 1-74 0"/>
            <symbol id="cfH" viewBox="0 0 100 100">
                <path d="M31 73c5-12 12-18 17-21-5-4-8-10-8-17 0-11 8-20 19-20 10 0 18 8 18 18 0 7-4 13-9 17 6 4 12 12 16 23Z"/>
                <circle cx="56" cy="16" r="2.4"/><path d="M56 25c-7 0-12 6-12 14 0 6 3 11 8 14l-4 4h13l-4-4c5-3 8-8 8-14 0-8-4-14-9-14Z"/>
            </symbol>
            <symbol id="cfT" viewBox="0 0 100 100">
                <path d="M25 34h50l-5 15c-1 3-4 5-8 5H38c-4 0-7-2-8-5Z"/>
                <path d="M30 54h40l-4 9H34Z"/>
                <path d="M34 66h32v13H34Z"/>
                <path d="M29 31 35 20l8 11 7-12 7 12 8-11 6 11Z"/>
            </symbol>
        </defs>
    </svg>

    <x-casino.how-it-works game-key="coinflip" title="Como funciona o Coinflip?" description="Escolhe Cara ou Coroa e lança a moeda. Se o resultado coincidir com a tua escolha, recebes o pagamento da ronda." :rules="[['title'=>'Escolhe o lado','text'=>'Seleciona Cara ou Coroa (teclas C e T).'], ['title'=>'Define a aposta','text'=>'Escolhe quantos créditos virtuais queres colocar na ronda.'], ['title'=>'Lança a moeda','text'=>'Carrega em Lançar (ou na barra de espaço) para revelar o resultado.'], ['title'=>'Ganha se acertares','text'=>'Se a moeda cair no teu lado, recebes os créditos correspondentes.']]" />

    <style>
        .cf{--cf-gold:#f4c45f;--cf-gold-soft:#dca33c;--cf-gold-dark:#7f4d09;--cf-green:#49e0a5;--cf-red:#f46d77}
        .cf-layout{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(18rem,.72fr);gap:1.35rem;align-items:start}
        .cf-stage{position:relative;min-height:37rem;overflow:hidden;border:1px solid rgba(255,255,255,.085);border-radius:1.7rem;background:radial-gradient(circle at 50% 22%,rgba(244,196,95,.14),transparent 28%),radial-gradient(circle at 50% 90%,rgba(73,224,165,.07),transparent 34%),linear-gradient(145deg,#12161d,#070a0e 68%,#171109);box-shadow:0 28px 80px rgba(0,0,0,.34)}
        .cf-stage::before{content:"";position:absolute;inset:-20%;background:conic-gradient(from 0deg,transparent 0 28%,rgba(244,196,95,.04) 36%,transparent 48%,rgba(73,224,165,.03) 60%,transparent 72%);animation:cfSweep 13s linear infinite;pointer-events:none}
        .cf-stage::after{content:"";position:absolute;inset:0;background:radial-gradient(circle at 50% 48%,transparent 0 34%,rgba(0,0,0,.22) 74%,rgba(0,0,0,.45) 100%);pointer-events:none}
        .cf-top{position:relative;z-index:4;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.25rem 1.35rem 0}
        .cf-eyebrow{font-size:.62rem;font-weight:950;letter-spacing:.18em;text-transform:uppercase;color:#85909f}
        .cf-title{margin-top:.22rem;font-size:1.55rem;font-weight:1000;letter-spacing:-.03em;color:#fff}
        .cf-status{display:inline-flex;align-items:center;gap:.5rem;padding:.48rem .72rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(4,7,10,.7);backdrop-filter:blur(12px);font-size:.65rem;font-weight:900;color:#c7ced8;white-space:nowrap}
        .cf-status.is-live{animation:cfLiveBadge 1.1s ease-in-out infinite alternate}
        .cf-broadcast{position:relative;z-index:4;display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;margin:.82rem 1.35rem 0;padding:.55rem .72rem;border:1px solid rgba(255,255,255,.055);border-radius:.8rem;background:linear-gradient(90deg,rgba(255,255,255,.025),rgba(244,196,95,.035),rgba(255,255,255,.02));box-shadow:inset 0 1px 0 rgba(255,255,255,.03)}
        .cf-broadcast span{display:inline-flex;align-items:center;gap:.35rem;padding:.26rem .48rem;border:1px solid rgba(255,255,255,.06);border-radius:999px;color:#8e98a6;font-size:.54rem;font-weight:950;letter-spacing:.12em}
        .cf-broadcast span:first-child{color:#e8ca7e;border-color:rgba(244,196,95,.17);background:rgba(244,196,95,.035)}
        .cf-broadcast i{width:.34rem;height:.34rem;border-radius:50%;background:#67e8b3;box-shadow:0 0 10px rgba(103,232,179,.9);animation:cfBroadcastPulse .8s ease-in-out infinite alternate}
        .cf-circuit{position:absolute;width:22rem;height:9rem;border:1px solid rgba(244,196,95,.045);border-radius:50%;pointer-events:none;opacity:.55}
        .cf-circuit::before,.cf-circuit::after{content:"";position:absolute;border-radius:50%;border:1px solid rgba(73,224,165,.045)}
        .cf-circuit::before{inset:1.15rem;transform:rotate(28deg)}
        .cf-circuit::after{inset:2.1rem;transform:rotate(-41deg)}
        .cf-circuit--one{left:-7rem;top:7rem;transform:rotate(-28deg);animation:cfCircuitDrift 9s ease-in-out infinite}
        .cf-circuit--two{right:-7rem;bottom:6rem;transform:rotate(33deg);animation:cfCircuitDrift 11s ease-in-out -4s infinite reverse}
        .cf-floating-chip{position:absolute;left:50%;top:50%;width:1.35rem;height:1.35rem;display:grid;place-items:center;border-radius:50%;border:1px solid rgba(255,236,179,.26);background:radial-gradient(circle at 35% 28%,#f0cf78,#a96c16 72%);color:#3f2907;font-size:.34rem;font-weight:1000;box-shadow:0 4px 12px rgba(0,0,0,.3),0 0 12px rgba(244,196,95,.07);opacity:.18;animation:cfChipOrbit calc(6s + (var(--i) * .33s)) linear infinite;animation-delay:calc(var(--i) * -.57s);pointer-events:none}
        .cf-floating-chip:nth-of-type(1){--chip-angle:8deg}.cf-floating-chip:nth-of-type(2){--chip-angle:44deg}.cf-floating-chip:nth-of-type(3){--chip-angle:82deg}.cf-floating-chip:nth-of-type(4){--chip-angle:119deg}.cf-floating-chip:nth-of-type(5){--chip-angle:155deg}.cf-floating-chip:nth-of-type(6){--chip-angle:193deg}.cf-floating-chip:nth-of-type(7){--chip-angle:228deg}.cf-floating-chip:nth-of-type(8){--chip-angle:267deg}.cf-floating-chip:nth-of-type(9){--chip-angle:306deg}.cf-floating-chip:nth-of-type(10){--chip-angle:344deg}

        .cf-status i{width:.42rem;height:.42rem;border-radius:50%;background:var(--cf-green);box-shadow:0 0 15px rgba(73,224,165,.9);animation:cfBlink 1s ease-in-out infinite alternate}
        .cf-scene{position:relative;z-index:2;display:grid;place-items:center;min-height:28rem;padding:1rem;isolation:isolate}
        .cf-space-grid{position:absolute;inset:-12%;opacity:.28;background-image:linear-gradient(rgba(255,255,255,.028) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.028) 1px,transparent 1px);background-size:46px 46px;mask-image:radial-gradient(circle at 50% 50%,black 0,rgba(0,0,0,.75) 48%,transparent 80%);transform:perspective(650px) rotateX(58deg) scale(1.25) translateY(25%);animation:cfGridDrift 10s linear infinite}
        .cf-nebula{position:absolute;border-radius:50%;filter:blur(35px);pointer-events:none;mix-blend-mode:screen}.cf-nebula--one{width:28rem;height:17rem;left:-4rem;top:4rem;background:radial-gradient(circle,rgba(244,196,95,.12),transparent 68%);animation:cfNebulaOne 8s ease-in-out infinite}.cf-nebula--two{width:24rem;height:16rem;right:-5rem;bottom:1rem;background:radial-gradient(circle,rgba(74,214,185,.09),transparent 68%);animation:cfNebulaTwo 10s ease-in-out infinite}
        .cf-rays{position:absolute;width:32rem;height:32rem;border-radius:50%;background:conic-gradient(from 0deg,transparent 0 10%,rgba(244,196,95,.05) 13%,transparent 19%,rgba(255,255,255,.025) 25%,transparent 32%,rgba(73,224,165,.045) 39%,transparent 48%,rgba(244,196,95,.045) 58%,transparent 68%,rgba(255,255,255,.02) 76%,transparent 88%);filter:blur(1px);opacity:.72;animation:cfRaySpin 18s linear infinite;pointer-events:none}
        .cf-orbit{position:absolute;left:50%;top:50%;border:1px solid rgba(244,196,95,.11);border-radius:50%;transform:translate(-50%,-50%) rotateX(68deg) rotateZ(-14deg);pointer-events:none}.cf-orbit::before,.cf-orbit::after{content:"";position:absolute;border-radius:50%}.cf-orbit--one{width:25rem;height:10rem;animation:cfOrbitSpin 11s linear infinite}.cf-orbit--two{width:20rem;height:7.8rem;border-color:rgba(73,224,165,.08);transform:translate(-50%,-50%) rotateX(69deg) rotateZ(17deg);animation:cfOrbitSpinReverse 8s linear infinite}.cf-orbit--one::before{inset:-.35rem;border:1px dashed rgba(244,196,95,.06)}.cf-orbit--two::after{inset:.35rem;border:1px solid rgba(73,224,165,.05)}
        .cf-energy-ring{position:absolute;left:50%;top:52%;border-radius:50%;border:1px solid rgba(244,196,95,.09);transform:translate(-50%,-50%) scale(.75);opacity:.1;pointer-events:none}.cf-energy-ring--one{width:10rem;height:10rem;animation:cfEnergyPulse 2.7s ease-out infinite}.cf-energy-ring--two{width:14rem;height:14rem;border-color:rgba(73,224,165,.07);animation:cfEnergyPulse 2.7s ease-out 1.35s infinite}
        .cf-star{position:absolute;left:calc(7% + (var(--i) * 3.7%));top:calc(11% + ((var(--i) * 17) % 74) * 1%);width:calc(.18rem + (var(--i) % 3) * .06rem);height:calc(.18rem + (var(--i) % 3) * .06rem);border-radius:50%;background:rgba(255,235,177,.82);box-shadow:0 0 9px rgba(244,196,95,.6);opacity:calc(.28 + (var(--i) % 5) * .11);animation:cfStarTwinkle calc(1.7s + (var(--i) * 0.13s)) ease-in-out infinite alternate;animation-delay:calc(var(--i) * -0.12s);pointer-events:none}
        .cf-platform{position:absolute;left:50%;bottom:3.05rem;width:16rem;height:3.4rem;transform:translateX(-50%);pointer-events:none;transition:transform .5s ease,filter .5s ease}.cf-platform__ring{position:absolute;inset:.15rem 0;border:1px solid rgba(244,196,95,.18);border-radius:50%;background:radial-gradient(ellipse,rgba(244,196,95,.12),rgba(244,196,95,.025) 48%,transparent 72%);box-shadow:0 0 30px rgba(244,196,95,.09),inset 0 0 20px rgba(244,196,95,.08);animation:cfPlatformSpin 7s linear infinite}.cf-platform__core{position:absolute;left:50%;top:50%;width:6.5rem;height:1.35rem;transform:translate(-50%,-50%);border-radius:50%;background:radial-gradient(ellipse,rgba(255,225,144,.23),rgba(244,196,95,.06) 52%,transparent 74%);box-shadow:0 0 30px rgba(244,196,95,.14);animation:cfCorePulse 2s ease-in-out infinite}.cf-platform__shine{position:absolute;left:50%;top:50%;width:11rem;height:.3rem;transform:translate(-50%,-50%);border-radius:50%;background:linear-gradient(90deg,transparent,rgba(255,241,191,.34),transparent);filter:blur(2px);animation:cfPlatformShine 2.8s ease-in-out infinite}.cf-platform.is-tossing{transform:translateX(-50%) scale(.82);filter:brightness(.7);}.cf-platform.is-landed{transform:translateX(-50%) scale(1.15);filter:brightness(1.3)}

        .cf-aura{position:absolute;width:25rem;height:25rem;border-radius:50%;background:radial-gradient(circle,rgba(244,196,95,.18),rgba(244,196,95,.05) 38%,transparent 72%);filter:blur(5px);animation:cfAura 3.6s ease-in-out infinite}
        .cf-floor{position:absolute;bottom:3.7rem;width:17rem;height:4.2rem;border-radius:50%;background:radial-gradient(ellipse,rgba(244,196,95,.09),transparent 70%);filter:blur(1px);border:1px solid rgba(255,255,255,.03)}
        .cf-shadow{position:absolute;bottom:4.55rem;width:10rem;height:1.7rem;border-radius:50%;background:rgba(0,0,0,.68);filter:blur(14px);transition:.55s ease;transform:scaleX(1.08)}
        .cf-shadow.is-toss{transform:scaleX(.5);opacity:.35;filter:blur(21px)}
        .cf-shadow.is-land{transform:scaleX(1.28);animation:cfLandShadow .55s ease-out}
        .cf-toss{position:relative;width:14rem;height:14rem;display:grid;place-items:center;perspective:1100px}
        .cf-toss.is-charge{animation:cfCharge .5s ease-in-out infinite alternate}
        .cf-toss.is-idle{animation:cfIdle 4.4s ease-in-out infinite}
        .cf-toss.is-land{animation:cfLand .52s cubic-bezier(.2,.9,.3,1)}
        .cf-live-status{position:absolute;top:4.4rem;left:50%;z-index:12;width:min(25rem,calc(100% - 2rem));transform:translateX(-50%);padding:.72rem .85rem;border:1px solid rgba(244,196,95,.32);border-radius:1rem;background:rgba(4,7,11,.78);box-shadow:0 14px 36px rgba(0,0,0,.26),0 0 28px rgba(244,196,95,.08);backdrop-filter:blur(16px);pointer-events:none}
        .cf-live-status__head{display:flex;justify-content:space-between;align-items:center;gap:1rem}.cf-live-status__head>span{display:flex;align-items:center;gap:.45rem}.cf-live-status__head i{width:.46rem;height:.46rem;border-radius:50%;background:#ffd76d;box-shadow:0 0 16px rgba(255,215,109,.95);animation:cfStatusPulse .55s ease-in-out infinite alternate}.cf-live-status__head b{font-size:.64rem;letter-spacing:.12em;color:#fff}.cf-live-status__head strong{font-size:1rem;font-weight:1000;color:#f9d980;font-variant-numeric:tabular-nums}.cf-live-status p{margin-top:.34rem;font-size:.58rem;color:#8f99a7}.cf-countdown{height:.32rem;margin-top:.55rem;overflow:hidden;border-radius:999px;background:rgba(255,255,255,.07)}.cf-countdown span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#e2a63d,#ffe49b);box-shadow:0 0 14px rgba(244,196,95,.46);transition:width .08s linear}.cf-live-status small{display:block;margin-top:.35rem;font-size:.55rem;color:#6f7b8a}
        .cf-spin-flare{position:absolute;inset:-2rem;z-index:-1;border-radius:50%;opacity:0;transform:scale(.7);pointer-events:none}.cf-spin-flare.is-active{opacity:1;transform:scale(1);animation:cfFlareSpin .9s linear infinite}.cf-spin-flare span{position:absolute;left:50%;top:50%;display:block;width:1px;height:44%;transform-origin:50% 100%;background:linear-gradient(180deg,rgba(255,244,186,0),rgba(244,196,95,.34),rgba(255,255,255,.8));filter:drop-shadow(0 0 5px rgba(244,196,95,.36))}.cf-spin-flare span:nth-child(1){transform:translate(-50%,-100%) rotate(0deg)}.cf-spin-flare span:nth-child(2){transform:translate(-50%,-100%) rotate(120deg)}.cf-spin-flare span:nth-child(3){transform:translate(-50%,-100%) rotate(240deg)}
        .cf-status.is-live{border-color:rgba(244,196,95,.35);color:#ffe39a}.cf-status.is-live i{background:#ffd76d;box-shadow:0 0 18px rgba(255,215,109,.95);animation:cfStatusPulse .45s ease-in-out infinite alternate}
        .cf-coin{position:relative;width:10.9rem;height:10.9rem;transform-style:preserve-3d;transform:rotateY(var(--rot));transition:transform .2s linear;filter:drop-shadow(0 26px 24px rgba(0,0,0,.42)) drop-shadow(0 0 26px rgba(244,196,95,.12));will-change:transform}
        .cf-coin.is-tossing{transition:none;filter:drop-shadow(0 26px 24px rgba(0,0,0,.42)) drop-shadow(0 0 38px rgba(244,196,95,.28));}
        .cf-coin.is-tossing .cf-slice{filter:blur(.25px);}
        .cf-unknown{position:absolute;inset:.38rem;z-index:3;display:grid;place-items:center;border-radius:50%;border:2px solid rgba(255,242,180,.38);background:radial-gradient(circle at 35% 28%,#e9c66b,#b97617 58%,#724308);color:rgba(255,243,190,.86);font-size:4rem;font-weight:1000;text-shadow:0 2px 10px rgba(84,43,4,.4);box-shadow:inset 0 0 0 .18rem rgba(255,255,255,.18),inset 0 -1rem 1.2rem rgba(84,43,4,.28);backface-visibility:hidden;transform:translateZ(.2rem);transition:opacity .15s ease,transform .25s ease}
        .cf-unknown.is-hidden{opacity:0;transform:translateZ(.2rem) scale(.96)}
        .cf-coin.is-tossing .cf-face{opacity:0;visibility:hidden}
        .cf-coin.is-revealed .cf-face{opacity:1;visibility:visible}
        .cf-coin.is-tossing .cf-unknown{opacity:1;visibility:visible}
        .cf-coin.is-revealed .cf-unknown{opacity:0;visibility:hidden}
        .cf-coin.is-tossing .cf-slice{opacity:.78}
        .cf-face{transition:opacity .16s ease,visibility .16s ease}
        .cf-coin::after{content:"";position:absolute;inset:-.7rem;border-radius:50%;border:1px solid rgba(244,196,95,.11);box-shadow:0 0 38px rgba(244,196,95,.1);transform:translateZ(-1px)}
        .cf-slice{position:absolute;inset:.05rem;border-radius:50%;border:1px solid rgba(151,95,17,.38);background:linear-gradient(180deg,#7c4b0b,#e0a43d 32%,#8f5b12 66%,#d99930);backface-visibility:hidden;transform-style:preserve-3d}
        .cf-face{position:absolute;inset:0;border-radius:50%;backface-visibility:hidden;display:grid;place-items:center;border:.38rem solid #b87718;background:radial-gradient(circle at 34% 28%,#fff3bd 0,#f4cd6d 12%,#d99a2e 38%,#ae6d11 70%,#6f4108 100%);box-shadow:inset 0 0 0 .14rem rgba(255,255,255,.34),inset 0 -1rem 1.4rem rgba(86,44,2,.3),0 0 0 2px rgba(255,255,255,.06)}
        .cf-face::before{content:"";position:absolute;inset:.6rem;border:2px solid rgba(255,241,183,.46);border-radius:50%;box-shadow:inset 0 0 0 1px rgba(78,42,4,.28)}
        .cf-face::after{content:"";position:absolute;top:11%;left:18%;width:28%;height:13%;border-radius:50%;background:rgba(255,255,255,.24);filter:blur(7px);transform:rotate(-18deg)}
        .cf-face--t{transform:rotateY(180deg)}
        .cf-face-word{position:absolute;z-index:4;left:50%;top:50%;transform:translate(-50%,-46%);font-size:1.65rem;line-height:1;letter-spacing:.06em;font-weight:1000;color:#6f4107;text-shadow:0 1px 0 rgba(255,243,190,.7),0 2px 5px rgba(80,40,0,.22);white-space:nowrap}.cf-face-brand{position:absolute;z-index:4;left:50%;top:67%;transform:translateX(-50%);font-size:.42rem;font-weight:1000;letter-spacing:.25em;color:#7b4a0a;white-space:nowrap}.cf-face--t .cf-face-word{font-size:1.42rem;letter-spacing:.045em}.cf-face svg{position:relative;z-index:2;width:84%;height:84%;overflow:visible}
        .cf-ring{fill:none;stroke:rgba(103,56,5,.72);stroke-width:1.4}.cf-arc{fill:#744306;font:900 5.2px ui-sans-serif,system-ui,sans-serif;letter-spacing:1px}.cf-emblem{fill:#734306;filter:drop-shadow(0 1px 0 rgba(255,241,181,.55))}
        .cf-result{position:absolute;left:50%;bottom:.95rem;z-index:5;min-width:15rem;transform:translateX(-50%);padding:.72rem 1rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(5,8,11,.78);backdrop-filter:blur(15px);text-align:center;box-shadow:0 16px 34px rgba(0,0,0,.28)}
        .cf-result p{font-size:.58rem;font-weight:950;letter-spacing:.17em;text-transform:uppercase;color:#7f8998}.cf-result strong{display:block;margin-top:.17rem;font-size:1rem;font-weight:1000}.cf-result strong.is-win{color:#77efb4}.cf-result strong.is-loss{color:#ff9299}.cf-result small{display:block;margin-top:.18rem;font-size:.68rem;font-weight:850;color:#ccd2db}
        .cf-stage.is-win .cf-coin{filter:drop-shadow(0 0 32px rgba(244,196,95,.28)) drop-shadow(0 26px 24px rgba(0,0,0,.42))}
        .cf-stage.is-loss .cf-coin{filter:drop-shadow(0 0 22px rgba(244,90,101,.12)) drop-shadow(0 26px 24px rgba(0,0,0,.42))}
        .cf-spark{position:absolute;left:50%;top:46%;width:.4rem;height:.4rem;border-radius:50%;background:#f8db89;box-shadow:0 0 18px rgba(244,196,95,.9);opacity:0;pointer-events:none}
        .cf-spark.is-on{animation:cfSpark 1050ms cubic-bezier(.16,.86,.2,1) forwards;animation-delay:calc(var(--i) * -18ms)}
        .cf-float{position:absolute;left:50%;top:33%;z-index:6;transform:translateX(-50%);font-size:1.45rem;font-weight:1000;color:#f7d77e;text-shadow:0 0 22px rgba(244,196,95,.36);animation:cfFloat 1.35s ease-out forwards}
        .cf-panel{padding:1rem;border:1px solid rgba(255,255,255,.07);border-radius:1.25rem;background:rgba(7,10,14,.76);box-shadow:0 17px 42px rgba(0,0,0,.13);backdrop-filter:blur(8px)}
        .cf-panel+.cf-panel{margin-top:.85rem}
        .cf-choice-head{display:flex;justify-content:space-between;align-items:flex-end;gap:1rem}.cf-choice-head>div:first-child strong{display:block;margin-top:.25rem;font-size:1.18rem;font-weight:1000;color:#f1f4f8}.cf-choice-head>div:last-child span{display:block;margin-top:.15rem;font-size:.58rem;font-weight:800;color:#74808f}.cf-gold{display:block;font-size:.95rem;color:#f5d17a}
        .cf-sides{display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-top:.9rem}.cf-sides button{position:relative;display:flex;align-items:center;gap:.7rem;padding:.82rem;border:1px solid rgba(255,255,255,.08);border-radius:.95rem;background:rgba(255,255,255,.025);color:#d4dae2;text-align:left;transition:transform .2s ease,border-color .2s ease,background .2s ease,box-shadow .2s ease;cursor:pointer}.cf-sides button:hover:not(:disabled){transform:translateY(-2px);border-color:rgba(244,196,95,.32);background:rgba(244,196,95,.045)}.cf-sides button.is-active{border-color:rgba(244,196,95,.72);background:linear-gradient(145deg,rgba(244,196,95,.14),rgba(244,196,95,.035));box-shadow:0 0 0 1px rgba(244,196,95,.1),0 12px 24px rgba(0,0,0,.16)}.cf-sides button:disabled{opacity:.56;cursor:not-allowed}.cf-sides svg{width:2.35rem;height:2.35rem;flex:none;fill:#e8bd5b;filter:drop-shadow(0 2px 6px rgba(244,196,95,.18))}.cf-sides span{min-width:0}.cf-sides b{display:block;font-size:.8rem;font-weight:950;color:#eef2f7}.cf-sides small{display:block;margin-top:.12rem;font-size:.59rem;color:#788492}
        .cf-label{display:block;margin:1rem 0 .55rem;font-size:.63rem;font-weight:950;letter-spacing:.15em;text-transform:uppercase;color:#8c96a4}
        .cf-stepper{display:grid;grid-template-columns:2.6rem minmax(5rem,1fr) 2.6rem;border:1px solid rgba(255,255,255,.08);border-radius:.9rem;overflow:hidden;background:#0b0f14}.cf-stepper button{border:0;background:rgba(255,255,255,.025);color:#dfe5ed;font-size:1.1rem;cursor:pointer;transition:background .18s ease}.cf-stepper button:hover:not(:disabled){background:rgba(244,196,95,.09)}.cf-stepper button:disabled{opacity:.36;cursor:not-allowed}.cf-input{width:100%;border:0;border-inline:1px solid rgba(255,255,255,.07);background:transparent;padding:.72rem .35rem;text-align:center;color:#fff;font-size:1rem;font-weight:950;outline:none}
        .cf-chips{display:flex;flex-wrap:wrap;gap:.38rem;margin-top:.6rem}.cf-chips button{padding:.42rem .64rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(255,255,255,.025);color:#aeb7c3;font-size:.65rem;font-weight:900;transition:.18s ease;cursor:pointer}.cf-chips button:hover:not(:disabled){transform:translateY(-1px);border-color:rgba(244,196,95,.3);color:#f4cf78;background:rgba(244,196,95,.055)}.cf-chips button:disabled{opacity:.38;cursor:not-allowed}
        .cf-command-bar{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;margin:.72rem 0 0;padding:.55rem .2rem;border:1px solid rgba(255,255,255,.055);border-radius:.9rem;background:rgba(255,255,255,.015);overflow:hidden}
        .cf-command-bar>div{min-width:0;padding:.25rem .65rem;border-right:1px solid rgba(255,255,255,.055)}
        .cf-command-bar>div:last-child{border-right:0}
        .cf-command-bar span{display:block;font-size:.48rem;font-weight:950;letter-spacing:.14em;color:#646e7c}
        .cf-command-bar strong{display:block;margin-top:.18rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.69rem;font-weight:1000;color:#eef2f5}
        .cf-command-bar__hot{background:linear-gradient(180deg,rgba(244,196,95,.055),transparent)}
        .cf-command-bar__hot strong{color:#f2ce76}
        .cf-error{margin-top:.55rem;font-size:.66rem;font-weight:800;color:#ff8f98}
        .cf-payout{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-top:.82rem;padding:.72rem .8rem;border:1px solid rgba(244,196,95,.11);border-radius:.8rem;background:rgba(244,196,95,.035)}.cf-payout strong{display:block;font-size:1.02rem;font-weight:1000;color:#f5d17a}.cf-payout span{display:block;margin-top:.12rem;font-size:.58rem;color:#707b89}
        .cf-play{position:relative;overflow:hidden;width:100%;min-height:3.2rem;margin-top:.82rem;border:0;border-radius:1rem;background:linear-gradient(135deg,#f6d888,#c78a24 55%,#8d590f);color:#2e2108;font-size:.82rem;font-weight:1000;letter-spacing:.065em;text-transform:uppercase;box-shadow:0 13px 29px rgba(178,118,24,.24),inset 0 1px 0 rgba(255,255,255,.48);transition:transform .2s ease,filter .2s ease,box-shadow .2s ease;cursor:pointer}.cf-play::after{content:"";position:absolute;inset:0;width:34%;transform:translateX(-180%) skewX(-18deg);background:linear-gradient(90deg,transparent,rgba(255,255,255,.38),transparent);animation:cfShine 3.2s ease-in-out infinite}.cf-play:hover:not(:disabled){transform:translateY(-2px);filter:brightness(1.035);box-shadow:0 18px 34px rgba(178,118,24,.28),inset 0 1px 0 rgba(255,255,255,.52)}.cf-play:disabled{opacity:.5;cursor:not-allowed}.cf-play span,.cf-play small{position:relative;z-index:1;display:block}.cf-play small{margin-top:.18rem;font-size:.55rem;font-weight:850;letter-spacing:.08em;text-transform:none;opacity:.68}
        .cf-hold{transition:transform .25s ease,filter .25s ease,opacity .25s ease}.cf-hold.is-hold{filter:brightness(.82);transform:translateY(1px)}.cf-statline{display:flex;justify-content:space-between;align-items:end;gap:1rem;margin-top:.4rem}.cf-statline strong{font-size:1rem;font-weight:1000;color:#edf1f5}.cf-statline span{font-size:.61rem;color:#7d8794}.cf-meter{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);height:.45rem;margin-top:.72rem;overflow:hidden;border-radius:999px;background:#1a2028}.cf-meter .is-h{background:linear-gradient(90deg,#f4c45f,#c9821d);transition:width .45s ease}.cf-meter .is-t{background:linear-gradient(90deg,#b54d57,#e37c82);transition:width .45s ease}.cf-meter-labels{display:flex;justify-content:space-between;margin-top:.4rem;font-size:.59rem;font-weight:850}.cf-meter-labels span:first-child{color:#f0c76d}.cf-meter-labels span:last-child{color:#eb8188}
        .cf-streak{display:flex;align-items:center;gap:.65rem;margin-top:.72rem;padding:.66rem .7rem;border:1px solid rgba(255,255,255,.06);border-radius:.78rem;background:rgba(255,255,255,.018)}.cf-orb{width:2rem;height:2rem;display:grid;place-items:center;flex:none;border-radius:50%;background:radial-gradient(circle at 30% 28%,#fff4c5,#eabb57 38%,#9d6115 100%);color:#6b4109;font-weight:1000;box-shadow:0 0 18px rgba(244,196,95,.12)}.cf-streak b{display:block;font-size:.67rem;color:#e4e9ef}.cf-streak>span:nth-child(2) small{display:block;margin-top:.08rem;font-size:.56rem;color:#6f7986}.cf-streak>small{font-size:.56rem;color:#687382}
        .cf-history{display:flex;flex-wrap:wrap;gap:.38rem;margin-top:.72rem}.cf-dot{width:1.9rem;height:1.9rem;display:grid;place-items:center;border-radius:50%;font-size:.63rem;font-weight:1000;border:1px solid rgba(255,255,255,.08);transition:transform .18s ease}.cf-dot:hover{transform:translateY(-2px)}.cf-dot.is-h{background:rgba(244,196,95,.1);color:#f2ca6c;border-color:rgba(244,196,95,.25)}.cf-dot.is-t{background:rgba(231,113,121,.1);color:#ef858d;border-color:rgba(231,113,121,.22)}
        .cf-panel>summary{list-style:none;cursor:pointer}.cf-panel>summary::-webkit-details-marker{display:none}.cf-panel>summary::after{content:"+";float:right;color:#727d8b;font-size:.95rem}.cf-panel[open]>summary::after{content:"−"}.cf-seed{width:100%;margin-top:.55rem;padding:.62rem .68rem;border:1px solid rgba(255,255,255,.08);border-radius:.72rem;background:#0b0f14;color:#dce2e8;font:600 .66rem ui-monospace,SFMono-Regular,Menlo,monospace;outline:none}
        @keyframes cfGridDrift{0%{background-position:0 0;opacity:.18}50%{opacity:.3}100%{background-position:46px 46px;opacity:.18}}@keyframes cfNebulaOne{0%,100%{transform:translate3d(0,0,0) scale(1)}50%{transform:translate3d(35px,-18px,0) scale(1.13)}}@keyframes cfNebulaTwo{0%,100%{transform:translate3d(0,0,0) scale(1)}50%{transform:translate3d(-30px,16px,0) scale(1.12)}}@keyframes cfRaySpin{to{transform:rotate(360deg)}}@keyframes cfOrbitSpin{to{transform:translate(-50%,-50%) rotateX(68deg) rotateZ(346deg)}}@keyframes cfOrbitSpinReverse{to{transform:translate(-50%,-50%) rotateX(69deg) rotateZ(-343deg)}}@keyframes cfEnergyPulse{0%{opacity:0;transform:translate(-50%,-50%) scale(.55)}25%{opacity:.8}100%{opacity:0;transform:translate(-50%,-50%) scale(1.35)}}@keyframes cfStarTwinkle{from{opacity:.18;transform:scale(.75)}to{opacity:.9;transform:scale(1.15)}}@keyframes cfPlatformSpin{to{transform:rotate(360deg)}}@keyframes cfCorePulse{0%,100%{transform:translate(-50%,-50%) scale(.82);opacity:.45}50%{transform:translate(-50%,-50%) scale(1.12);opacity:1}}@keyframes cfPlatformShine{0%,100%{opacity:.15;transform:translate(-50%,-50%) scaleX(.55)}50%{opacity:1;transform:translate(-50%,-50%) scaleX(1.1)}}        @keyframes cfStatusPulse{from{transform:scale(.88);opacity:.55}to{transform:scale(1.18);opacity:1}}@keyframes cfFlareSpin{to{transform:scale(1) rotate(360deg)}}
        @keyframes cfLiveBadge{from{box-shadow:0 0 0 rgba(244,196,95,0)}to{box-shadow:0 0 26px rgba(244,196,95,.11)}}@keyframes cfBroadcastPulse{from{transform:scale(.8);opacity:.45}to{transform:scale(1.15);opacity:1}}@keyframes cfCircuitDrift{0%,100%{transform:rotate(-28deg) translate3d(0,0,0)}50%{transform:rotate(-24deg) translate3d(22px,-10px,0)}}@keyframes cfChipOrbit{0%{transform:rotate(var(--chip-angle)) translateX(10.5rem) rotate(calc(var(--chip-angle) * -1)) scale(.72);opacity:.05}12%{opacity:.3}50%{opacity:.18}88%{opacity:.3}100%{transform:rotate(calc(var(--chip-angle) + 360deg)) translateX(10.5rem) rotate(calc((var(--chip-angle) + 360deg) * -1)) scale(.72);opacity:.05}}
        @keyframes cfSweep{to{transform:rotate(360deg)}}@keyframes cfBlink{from{opacity:.55;transform:scale(.75)}to{opacity:1;transform:scale(1.12)}}@keyframes cfAura{0%,100%{transform:scale(.95);opacity:.7}50%{transform:scale(1.06);opacity:1}}@keyframes cfIdle{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}@keyframes cfCharge{from{transform:translateY(0) scale(.99)}to{transform:translateY(-2px) scale(1.015)}}@keyframes cfLand{0%{transform:translateY(-.15rem) scale(.98)}45%{transform:translateY(.38rem) scale(1.035)}100%{transform:translateY(0) scale(1)}}@keyframes cfLandShadow{0%{opacity:.55;transform:scaleX(.65)}100%{opacity:1;transform:scaleX(1.28)}}@keyframes cfSpark{0%{opacity:0;transform:rotate(calc(var(--i)*25.7deg)) translateY(0) scale(.3)}12%{opacity:1}100%{opacity:0;transform:rotate(calc(var(--i)*25.7deg)) translateY(150px) scale(.08)}}@keyframes cfFloat{0%{opacity:0;transform:translate(-50%,8px) scale(.6)}18%{opacity:1;transform:translate(-50%,0) scale(1.05)}100%{opacity:0;transform:translate(-50%,-62px) scale(.9)}}@keyframes cfShine{0%,55%,100%{transform:translateX(-180%) skewX(-18deg)}72%{transform:translateX(350%) skewX(-18deg)}}
        @media(max-width:1024px){.cf-layout{grid-template-columns:1fr}.cf-stage{min-height:33rem}}
        @media(max-width:640px){.cf-top{align-items:flex-start}.cf-status{max-width:45%;white-space:normal;text-align:right}.cf-broadcast{margin-inline:.8rem}.cf-command-bar{grid-template-columns:repeat(2,minmax(0,1fr))}.cf-command-bar>div:nth-child(2){border-right:0}.cf-command-bar>div:nth-child(-n+2){border-bottom:1px solid rgba(255,255,255,.055)}.cf-stage{min-height:30rem;border-radius:1.25rem}.cf-scene{min-height:23rem}.cf-toss{width:12rem;height:12rem}.cf-coin{width:9.3rem;height:9.3rem}.cf-face-word{font-size:1.4rem}.cf-face--t .cf-face-word{font-size:1.2rem}.cf-face-brand{font-size:.36rem}.cf-sides{grid-template-columns:1fr}.cf-result{min-width:12.5rem}.cf-aura{width:18rem;height:18rem}.cf-floor{width:14rem}.cf-shadow{width:8rem}.cf-platform{width:13rem;bottom:2.8rem}.cf-orbit--one{width:19rem}.cf-orbit--two{width:15rem}.cf-rays{width:25rem;height:25rem}.cf-space-grid{background-size:34px 34px}.cf-payout{align-items:flex-start}}
        @media(prefers-reduced-motion:reduce){.cf-live-status__head i,.cf-spin-flare,.cf-status.is-live,.cf-broadcast i,.cf-circuit,.cf-floating-chip{animation:none!important}.cf-countdown span{transition:none!important}.cf-stage::before,.cf-status i,.cf-aura,.cf-toss,.cf-spark.is-on,.cf-float,.cf-play::after,.cf-space-grid,.cf-nebula,.cf-rays,.cf-orbit,.cf-energy-ring,.cf-star,.cf-platform__ring,.cf-platform__core,.cf-platform__shine{animation:none!important}.cf-coin{transition:none!important}.cf-platform{transition:none}.cf-shadow.is-land{animation:none}}
    </style>

    <div class="cf-layout">
        <section class="cf-stage" :class="{ 'is-win': resultVisible && won, 'is-loss': resultVisible && !won }">
            <header class="cf-top">
                <div>
                    <p class="cf-eyebrow">Jogo 01 · Original</p>
                    <h2 class="cf-title">Coinflip</h2>
                </div>
                <span class="cf-status" :class="{ 'is-live': busy }"><i></i><span x-text="statusText"></span></span>
            </header>
            <div class="cf-broadcast" aria-label="Informação da mesa">
                <span><i></i> LIVE TABLE</span>
                <span>ALLINBET · COINFLIP</span>
                <span>50 / 50</span>
                <span>{{ number_format($houseEdge / 100, 2, ',', '') }}% vantagem da casa</span>
            </div>

            <div class="cf-scene" aria-live="polite">
                <div class="cf-live-status" x-show="busy" x-cloak>
                    <div class="cf-live-status__head"><span><i></i><b x-text="tossing ? 'A MOEDA ESTÁ A RODAR' : (phase === 'result' ? 'A DISTRIBUIR O PRÉMIO' : 'A PREPARAR A RONDA')"></b></span><strong x-text="countdownText"></strong></div>
                    <p x-text="phase === 'result' ? ('Prémio confirmado · +' + Number(payout).toLocaleString('pt-PT') + ' créditos virtuais') : (backendReady ? 'Resultado calculado · a moeda está a parar…' : 'Resultado oculto · aguarda o lançamento terminar')"></p>
                    <div class="cf-countdown"><span :style="'width:' + countdownPct + '%'"></span></div>
                    <small x-text="countdown > 0 ? 'Para em ' + countdownText : 'A revelar resultado…'"></small>
                </div>
                <div class="cf-space-grid" aria-hidden="true"></div>
                <div class="cf-circuit cf-circuit--one" aria-hidden="true"></div>
                <div class="cf-circuit cf-circuit--two" aria-hidden="true"></div>
                <div class="cf-nebula cf-nebula--one" aria-hidden="true"></div>
                <div class="cf-nebula cf-nebula--two" aria-hidden="true"></div>
                <div class="cf-rays" aria-hidden="true"></div>
                <div class="cf-orbit cf-orbit--one" aria-hidden="true"></div>
                <div class="cf-orbit cf-orbit--two" aria-hidden="true"></div>
                <div class="cf-energy-ring cf-energy-ring--one" aria-hidden="true"></div>
                <div class="cf-energy-ring cf-energy-ring--two" aria-hidden="true"></div>
                @for ($i = 0; $i < 24; $i++)
                    <i class="cf-star" style="--i: {{ $i }}" aria-hidden="true"></i>
                @endfor
                @for ($i = 0; $i < 10; $i++)
                    <span class="cf-floating-chip" style="--i: {{ $i }}" aria-hidden="true">CR</span>
                @endfor
                <div class="cf-aura" aria-hidden="true"></div>
                <div class="cf-floor" aria-hidden="true"></div>
                <div class="cf-shadow" :class="{ 'is-toss': tossing, 'is-land': landed }" aria-hidden="true"></div>
                <div class="cf-platform" :class="{ 'is-tossing': tossing, 'is-landed': landed }" aria-hidden="true">
                    <span class="cf-platform__ring"></span>
                    <span class="cf-platform__core"></span>
                    <span class="cf-platform__shine"></span>
                </div>

                <div class="cf-toss" :class="{ 'is-toss': tossing, 'is-idle': !tossing && !busy, 'is-charge': charge, 'is-land': landed }">
                    <div class="cf-spin-flare" :class="{ 'is-active': tossing }" aria-hidden="true"><span></span><span></span><span></span></div>
                    <div class="cf-coin" :class="{ 'is-tossing': tossing, 'is-revealed': revealFace }" :style="'--dur:' + dur + 'ms; --rot:' + rot + 'deg'" role="img" :aria-label="tossing ? 'Moeda a rodar' : (outcome === 'heads' ? 'Moeda: Cara' : 'Moeda: Coroa')">
                        @for ($z = -5; $z <= 5; $z++)<i class="cf-slice" style="transform: translateZ({{ $z }}px)"></i>@endfor
                        <div class="cf-unknown" :class="{ 'is-hidden': revealFace }" aria-hidden="true">?</div>
                        <div class="cf-face cf-face--h">
                            <svg viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="46" class="cf-ring"/><circle cx="50" cy="50" r="31" class="cf-ring"/><text class="cf-arc"><textPath href="#cfArc">ALLINBET · CARA · ALLINBET · CARA ·</textPath></text></svg>
                            <strong class="cf-face-word">CARA</strong>
                            <span class="cf-face-brand">ALLINBET</span>
                        </div>
                        <div class="cf-face cf-face--t">
                            <svg viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="46" class="cf-ring"/><circle cx="50" cy="50" r="31" class="cf-ring"/><text class="cf-arc"><textPath href="#cfArc">ALLINBET · COROA · ALLINBET · COROA ·</textPath></text></svg>
                            <strong class="cf-face-word">COROA</strong>
                            <span class="cf-face-brand">ALLINBET</span>
                        </div>
                    </div>
                </div>

                @for ($i = 0; $i < 14; $i++)<i class="cf-spark" :class="{ 'is-on': burst }" style="--i: {{ $i }}" aria-hidden="true"></i>@endfor
                <span class="cf-float" x-show="burst" x-cloak x-text="'+' + Number(payout).toLocaleString('pt-PT')"></span>
            </div>

            <div class="cf-result" x-show="resultVisible" x-cloak x-transition.opacity>
                <p>Resultado confirmado</p>
                <strong :class="won ? 'is-win' : 'is-loss'"><span x-text="outcome === 'heads' ? 'CARA' : 'COROA'"></span> · <span x-text="won ? 'GANHOU' : 'NÃO GANHOU'"></span></strong>
                <small x-text="payout > 0 ? '+' + Number(shown).toLocaleString('pt-PT') + ' créditos virtuais' : '0 créditos virtuais'"></small>
            </div>
            <div class="cf-command-bar" aria-label="Estado da ronda">
                <div><span>ESCOLHA</span><strong x-text="label.toUpperCase()"></strong></div>
                <div><span>EM JOGO</span><strong x-text="Number($wire.bet || 0).toLocaleString('pt-PT') + ' CR'"></strong></div>
                <div><span>ESTADO</span><strong x-text="statusText"></strong></div>
                <div class="cf-command-bar__hot"><span>PAGAMENTO</span><strong>{{ number_format($multiplier, 2, '.', '') }}×</strong></div>
            </div>
        </section>

        <aside class="space-y-3">
            <div class="cf-panel">
                <div class="cf-choice-head">
                    <div><p class="cf-eyebrow">A tua escolha</p><strong x-text="label"></strong></div>
                    <div class="text-right"><strong class="cf-gold">{{ number_format($multiplier, 2, '.', '') }}×</strong><span>pagamento bruto</span></div>
                </div>

                <div class="cf-sides">
                    <button type="button" :class="{ 'is-active': $wire.side === 'heads' }" x-on:click="pick('heads')" :disabled="off"><svg viewBox="0 0 100 100"><use href="#cfH"/></svg><span><b>Cara</b><small>50% · tecla C</small></span></button>
                    <button type="button" :class="{ 'is-active': $wire.side === 'tails' }" x-on:click="pick('tails')" :disabled="off"><svg viewBox="0 0 100 100"><use href="#cfT"/></svg><span><b>Coroa</b><small>50% · tecla T</small></span></button>
                </div>

                <label class="cf-label">Aposta em créditos virtuais</label>
                <div class="cf-stepper">
                    <button type="button" x-on:click="step(-5)" :disabled="off" aria-label="Diminuir aposta">−</button>
                    <input type="number" min="1" step="1" max="{{ $maxBet }}" wire:model="bet" :disabled="off" class="cf-input" aria-label="Aposta em créditos virtuais">
                    <button type="button" x-on:click="step(5)" :disabled="off" aria-label="Aumentar aposta">+</button>
                </div>
                <div class="cf-chips">
                    @foreach ([25, 50, 100, 250, 500, 1000] as $chip)
                        @if ($chip <= $maxBet)<button type="button" x-on:click="$wire.bet = {{ $chip }}" :disabled="off">{{ $chip }}</button>@endif
                    @endforeach
                    <button type="button" x-on:click="$wire.bet = {{ $maxBet }}" :disabled="off">ALL IN · {{ number_format($maxBet, 0, ',', ' ') }}</button>
                </div>
                @error('bet')<p class="cf-error" role="alert">{{ $message }}</p>@enderror

                <div class="cf-payout"><div><strong x-text="'+' + potential.toLocaleString('pt-PT')"></strong><span>se acertares</span></div><div class="text-right"><strong x-text="Number($wire.bet || 0).toLocaleString('pt-PT')"></strong><span>em jogo</span></div></div>

                <button type="button" class="cf-play" x-on:click="play()" :disabled="off">
                    <span x-text="tossing ? 'A RODAR… ' + countdownText : (busy ? 'A preparar…' : 'Lançar moeda')"></span>
                    <small x-text="tossing ? 'Não mexas nas opções até a moeda parar' : 'ou barra de espaço'"></small>
                </button>
                @error('game')<p class="cf-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="cf-panel cf-hold" :class="{ 'is-hold': busy }">
                <p class="cf-eyebrow">Ritmo recente</p>
                <div class="cf-statline"><strong>Últimas {{ $historyTotal }} rondas</strong><span>{{ $winsCount }} vitória(s)</span></div>
                <div class="cf-meter" aria-label="Distribuição das últimas rondas"><span class="is-h" style="width: {{ $headsPercent }}%"></span><span class="is-t" style="width: {{ $tailsPercent }}%"></span></div>
                <div class="cf-meter-labels"><span>Cara {{ $headsPercent }}%</span><span>Coroa {{ $tailsPercent }}%</span></div>
                <div class="cf-streak">
                    <span class="cf-orb">{{ $streakSide === 'tails' ? 'T' : 'C' }}</span>
                    <span><b>{{ $currentStreak > 0 ? $currentStreak.' seguida(s)' : 'Sem sequência' }}</b><small>{{ $currentStreak > 0 ? (($streakSide === 'tails' ? 'Coroa' : 'Cara').' na sequência atual') : 'Nenhuma sequência ainda' }}</small></span>
                    <small class="ml-auto">50/50 teórico</small>
                </div>
                <div class="cf-history" aria-label="Últimas rondas">
                    @forelse (array_slice($recentFlips, 0, 12) as $flip)
                        <span class="cf-dot {{ $flip['outcome'] === 'heads' ? 'is-h' : 'is-t' }}" title="{{ $flip['outcome'] === 'heads' ? 'Cara' : 'Coroa' }} · {{ $flip['won'] ? 'Vitória' : 'Sem prémio' }} · {{ $flip['time'] }}">{{ $flip['outcome'] === 'heads' ? 'C' : 'T' }}</span>
                    @empty
                        <span class="text-xs text-zinc-500">As tuas últimas jogadas aparecem aqui.</span>
                    @endforelse
                </div>
            </div>

            <details class="cf-panel">
                <summary class="cf-eyebrow cursor-pointer">Jogo transparente</summary>
                <p class="mt-3 text-xs leading-5 text-zinc-500">O hash do servidor é fixado antes do resultado. Depois da ronda, o resultado pode ser verificado.</p>
                @if ($serverSeedHash)<p class="mt-3 break-all font-mono text-[0.68rem] leading-5 text-zinc-300">{{ $serverSeedHash }}</p>@endif
                <label class="mt-3 block text-[0.66rem] font-bold text-zinc-500">Seed do cliente
                    <input type="text" maxlength="128" wire:model="clientSeed" :disabled="off" class="cf-seed">
                </label>
            </details>

            @if ($roundPhase === 'completed' && $roundId)
                <button type="button" class="w-full px-3 py-2 text-xs font-bold text-emerald-300 hover:text-emerald-200" x-on:click="$dispatch('casino-open-fairness', { roundId: {{ $roundId }} })">Verificar esta ronda</button>
            @endif

            <p class="px-1 text-center text-[0.68rem] text-zinc-600">Créditos virtuais — sem valor monetário</p>
        </aside>
    </div>
</div>
