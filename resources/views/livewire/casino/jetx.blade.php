@php
    $maxBet = max(0, (int) (auth()->user()?->wallet?->balance ?? 0));
@endphp

<div class="jetx-page jx casino-game-screen"
     data-casino-game="jetx"
     x-data="{
        busy: false, launching: false, flying: false, phase: @js($roundPhase),
        status: @js($roundPhase === 'in_progress' ? 'paused' : 'ready'),
        tau: @js((int) config('casino.games.jetx.multiplier_time_constant_ms', 6500)),
        cap: @js((float) config('casino.games.jetx.max_multiplier', 2500)),
        started: 0, secs: 0, mult: 1, serverMult: 1, finalMult: 0, crashMult: 0, payout: 0, estimated: 0, lastMilestone: 1,
        resultOpen: false, label: '', pollTimer: null, raf: null, auto: '', history: [],
        line: '', area: '', pos: 'left:0%;top:100%', ang: -22, yTicks: [],

        sfx(name) { this.$dispatch('casino-sfx', { name }); },
        get statusText() { return { launching: 'A preparar aposta…', flying: 'Foguete em voo', paused: 'Ronda em espera', crashed: 'Crash', cashed_out: 'Prémio recolhido', cashing_out: 'A recolher…' }[this.status] || 'Pronto'; },
        current() { const e = Math.max(0, Date.now() - Number(this.started || Date.now())); return Math.min(this.cap, Math.round(Math.exp(e / this.tau) * 100) / 100); },

        draw(t) {
            const tau = this.tau / 1000, m = Math.min(this.cap, Math.exp(t / tau)), xMax = Math.max(6, t * 1.12), yMax = Math.max(2, m * 1.18), N = 48, pts = [];
            for (let i = 0; i <= N; i++) {
                const tt = (t * i) / N, mm = Math.min(this.cap, Math.exp(tt / tau));
                pts.push([(tt / xMax) * 1000, 400 - ((mm - 1) / (yMax - 1)) * 400]);
            }
            const f = (p) => p[0].toFixed(1) + ' ' + p[1].toFixed(1), last = pts[N], prev = pts[N - 1], el = this.$refs.chart;
            const W = el ? el.clientWidth : 1000, H = el ? el.clientHeight : 400;
            this.line = 'M' + pts.map(f).join(' L');
            this.area = this.line + ' L' + last[0].toFixed(1) + ' 400 L0 400 Z';
            this.pos = 'left:' + (last[0] / 10).toFixed(2) + '%;top:' + (last[1] / 4).toFixed(2) + '%';
            this.ang = t > 0.05 ? (Math.atan2(((last[1] - prev[1]) / 400) * H, ((last[0] - prev[0]) / 1000) * W) * 180) / Math.PI : -22;
            const raw = (yMax - 1) / 4, step = [.25, .5, 1, 2, 5, 10, 20, 50, 100, 200, 500, 1000].find((s) => s >= raw) || 1000;
            this.yTicks = Array.from({ length: Math.floor((yMax - 1) / step) }, (_, k) => {
                const v = 1 + step * (k + 1);
                return { v: +v.toFixed(2), y: 400 - ((v - 1) / (yMax - 1)) * 400 };
            });
        },

        stop() { clearTimeout(this.pollTimer); cancelAnimationFrame(this.raf); this.pollTimer = null; this.raf = null; },

        animate() {
            if (!this.flying || !this.started) return;
            const m = this.current();
            this.mult = m;
            this.secs = Math.max(0, (Date.now() - this.started) / 1000);
            this.estimated = Math.floor(Number(this.$wire.bet || 0) * m);
            this.draw(this.secs);
            const ms = Math.floor(m);
            if (ms >= 2 && ms > this.lastMilestone) {
                this.lastMilestone = ms;
                this.sfx('milestone');
            }
            if (this.auto && !this.busy && m >= Number(this.auto)) this.cashout();
            this.raf = requestAnimationFrame(() => this.animate());
        },

        syncServerResult() {
            const w = this.$wire, r = w.roundResult || {};
            const v = Number(r.multiplier ?? r.cashout_multiplier ?? r.crash_multiplier ?? 1);
            this.phase = w.roundPhase;
            if (Number.isFinite(v) && v >= 1) this.serverMult = Math.min(this.cap, v);
            if (w.roundPhase === 'completed') { this.finalize(); return false; }
            return true;
        },

        async beginFlight(startedAtMs = Date.now()) {
            const w = this.$wire, s = Number(startedAtMs || Date.now());
            if (this.flying && Math.abs(this.started - s) < 50) return;
            this.stop();
            this.launching = false;
            this.started = s;
            this.phase = 'in_progress';
            this.flying = true;
            this.status = 'flying';
            this.busy = false;
            this.serverMult = Math.max(1, Number(w.roundResult?.multiplier || 1));
            this.mult = 1;
            this.lastMilestone = 1;
            this.resultOpen = false;
            this.label = '';
            this.payout = 0;
            this.secs = 0;
            this.estimated = Number(w.bet || 0);
            this.sfx('launch');
            this.animate();
            try { await w.tick(); if (!this.syncServerResult()) return; } catch (e) {}
            this.schedulePoll();
        },

        async startFlight() {
            if (this.busy || this.flying) return;

            this.stop();
            this.sfx('charge');
            this.launching = true;
            this.flying = false;
            this.busy = true;
            this.phase = 'launching';
            this.status = 'launching';
            this.resultOpen = false;
            this.label = '';
            this.lastMilestone = 1;
            this.mult = 1;
            this.serverMult = 1;
            this.finalMult = 0;
            this.crashMult = 0;
            this.payout = 0;
            this.secs = 0;
            this.estimated = Math.max(0, Number(this.$wire.bet || 0));
            this.draw(0);

            try {
                await this.$wire.start();

                if (this.$wire.roundPhase === 'in_progress') {
                    await this.beginFlight(Number((this.$wire.roundResult || {}).started_at_ms || Date.now()));
                    return;
                }

                this.launching = false;
                this.busy = false;
                this.phase = this.$wire.roundPhase;
                this.status = this.$wire.roundPhase === 'prepared' ? 'ready' : 'ready';
            } catch (e) {
                this.launching = false;
                this.busy = false;
                this.phase = this.$wire.roundPhase || 'ready';
                this.status = 'ready';
            }
        },

        async launchPrepared() {
            if (this.busy || this.flying || this.$wire.roundPhase !== 'prepared') return;

            this.busy = true;
            this.launching = true;
            this.phase = 'launching';
            this.status = 'launching';
            this.resultOpen = false;
            this.sfx('charge');

            try {
                await this.$wire.launch();

                if (this.$wire.roundPhase === 'in_progress') {
                    await this.beginFlight(Number((this.$wire.roundResult || {}).started_at_ms || Date.now()));
                } else {
                    this.launching = false;
                    this.busy = false;
                    this.phase = this.$wire.roundPhase;
                    this.status = 'ready';
                }
            } catch (e) {
                this.launching = false;
                this.busy = false;
                this.phase = this.$wire.roundPhase || 'prepared';
                this.status = 'ready';
            }
        },

        schedulePoll() {
            if (!this.flying) return;
            clearTimeout(this.pollTimer);
            this.pollTimer = setTimeout(() => this.poll(), 350);
        },

        async poll() {
            if (!this.flying) return;
            try { await this.$wire.tick(); if (!this.syncServerResult()) return; } catch (e) {}
            this.schedulePoll();
        },

        async cashout() {
            if (!this.flying || this.busy) return;
            const w = this.$wire;
            this.busy = true;
            this.flying = false;
            this.stop();
            this.status = 'cashing_out';
            try {
                await w.cashout();
                if (w.roundPhase === 'completed') { this.finalize(); return; }
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

        finalize() {
            const w = this.$wire, r = w.roundResult || {}, st = r.status || (w.roundPayout > 0 ? 'cashed_out' : 'crashed');
            this.stop();
            this.flying = false;
            this.phase = 'completed';
            this.busy = false;
            this.payout = Number(w.roundPayout || r.payout || 0);
            this.finalMult = Number(r.cashout_multiplier || r.crash_multiplier || r.multiplier || this.serverMult || 1);
            this.crashMult = Number(r.crash_multiplier || (st === 'crashed' ? this.finalMult : 0));
            this.serverMult = this.finalMult;
            this.mult = this.finalMult;
            this.estimated = this.payout;
            this.draw((this.tau / 1000) * Math.log(Math.max(1, this.finalMult)));
            if (st === 'cashed_out' || st === 'crashed') {
                this.status = st;
                this.label = st === 'cashed_out' ? 'Coletado' : 'Crash';
                this.resultOpen = true;
                this.history = [{ m: this.crashMult || this.finalMult, won: st === 'cashed_out' }, ...this.history].slice(0, 10);
                this.sfx(st === 'cashed_out' ? 'cashout' : 'crash');
            } else {
                this.status = 'ready';
                this.resultOpen = false;
            }
        },

        resetIdle() {
            this.stop();
            this.launching = false;
            this.flying = false;
            this.busy = false;
            this.status = 'ready';
            this.mult = 1;
            this.serverMult = 1;
            this.finalMult = 0;
            this.crashMult = 0;
            this.payout = 0;
            this.secs = 0;
            this.estimated = 0;
            this.resultOpen = false;
            this.label = '';
            this.draw(0);
        },

        destroy() { this.stop(); }
     }"
     x-init="
        draw(0);
        $watch('$wire.roundPhase', (value) => {
            phase = value;
            if (value === 'in_progress') {
                if (!flying) beginFlight(Number(($wire.roundResult || {}).started_at_ms || Date.now()));
                return;
            }
            if (value === 'completed') {
                if (flying) finalize();
                return;
            }
            if (value === 'ready' || value === 'prepared') resetIdle();
        })
     "
     x-on:keydown.window="if ($event.code === 'Space' && !['INPUT','TEXTAREA','BUTTON','SUMMARY'].includes($event.target.tagName)) { $event.preventDefault(); if (flying) cashout(); else if (['ready','completed'].includes(phase)) startFlight(); else if (phase === 'prepared') launchPrepared(); }"
     x-on:jetx-flight-started.window="beginFlight($event.detail.startedAtMs)"
     x-on:pagehide.window="stop()">

    <style>
        .jx { --cy: #4dd9ff; --gold: #f5c451; --gold-hi: #ffe7a1; --red: #ff5e67; color: #e9eef4; }
        .jx-eyebrow { font-size: .62rem; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; color: #748394; }
        .jx-hero { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem; margin-bottom: 1rem; }
        .jx-title { font-size: 1.7rem; font-weight: 900; letter-spacing: -.02em; color: #fff; }
        .jx-hint { margin-top: .15rem; font-size: .8rem; color: #8996a5; }
        .jx-pill { padding: .45rem .8rem; border-radius: 9999px; font-size: .68rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #9cacba; border: 1px solid rgba(255, 255, 255, .1); background: rgba(3, 7, 12, .7); }
        .jx-pill.is-flying { color: var(--cy); border-color: rgba(77, 217, 255, .4); }
        .jx-pill.is-crashed { color: #ff9a9f; border-color: rgba(255, 94, 103, .4); }
        .jx-pill.is-cashed_out { color: var(--gold-hi); border-color: rgba(245, 196, 81, .4); }
        .jx-layout { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr) 18rem; margin-top: 1rem; }
        .jx-main { min-width: 0; }

        .jx-stage {
            position: relative; min-height: 34rem; overflow: hidden; border-radius: 1.5rem; border: 1px solid rgba(77, 217, 255, .18);
            background: radial-gradient(circle at 75% 10%, rgba(77, 217, 255, .12), transparent 35%), radial-gradient(circle at 30% 100%, rgba(245, 196, 81, .08), transparent 35%), linear-gradient(160deg, #0b101a, #060a11 65%);
            box-shadow: 0 30px 70px rgba(0, 0, 0, .4), inset 0 1px 0 rgba(255, 255, 255, .05);
        }
        .jx-stage.is-crashed { animation: jx-shake .45s ease-out; }
        .jx-stage.is-crashed::after { content: ''; position: absolute; inset: 0; background: radial-gradient(circle, transparent 40%, rgba(255, 70, 80, .16)); pointer-events: none; }
        .jx-stars { position: absolute; inset: -20%; pointer-events: none; opacity: .3; }
        .jx-stars--far { background-image: radial-gradient(circle, rgba(255, 255, 255, .7) 0 1px, transparent 1.5px), radial-gradient(circle, rgba(77, 217, 255, .45) 0 1px, transparent 1.5px); background-size: 145px 145px, 210px 210px; background-position: 20px 35px, 90px 110px; }
        .jx-stars--near { background-image: radial-gradient(circle, rgba(255, 255, 255, .85) 0 1.5px, transparent 2px); background-size: 95px 95px; opacity: .2; }
        .jx-stage.is-flying .jx-stars--far { animation: jx-drift 11s linear infinite; }
        .jx-stage.is-flying .jx-stars--near { animation: jx-drift 4.5s linear infinite; }
        .jx-speed { position: absolute; inset: 0; overflow: hidden; pointer-events: none; opacity: 0; transition: opacity .4s; }
        .jx-stage.is-fast .jx-speed { opacity: 1; }
        .jx-speed span { position: absolute; left: -20%; width: 30%; height: 2px; border-radius: 9999px; background: linear-gradient(90deg, transparent, rgba(77, 217, 255, .7), transparent); transform: rotate(-19deg); animation: jx-streak 1.1s linear infinite; }
        .jx-speed span:nth-child(1) { top: 18%; }
        .jx-speed span:nth-child(2) { top: 32%; animation-delay: -.5s; }
        .jx-speed span:nth-child(3) { top: 46%; animation-delay: -.8s; }
        .jx-speed span:nth-child(4) { top: 58%; animation-delay: -.3s; }
        .jx-speed span:nth-child(5) { top: 70%; animation-delay: -.9s; }
        .jx-speed span:nth-child(6) { top: 82%; animation-delay: -.15s; }

        .jx-hud { position: absolute; top: 1rem; left: 1rem; right: 1rem; z-index: 6; display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
        .jx-chip { padding: .4rem .65rem; border-radius: .7rem; border: 1px solid rgba(255, 255, 255, .09); background: rgba(3, 8, 13, .7); backdrop-filter: blur(10px); }
        .jx-chip span, .jx-telemetry span { display: block; font-size: .5rem; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; color: #6b7b8c; }
        .jx-chip strong { font-size: .72rem; font-weight: 800; }
        .jx-past { padding: .25rem .6rem; border-radius: 9999px; font-size: .68rem; font-weight: 800; background: rgba(255, 255, 255, .06); animation: jx-pop .3s ease; }
        .jx-past.is-won { color: #ffe7a1; border: 1px solid rgba(245, 196, 81, .4); }
        .jx-past.is-lost { color: #ff9a9f; border: 1px solid rgba(255, 94, 103, .35); }

        .jx-chart { position: absolute; z-index: 3; top: 5rem; bottom: 6rem; left: 3.4rem; right: 1.6rem; border-left: 1px solid rgba(255, 255, 255, .12); border-bottom: 1px solid rgba(255, 255, 255, .12); }
        .jx-chart svg { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }
        .jx-gridline { stroke: rgba(255, 255, 255, .06); stroke-dasharray: 4 6; vector-effect: non-scaling-stroke; }
        .jx-line { fill: none; stroke: var(--cy); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; vector-effect: non-scaling-stroke; filter: drop-shadow(0 0 8px rgba(77, 217, 255, .7)); }
        .jx-stage.is-crashed .jx-line { stroke: var(--red); filter: drop-shadow(0 0 8px rgba(255, 94, 103, .7)); }
        .jx-stage.is-cashed_out .jx-line { stroke: var(--gold); filter: drop-shadow(0 0 8px rgba(245, 196, 81, .6)); }
        .jx-tick { position: absolute; left: -3.1rem; width: 2.7rem; transform: translateY(-50%); text-align: right; font-size: .62rem; font-weight: 700; color: #6b7b8c; font-variant-numeric: tabular-nums; }

        .jx-rocket { position: absolute; width: 4.6rem; height: 2.3rem; margin: -1.15rem 0 0 -2.3rem; will-change: left, top; }
        .jx-rocket__rot { width: 100%; height: 100%; transition: transform .12s linear; }
        .jx-rocket-svg { width: 100%; height: 100%; overflow: visible; filter: drop-shadow(0 8px 10px rgba(0, 0, 0, .5)); }
        .jx-rocket.is-idle .jx-rocket-svg { animation: jx-bob 2.2s ease-in-out infinite; }
        .jx-flame { opacity: 0; transform-origin: 18px 22px; }
        .jx-rocket.is-flying .jx-flame { opacity: 1; animation: jx-flame .09s ease-in-out infinite alternate; }
        .jx-rocket.is-flying .jx-rocket-svg { filter: drop-shadow(0 0 14px rgba(77, 217, 255, .4)) drop-shadow(0 8px 10px rgba(0, 0, 0, .5)); }
        .jx-burst { position: absolute; width: 0; height: 0; z-index: 4; }
        .jx-burst i { position: absolute; left: 0; top: 0; border-radius: 50%; transform: translate(-50%, -50%) scale(.2); }
        .jx-burst i:nth-child(1) { width: 11rem; height: 11rem; border: 3px solid rgba(255, 94, 103, .7); animation: jx-ring .8s cubic-bezier(.15, .9, .3, 1) both; }
        .jx-burst i:nth-child(2) { width: 7rem; height: 7rem; border: 2px solid rgba(255, 180, 90, .7); animation: jx-ring .6s .08s cubic-bezier(.15, .9, .3, 1) both; }
        .jx-burst i:nth-child(3) { width: 8rem; height: 8rem; background: radial-gradient(circle, #fff, #ffb347 35%, rgba(255, 94, 103, .5) 60%, transparent 70%); animation: jx-flash .55s ease-out both; }

        .jx-readout { position: absolute; z-index: 5; top: 26%; left: 50%; transform: translateX(-50%); text-align: center; pointer-events: none; width: min(90%, 36rem); }
        .jx-mult { font-size: clamp(3.8rem, 11vw, 7.4rem); font-weight: 900; line-height: .95; letter-spacing: -.05em; color: #f7fbff; text-shadow: 0 0 40px rgba(77, 217, 255, .25); font-variant-numeric: tabular-nums; }
        .jx-stage.is-flying .jx-mult { color: #c6f5ff; text-shadow: 0 0 45px rgba(77, 217, 255, .4); }
        .jx-stage.is-crashed .jx-mult { color: #ffb0b4; text-shadow: 0 0 40px rgba(255, 94, 103, .35); }
        .jx-stage.is-cashed_out .jx-mult { color: var(--gold-hi); text-shadow: 0 0 40px rgba(245, 196, 81, .35); }
        .jx-mult.is-final { animation: jx-final .5s cubic-bezier(.16, 1, .3, 1); }
        .jx-sub { display: flex; justify-content: center; gap: .6rem; margin-top: .5rem; font-size: .72rem; color: #8fa1b2; }
        .jx-sub strong { color: #dffbff; }
        .jx-result { position: absolute; z-index: 8; left: 50%; bottom: 7.2rem; transform: translateX(-50%); min-width: min(88%, 22rem); padding: .9rem 1.2rem; text-align: center; border-radius: 1.1rem; border: 1px solid rgba(255, 255, 255, .12); background: rgba(7, 11, 18, .88); backdrop-filter: blur(10px); box-shadow: 0 20px 50px rgba(0, 0, 0, .4); animation: jx-card .45s cubic-bezier(.16, 1, .3, 1); }
        .jx-result p { font-size: .72rem; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; }
        .jx-result.is-cash p, .jx-result.is-cash strong { color: var(--gold); }
        .jx-result.is-crash p, .jx-result.is-crash strong { color: #ff8d94; }
        .jx-result strong { display: block; font-size: 1.5rem; font-weight: 900; }
        .jx-result small { display: block; margin-top: .2rem; font-size: .68rem; color: #8896a5; }
        .jx-telemetry { position: absolute; z-index: 6; left: 1rem; right: 1rem; bottom: 1rem; display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
        .jx-telemetry > div { padding: .55rem .7rem; border-radius: .7rem; border: 1px solid rgba(255, 255, 255, .08); background: rgba(3, 8, 13, .7); backdrop-filter: blur(10px); }
        .jx-telemetry strong { display: block; margin-top: .15rem; font-size: .76rem; font-weight: 800; font-variant-numeric: tabular-nums; }

        .jx-controls { display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: center; margin-top: 1rem; padding: 1rem; border-radius: 1.1rem; border: 1px solid rgba(255, 255, 255, .08); background: rgba(11, 17, 26, .88); }
        .jx-fields { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; }
        .jx-field span { display: block; margin-bottom: .3rem; font-size: .62rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #7c8a99; }
        .jx-input { width: 100%; min-height: 2.7rem; padding: .6rem .75rem; color: #fff; border-radius: .7rem; border: 1px solid rgba(255, 255, 255, .1); background: #060b12; outline: 0; }
        .jx-input:focus-visible { border-color: rgba(77, 217, 255, .5); box-shadow: 0 0 0 3px rgba(77, 217, 255, .1); }
        .jx-input:disabled { opacity: .55; }
        .jx-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .7rem; }
        .jx-chips button { padding: .4rem .75rem; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, .1); background: rgba(255, 255, 255, .04); color: #b4c0cb; font-size: .72rem; font-weight: 700; transition: transform .15s, color .15s; }
        .jx-chips button:hover:not(:disabled) { transform: translateY(-2px); color: var(--gold-hi); }
        .jx-chips button:disabled { opacity: .5; cursor: not-allowed; }
        .jx-seed-box { margin-top: .6rem; font-size: .75rem; color: #8896a5; }
        .jx-seed-box summary { cursor: pointer; }
        .jx-mini { margin-top: .6rem; font-size: .66rem; line-height: 1.5; color: #6d7c8b; }
        .jx-action-wrap { display: flex; min-width: 13rem; }
        .jx-action { width: 100%; min-height: 3.4rem; padding: .5rem 1.3rem; border-radius: 1rem; border: 1px solid rgba(245, 196, 81, .6); font-size: .9rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #251a06; background: linear-gradient(180deg, #ffeaa9, #e3ad37 55%, #98630e); box-shadow: 0 5px 0 #694609, 0 14px 30px rgba(177, 116, 20, .25); transition: transform .08s, box-shadow .08s, filter .15s; }
        .jx-action small, .jx-action kbd { display: block; margin-top: .1rem; font: 600 .58rem system-ui; letter-spacing: .04em; text-transform: none; opacity: .65; }
        .jx-action.collect { border-color: rgba(77, 217, 255, .6); color: #04222a; background: linear-gradient(180deg, #c2f8ff, #3dcde9 55%, #087e9e); box-shadow: 0 5px 0 #04576c, 0 14px 30px rgba(61, 205, 233, .25); animation: jx-glow 1.2s ease-in-out infinite; }
        .jx-action:hover:not(:disabled) { filter: brightness(1.07); }
        .jx-action:active:not(:disabled) { transform: translateY(4px); box-shadow: 0 1px 0 #694609; }
        .jx-action:disabled { opacity: .55; cursor: not-allowed; }
        .jx-action:focus-visible, .jx-chips button:focus-visible { outline: 2px solid var(--gold-hi); outline-offset: 2px; }

        .jx-side { display: grid; gap: .8rem; align-content: start; }
        .jx-card { padding: 1rem; border-radius: 1rem; border: 1px solid rgba(255, 255, 255, .08); background: rgba(11, 17, 26, .8); }
        .jx-card h3 { margin-top: .3rem; font-size: .92rem; font-weight: 800; color: #f4f7fa; }
        .jx-card p:not(.jx-eyebrow) { margin-top: .4rem; font-size: .7rem; line-height: 1.55; color: #8996a4; }
        .jx-stat { display: flex; justify-content: space-between; gap: 1rem; padding: .55rem 0; border-bottom: 1px solid rgba(255, 255, 255, .06); font-size: .72rem; }
        .jx-stat:last-child { border-bottom: 0; }
        .jx-stat span { color: #738292; }
        .jx-stat strong { color: #dfe8ef; font-variant-numeric: tabular-nums; }
        .jx-seed { margin-top: .5rem; padding: .55rem; border-radius: .6rem; border: 1px solid rgba(255, 255, 255, .07); background: #060a10; font: .58rem/1.4 ui-monospace, Menlo, monospace; color: #93a1af; word-break: break-all; }

        @keyframes jx-drift { to { transform: translate3d(-130px, 85px, 0); } }
        @keyframes jx-streak { from { transform: translateX(-30vw) rotate(-19deg); opacity: 0; } 20% { opacity: .9; } to { transform: translateX(150vw) rotate(-19deg); opacity: 0; } }
        @keyframes jx-flame { from { transform: scaleX(.8); opacity: .6; } to { transform: scaleX(1.2); opacity: 1; } }
        @keyframes jx-bob { 50% { transform: translateY(-5px); } }
        @keyframes jx-ring { to { transform: translate(-50%, -50%) scale(1.3); opacity: 0; } from { opacity: .9; } }
        @keyframes jx-flash { 40% { opacity: 1; } to { transform: translate(-50%, -50%) scale(1.4); opacity: 0; } from { opacity: 0; } }
        @keyframes jx-shake { 20% { transform: translate(-8px, 4px); } 40% { transform: translate(7px, -3px); } 60% { transform: translate(-5px, 2px); } 80% { transform: translate(3px, -1px); } }
        @keyframes jx-final { from { transform: scale(.9); opacity: .6; } 70% { transform: scale(1.04); } }
        @keyframes jx-card { from { opacity: 0; transform: translateX(-50%) translateY(16px) scale(.96); } }
        @keyframes jx-pop { from { transform: scale(.5); opacity: 0; } }
        @keyframes jx-glow { 50% { box-shadow: 0 5px 0 #04576c, 0 14px 40px rgba(61, 205, 233, .55); } }

        @media (max-width: 900px) {
            .jx-layout { grid-template-columns: 1fr; }
            .jx-controls { grid-template-columns: 1fr; }
            .jx-action-wrap { min-width: 0; }
            .jx-side { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 600px) {
            .jx-stage { min-height: 31rem; }
            .jx-fields { grid-template-columns: 1fr; }
            .jx-side { grid-template-columns: 1fr; }
            .jx-chart { left: 2.9rem; top: 4.6rem; }
            .jx-result { bottom: 6.6rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .jx-stars, .jx-speed span, .jx-rocket-svg, .jx-flame, .jx-action.collect, .jx-stage.is-crashed, .jx-burst i, .jx-result, .jx-mult { animation: none !important; }
            .jx-rocket__rot { transition: none; }
        }
    </style>

    <x-casino.loading-overlay target="prepare,launch,start,cashout" />

    <div class="jx-hero">
        <div>
            <p class="jx-eyebrow">Allinbet · Original</p>
            <h1 class="jx-title">JetX</h1>
            <p class="jx-hint">Vê o multiplicador subir e recolhe antes do crash.</p>
        </div>
        <span class="jx-pill" :class="'is-' + status" x-text="statusText"></span>
    </div>

    <x-casino.how-it-works
        game-key="jetx"
        title="Como funciona o JetX?"
        description="O foguete sobe e o multiplicador aumenta. Recolhe os créditos antes do crash para fechar a ronda com o multiplicador atingido."
        :rules="[
            ['title'=>'Escolhe a aposta','text'=>'Define quantos créditos virtuais queres colocar na ronda.'],
            ['title'=>'Lança o foguete','text'=>'O multiplicador começa em 1.00× e cresce enquanto o voo continua.'],
            ['title'=>'Recolhe quando quiseres','text'=>'Carrega em Coletar (ou na barra de espaço) para fixar o multiplicador desse momento.'],
            ['title'=>'Se houver crash','text'=>'Se o crash acontecer antes da recolha, a aposta termina sem prémio.']
        ]"
        badge="Créditos virtuais"
    />

    <div class="jx-layout">
        <main class="jx-main">
            <section class="jx-stage" :class="['is-' + status, mult >= 2 && flying ? 'is-fast' : '']" aria-label="JetX">
                <div class="jx-stars jx-stars--far" aria-hidden="true"></div>
                <div class="jx-stars jx-stars--near" aria-hidden="true"></div>
                <div class="jx-speed" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></div>

                <div class="jx-hud">
                    <div class="jx-chip"><span>Voo</span><strong x-text="flying ? secs.toFixed(1) + 's' : launching ? 'A preparar' : '—'"></strong></div>
                    <template x-for="(h, i) in history" :key="i"><span class="jx-past" :class="h.won ? 'is-won' : 'is-lost'" x-text="Number(h.m).toFixed(2) + '×'"></span></template>
                </div>

                <div class="jx-chart" x-ref="chart">
                    <template x-for="t in yTicks" :key="t.v">
                        <span class="jx-tick" :style="'top:' + (t.y / 4) + '%'" x-text="t.v + '×'"></span>
                    </template>
                    <svg viewBox="0 0 1000 400" preserveAspectRatio="none" aria-hidden="true">
                        <defs><linearGradient id="jxArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4dd9ff" stop-opacity=".35"/><stop offset="1" stop-color="#4dd9ff" stop-opacity="0"/></linearGradient></defs>
                        <template x-for="t in yTicks" :key="t.v"><line class="jx-gridline" x1="0" x2="1000" :y1="t.y" :y2="t.y"/></template>
                        <path class="jx-area" :d="area" fill="url(#jxArea)"/>
                        <path class="jx-line" :d="line"/>
                    </svg>

                    <div class="jx-rocket" :class="{ 'is-flying': flying, 'is-idle': !flying && !resultOpen }" :style="pos" x-show="status !== 'crashed'">
                        <div class="jx-rocket__rot" :style="'transform: rotate(' + ang + 'deg)'">
                            <svg class="jx-rocket-svg" viewBox="-6 0 96 44" aria-hidden="true">
                                <defs><linearGradient id="jxB" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="#8f9eae"/></linearGradient></defs>
                                <path class="jx-flame" d="M18 22 L-5 13 Q3 22 -5 31Z" fill="#ffb347"/>
                                <path d="M26 6 44 12 30 18ZM26 38 44 32 30 26Z" fill="#d9434f"/>
                                <path d="M16 22Q16 8 40 8H62Q86 14 86 22 86 30 62 36H40Q16 36 16 22Z" fill="url(#jxB)" stroke="#fff" stroke-opacity=".6"/>
                                <circle cx="60" cy="21" r="6" fill="#32c8ee" stroke="#e8fbff" stroke-width="2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="jx-burst" :style="pos" x-show="status === 'crashed' && resultOpen" x-cloak aria-hidden="true"><i></i><i></i><i></i></div>
                </div>

                <div class="jx-readout" aria-live="polite">
                    <div class="jx-mult" :class="{ 'is-final': resultOpen }" x-text="Number(resultOpen ? finalMult : mult).toFixed(2) + '×'"></div>
                    <div class="jx-sub" x-show="launching || flying" x-cloak>
                        <span x-text="launching ? 'Aposta em jogo' : 'Prémio se recolheres agora'"></span>
                        <strong x-text="Number(estimated).toLocaleString('pt-PT') + ' CR'"></strong>
                    </div>
                </div>

                <div class="jx-result" x-show="resultOpen" x-cloak :class="status === 'crashed' ? 'is-crash' : 'is-cash'">
                    <p x-text="label"></p>
                    <strong x-text="Number(finalMult).toFixed(2) + '×'"></strong>
                    <small x-text="status === 'cashed_out' ? '+' + Number(payout).toLocaleString('pt-PT') + ' créditos virtuais' : 'Aposta perdida nesta ronda'"></small>
                    <small x-show="status === 'cashed_out' && crashMult > 0" x-cloak>O voo teria terminado em <b x-text="Number(crashMult).toFixed(2) + '×'"></b></small>
                </div>

                <div class="jx-telemetry">
                    <div><span>Aposta</span><strong x-text="Number($wire.bet || 0).toLocaleString('pt-PT') + ' CR'"></strong></div>
                    <div><span>Potencial</span><strong x-text="Number(estimated).toLocaleString('pt-PT') + ' CR'"></strong></div>
                    <div><span>Multiplicador</span><strong x-text="Number(mult).toFixed(2) + '×'"></strong></div>
                </div>
            </section>

            <section class="jx-controls">
                <div>
                    @if (in_array($roundPhase, ['ready', 'completed'], true))
                        <div class="jx-fields">
                            <label class="jx-field"><span>Aposta</span>
                                <input type="number" min="1" max="{{ $maxBet }}" wire:model="bet" class="jx-input" inputmode="numeric" :disabled="busy || launching">
                            </label>
                            <label class="jx-field"><span>Recolha automática (opcional)</span>
                                <input type="number" min="1.01" step="0.01" x-model="auto" class="jx-input" placeholder="Ex.: 2.00" :disabled="busy || launching">
                            </label>
                        </div>
                        <div class="jx-chips" aria-label="Apostas rápidas">
                            @foreach ([5, 10, 25, 50, 100] as $chip)
                                @if ($chip <= $maxBet)
                                    <button type="button" x-on:click="$wire.bet = {{ $chip }}" :disabled="busy || launching">{{ $chip }}</button>
                                @endif
                            @endforeach
                            <button type="button" x-on:click="$wire.bet = {{ $maxBet }}" :disabled="busy || launching || {{ $maxBet < 1 ? 'true' : 'false' }}">ALL IN · {{ number_format($maxBet, 0, ',', ' ') }}</button>
                        </div>
                        <details class="jx-seed-box"><summary>Seed do cliente</summary>
                            <input type="text" maxlength="128" wire:model="clientSeed" class="jx-input font-mono text-xs mt-2" :disabled="busy || launching">
                        </details>
                        <p class="jx-mini">A recolha automática é aproximada: o servidor usa o multiplicador do momento em que recebe o pedido.</p>
                    @elseif ($roundPhase === 'prepared')
                        <p class="jx-mini">A ronda foi preparada e o hash já está fixado. Carrega em Lançar para iniciar o voo.</p>
                    @elseif ($roundPhase === 'in_progress')
                        <p class="jx-mini">Esta ronda já estava em curso antes do refresh. Carrega em Retomar voo para continuar.</p>
                    @endif
                </div>

                <div class="jx-action-wrap">
                    @if ($roundPhase === 'prepared')
                        <button type="button" class="jx-action" :disabled="busy" x-on:click="launchPrepared()">Lançar <kbd>Espaço</kbd></button>
                    @elseif ($roundPhase === 'in_progress')
                        <template x-if="!flying">
                            <button type="button" class="jx-action" :disabled="busy" x-on:click="beginFlight(Number(($wire.roundResult || {}).started_at_ms || Date.now()))">Retomar voo</button>
                        </template>
                        <button type="button" class="jx-action collect" x-show="flying" x-cloak :disabled="busy" x-on:click="cashout()">
                            <span>Coletar <b x-text="Number(mult).toFixed(2) + '×'"></b></span>
                            <small x-text="'+' + Number(estimated).toLocaleString('pt-PT') + ' CR · Espaço'"></small>
                        </button>
                    @else
                        <button type="button" class="jx-action" :disabled="busy" x-on:click="startFlight()">
                            <span x-show="!launching">Iniciar voo <kbd>Espaço</kbd></span>
                            <span x-show="launching" x-cloak>A preparar <b x-text="Number($wire.bet || 0).toLocaleString('pt-PT') + ' CR'"></b></span>
                        </button>
                    @endif
                </div>
            </section>

            @error('bet')<p class="mt-3 text-xs text-rose-300" role="alert">{{ $message }}</p>@enderror
            @error('game')<p class="mt-3 text-xs text-rose-300" role="alert">{{ $message }}</p>@enderror
        </main>

        <aside class="jx-side">
            <div class="jx-card">
                <p class="jx-eyebrow">Estado da ronda</p>
                <h3 x-text="statusText"></h3>
                <div class="mt-2">
                    <div class="jx-stat"><span>Multiplicador</span><strong x-text="Number(mult).toFixed(2) + '×'"></strong></div>
                    <div class="jx-stat"><span>Prémio</span><strong x-text="Number(payout).toLocaleString('pt-PT') + ' créditos'"></strong></div>
                    <div class="jx-stat" x-show="status === 'cashed_out' && crashMult > 0" x-cloak><span>Crash do voo</span><strong x-text="Number(crashMult).toFixed(2) + '×'"></strong></div>
                    @if ($roundId)<div class="jx-stat"><span>Ronda</span><strong>#{{ $roundId }}</strong></div>@endif
                </div>
            </div>

            <div class="jx-card">
                <p class="jx-eyebrow">Provably fair</p>
                <h3>Resultado comprometido antes do voo</h3>
                <p>O crash é definido no servidor e só é revelado depois de a ronda terminar. O hash publicado permite verificar a semente.</p>
                @if ($serverSeedHash)<div class="jx-seed">{{ $serverSeedHash }}</div>@endif
                @if ($roundPhase === 'completed' && $roundId)
                    <button type="button" class="mt-3 text-xs font-bold text-cyan-300 underline underline-offset-4 hover:text-cyan-200" x-on:click="$dispatch('casino-open-fairness', { roundId: {{ $roundId }} })">Verificar esta ronda</button>
                @endif
            </div>

            <p class="px-1 text-center text-[0.68rem] text-zinc-600">Créditos virtuais — sem valor monetário</p>
        </aside>
    </div>
</div>
