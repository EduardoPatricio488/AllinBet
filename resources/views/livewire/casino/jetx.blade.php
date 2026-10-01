<div class="jetx-page"
     x-data="{
        busy: false,
        flying: false,
        phase: @js($roundPhase),
        status: 'ready',
        multiplier: 1,
        displayMultiplier: 1,
        flightStartedAt: 0,
        finalMultiplier: 0,
        payout: 0,
        resultOpen: false,
        resultLabel: '',
        pollTimer: null,
        raf: null,

        wait(ms) {
            return new Promise((resolve) => setTimeout(resolve, ms));
        },

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
            if (!this.flying || !this.flightStartedAt) return;

            const elapsed = Math.max(0, Date.now() - this.flightStartedAt);
            this.multiplier = Math.min(2500, Math.exp(elapsed / 6500));
            this.displayMultiplier = this.multiplier;
            this.raf = requestAnimationFrame(() => this.animate());
        },

        rocketStyle() {
            const progress = Math.min(1, Math.log(Math.max(1, this.displayMultiplier)) / Math.log(2500));
            const x = 8 + progress * 78;
            const y = 78 - Math.pow(progress, 1.25) * 65;

            return \`transform: translate3d(\${x}%, \${y}%, 0) rotate(-18deg);\`;
        },

        async start() {
            if (this.busy) return;

            const w = this.$wire;
            this.busy = true;
            this.resultOpen = false;
            this.status = 'preparing';
            this.resetFlight();

            try {
                if (w.roundPhase !== 'prepared') {
                    await w.prepare();
                }

                if (w.roundPhase !== 'prepared') {
                    this.status = 'ready';
                    return;
                }

                await w.launch();

                if (w.roundPhase !== 'in_progress') {
                    this.finalize();
                    return;
                }

                this.flightStartedAt = Number(w.roundResult?.started_at_ms || Date.now());
                this.phase = w.roundPhase;
                this.flying = true;
                this.status = 'flying';
                this.multiplier = 1;
                this.displayMultiplier = 1;
                this.tone('launch');

                this.animate();
                await w.tick();

                if (w.roundPhase === 'completed') {
                    this.finalize();
                    return;
                }

                this.busy = false;
                this.schedulePoll();
            } catch (e) {
                this.resetFlight();
                this.flying = false;
                this.busy = false;
                this.status = 'ready';
            }
        },

        schedulePoll() {
            if (!this.flying) return;

            clearTimeout(this.pollTimer);
            this.pollTimer = setTimeout(() => this.poll(), 650);
        },

        async poll() {
            if (!this.flying) return;

            const w = this.$wire;

            try {
                await w.tick();

                if (w.roundPhase === 'completed') {
                    this.finalize();
                    return;
                }
            } catch (e) {
                this.schedulePoll();
                return;
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
                this.finalize();
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
            const w = this.$wire;
            const result = w.roundResult || {};
            const completedStatus = result.status || (w.roundPayout > 0 ? 'cashed_out' : 'crashed');

            this.resetFlight();
            this.flying = false;
            this.phase = w.roundPhase;
            this.busy = false;
            this.payout = Number(w.roundPayout || result.payout || 0);
            this.finalMultiplier = Number(
                result.cashout_multiplier
                || result.crash_multiplier
                || result.multiplier
                || this.displayMultiplier
                || 1
            );
            this.displayMultiplier = this.finalMultiplier;

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
            }
        },

        destroy() {
            this.resetFlight();
        }
     }"
     x-init="$watch('$wire.roundPhase', (value) => phase = value)"
     x-on:keydown.window="if ($event.code === 'Space' && ['ready','completed'].includes(phase) && !['INPUT','TEXTAREA','BUTTON','SUMMARY'].includes($event.target.tagName)) { $event.preventDefault(); start(); }"
     x-on:pagehide.window="resetFlight()">

    <style>
        .jetx-page{--jet-gold:#f5c451;--jet-gold-hi:#ffe7a1;--jet-cyan:#4dd9ff;--jet-red:#ff5e67;--jet-bg:#070b12;--jet-panel:#0b111a;--jet-text:#eff5fb;color:#e9eef4}
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
        .jetx-rocket{position:absolute;left:0;top:0;z-index:6;font-size:clamp(3.3rem,7vw,5.6rem);filter:drop-shadow(0 15px 12px rgba(0,0,0,.4));will-change:transform}.jetx-rocket::after{content:"";position:absolute;right:76%;top:49%;width:6rem;height:.5rem;border-radius:999px;background:linear-gradient(90deg,rgba(245,196,81,0),rgba(245,196,81,.35),rgba(77,217,255,.8));filter:blur(6px);transform:rotate(8deg);transform-origin:right center;animation:jetx-flame .11s ease-in-out infinite alternate}
        .jetx-explosion{position:absolute;left:50%;top:48%;z-index:10;transform:translate(-50%,-50%);font-size:clamp(5rem,12vw,9rem);animation:jetx-boom .45s cubic-bezier(.15,.9,.3,1.15);filter:drop-shadow(0 0 35px rgba(255,94,103,.55))}
        .jetx-result{position:absolute;left:50%;bottom:9%;z-index:12;transform:translateX(-50%);min-width:min(90%,25rem);padding:1rem 1.25rem;text-align:center;border:1px solid rgba(255,255,255,.1);border-radius:1.15rem;background:rgba(7,11,18,.86);box-shadow:0 20px 50px rgba(0,0,0,.35)}.jetx-result strong{display:block;color:#fff;font-size:.72rem;font-weight:950;letter-spacing:.18em}.jetx-result span{display:block;margin-top:.2rem;color:var(--jet-gold);font-size:1.35rem;font-weight:1000}.jetx-result small{display:block;margin-top:.2rem;color:#8593a3;font-size:.65rem}
        .jetx-controls{display:grid;grid-template-columns:1fr auto;gap:1rem;margin-top:1rem;padding:1rem;border:1px solid rgba(255,255,255,.08);border-radius:1.1rem;background:rgba(11,17,26,.86);box-shadow:0 18px 45px rgba(0,0,0,.18)}
        .jetx-bet{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}.jetx-field span{display:block;margin-bottom:.3rem;color:#7c8a99;font-size:.62rem;font-weight:850;letter-spacing:.08em;text-transform:uppercase}.jetx-input{width:100%;min-height:2.7rem;border:1px solid rgba(255,255,255,.09);border-radius:.7rem;background:#060b12;color:#fff;padding:.65rem .75rem;outline:0}.jetx-input:focus{border-color:rgba(77,217,255,.45);box-shadow:0 0 0 3px rgba(77,217,255,.08)}
        .jetx-action{min-width:12rem;min-height:2.8rem;border:1px solid rgba(245,196,81,.55);border-radius:.8rem;background:linear-gradient(180deg,#ffeaa9,#e3ad37 55%,#98630e);color:#251a06;font-size:.82rem;font-weight:1000;letter-spacing:.1em;text-transform:uppercase;box-shadow:0 4px 0 #694609,0 12px 28px rgba(177,116,20,.22);cursor:pointer;transition:transform .08s,filter .15s}.jetx-action:hover:not(:disabled){filter:brightness(1.06)}.jetx-action:active:not(:disabled){transform:translateY(3px);box-shadow:0 1px 0 #694609,0 7px 18px rgba(177,116,20,.22)}.jetx-action.collect{border-color:rgba(77,217,255,.55);background:linear-gradient(180deg,#bff7ff,#3dcde9 55%,#087e9e);color:#04222a;box-shadow:0 4px 0 #04576c,0 12px 30px rgba(61,205,233,.18)}.jetx-action:disabled{opacity:.55;cursor:not-allowed}
        .jetx-mini{margin-top:.65rem;color:#697888;font-size:.62rem;line-height:1.45}
        .jetx-side{display:grid;gap:.8rem;align-content:start}.jetx-card{border:1px solid rgba(255,255,255,.08);border-radius:1rem;background:rgba(11,17,26,.78);padding:1rem;box-shadow:0 16px 38px rgba(0,0,0,.15)}.jetx-card__eyebrow{margin:0;color:#6e7d8c;font-size:.58rem;font-weight:950;letter-spacing:.16em;text-transform:uppercase}.jetx-card h3{margin:.35rem 0 0;color:#f4f7fa;font-size:.92rem;font-weight:900}.jetx-card p{margin:.4rem 0 0;color:#8996a4;font-size:.68rem;line-height:1.55}.jetx-stat{display:flex;justify-content:space-between;gap:1rem;padding:.65rem 0;border-bottom:1px solid rgba(255,255,255,.055);font-size:.68rem}.jetx-stat:last-child{border-bottom:0}.jetx-stat span{color:#738292}.jetx-stat strong{color:#dfe8ef}.jetx-seed{margin-top:.55rem;padding:.6rem;border:1px solid rgba(255,255,255,.06);border-radius:.65rem;background:#060a10;color:#93a1af;font: .56rem/1.4 ui-monospace,SFMono-Regular,Menlo,monospace;word-break:break-all}
        @keyframes jetx-stars{from{transform:translate3d(0,0,0)}to{transform:translate3d(-120px,80px,0)}}@keyframes jetx-flame{from{transform:scaleX(.85);opacity:.55}to{transform:scaleX(1.15);opacity:1}}@keyframes jetx-trail-pulse{from{opacity:.35;transform:translateX(-8px)}to{opacity:.9;transform:translateX(10px)}}@keyframes jetx-boom{from{opacity:0;transform:translate(-50%,-50%) scale(.45)}65%{transform:translate(-50%,-50%) scale(1.12)}to{opacity:1;transform:translate(-50%,-50%) scale(1)}}        
        @media(max-width:900px){.jetx-layout{grid-template-columns:1fr}.jetx-side{grid-template-columns:repeat(2,minmax(0,1fr))}.jetx-controls{grid-template-columns:1fr}.jetx-action{width:100%}}@media(max-width:600px){.jetx-hero{align-items:flex-start;flex-direction:column}.jetx-stage{min-height:30rem}.jetx-bet{grid-template-columns:1fr}.jetx-side{grid-template-columns:1fr}.jetx-multiplier{top:19%}.jetx-result{bottom:6%}}
        @media(prefers-reduced-motion:reduce){.jetx-stage::before,.jetx-rocket::after{animation:none}.jetx-rocket,.jetx-explosion,.jetx-result{transition:none}}
    </style>

    <x-casino.loading-overlay target="prepare,launch,cashout" />

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
        }" x-text="status === 'flying' ? 'Foguete em voo' : status === 'crashed' ? 'Crash' : status === 'cashed_out' ? 'Prémio recolhido' : status === 'cashing_out' ? 'A recolher…' : 'Pronto'"></span>
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
                     aria-label="JetX">
                <div class="jetx-grid" aria-hidden="true"></div>
                <div class="jetx-trail" aria-hidden="true"></div>

                <div class="jetx-multiplier">
                    <span x-text="Number(displayMultiplier).toFixed(2) + '×'"></span>
                </div>

                <div class="jetx-rocket" :style="rocketStyle()" x-show="flying" x-cloak aria-hidden="true">🚀</div>
                <div class="jetx-explosion" x-show="status === 'crashed' && resultOpen" x-cloak aria-hidden="true">💥</div>

                <div class="jetx-result" x-show="resultOpen" x-cloak>
                    <strong x-text="resultLabel"></strong>
                    <span x-text="Number(finalMultiplier).toFixed(2) + '×'"></span>
                    <small x-text="status === 'cashed_out' ? '+' + Number(payout).toLocaleString('pt-PT') + ' créditos virtuais' : 'Aposta perdida nesta ronda'"></small>
                </div>
            </section>

            <section class="jetx-controls">
                <div>
                    <form x-show="['ready','completed'].includes(phase)" wire:submit="prepare">
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
                        <p class="jetx-mini">Primeiro prepara a ronda para fixar o hash do servidor.</p>
                    </form>

                    <div x-show="phase === 'prepared'" x-cloak>
                        <p class="jetx-mini">Ronda preparada. O hash foi fixado antes do lançamento.</p>
                    </div>

                    <div x-show="phase === 'in_progress'" x-cloak>
                        <p class="jetx-mini">O foguete está em voo. O valor mostrado pelo servidor é o valor usado para o cálculo do COLETAR.</p>
                    </div>
                </div>

                <div>
                    <button type="button"
                            class="jetx-action"
                            x-show="['ready','completed','prepared'].includes(phase)"
                            :disabled="busy || phase === 'preparing'"
                            x-on:click="start()">
                        <span x-text="phase === 'prepared' ? '🚀 LANÇAR' : '🚀 PREPARAR & LANÇAR'"></span>
                    </button>

                    <button type="button"
                            class="jetx-action collect"
                            x-show="phase === 'in_progress'"
                            x-cloak
                            :disabled="busy || !flying"
                            x-on:click="cashout()">
                        ⚡ COLETAR <span x-text="Number(displayMultiplier).toFixed(2) + '×'"></span>
                    </button>
                </div>
            </section>

            @error('bet') <p class="mt-3 text-xs text-rose-300">{{ $message }}</p> @enderror
            @error('game') <p class="mt-3 text-xs text-rose-300">{{ $message }}</p> @enderror
        </main>

        <aside class="jetx-side">
            <div class="jetx-card">
                <p class="jetx-card__eyebrow">Estado da ronda</p>
                <h3 x-text="status === 'flying' ? '🚀 Em voo' : status === 'crashed' ? '💥 Crash confirmado' : status === 'cashed_out' ? '⚡ Recolhida com sucesso' : 'Pronta para lançar'"></h3>
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
