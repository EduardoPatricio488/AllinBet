<div class="jetx-page"
     x-data="{
        busy: false,
        flying: false,
        phase: @js($roundPhase),
        status: @js($roundPhase === 'in_progress' ? 'paused' : 'ready'),
        multiplier: 1,
        serverMultiplier: 1,
        displayMultiplier: 1,
        flightStartedAt: 0,
        lastServerSyncAt: 0,
        finalMultiplier: 0,
        payout: 0,
        resultOpen: false,
        resultLabel: '',
        pollTimer: null,
        raf: null,

        tone(kind) {
            const Audio = window.AudioContext || window.webkitAudioContext;
            if (!Audio) return;

            const ctx = new Audio();
            const gain = ctx.createGain();
            const osc = ctx.createOscillator();

            osc.connect(gain);
            gain.connect(ctx.destination);

            const now = ctx.currentTime;
            const volume = kind === 'crash' ? 0.14 : 0.09;
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(volume, now + 0.025);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + (kind === 'cashout' ? 0.6 : 0.8));

            if (kind === 'launch') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(180, now);
                osc.frequency.exponentialRampToValueAtTime(520, now + 0.55);
            } else if (kind === 'cashout') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(620, now);
                osc.frequency.exponentialRampToValueAtTime(990, now + 0.28);
            } else {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(240, now);
                osc.frequency.exponentialRampToValueAtTime(58, now + 0.75);
            }

            osc.start(now);
            osc.stop(now + (kind === 'cashout' ? 0.6 : 0.8));
        },

        resetFlight() {
            clearTimeout(this.pollTimer);
            cancelAnimationFrame(this.raf);
            this.pollTimer = null;
            this.raf = null;
        },

        animate() {
            if (!this.flying) return;

            const gap = this.serverMultiplier - this.displayMultiplier;

            if (Math.abs(gap) > 0.01) {
                this.displayMultiplier = Math.max(
                    1,
                    Math.min(2500, this.displayMultiplier + (gap * 0.18))
                );
            } else {
                this.displayMultiplier = this.serverMultiplier;
            }

            this.multiplier = this.displayMultiplier;
            this.raf = requestAnimationFrame(() => this.animate());
        },

        rocketStyle() {
            const progress = Math.min(
                1,
                Math.log(Math.max(1, this.displayMultiplier)) / Math.log(2500)
            );
            const x = 9 + progress * 79;
            const y = 78 - Math.pow(progress, 1.2) * 63;

            return `left: ${x}%; top: ${y}%; transform: translate(-50%, -50%) rotate(-18deg);`;
        },

        syncServerResult() {
            const w = this.$wire;
            const result = w.roundResult || {};
            const value = Number(
                result.multiplier
                ?? result.cashout_multiplier
                ?? result.crash_multiplier
                ?? 1
            );

            this.phase = w.roundPhase;

            if (Number.isFinite(value) && value >= 1) {
                this.serverMultiplier = Math.min(2500, value);
            }

            this.lastServerSyncAt = Date.now();

            if (w.roundPhase === 'completed') {
                this.finalize();
                return false;
            }

            return true;
        },

        async beginFlight(startedAtMs = Date.now()) {
            const w = this.$wire;
            const started = Number(startedAtMs || Date.now());

            if (this.flying && Math.abs(this.flightStartedAt - started) < 50) {
                return;
            }

            this.resetFlight();
            this.flightStartedAt = started;
            this.lastServerSyncAt = Date.now();
            this.phase = 'in_progress';
            this.flying = true;
            this.status = 'flying';
            this.busy = false;
            this.serverMultiplier = Math.max(1, Number(w.roundResult?.multiplier || 1));
            this.multiplier = 1;
            this.displayMultiplier = 1;
            this.resultOpen = false;
            this.resultLabel = '';
            this.payout = 0;
            this.tone('launch');
            this.animate();

            try {
                await w.tick();

                if (!this.syncServerResult()) {
                    return;
                }
            } catch (e) {
                // The next poll retries automatically.
            }

            this.schedulePoll();
        },

        startFlight() {
            this.busy = false;
            this.resultOpen = false;
            this.resultLabel = '';
            this.resetFlight();
        },

        schedulePoll() {
            if (!this.flying) return;

            clearTimeout(this.pollTimer);
            this.pollTimer = setTimeout(() => this.poll(), 350);
        },

        async poll() {
            if (!this.flying) return;

            const w = this.$wire;

            try {
                await w.tick();

                if (!this.syncServerResult()) {
                    return;
                }
            } catch (e) {
                // Keep polling through transient request failures.
            }

            this.schedulePoll();
        },

        async cashout() {
            if (!this.flying || this.busy) return;

            const w = this.$wire;
            this.busy = true;
            this.flying = false;
            this.resetFlight();
            this.status = 'cashing_out';

            try {
                await w.cashout();

                if (w.roundPhase === 'completed') {
                    this.finalize();
                    return;
                }

                this.busy = false;
                this.flying = true;
                this.status = 'flying';
                this.syncServerResult();
                this.animate();
                this.schedulePoll();
            } catch (e) {
                this.busy = false;

                if (w.roundPhase === 'in_progress') {
                    this.flying = true;
                    this.status = 'flying';
                    this.animate();
                    this.schedulePoll();
                }
            }
        },

        resetAfterRound() {
            this.resetFlight();
            this.flying = false;
            this.busy = false;
            this.phase = 'completed';
            this.status = 'ready';
            this.multiplier = 1;
            this.serverMultiplier = 1;
            this.displayMultiplier = 1;
            this.finalMultiplier = 0;
            this.payout = 0;
            this.resultOpen = false;
            this.resultLabel = '';
        },

        finalize() {
            const w = this.$wire;
            const result = w.roundResult || {};
            const completedStatus = result.status || (w.roundPayout > 0 ? 'cashed_out' : 'crashed');

            this.resetFlight();
            this.flying = false;
            this.phase = 'completed';
            this.busy = false;
            this.payout = Number(w.roundPayout || result.payout || 0);
            this.finalMultiplier = Number(
                result.cashout_multiplier
                || result.crash_multiplier
                || result.multiplier
                || this.serverMultiplier
                || 1
            );
            this.serverMultiplier = this.finalMultiplier;
            this.displayMultiplier = this.finalMultiplier;
            this.multiplier = this.finalMultiplier;

            if (completedStatus === 'cashed_out') {
                this.status = 'cashed_out';
                this.resultLabel = '🚀 COLETADO';
                this.tone('cashout');
                this.resultOpen = true;
            } else if (completedStatus === 'crashed') {
                this.status = 'crashed';
                this.resultLabel = '💥 CRASH';
                this.tone('crash');
                this.resultOpen = true;
            } else {
                this.status = 'ready';
                this.resultOpen = false;
            }
        },

        destroy() {
            this.resetFlight();
        }
     }"
     x-init="
        $watch('$wire.roundPhase', (value) => {
            phase = value;

            if (value === 'in_progress') {
                const result = $wire.roundResult || {};
                if (!flying) {
                    beginFlight(Number(result.started_at_ms || Date.now()));
                }
                return;
            }

            if (value === 'completed') {
                if (flying) {
                    finalize();
                }
                return;
            }

            if (value === 'ready' || value === 'prepared') {
                resetFlight();
                flying = false;
                busy = false;
                status = 'ready';
                multiplier = 1;
                serverMultiplier = 1;
                displayMultiplier = 1;
                finalMultiplier = 0;
                payout = 0;
                resultOpen = false;
                resultLabel = '';
            }
        })
     "
     x-on:keydown.window="if ($event.code === 'Space' && ['ready','completed','prepared'].includes(phase) && !['INPUT','TEXTAREA','BUTTON','SUMMARY'].includes($event.target.tagName)) { $event.preventDefault(); $wire.start(); }"
     x-on:jetx-flight-started.window="beginFlight($event.detail.startedAtMs)"
     x-on:pagehide.window="resetFlight()">    <style>
        .jetx-page{--jet-gold:#f5c451;--jet-gold-hi:#ffe7a1;--jet-cyan:#4dd9ff;--jet-red:#ff5e67;--jet-bg:#070b12;--jet-panel:#0b111a;--jet-text:#eff5fb;color:#e9eef4}

        .jetx-stars{position:absolute;inset:-20%;pointer-events:none;background-repeat:repeat;opacity:.28;mix-blend-mode:screen;will-change:transform}.jetx-stars--far{background-image:radial-gradient(circle,rgba(255,255,255,.65) 0 1px,transparent 1.5px),radial-gradient(circle,rgba(77,217,255,.4) 0 1px,transparent 1.5px);background-size:145px 145px,210px 210px;background-position:20px 35px,90px 110px}.jetx-stars--near{background-image:radial-gradient(circle,rgba(255,255,255,.8) 0 1.5px,transparent 2px),radial-gradient(circle,rgba(245,196,81,.55) 0 1px,transparent 1.5px);background-size:95px 95px,175px 175px;background-position:12px 18px,65px 92px;opacity:.18}.jetx-stage.flying .jetx-stars--far{animation:jetx-stars-far 11s linear infinite}.jetx-stage.flying .jetx-stars--near{animation:jetx-stars-near 4.5s linear infinite}.jetx-speedlines{position:absolute;inset:0;overflow:hidden;pointer-events:none;opacity:0}.jetx-stage.flying .jetx-speedlines{opacity:1}.jetx-speedlines span{position:absolute;left:-18%;width:34%;height:2px;border-radius:999px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.14),rgba(77,217,255,.72),transparent);filter:blur(.2px);transform:rotate(-19deg);animation:jetx-speed 1.15s linear infinite}.jetx-speedlines span:nth-child(1){top:18%;animation-delay:-.2s}.jetx-speedlines span:nth-child(2){top:28%;animation-delay:-.7s;width:25%}.jetx-speedlines span:nth-child(3){top:38%;animation-delay:-1s;width:40%}.jetx-speedlines span:nth-child(4){top:52%;animation-delay:-.35s;width:28%}.jetx-speedlines span:nth-child(5){top:64%;animation-delay:-.9s;width:44%}.jetx-speedlines span:nth-child(6){top:74%;animation-delay:-.1s;width:22%}.jetx-speedlines span:nth-child(7){top:13%;animation-delay:-.55s;width:19%}.jetx-speedlines span:nth-child(8){top:46%;animation-delay:-.75s;width:20%}.jetx-speedlines span:nth-child(9){top:83%;animation-delay:-.4s;width:31%}.jetx-speedlines span:nth-child(10){top:34%;animation-delay:-1.2s;width:18%}.jetx-orbit{position:absolute;left:50%;top:50%;border:1px solid rgba(77,217,255,.12);border-radius:50%;transform:translate(-50%,-50%) rotate(-17deg);pointer-events:none;opacity:0}.jetx-orbit--one{width:78%;height:38%}.jetx-orbit--two{width:62%;height:26%;border-color:rgba(245,196,81,.08)}.jetx-stage.flying .jetx-orbit--one{opacity:1;animation:jetx-orbit 7s ease-in-out infinite}.jetx-stage.flying .jetx-orbit--two{opacity:1;animation:jetx-orbit-rev 9s ease-in-out infinite}.jetx-boost-glow{position:absolute;left:6%;bottom:14%;width:34%;height:20%;background:radial-gradient(ellipse,rgba(77,217,255,.16),transparent 68%);filter:blur(18px);opacity:0;pointer-events:none}.jetx-stage.flying .jetx-boost-glow{opacity:1;animation:jetx-boost 1.1s ease-in-out infinite alternate}.jetx-stage.is-launching{animation:jetx-launch-stage .65s cubic-bezier(.2,.8,.2,1)}.jetx-stage.is-flying-fast .jetx-multiplier{animation:jetx-multiplier-pulse 1.5s ease-in-out infinite}.jetx-stage.is-crashed{animation:jetx-crash-shake .42s ease-out}.jetx-multiplier.is-growing{text-shadow:0 0 38px rgba(77,217,255,.28),0 0 70px rgba(77,217,255,.08)}.jetx-multiplier.is-final{animation:jetx-result-pulse .5s cubic-bezier(.16,1,.3,1)}.jetx-rocket{transition:filter .15s ease}.jetx-stage.flying .jetx-rocket{filter:drop-shadow(0 0 12px rgba(77,217,255,.15)) drop-shadow(0 16px 16px rgba(0,0,0,.42))}.jetx-rocket__particle{position:absolute;left:0;top:50%;width:7px;height:7px;border-radius:50%;background:#f5c451;filter:blur(1px);opacity:0;box-shadow:0 0 10px rgba(245,196,81,.55)}.jetx-rocket__particle--one{animation:jetx-particle-one .55s linear infinite}.jetx-rocket__particle--two{background:#4dd9ff;animation:jetx-particle-two .72s linear infinite -.18s}.jetx-rocket__particle--three{width:5px;height:5px;background:#ff6470;animation:jetx-particle-three .9s linear infinite -.4s}.jetx-shockwave{position:absolute;left:50%;top:48%;width:14rem;height:14rem;border:3px solid rgba(255,94,103,.55);border-radius:50%;transform:translate(-50%,-50%) scale(.2);box-shadow:0 0 70px rgba(255,94,103,.18),inset 0 0 40px rgba(255,94,103,.08);animation:jetx-shockwave .7s cubic-bezier(.15,.9,.3,1)}.jetx-result{animation:jetx-result-card .45s cubic-bezier(.16,1,.3,1)}
        .jetx-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1rem}.jetx-hero__title{display:flex;gap:.8rem;align-items:center}.jetx-mark{display:grid;place-items:center;width:3rem;height:3rem;border:1px solid rgba(77,217,255,.25);border-radius:1rem;background:linear-gradient(145deg,rgba(77,217,255,.14),rgba(245,196,81,.07));font-size:1.45rem;box-shadow:0 0 30px rgba(77,217,255,.08)}
        .jetx-eyebrow{margin:0;color:#748394;font-size:.62rem;font-weight:900;letter-spacing:.18em;text-transform:uppercase}.jetx-title{margin:.12rem 0 0;font-size:1.6rem;font-weight:950;letter-spacing:-.02em}.jetx-hint{margin:.2rem 0 0;color:#8996a5;font-size:.78rem}
        .jetx-layout{display:grid;grid-template-columns:minmax(0,1fr) 18rem;gap:1rem}.jetx-main{min-width:0}
        .jetx-stage{position:relative;min-height:36rem;overflow:hidden;border:1px solid rgba(77,217,255,.16);border-radius:1.5rem;background:radial-gradient(circle at 70% 15%,rgba(77,217,255,.09),transparent 28%),radial-gradient(circle at 40% 95%,rgba(245,196,81,.07),transparent 28%),linear-gradient(160deg,#0b101a,#060a11 62%,#080c12);box-shadow:0 30px 75px rgba(0,0,0,.35),inset 0 1px 0 rgba(255,255,255,.045)}
        .jetx-stage::before{content:"";position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.55) 0 1px,transparent 1.5px);background-size:115px 115px;opacity:.15;animation:jetx-stars 18s linear infinite}
        .jetx-stage::after{content:"";position:absolute;inset:auto -10% 0;height:46%;background:linear-gradient(180deg,transparent,rgba(77,217,255,.045));transform:skewY(-10deg);pointer-events:none}
        .jetx-grid{position:absolute;inset:15% 7% 13%;opacity:.3;background:linear-gradient(rgba(77,217,255,.11) 1px,transparent 1px),linear-gradient(90deg,rgba(77,217,255,.08) 1px,transparent 1px);background-size:12.5% 20%;mask-image:linear-gradient(180deg,transparent,#000 18%,#000 82%,transparent)}
        .jetx-trail{position:absolute;left:8%;bottom:17%;width:78%;height:2px;background:linear-gradient(90deg,transparent,rgba(77,217,255,.45),rgba(245,196,81,.65),transparent);box-shadow:0 0 18px rgba(77,217,255,.22);transform:rotate(-23deg);transform-origin:left center;opacity:.65}
        .jetx-trail::after{content:"";position:absolute;right:12%;top:-3px;width:24px;height:8px;border-radius:999px;background:rgba(255,255,255,.8);filter:blur(5px);animation:jetx-trail-pulse 1s ease-in-out infinite alternate}
        .jetx-status{position:absolute;top:1rem;left:1rem;z-index:5;padding:.45rem .7rem;border:1px solid rgba(255,255,255,.09);border-radius:999px;background:rgba(3,7,12,.72);backdrop-filter:blur(12px);font-size:.56rem;font-weight:950;letter-spacing:.14em;text-transform:uppercase;color:#9cacba}.jetx-status.flying{border-color:rgba(77,217,255,.3);color:var(--jet-cyan);box-shadow:0 0 25px rgba(77,217,255,.1)}.jetx-status.crashed{border-color:rgba(255,94,103,.35);color:#ff9a9f}.jetx-status.cashed{border-color:rgba(245,196,81,.35);color:var(--jet-gold-hi)}
        .jetx-multiplier{position:absolute;top:17%;left:50%;z-index:4;transform:translateX(-50%);font-size:clamp(4rem,10vw,7.4rem);font-weight:1000;line-height:.9;letter-spacing:-.06em;color:#f7fbff;text-shadow:0 0 35px rgba(77,217,255,.2)}.jetx-stage.flying .jetx-multiplier{color:#c6f5ff;text-shadow:0 0 40px rgba(77,217,255,.35)}.jetx-stage.crashed .jetx-multiplier{color:#ffb0b4;text-shadow:0 0 40px rgba(255,94,103,.28)}
        .jetx-rocket{position:absolute;left:0;top:0;z-index:6;width:76px;height:42px;will-change:left,top,transform;filter:drop-shadow(0 16px 14px rgba(0,0,0,.42))}.jetx-rocket__body{position:absolute;inset:5px 7px 5px 14px;border-radius:18px 25px 25px 18px;background:linear-gradient(180deg,#f9fbff 0%,#cfd9e3 48%,#8493a2 100%);border:1px solid rgba(255,255,255,.75);box-shadow:inset 0 2px 3px rgba(255,255,255,.55),inset 0 -5px 8px rgba(24,35,46,.28)}.jetx-rocket__nose{position:absolute;right:-12px;top:3px;width:0;height:0;border-top:13px solid transparent;border-bottom:13px solid transparent;border-left:18px solid #dbe4ec;filter:drop-shadow(2px 3px 2px rgba(0,0,0,.18))}.jetx-rocket__window{position:absolute;right:21px;top:8px;width:12px;height:12px;border-radius:50%;background:radial-gradient(circle at 35% 30%,#e6fbff 0 14%,#55ddff 22%,#19718e 68%,#0a2e3b 100%);border:2px solid #effcff;box-shadow:0 0 10px rgba(77,217,255,.55)}.jetx-rocket__stripe{position:absolute;left:10px;right:13px;bottom:7px;height:3px;border-radius:99px;background:linear-gradient(90deg,#ff6470,#f5c451,#4dd9ff)}.jetx-rocket__fin{position:absolute;z-index:-1;width:19px;height:16px;background:linear-gradient(160deg,#ef5f69,#8d2430);border:1px solid rgba(255,255,255,.18)}.jetx-rocket__fin--top{left:17px;top:0;clip-path:polygon(0 100%,100% 0,75% 100%)}.jetx-rocket__fin--bottom{left:17px;bottom:0;clip-path:polygon(0 0,100% 100%,75% 0)}.jetx-rocket__flame{position:absolute;left:0;top:13px;width:27px;height:16px;border-radius:100% 10% 10% 100%;background:linear-gradient(90deg,transparent,#4dd9ff 18%,#f5c451 48%,#ff7c42 72%,#ff5561 100%);filter:blur(1px);transform-origin:right center;animation:jetx-flame .09s ease-in-out infinite alternate;box-shadow:0 0 18px rgba(77,217,255,.28)}
        .jetx-explosion{position:absolute;left:50%;top:48%;z-index:10;transform:translate(-50%,-50%);font-size:clamp(5rem,12vw,9rem);animation:jetx-boom .45s cubic-bezier(.15,.9,.3,1.15);filter:drop-shadow(0 0 35px rgba(255,94,103,.55))}
        .jetx-result{position:absolute;left:50%;bottom:9%;z-index:12;transform:translateX(-50%);min-width:min(90%,25rem);padding:1rem 1.25rem;text-align:center;border:1px solid rgba(255,255,255,.1);border-radius:1.15rem;background:rgba(7,11,18,.86);box-shadow:0 20px 50px rgba(0,0,0,.35)}.jetx-result strong{display:block;color:#fff;font-size:.72rem;font-weight:950;letter-spacing:.18em}.jetx-result span{display:block;margin-top:.2rem;color:var(--jet-gold);font-size:1.35rem;font-weight:1000}.jetx-result small{display:block;margin-top:.2rem;color:#8593a3;font-size:.65rem}
        .jetx-controls{display:grid;grid-template-columns:1fr auto;gap:1rem;margin-top:1rem;padding:1rem;border:1px solid rgba(255,255,255,.08);border-radius:1.1rem;background:rgba(11,17,26,.86);box-shadow:0 18px 45px rgba(0,0,0,.18)}
        .jetx-bet{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}.jetx-field span{display:block;margin-bottom:.3rem;color:#7c8a99;font-size:.62rem;font-weight:850;letter-spacing:.08em;text-transform:uppercase}.jetx-input{width:100%;min-height:2.7rem;border:1px solid rgba(255,255,255,.09);border-radius:.7rem;background:#060b12;color:#fff;padding:.65rem .75rem;outline:0}.jetx-input:focus{border-color:rgba(77,217,255,.45);box-shadow:0 0 0 3px rgba(77,217,255,.08)}
        .jetx-action-wrap{display:flex;align-items:center;justify-content:flex-end;min-width:12rem}.jetx-action-wrap .jetx-action{width:100%}
        .jetx-action{min-width:12rem;min-height:2.8rem;border:1px solid rgba(245,196,81,.55);border-radius:.8rem;background:linear-gradient(180deg,#ffeaa9,#e3ad37 55%,#98630e);color:#251a06;font-size:.82rem;font-weight:1000;letter-spacing:.1em;text-transform:uppercase;box-shadow:0 4px 0 #694609,0 12px 28px rgba(177,116,20,.22);cursor:pointer;transition:transform .08s,filter .15s}.jetx-action:hover:not(:disabled){filter:brightness(1.06)}.jetx-action:active:not(:disabled){transform:translateY(3px);box-shadow:0 1px 0 #694609,0 7px 18px rgba(177,116,20,.22)}.jetx-action.collect{border-color:rgba(77,217,255,.55);background:linear-gradient(180deg,#bff7ff,#3dcde9 55%,#087e9e);color:#04222a;box-shadow:0 4px 0 #04576c,0 12px 30px rgba(61,205,233,.18)}.jetx-action:disabled{opacity:.55;cursor:not-allowed}
        .jetx-mini{margin-top:.65rem;color:#697888;font-size:.62rem;line-height:1.45}
        .jetx-side{display:grid;gap:.8rem;align-content:start}.jetx-card{border:1px solid rgba(255,255,255,.08);border-radius:1rem;background:rgba(11,17,26,.78);padding:1rem;box-shadow:0 16px 38px rgba(0,0,0,.15)}.jetx-card__eyebrow{margin:0;color:#6e7d8c;font-size:.58rem;font-weight:950;letter-spacing:.16em;text-transform:uppercase}.jetx-card h3{margin:.35rem 0 0;color:#f4f7fa;font-size:.92rem;font-weight:900}.jetx-card p{margin:.4rem 0 0;color:#8996a4;font-size:.68rem;line-height:1.55}.jetx-stat{display:flex;justify-content:space-between;gap:1rem;padding:.65rem 0;border-bottom:1px solid rgba(255,255,255,.055);font-size:.68rem}.jetx-stat:last-child{border-bottom:0}.jetx-stat span{color:#738292}.jetx-stat strong{color:#dfe8ef}.jetx-seed{margin-top:.55rem;padding:.6rem;border:1px solid rgba(255,255,255,.06);border-radius:.65rem;background:#060a10;color:#93a1af;font: .56rem/1.4 ui-monospace,SFMono-Regular,Menlo,monospace;word-break:break-all}
        @keyframes jetx-stars{from{transform:translate3d(0,0,0)}to{transform:translate3d(-120px,80px,0)}}@keyframes jetx-flame{from{transform:scaleX(.85);opacity:.55}to{transform:scaleX(1.15);opacity:1}}@keyframes jetx-trail-pulse{from{opacity:.35;transform:translateX(-8px)}to{opacity:.9;transform:translateX(10px)}}@keyframes jetx-boom{from{opacity:0;transform:translate(-50%,-50%) scale(.45)}65%{transform:translate(-50%,-50%) scale(1.12)}to{opacity:1;transform:translate(-50%,-50%) scale(1)}}        
        @media(max-width:900px){.jetx-layout{grid-template-columns:1fr}.jetx-side{grid-template-columns:repeat(2,minmax(0,1fr))}.jetx-controls{grid-template-columns:1fr}.jetx-action-wrap{width:100%}.jetx-action{width:100%}}@media(max-width:600px){.jetx-hero{align-items:flex-start;flex-direction:column}.jetx-stage{min-height:30rem}.jetx-bet{grid-template-columns:1fr}.jetx-side{grid-template-columns:1fr}.jetx-multiplier{top:19%}.jetx-result{bottom:6%}}
        @media(prefers-reduced-motion:reduce){.jetx-stage::before,.jetx-rocket__flame,.jetx-result{animation:none}.jetx-rocket,.jetx-explosion,.jetx-result{transition:none}}
    
        @keyframes jetx-stars-far{from{transform:translate3d(0,0,0) scale(1)}to{transform:translate3d(-130px,85px,0) scale(1.03)}}@keyframes jetx-stars-near{from{transform:translate3d(0,0,0)}to{transform:translate3d(-260px,165px,0)}}@keyframes jetx-speed{from{transform:translate3d(-30vw,0,0) rotate(-19deg);opacity:0}18%{opacity:.9}100%{transform:translate3d(150vw,0,0) rotate(-19deg);opacity:0}}@keyframes jetx-orbit{0%,100%{transform:translate(-50%,-50%) rotate(-17deg) scale(1)}50%{transform:translate(-50%,-50%) rotate(-9deg) scale(1.05)}}@keyframes jetx-orbit-rev{0%,100%{transform:translate(-50%,-50%) rotate(17deg) scale(1)}50%{transform:translate(-50%,-50%) rotate(9deg) scale(.96)}}@keyframes jetx-boost{from{transform:translateX(-6px) scale(.96);opacity:.4}to{transform:translateX(28px) scale(1.05);opacity:.8}}@keyframes jetx-launch-stage{0%{transform:scale(.985);filter:brightness(.9)}45%{transform:scale(1.012);filter:brightness(1.14)}100%{transform:scale(1);filter:brightness(1)}}@keyframes jetx-multiplier-pulse{0%,100%{transform:translateX(-50%) scale(1)}50%{transform:translateX(-50%) scale(1.025)}}@keyframes jetx-crash-shake{0%,100%{transform:translate3d(0,0,0)}18%{transform:translate3d(-9px,4px,0) rotate(-.25deg)}36%{transform:translate3d(8px,-3px,0) rotate(.25deg)}54%{transform:translate3d(-6px,2px,0)}72%{transform:translate3d(4px,-1px,0)}90%{transform:translate3d(-2px,0,0)}}@keyframes jetx-result-pulse{0%{transform:translateX(-50%) scale(.92);opacity:.7}70%{transform:translateX(-50%) scale(1.04)}100%{transform:translateX(-50%) scale(1)}}@keyframes jetx-result-card{from{opacity:0;transform:translateX(-50%) translateY(18px) scale(.96)}to{opacity:1;transform:translateX(-50%) translateY(0) scale(1)}}@keyframes jetx-shockwave{0%{opacity:.8;transform:translate(-50%,-50%) scale(.2)}70%{opacity:.25;transform:translate(-50%,-50%) scale(1.25)}100%{opacity:0;transform:translate(-50%,-50%) scale(1.55)}}@keyframes jetx-particle-one{0%{transform:translate(0,-1px) scale(.4);opacity:0}25%{opacity:.9}100%{transform:translate(-42px,-14px) scale(1.1);opacity:0}}@keyframes jetx-particle-two{0%{transform:translate(0,2px) scale(.4);opacity:0}25%{opacity:.8}100%{transform:translate(-50px,8px) scale(.8);opacity:0}}@keyframes jetx-particle-three{0%{transform:translate(0,0) scale(.3);opacity:0}30%{opacity:.8}100%{transform:translate(-62px,18px) scale(.7);opacity:0}}
</style>

    <x-casino.loading-overlay target="prepare,launch,start,cashout" />


    <div class="jetx-hero">
        <div class="jetx-hero__title">
            <span class="jetx-mark" aria-hidden="true">🚀</span>
            <div>
                <p class="jetx-eyebrow">ALLINBET · ORIGINAL</p>
                <h1 class="jetx-title">JetX</h1>
                <p class="jetx-hint">Acompanha o foguete, vê o multiplicador subir e recolhe antes do crash.</p>
            </div>
        </div>
        <span class="jetx-status" :class="{
            flying: status === 'flying',
            crashed: status === 'crashed',
            cashed: status === 'cashed_out'
        }" x-text="status === 'flying' ? 'Foguete em voo' : status === 'paused' ? 'Ronda em espera' : status === 'crashed' ? 'Crash' : status === 'cashed_out' ? 'Prémio recolhido' : status === 'cashing_out' ? 'A recolher…' : 'Pronto'"></span>
    </div>

    <x-casino.how-it-works
        game-key="jetx"
        title="Como funciona o JetX?"
        description="O foguete sobe e o multiplicador aumenta. Recolhe os créditos antes do crash para fechar a ronda com o multiplicador atingido."
        :rules="[
            ['title' => 'Escolhe a aposta', 'text' => 'Define quantos créditos virtuais queres colocar na ronda.'],
            ['title' => 'Lança o foguete', 'text' => 'O multiplicador começa em 1.00× e cresce enquanto o voo continua.'],
            ['title' => 'Recolhe quando quiseres', 'text' => 'Carrega em COLETAR para fixar o multiplicador disponível nesse momento.'],
            ['title' => 'Se houver crash', 'text' => 'Se o crash acontecer antes da recolha, a aposta da ronda termina sem prémio.'],
        ]"
        badge="Créditos virtuais"
    />

    <div class="jetx-layout">
        <main class="jetx-main">
            <section class="jetx-stage"
                     :class="status"
                     :class="{ 'is-launching': status === 'flying' && displayMultiplier <= 1.08, 'is-flying-fast': status === 'flying' && displayMultiplier >= 2, 'is-crashed': status === 'crashed' }"
                     aria-label="JetX">
                <div class="jetx-stars jetx-stars--far" aria-hidden="true"></div>
                <div class="jetx-stars jetx-stars--near" aria-hidden="true"></div>
                <div class="jetx-speedlines" aria-hidden="true">
                    <span></span><span></span><span></span><span></span><span></span><span></span>
                    <span></span><span></span><span></span><span></span>
                </div>
                <div class="jetx-orbit jetx-orbit--one" aria-hidden="true"></div>
                <div class="jetx-orbit jetx-orbit--two" aria-hidden="true"></div>
                <div class="jetx-grid" aria-hidden="true"></div>
                <div class="jetx-trail" aria-hidden="true"></div>
                <div class="jetx-boost-glow" aria-hidden="true"></div>

                <div class="jetx-multiplier" :class="{ 'is-growing': flying, 'is-final': resultOpen }">
                    <span x-text="Number(displayMultiplier).toFixed(2) + '×'"></span>
                </div>

                <div class="jetx-rocket" :style="rocketStyle()" x-show="flying" x-cloak aria-hidden="true">
                    <span class="jetx-rocket__flame"></span>
                    <span class="jetx-rocket__particle jetx-rocket__particle--one"></span>
                    <span class="jetx-rocket__particle jetx-rocket__particle--two"></span>
                    <span class="jetx-rocket__particle jetx-rocket__particle--three"></span>
                    <span class="jetx-rocket__fin jetx-rocket__fin--top"></span>
                    <span class="jetx-rocket__body">
                        <span class="jetx-rocket__nose"></span>
                        <span class="jetx-rocket__window"></span>
                        <span class="jetx-rocket__stripe"></span>
                    </span>
                    <span class="jetx-rocket__fin jetx-rocket__fin--bottom"></span>
                </div>
                <div class="jetx-shockwave" x-show="status === 'crashed' && resultOpen" x-cloak aria-hidden="true"></div>
                <div class="jetx-explosion" x-show="status === 'crashed' && resultOpen" x-cloak aria-hidden="true">💥</div>

                <div class="jetx-result" x-show="resultOpen" x-cloak>
                    <strong x-text="resultLabel"></strong>
                    <span x-text="Number(finalMultiplier).toFixed(2) + '×'"></span>
                    <small x-text="status === 'cashed_out' ? '+' + Number(payout).toLocaleString('pt-PT') + ' créditos virtuais' : 'Aposta perdida nesta ronda'"></small>
                </div>
            </section>

            <section class="jetx-controls">
                <div>
                    @if (in_array($roundPhase, ['ready', 'completed'], true))
                        <div class="jetx-bet">
                            <label class="jetx-field">
                                <span>Aposta</span>
                                <input type="number" min="1" max="{{ config('casino.bet_limits.max') }}" wire:model="bet" class="jetx-input" inputmode="numeric">
                            </label>
                            <label class="jetx-field">
                                <span>Seed do cliente</span>
                                <input type="text" maxlength="128" wire:model="clientSeed" class="jetx-input font-mono text-xs">
                            </label>
                        </div>
                        <p class="jetx-mini">Escolhe o valor da aposta. Ao clicar em INICIAR VOO, a aposta é debitada e o foguete arranca imediatamente.</p>
                    @elseif ($roundPhase === 'prepared')
                        <p class="jetx-mini">A ronda foi preparada e o hash já está fixado. Carrega em LANÇAR para iniciar o voo.</p>
                    @elseif ($roundPhase === 'in_progress')
                        <p class="jetx-mini">Esta ronda já estava iniciada antes do refresh. O jogo não arranca sozinho: carrega em RETOMAR VOO para continuar a ronda.</p>
                    @endif
                </div>

                <div class="jetx-action-wrap">
                    @if ($roundPhase === 'prepared')
                        <button type="button"
                                class="jetx-action"
                                wire:click="launch"
                                wire:loading.attr="disabled"
                                wire:target="launch">
                            🚀 LANÇAR
                        </button>
                    @elseif ($roundPhase === 'in_progress')
                        <template x-if="!flying">
                            <button type="button"
                                    class="jetx-action"
                                    :disabled="busy"
                                    x-on:click="beginFlight(Number(($wire.roundResult || {}).started_at_ms || Date.now()))">
                                🚀 RETOMAR VOO
                            </button>
                        </template>
                        <button type="button"
                                class="jetx-action collect"
                                x-show="flying"
                                x-cloak
                                :disabled="busy"
                                x-on:click="cashout()">
                            ⚡ COLETAR <span x-text="Number(displayMultiplier).toFixed(2) + '×'"></span>
                        </button>
                    @else
                        <button type="button"
                                class="jetx-action"
                                x-on:click="startFlight()"
                                wire:click="start"
                                wire:loading.attr="disabled"
                                wire:target="start">
                            🚀 INICIAR VOO
                        </button>
                    @endif
                </div>
            </section>

            @error('bet') <p class="mt-3 text-xs text-rose-300">{{ $message }}</p> @enderror
            @error('game') <p class="mt-3 text-xs text-rose-300">{{ $message }}</p> @enderror
        </main>

        <aside class="jetx-side">
            <div class="jetx-card">
                <p class="jetx-card__eyebrow">Estado da ronda</p>
                <h3 x-text="status === 'flying' ? '🚀 Em voo' : status === 'paused' ? '⏸️ Ronda em espera' : status === 'crashed' ? '💥 Crash confirmado' : status === 'cashed_out' ? '⚡ Recolhida com sucesso' : 'Pronta para lançar'"></h3>
                <div class="mt-3">
                    <div class="jetx-stat"><span>Multiplicador</span><strong x-text="Number(displayMultiplier).toFixed(2) + '×'"></strong></div>
                    <div class="jetx-stat"><span>Prémio</span><strong x-text="Number(payout).toLocaleString('pt-PT') + ' créditos'"></strong></div>
                    @if ($roundId)
                        <div class="jetx-stat"><span>Ronda</span><strong>#{{ $roundId }}</strong></div>
                    @endif
                </div>
            </div>

            <div class="jetx-card">
                <p class="jetx-card__eyebrow">Provably fair</p>
                <h3>Resultado comprometido antes do voo</h3>
                <p>O crash é definido no servidor e só é revelado à interface depois de a ronda terminar. O hash publicado permite verificar a semente no painel de fairness.</p>
                @if ($serverSeedHash)
                    <div class="jetx-seed">{{ $serverSeedHash }}</div>
                @endif
            </div>

            @if ($roundPhase === 'completed' && $roundId)
                <div class="jetx-card">
                    <p class="jetx-card__eyebrow">Verificação</p>
                    <h3>Ronda verificável</h3>
                    <p>Podes abrir os dados completos desta ronda e confirmar o compromisso do servidor.</p>
                    <button type="button" class="mt-3 text-xs font-bold text-cyan-300 underline underline-offset-4 hover:text-cyan-200" x-on:click="$dispatch('casino-open-fairness', { roundId: {{ $roundId }} })">Abrir verificação →</button>
                </div>
            @endif
        </aside>
    </div>
</div>
