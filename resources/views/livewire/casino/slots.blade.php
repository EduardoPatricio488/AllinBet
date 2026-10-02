@php
    $rows = (int) config('casino.games.slots.rows', 5);
    $columns = (int) config('casino.games.slots.columns', 5);
    $grid = $roundResult['grid'] ?? array_fill(0, $rows, array_fill(0, $columns, 0));
    $maxBet = max(0, (int) (auth()->user()?->wallet?->balance ?? 0));
    $locked = in_array($roundPhase, ['prepared', 'in_progress'], true);
    $slotVariants = config('casino.games.slots.variants', []);
    $selectedSlotConfig = $slotVariants[$selectedSlot] ?? $slotVariants['classic'] ?? [];
    $symbols = $selectedSlotConfig['symbols'] ?? ['🍒', '🍋', '🍊', '🔔', '⭐', '🍀', '💎', '7️⃣'];

    $winningLines = $roundPhase === 'completed'
        ? collect($roundResult['winning_lines'] ?? [])->values()->all()
        : [];
    $payout = (int) $roundPayout;
@endphp

<div class="casino-game-play casino-game-screen slot-page" data-casino-game="slots"
     x-data="{
        st: [0, 0, 0, 0, 0].map(() => 'idle'),
        selectedSlot: @js($selectedSlot),
        busy: false,
        done: @js($roundResult !== []),
        shown: 0,
        prize: 0,
        tier: '',
        settled: 0,
        grid: @js($grid),
        winningLines: @js($winningLines),
        symbols: @js($symbols),
        tracks: [[], [], [], [], []],
        reelPrefix: 72,
        spinCycle: 0,
        bonusRunning: false,
        bonusDone: false,
        bonusMultiplier: 0,
        bonusSpins: 0,
        bonusProgress: 0,
        bonusCost: 0,
        bonusPayout: 0,
        bonusProfit: 0,
        bonusConfirmation: null,
        bonusOptions: @js(config('casino.games.slots.bonus_buy.options', [])),
        maxBet: {{ $maxBet }},
        wait: (ms) => new Promise((r) => setTimeout(r, ms)),
        sfx(name) { this.$dispatch('casino-sfx', { name }); },
        bonusOption(multiplier) {
            return this.bonusOptions.find((option) => Number(option.multiplier) === Number(multiplier)) || null;
        },
        buildReelTracks() {
            const orders = [
                [0, 3, 6, 1, 4, 7, 2, 5],
                [5, 2, 7, 4, 1, 6, 3, 0],
                [2, 6, 1, 5, 0, 4, 7, 3],
                [1, 7, 4, 2, 6, 0, 5, 3],
                [4, 0, 6, 2, 7, 3, 1, 5],
            ];
            const count = this.symbols.length || 1;

            for (let column = 0; column < 5; column++) {
                const track = [];
                const order = orders[column];

                for (let i = 0; i < this.reelPrefix; i++) {
                    const index = order[(i + (this.spinCycle * 3) + column) % order.length];
                    track.push(this.symbols[index % count]);
                }

                for (let row = 0; row < 5; row++) {
                    const value = Number(this.grid?.[row]?.[column] ?? 0);
                    track.push(this.symbols[Math.abs(value) % count]);
                }

                this.tracks[column] = track;
            }
        },
        syncServerResult() {
            const result = this.$wire.roundResult || {};

            if (Array.isArray(result.grid) && result.grid.length === 5) {
                this.grid = result.grid;
            }

            this.winningLines = Array.isArray(result.winning_lines) ? result.winning_lines : [];
            this.buildReelTracks();
        },
        isWinningCell(row, column) {
            return this.done && this.winningLines.some((line) =>
                (line.direction === 'horizontal' && Number(line.line) === row) ||
                (line.direction === 'vertical' && Number(line.line) === column)
            );
        },
        reelSpinners() {
            return [...this.$root.querySelectorAll('.slot-spinner')];
        },
        resetReels() {
            this.spinCycle += 1;
            this.buildReelTracks();

            this.reelSpinners().forEach((spinner) => {
                spinner.style.animation = '';
                spinner.style.transition = 'none';
                spinner.style.transform = 'translate3d(0, 0, 0)';
            });
        },
        readTransformY(element) {
            const transform = getComputedStyle(element).transform;

            if (!transform || transform === 'none') return 0;

            const match3d = transform.match(/matrix3d\(([^)]+)\)/);
            if (match3d) {
                const values = match3d[1].split(',').map(Number);
                return Number(values[13]) || 0;
            }

            const match2d = transform.match(/matrix\(([^)]+)\)/);
            if (match2d) {
                const values = match2d[1].split(',').map(Number);
                return Number(values[5]) || 0;
            }

            return 0;
        },
        async settleReel(index) {
            const reel = this.$root.querySelectorAll('.slot-reel')[index];
            const spinner = this.reelSpinners()[index];

            if (!reel || !spinner) return;

            const cell = Math.max(1, reel.getBoundingClientRect().height / 5);
            const currentY = this.readTransformY(spinner);
            const targetY = -(this.reelPrefix * cell);
            const durations = [590, 680, 770, 860, 950];
            const duration = durations[index] || 770;

            spinner.style.animation = 'none';
            spinner.style.transition = 'none';
            spinner.style.transform = 'translate3d(0, ' + currentY + 'px, 0)';
            void spinner.offsetHeight;

            spinner.style.transition = 'transform ' + duration + 'ms cubic-bezier(.12,.82,.18,1)';
            spinner.style.transform = 'translate3d(0, ' + targetY + 'px, 0)';

            await this.wait(duration + 35);
            spinner.style.transition = 'none';
            spinner.style.transform = 'translate3d(0, ' + targetY + 'px, 0)';
            this.st[index] = 'idle';
            this.settled = index + 1;
            this.sfx('stop', index);
        },
        startSpinning() {
            this.reelSpinners().forEach((spinner, index) => {
                spinner.style.transition = 'none';
                spinner.style.transform = 'translate3d(0, 0, 0)';
                spinner.style.animation = 'slot-scroll var(--spin-speed-' + index + ', .52s) linear infinite';
            });
        },
        async finishSpin() {
            this.syncServerResult();

            for (let i = 0; i < 5; i++) {
                this.st[i] = 'settle';
                await this.settleReel(i);
                if (i < 4) await this.wait(115);
            }

            this.done = true;
        },
        async confirmBonusPurchase() {
            const multiplier = Number(this.bonusConfirmation?.multiplier || 0);
            this.bonusConfirmation = null;

            if (multiplier > 0) {
                await this.buyBonus(multiplier);
            }
        },

        async buyBonus(multiplier) {
            if (this.busy || this.bonusRunning) return;

            const option = this.bonusOption(multiplier);
            const baseBet = Number(this.$wire.bet || 0);
            const spins = Number(option?.spins || 0);
            const cost = baseBet * Number(multiplier);

            if (!option || !Number.isInteger(baseBet) || baseBet < 1 || spins < 1 || cost > this.maxBet) {
                return;
            }

            const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
            const duration = calm ? 120 : Math.max(5200, 4300 + (spins * 190));
            const startedAt = performance.now();
            const w = this.$wire;

            this.busy = true;
            this.bonusRunning = true;
            this.bonusDone = false;
            this.bonusMultiplier = Number(multiplier);
            this.bonusSpins = spins;
            this.bonusProgress = 0;
            this.bonusCost = cost;
            this.bonusPayout = 0;
            this.bonusProfit = 0;
            this.done = false;
            this.overlay = false;
            this.settled = 0;
            this.st = [0, 0, 0, 0, 0].map(() => 'spin');
            this.resetReels();
            this.startSpinning();
            this.sfx('spin');

            const progressTimer = window.setInterval(() => {
                const progress = Math.min(1, (performance.now() - startedAt) / duration);
                this.bonusProgress = Math.min(spins, Math.floor(progress * (spins + 0.8)));
            }, 90);

            const backend = (async () => {
                try {
                    await w.buyBonus(Number(multiplier));

                    if (w.roundPhase !== 'in_progress' || !w.roundResult?.settlement_pending) {
                        return false;
                    }

                    this.bonusProgress = spins;
                    this.syncServerResult();

                    return true;
                } catch (e) {
                    return false;
                }
            })();

            await this.wait(duration);
            const ok = await backend;
            window.clearInterval(progressTimer);

            if (!ok) {
                this.st = [0, 0, 0, 0, 0].map(() => 'idle');
                this.bonusRunning = false;
                this.bonusDone = false;
                this.bonusProgress = 0;
                this.done = true;
                this.busy = false;
                this.resetReels();
                return;
            }

            this.bonusProgress = spins;
            await this.finishSpin();

            this.bonusRunning = true;

            const result = w.roundResult || {};
            this.bonusPayout = Number(result.bonus_payout ?? 0);
            this.bonusProfit = Number(result.bonus_profit ?? (this.bonusPayout - this.bonusCost));

            // O bónus já terminou visualmente, mas o payout só fica disponível
            // depois da liquidação autoritativa do servidor.
            this.$dispatch('casino-toast', {
                type: 'info',
                title: 'Resultado pronto',
                message: 'Aguardando a liquidação segura do payout…'
            });
        },
        async selectSlot(key) {
            const allowed = @js(array_keys($slotVariants));

            if (this.busy || !allowed.includes(key) || key === this.selectedSlot) return;

            this.selectedSlot = key;

            const url = new URL(window.location.href);
            url.searchParams.set('slot', key);
            window.history.replaceState({}, '', url);

            await this.$wire.selectSlot(key);
            this.buildReelTracks();
        },
        step(d) {
            const current = Number(this.$wire.bet || 1);
            const next = Math.round(current + d);

            this.$wire.bet = Math.min(this.maxBet, Math.max(1, next));
        },
        async go() {
            if (this.busy) return;

            const w = this.$wire;
            const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
            const minSpinMs = calm ? 120 : 2200;
            const startedAt = Date.now();

            this.busy = true;
            this.bonusRunning = false;
            this.bonusDone = false;
            this.done = false;
            this.overlay = false;
            this.settled = 0;
            this.prize = 0;
            this.shown = 0;
            this.tier = '';
            this.st = [0, 0, 0, 0, 0].map(() => 'spin');
            this.resetReels();
            this.startSpinning();
            this.sfx('spin');

            const backend = (async () => {
                try {
                    if (w.roundPhase !== 'prepared') await w.prepare();
                    if (w.roundPhase !== 'prepared') return false;
                    await w.spin();
                    return w.roundPhase === 'in_progress' && !!w.roundResult?.settlement_pending;
                } catch (e) {
                    return false;
                }
            })();

            await this.wait(Math.max(0, minSpinMs - (Date.now() - startedAt)));
            const ok = await backend;

            if (!ok) {
                this.st = [0, 0, 0, 0, 0].map(() => 'idle');
                this.done = true;
                this.busy = false;
                this.resetReels();
                return;
            }

            await this.finishSpin();

            const p = Number(w.roundPayout || 0);

            // O payout é libertado pelo evento casino-round-result depois da
            // animação e da liquidação server-side.
            void p;
        },
        settleVisualResult(event) {
            if (this.$wire.roundPhase !== 'completed') return;

            const result = this.$wire.roundResult || {};
            this.busy = false;

            if (result.bonus_buy) {
                this.bonusRunning = false;
                this.bonusDone = true;
                this.bonusPayout = Number(result.bonus_payout ?? this.$wire.roundPayout ?? 0);
                this.bonusProfit = Number(result.bonus_profit ?? (this.bonusPayout - this.bonusCost));
                this.overlay = true;

                if (this.bonusProfit > 0) {
                    this.sfx(this.bonusProfit >= this.bonusCost * 5 ? 'slotJackpot' : 'slotWin');
                }

                return;
            }

            const amount = Number(event.detail?.amount ?? this.$wire.roundPayout ?? 0);
            if (amount > 0) {
                this.win(amount, Number(this.$wire.bet || 1), false);
            }
        },
        win(p, b, notify = true) {
            const m = b > 0 ? p / b : 0;
            this.tier = m >= 15 ? 'Mega vitória' : m >= 5 ? 'Grande vitória' : 'Vitória';
            this.prize = p;
            this.shown = 0;
            this.overlay = true;

            this.sfx(m >= 5 ? 'slotJackpot' : 'slotWin');

            const t0 = performance.now();
            const tick = (t) => {
                const k = Math.min(1, (t - t0) / 1100);
                this.shown = Math.round(p * (1 - Math.pow(1 - k, 3)));
                if (k < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);

            setTimeout(() => { this.overlay = false; }, 3400);

            if (notify) {
                this.$dispatch('casino-toast', {
                    title: 'Vitória!',
                    message: '+' + p + ' créditos virtuais'
                });

                if (m >= 10) {
                    this.$dispatch('casino-big-win', { amount: p });
                }
            }
        }
     }"
     x-init="buildReelTracks()"
     x-on:casino-round-result.window="settleVisualResult($event)">
    <div class="slot-page-main">
        <section class="slot-collection" aria-label="Escolher máquina de Slots">
        <div class="slot-collection__head">
            <div>
                <p class="casino-eyebrow">SLOTS ORIGINALS</p>
                <h2 class="slot-collection__title">Escolhe a tua máquina</h2>
                <p class="slot-collection__subtitle">Seis máquinas com símbolos, prémios e identidade própria. Todas usam a mesma base de créditos virtuais.</p>
            </div>
            <span class="slot-collection__count">6 JOGOS</span>
        </div>

        <div class="slot-collection__grid">
            @foreach ($slotVariants as $key => $variant)
                <button type="button"
                        class="slot-variant-card slot-variant-card--{{ $key }}"
                        :class="{ 'is-active': selectedSlot === '{{ $key }}' }"
                        x-on:click="selectSlot('{{ $key }}')"
                        :aria-pressed="selectedSlot === '{{ $key }}'"
                        :disabled="busy || @js($locked)"
                        wire:key="slot-variant-{{ $key }}">
                    <span class="slot-variant-card__glow" aria-hidden="true"></span>
                    <span class="slot-variant-card__icon" aria-hidden="true">{{ $variant['icon'] }}</span>
                    <span class="slot-variant-card__body">
                        <strong>{{ $variant['name'] }}</strong>
                        <small>{{ $variant['tag'] }}</small>
                        <em>{{ $variant['description'] }}</em>
                    </span>
                    <span class="slot-variant-card__state" x-show="selectedSlot === '{{ $key }}'">A JOGAR</span>
                </button>
            @endforeach
        </div>
    </section>

    <x-casino.how-it-works game-key="slots" title="Como funcionam as Slots?" description="Grelha 5×5 com 10 linhas de pagamento: 5 horizontais e 5 verticais. Cinco símbolos iguais numa linha pagam." :rules="[['title'=>'Escolhe a aposta','text'=>'Aposta qualquer valor inteiro positivo dentro do saldo.'], ['title'=>'Gira os rolos','text'=>'Os símbolos passam continuamente pelos cinco níveis visíveis.'], ['title'=>'10 linhas pagam','text'=>'Existem 5 linhas horizontais e 5 linhas verticais.'], ['title'=>'5 iguais pagam','text'=>'Uma linha só vence quando os cinco símbolos dessa linha são iguais. Várias linhas vencedoras acumulam.']]" />

<style>
        .slot-page-main{min-width:0}
        .slot-collection{position:relative;margin-bottom:.15rem}
        .slot-collection__head{display:flex;align-items:end;justify-content:space-between;gap:1rem;margin-bottom:.8rem}
        .slot-collection__title{margin-top:.15rem;font-size:clamp(1.25rem,3vw,1.8rem);font-weight:950;letter-spacing:-.02em;color:#f4f6f4}
        .slot-collection__subtitle{margin-top:.3rem;max-width:48rem;font-size:.78rem;line-height:1.5;color:#77847e}
        .slot-collection__count{padding:.42rem .7rem;border:1px solid rgba(242,193,78,.18);border-radius:9999px;background:rgba(242,193,78,.045);color:#bfae78;font-size:.58rem;font-weight:950;letter-spacing:.14em;white-space:nowrap}
        .slot-collection__grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.55rem}
        .slot-variant-card{position:relative;display:flex;align-items:center;gap:.65rem;min-width:0;overflow:hidden;padding:.72rem .7rem;text-align:left;border:1px solid rgba(255,255,255,.08);border-radius:1rem;background:linear-gradient(145deg,#111816,#0a0f0e);color:#fff;cursor:pointer;transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease}
        .slot-variant-card:hover{transform:translateY(-2px);border-color:rgba(242,193,78,.3)}
        .slot-variant-card.is-active{border-color:rgba(242,193,78,.72);box-shadow:0 10px 28px rgba(0,0,0,.25),0 0 24px rgba(242,193,78,.1)}
        .slot-variant-card__glow{position:absolute;inset:0;pointer-events:none;opacity:.5;background:radial-gradient(circle at 15% 20%,rgba(242,193,78,.12),transparent 55%)}
        .slot-variant-card--neon .slot-variant-card__glow{background:radial-gradient(circle at 15% 20%,rgba(34,211,238,.22),transparent 58%),radial-gradient(circle at 90% 80%,rgba(217,70,239,.12),transparent 50%)}
        .slot-variant-card--gems .slot-variant-card__glow{background:radial-gradient(circle at 15% 20%,rgba(56,189,248,.18),transparent 58%),radial-gradient(circle at 90% 80%,rgba(99,102,241,.15),transparent 50%)}
        .slot-variant-card--candy .slot-variant-card__glow{background:radial-gradient(circle at 15% 20%,rgba(244,114,182,.2),transparent 58%),radial-gradient(circle at 90% 80%,rgba(251,191,36,.15),transparent 50%)}
        .slot-variant-card--space .slot-variant-card__glow{background:radial-gradient(circle at 15% 20%,rgba(129,140,248,.22),transparent 58%),radial-gradient(circle at 90% 80%,rgba(34,211,238,.12),transparent 50%)}
        .slot-variant-card--wild .slot-variant-card__glow{background:radial-gradient(circle at 15% 20%,rgba(34,197,94,.18),transparent 58%),radial-gradient(circle at 90% 80%,rgba(245,158,11,.13),transparent 50%)}
        .slot-variant-card__icon{position:relative;z-index:1;display:grid;place-items:center;flex:0 0 2.6rem;width:2.6rem;height:2.6rem;border-radius:.8rem;background:rgba(255,255,255,.045);font-size:1.35rem;box-shadow:inset 0 1px 0 rgba(255,255,255,.06)}
        .slot-variant-card__body{position:relative;z-index:1;display:grid;min-width:0}
        .slot-variant-card__body strong{font-size:.72rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .slot-variant-card__body small{margin-top:.1rem;color:#a39469;font-size:.48rem;font-weight:900;letter-spacing:.1em}
        .slot-variant-card__body em{display:none;margin-top:.18rem;color:#6d7873;font-size:.54rem;line-height:1.35;font-style:normal}
        .slot-variant-card__state{position:absolute;right:.45rem;bottom:.38rem;z-index:2;color:#f2c14e;font-size:.43rem;font-weight:950;letter-spacing:.08em}
        .slot-title__variant{display:block;margin-top:.18rem;color:#77847d;font-size:.5em;font-weight:800;letter-spacing:.12em;text-transform:uppercase}

        .slot-theme-classic{--theme1:#f2c14e;--theme2:#23d99a}
        .slot-theme-neon{--theme1:#22d3ee;--theme2:#d946ef}
        .slot-theme-gems{--theme1:#38bdf8;--theme2:#818cf8}
        .slot-theme-candy{--theme1:#f472b6;--theme2:#fbbf24}
        .slot-theme-space{--theme1:#818cf8;--theme2:#22d3ee}
        .slot-theme-wild{--theme1:#22c55e;--theme2:#f59e0b}
        .slot-theme-neon .slot-cabinet{background:radial-gradient(120% 65% at 50% 0,rgba(34,211,238,.16),transparent 60%),linear-gradient(160deg,#07131a,#090b14 60%,#070a0d)}
        .slot-theme-gems .slot-cabinet{background:radial-gradient(120% 65% at 50% 0,rgba(56,189,248,.13),transparent 60%),linear-gradient(160deg,#08111a,#0b0d17 60%,#070a0d)}
        .slot-theme-candy .slot-cabinet{background:radial-gradient(120% 65% at 50% 0,rgba(244,114,182,.13),transparent 60%),linear-gradient(160deg,#180b15,#120c12 60%,#09090c)}
        .slot-theme-space .slot-cabinet{background:radial-gradient(120% 65% at 50% 0,rgba(129,140,248,.17),transparent 60%),linear-gradient(160deg,#090d1c,#090b15 60%,#06080f)}
        .slot-theme-wild .slot-cabinet{background:radial-gradient(120% 65% at 50% 0,rgba(34,197,94,.12),transparent 60%),linear-gradient(160deg,#09160f,#0b100d 60%,#070a0b)}
        .slot-theme-neon .slot-title em,.slot-theme-space .slot-title em{color:var(--theme1)}
        .slot-theme-neon .slot-window{box-shadow:inset 0 0 40px #000,0 0 0 2px rgba(34,211,238,.45),0 0 0 6px rgba(217,70,239,.1)}
        .slot-theme-gems .slot-window{box-shadow:inset 0 0 40px #000,0 0 0 2px rgba(56,189,248,.45),0 0 0 6px rgba(129,140,248,.1)}
        .slot-theme-candy .slot-window{box-shadow:inset 0 0 40px #000,0 0 0 2px rgba(244,114,182,.42),0 0 0 6px rgba(251,191,36,.1)}
        .slot-theme-space .slot-window{box-shadow:inset 0 0 40px #000,0 0 0 2px rgba(129,140,248,.45),0 0 0 6px rgba(34,211,238,.1)}
        .slot-theme-wild .slot-window{box-shadow:inset 0 0 40px #000,0 0 0 2px rgba(34,197,94,.4),0 0 0 6px rgba(245,158,11,.1)}
        .slot-theme-neon .slot-prize__card,.slot-theme-space .slot-prize__card{background:linear-gradient(145deg,rgba(5,14,20,.98),rgba(22,14,38,.98));border-color:rgba(34,211,238,.72)}
        .slot-theme-gems .slot-prize__card{background:linear-gradient(145deg,rgba(7,15,22,.98),rgba(16,15,38,.98));border-color:rgba(56,189,248,.72)}
        .slot-theme-candy .slot-prize__card{background:linear-gradient(145deg,rgba(27,10,20,.98),rgba(38,21,8,.98));border-color:rgba(244,114,182,.72)}
        .slot-theme-wild .slot-prize__card{background:linear-gradient(145deg,rgba(8,19,13,.98),rgba(37,28,8,.98));border-color:rgba(34,197,94,.72)}
        @media (max-width:1100px){.slot-collection__grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media (max-width:640px){.slot-collection__head{align-items:start;flex-direction:column}.slot-collection__grid{grid-template-columns:repeat(2,minmax(0,1fr))}.slot-variant-card{padding:.6rem}.slot-variant-card__icon{flex-basis:2.2rem;width:2.2rem;height:2.2rem}.slot-variant-card__body em{display:block}}
        .slot-header__status {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .38rem .65rem;
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 9999px;
            background: rgba(255,255,255,.03);
            color: #87928d;
            font-size: .55rem;
            font-weight: 950;
            letter-spacing: .13em;
        }
        .slot-header__status i {
            width: .4rem;
            height: .4rem;
            border-radius: 50%;
            background: #6b7771;
        }
        .slot-header__status.is-spinning {
            border-color: rgba(242,193,78,.3);
            color: #f5d778;
            box-shadow: 0 0 18px rgba(242,193,78,.07);
        }
        .slot-header__status.is-spinning i {
            background: #f2c14e;
            box-shadow: 0 0 10px rgba(242,193,78,.8);
            animation: slot-status-pulse .65s ease-in-out infinite alternate;
        }
        .slot-header__status.is-ready i { background: #65d6a3; }

        @keyframes slot-status-pulse { from { transform: scale(.75); opacity: .55; } to { transform: scale(1.15); opacity: 1; } }

        .slot-machine {
            --cell: clamp(2.6rem, 5.1vw, 4rem);
            --gold: #f2c14e;
            --gold-hi: #ffe39a;
        }
        .allin-slots { --green: #23d99a; --line: rgba(255,255,255,.08); }

        .slot-cabinet {
            position: relative;
            overflow: hidden;
            padding: 1rem;
            border-radius: 1.75rem;
            border: 1px solid rgba(242,193,78,.3);
            background: radial-gradient(120% 60% at 50% 0, rgba(35,217,154,.12), transparent 60%), linear-gradient(160deg,#0b1714,#0c0f12 60%,#070a0b);
            box-shadow: 0 40px 80px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.07);
        }
        .slot-cabinet .casino-bulbs { inset: 5px; border-width: 3px; opacity: .5; }

        .slot-header {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: 1rem;
        }
        .slot-title { font-size: clamp(1.05rem,2.6vw,1.45rem); font-weight: 900; letter-spacing: .09em; text-transform: uppercase; }
        .slot-title em { font-style: normal; color: var(--gold); }
        .slot-badge { padding: .35rem .7rem; border: 1px solid rgba(242,193,78,.25); border-radius: 9999px; font-size: .7rem; color: #cdb878; }

        .slot-window {
            position: relative;
            display: block;
            width: 100%;
            height: calc((var(--cell) * 5) + 1.4rem);
            overflow: hidden;
            padding: .7rem;
            border-radius: 1.2rem;
            background: #030706;
            box-shadow: inset 0 0 40px #000, 0 0 0 2px rgba(242,193,78,.45), 0 0 0 6px rgba(242,193,78,.1);
        }
        .slot-reels {
            position: relative;
            display: grid;
            grid-template-columns: repeat(5,minmax(0,1fr));
            grid-template-rows: repeat(5,var(--cell));
            gap: .4rem;
            width: 100%;
            height: calc(var(--cell) * 5);
            min-width: 0;
            overflow: hidden;
        }
        .slot-reel {
            position: relative;
            overflow: hidden;
            height: calc(var(--cell) * 5);
            min-width: 0;
            border-radius: .75rem;
            background: linear-gradient(180deg,#19221f,#0a0f0e);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.07);
        }
        .slot-reel::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 3;
            pointer-events: none;
            background: linear-gradient(180deg,rgba(0,0,0,.5),transparent 22%,transparent 78%,rgba(0,0,0,.58));
        }
        .slot-sym {
            display: grid;
            place-items: center;
            width: 100%;
            height: var(--cell);
            font-size: clamp(1.65rem,3.45vw,2.65rem);
            line-height: 1;
            filter: drop-shadow(0 5px 6px rgba(0,0,0,.45));
            user-select: none;
            transform: translateZ(0);
        }

        .slot-spinner {
            display: grid;
            grid-template-columns: 1fr;
            width: 100%;
            will-change: transform;
        }

        .slot-reels::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: calc(var(--cell) * 2);
            height: var(--cell);
            z-index: 5;
            pointer-events: none;
            border-top: 1px solid rgba(242,193,78,.13);
            border-bottom: 1px solid rgba(242,193,78,.13);
            background: linear-gradient(180deg,rgba(255,255,255,.02),rgba(242,193,78,.045) 50%,rgba(255,255,255,.02));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.025), inset 0 -1px 0 rgba(255,255,255,.025);
        }
        .slot-reels::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 4;
            pointer-events: none;
            background: linear-gradient(180deg,rgba(0,0,0,.26),transparent 20%,transparent 80%,rgba(0,0,0,.36));
        }

        .slot-reel.is-spin .slot-spinner {
            animation: slot-scroll var(--spin-speed,.52s) linear infinite;
            filter: blur(1.7px);
        }
        .slot-reel.is-settle .slot-spinner {
            filter: blur(.5px);
        }
        .slot-reel.is-settle::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 4;
            pointer-events: none;
            background: linear-gradient(180deg,transparent 28%,rgba(242,193,78,.08) 46%,rgba(255,255,255,.11) 50%,rgba(242,193,78,.08) 54%,transparent 72%);
            animation: slot-lock-flash .56s ease-out both;
        }
        .slot-reel:nth-child(1) { --spin-speed:.54s; }
        .slot-reel:nth-child(2) { --spin-speed:.49s; }
        .slot-reel:nth-child(3) { --spin-speed:.45s; }
        .slot-reel:nth-child(4) { --spin-speed:.41s; }
        .slot-reel:nth-child(5) { --spin-speed:.38s; }
        
        @keyframes slot-scroll {
            from { transform: translate3d(0,0,0); }
            to { transform: translate3d(0,calc(var(--cell) * -8),0); }
        }
        @keyframes slot-lock-flash {
            0% { opacity:0; transform:scaleY(.7); }
            30% { opacity:1; }
            100% { opacity:0; transform:scaleY(1); }
        }

        .slot-payline {
            position: absolute; left: -.3rem; right: -.3rem; top: calc((var(--row) + .5) * var(--cell)); z-index: 4;
            height: 3px; border-radius: 2px; opacity: 0; pointer-events: none;
            background: linear-gradient(90deg, transparent, var(--gold), #fff, var(--gold), transparent);
            box-shadow: 0 0 14px rgba(242, 193, 78, .9);
        }
        .slot-payline--vertical {
            top: -.3rem; bottom: -.3rem; left: calc((var(--col) + .5) * 20%); right: auto;
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

        .slot-bonus-confirm-backdrop{
            position:fixed;inset:0;z-index:90;display:grid;place-items:center;padding:1rem;
            background:rgba(1,4,3,.74);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);
        }
        .slot-bonus-confirm{
            width:min(100%,29rem);padding:1.35rem;border:1px solid rgba(242,193,78,.34);
            border-radius:1.35rem;background:linear-gradient(145deg,#121a17,#090f0d);
            box-shadow:0 30px 90px rgba(0,0,0,.62),0 0 50px rgba(242,193,78,.08);
        }
        .slot-bonus-confirm__icon{
            display:grid;place-items:center;width:2.35rem;height:2.35rem;border-radius:.75rem;
            border:1px solid rgba(242,193,78,.25);background:rgba(242,193,78,.07);
            color:#f5d778;font-size:1.05rem;
        }
        .slot-bonus-confirm h2{margin-top:.5rem;font-size:1.2rem;font-weight:950;color:#f3f6f4}
        .slot-bonus-confirm__copy{margin-top:.45rem;font-size:.72rem;line-height:1.55;color:#89968f}
        .slot-bonus-confirm__copy strong{color:#f4d17b}
        .slot-bonus-confirm__summary{display:grid;grid-template-columns:repeat(3,1fr);gap:.45rem;margin-top:1rem}
        .slot-bonus-confirm__summary span{padding:.65rem .55rem;border:1px solid rgba(255,255,255,.07);border-radius:.75rem;background:rgba(255,255,255,.025)}
        .slot-bonus-confirm__summary small{display:block;color:#6f7c75;font-size:.5rem;text-transform:uppercase;letter-spacing:.08em}
        .slot-bonus-confirm__summary b{display:block;margin-top:.18rem;color:#e8eee9;font-size:.74rem;font-weight:900}
        .slot-bonus-confirm__actions{display:grid;grid-template-columns:1fr 1.2fr;gap:.55rem;margin-top:1rem}
        .slot-bonus-confirm__cancel,.slot-bonus-confirm__accept{min-height:2.8rem;border-radius:.85rem;font-size:.72rem;font-weight:950;cursor:pointer}
        .slot-bonus-confirm__cancel{border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.025);color:#a7b0ab}
        .slot-bonus-confirm__accept{border:1px solid rgba(242,193,78,.55);background:linear-gradient(180deg,#ffe9aa,#dcae3c);color:#261b07;box-shadow:0 8px 20px rgba(211,164,48,.18)}
        .slot-bonus-confirm__note{display:block;margin-top:.75rem;color:#68756e;font-size:.53rem;line-height:1.45}
        @media(max-width:520px){.slot-bonus-confirm__summary{grid-template-columns:1fr}.slot-bonus-confirm__actions{grid-template-columns:1fr}}
        
        .slot-bonus-buy {
            position: relative;
            margin-top: .8rem;
            padding: .9rem;
            border: 1px solid rgba(242,193,78,.17);
            border-radius: 1rem;
            background: linear-gradient(145deg, rgba(242,193,78,.045), rgba(255,255,255,.018));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.025);
        }
        .slot-bonus-buy__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .8rem;
        }
        .slot-bonus-buy__head>div { display: grid; gap: .12rem; }
        .slot-bonus-buy__head>div>strong { color: #e7dfc8; font-size: .76rem; font-weight: 900; }
        .slot-bonus-buy__head>div>small { color: #748078; font-size: .56rem; line-height: 1.45; }
        .slot-bonus-buy__status {
            flex: none;
            padding: .35rem .55rem;
            border: 1px solid rgba(242,193,78,.14);
            border-radius: 9999px;
            color: #a59568;
            font-size: .46rem;
            font-weight: 950;
            letter-spacing: .12em;
        }
        .slot-bonus-buy__status.is-running {
            border-color: rgba(242,193,78,.34);
            color: #f2c14e;
            box-shadow: 0 0 20px rgba(242,193,78,.06);
            animation: slot-bonus-status 1s ease-in-out infinite alternate;
        }
        .slot-bonus-buy__meter {
            position: relative;
            height: .24rem;
            margin-top: .8rem;
            overflow: hidden;
            border-radius: 9999px;
            background: rgba(255,255,255,.06);
        }
        .slot-bonus-buy__meter span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #a87516, #f2c14e, #ffe39a);
            box-shadow: 0 0 12px rgba(242,193,78,.45);
            transition: width .12s linear;
        }
        .slot-bonus-buy__options {
            display: grid;
            grid-template-columns: repeat(3,minmax(0,1fr));
            gap: .5rem;
            margin-top: .7rem;
        }
        .slot-bonus-option {
            display: grid;
            gap: .5rem;
            min-width: 0;
            padding: .72rem;
            text-align: left;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: .8rem;
            background: rgba(255,255,255,.025);
            color: #dce3df;
            cursor: pointer;
            transition: transform .15s ease,border-color .15s ease,background .15s ease,box-shadow .15s ease;
        }
        .slot-bonus-option:hover:not(:disabled) {
            transform: translateY(-2px);
            border-color: rgba(242,193,78,.42);
            background: rgba(242,193,78,.06);
            box-shadow: 0 10px 22px rgba(0,0,0,.18);
        }
        .slot-bonus-option:disabled { opacity: .42; cursor: not-allowed; }
        .slot-bonus-option>span { display: grid; gap: .16rem; min-width: 0; }
        .slot-bonus-option b { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .65rem; font-weight: 900; color: #ece7d8; }
        .slot-bonus-option span small { color: #738078; font-size: .52rem; }
        .slot-bonus-option>strong { display: flex; align-items: baseline; justify-content: space-between; gap: .35rem; color: #f2c14e; font-size: .78rem; font-weight: 950; }
        .slot-bonus-option>strong small { color: #88784e; font-size: .45rem; font-weight: 800; }
        .slot-bonus-result { width: min(92%, 30rem); }
        .slot-bonus-result>strong { color: #ffe39a; }
        .slot-bonus-result__stats {
            display: grid;
            grid-template-columns: repeat(3,1fr);
            gap: .45rem;
            margin-top: .9rem;
        }
        .slot-bonus-result__stats>span {
            display: grid;
            gap: .15rem;
            padding: .55rem;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: .65rem;
            background: rgba(255,255,255,.025);
        }
        .slot-bonus-result__stats b { color: #e9eee9; font-size: .72rem; font-variant-numeric: tabular-nums; }
        .slot-bonus-result__stats small { color: #77827c; font-size: .48rem; }
        .slot-bonus-result__close {
            margin-top: .9rem;
            min-height: 2.25rem;
            padding: .45rem 1rem;
            border: 1px solid rgba(242,193,78,.36);
            border-radius: .7rem;
            background: rgba(242,193,78,.07);
            color: #f5d778;
            font-size: .62rem;
            font-weight: 850;
            cursor: pointer;
        }
        .slot-bonus-result__close:hover { background: rgba(242,193,78,.12); }
        @keyframes slot-bonus-status { from { opacity:.55; transform:scale(.97); } to { opacity:1; transform:scale(1.02); } }
        @keyframes slot-scroll {
            0% { transform: translate3d(0,0,0); filter: blur(0); }
            50% { filter: blur(1.5px); }
            100% { transform: translate3d(0,-50%,0); filter: blur(.7px); }
        }
        .slot-reel.is-spin .slot-spinner { will-change: transform; }
        .slot-reel.is-spin:nth-child(1) .slot-spinner { --spin-speed-0: .42s; }
        .slot-reel.is-spin:nth-child(2) .slot-spinner { --spin-speed-1: .48s; }
        .slot-reel.is-spin:nth-child(3) .slot-spinner { --spin-speed-2: .54s; }
        .slot-reel.is-spin:
        .slot-payout { font-size: 1.9rem; font-weight: 800; line-height: 1.1; }
        .slot-payout--win { color: var(--gold); text-shadow: 0 0 18px rgba(242, 193, 78, .5); }

        @media (max-width: 900px) { .slot-deck { grid-template-columns: 1fr 1fr; } .slot-spin { grid-column: 1 / -1; } .slot-chips { justify-content: flex-start; } .slot-machine { --cell: clamp(2.35rem, 7vw, 3.4rem); } }
        @media (max-width: 560px) {
            .slot-header__status { order: 3; width: 100%; justify-content: center; }
            .slot-bonus-buy__head { align-items: flex-start; flex-direction: column; }
            .slot-bonus-buy__status { align-self: flex-start; }
            .slot-bonus-buy__options { grid-template-columns: 1fr; }
            .slot-bonus-result__stats { grid-template-columns: 1fr 1fr; }
            .slot-deck { grid-template-columns: 1fr; } .slot-spin { grid-column: auto; }
            .slot-window { padding: .55rem; height: calc((var(--cell) * 3) + 1.1rem); }
        }
        @media (prefers-reduced-motion: reduce) {
            .slot-reel.is-spin .slot-spinner, .slot-reel.is-settle .slot-spinner,
            .slot-window.is-done .slot-payline, .slot-window.is-done .slot-sym.is-win, .slot-prize__card, .slot-prize-card,
            .slot-header__status.is-spinning i { animation: none; }
            .slot-reel.is-spin .slot-spinner { filter: none; }
        }
    </style>

        <section class="slot-machine" :class="'slot-theme-' + selectedSlot">
        <div class="slot-cabinet">
            <span class="casino-bulbs" aria-hidden="true"></span>

            <div class="slot-prize-overlay" x-show="overlay" x-cloak x-transition.opacity role="status" aria-live="assertive">
                <template x-if="bonusDone">
                    <div class="slot-prize__card slot-bonus-result">
                        <p>☘ BÓNUS CONCLUÍDO</p>
                        <strong x-text="(bonusProfit >= 0 ? '+' : '') + Number(bonusProfit).toLocaleString('pt-PT')"></strong>
                        <small>lucro líquido em créditos virtuais</small>
                        <div class="slot-bonus-result__stats">
                            <span><b x-text="Number(bonusPayout).toLocaleString('pt-PT')"></b><small>retorno</small></span>
                            <span><b x-text="Number(bonusCost).toLocaleString('pt-PT')"></b><small>custo</small></span>
                            <span><b x-text="bonusSpins"></b><small>giros</small></span>
                        </div>
                        <button type="button" class="slot-bonus-result__close" x-on:click.prevent="overlay = false; bonusDone = false; bonusRunning = false">Continuar</button>
                    </div>
                </template>

                <template x-if="!bonusDone">
                    <div class="slot-prize__card">
                        <p x-text="tier"></p>
                        <strong>+<span x-text="Number(shown).toLocaleString('pt-PT')"></span></strong>
                        <small>créditos virtuais</small>
                    </div>
                </template>
            </div>

            <header class="slot-header">
                <h1 class="slot-title">
                    Allinbet <em class="casino-shimmer-text">Slots</em>
                    <span class="slot-title__variant" x-text="@js($slotVariants)[selectedSlot].name"></span>
                </h1>

                <div class="slot-header__status" :class="{ 'is-spinning': busy, 'is-ready': !busy }">
                    <i></i>
                    <span x-show="!busy">PRONTO</span>
                    <span x-show="busy && $wire.roundResult?.settlement_pending" x-cloak>A LIQUIDAR</span>
                    <span x-show="busy && !$wire.roundResult?.settlement_pending" x-cloak x-text="settled === 0 ? 'A GIRAR' : 'A PARAR ' + settled + '/5'"></span>
                </div>

                <span class="slot-badge" x-text="@js($slotVariants)[selectedSlot].tag"></span>
            </header>

            <div class="slot-window"
                 :class="{
                    'is-done': done,
                    'has-win': done && winningLines.length > 0,
                    'is-spinning': busy
                 }">
                <div class="slot-reels" wire:ignore role="img" aria-label="Grelha de Slots 5 por 5, com cinco rolos e cinco linhas">
                    <template x-for="column in [0, 1, 2, 3, 4]" :key="'reel-' + column">
                        <div class="slot-reel" :class="'is-' + st[column]">
                            <div class="slot-spinner">
                                <template x-for="(symbol, index) in tracks[column]" :key="column + '-' + index">
                                    <span class="slot-sym"
                                          :class="{ 'is-win': done && index >= reelPrefix && index < reelPrefix + 5 && isWinningCell(index - reelPrefix, column) }"
                                          x-text="symbol"></span>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-for="row in [0, 1, 2, 3, 4]" :key="'hline-' + row">
                        <i class="slot-payline"
                           x-show="done && winningLines.some((line) => line.direction === 'horizontal' && Number(line.line) === row)"
                           :style="'--row:' + row"
                           aria-hidden="true"></i>
                    </template>

                    <template x-for="column in [0, 1, 2, 3, 4]" :key="'vline-' + column">
                        <i class="slot-payline slot-payline--vertical"
                           x-show="done && winningLines.some((line) => line.direction === 'vertical' && Number(line.line) === column)"
                           :style="'--col:' + column"
                           aria-hidden="true"></i>
                    </template>
                </div>
            </div>

            <div class="slot-deck">
                <div>
                    <span class="slot-label">Aposta total</span>
                    <div class="slot-stepper">
                        <button type="button" x-on:click="step(-1)" :disabled="busy || @js($locked)" aria-label="Diminuir aposta">−</button>
                        <input type="number" min="1" step="1" max="{{ $maxBet }}" wire:model="bet" :disabled="busy || @js($locked)" class="slot-bet-input" aria-label="Aposta total em créditos">
                        <button type="button" x-on:click="step(1)" :disabled="busy || @js($locked)" aria-label="Aumentar aposta">+</button>
                    </div>
                    <small class="slot-hint">Aposta total · 10 linhas de pagamento</small>
                </div>

                <div class="slot-chips" aria-label="Apostas rápidas">
                    @foreach ([1, 5, 10, 25, 50, 100] as $chip)
                        @if ($chip <= $maxBet)
                            <button type="button" x-on:click="$wire.bet = {{ $chip }}" :disabled="busy || @js($locked)">{{ $chip }}</button>
                        @endif
                    @endforeach
                    <button type="button" x-on:click="$wire.bet = maxBet" :disabled="busy || @js($locked) || maxBet < 1">ALL IN · {{ number_format($maxBet, 0, ',', ' ') }}</button>
                </div>

                <button type="button" class="slot-spin" x-on:click="go()" :disabled="busy">
                    <span x-text="busy ? 'A girar…' : 'Girar'"></span>
                    <small>ou barra de espaço</small>
                </button>
            </div>

            <section class="slot-bonus-buy" aria-label="Comprar bónus de giros automáticos">
                <div class="slot-bonus-buy__head">
                    <div>
                        <span class="slot-label">BÓNUS BUY</span>
                        <strong>Compra uma sequência automática</strong>
                        <small>O valor é cobrado uma vez e os giros decorrem automaticamente.</small>
                    </div>
                    <span class="slot-bonus-buy__status" x-show="!bonusRunning && !bonusDone">CRÉDITOS VIRTUAIS</span>
                    <span class="slot-bonus-buy__status is-running" x-show="bonusRunning" x-cloak x-text="'BÓNUS ' + bonusProgress + '/' + bonusSpins"></span>
                </div>

                <div class="slot-bonus-buy__meter" x-show="bonusRunning" x-cloak>
                    <span :style="'width:' + (bonusSpins ? ((bonusProgress / bonusSpins) * 100) : 0) + '%'"></span>
                </div>

                <div class="slot-bonus-buy__options">
                    @foreach (config('casino.games.slots.bonus_buy.options', []) as $option)
                        <button type="button"
                                class="slot-bonus-option"
                                :disabled="busy || bonusRunning || (Number($wire.bet || 0) * {{ (int) $option['multiplier'] }}) > maxBet || Number($wire.bet || 0) < 1"
                                x-on:click="bonusConfirmation = bonusOption({{ (int) $option['multiplier'] }})">
                            <span>
                                <b>{{ $option['label'] }}</b>
                                <small>{{ (int) $option['spins'] }} giros automáticos</small>
                            </span>
                            <strong>
                                <span x-text="Number($wire.bet || 0) * {{ (int) $option['multiplier'] }}"></span> CR
                                <small>{{ (int) $option['multiplier'] }}× aposta</small>
                            </strong>
                        </button>
                    @endforeach
                </div>
            </section>

            @error('bet')<p role="alert" class="slot-error">{{ $message }}</p>@enderror
            @error('game')<p role="alert" class="slot-error">{{ $message }}</p>@enderror
        </div>
        </section>
    </div>

    <div x-cloak x-show="bonusConfirmation" x-transition.opacity class="slot-bonus-confirm-backdrop"
         x-on:click.self="bonusConfirmation = null"
         x-on:keydown.escape.window="bonusConfirmation = null"
         role="dialog"
         aria-modal="true"
         aria-labelledby="slot-bonus-confirm-title">
        <div class="slot-bonus-confirm" x-show="bonusConfirmation" x-transition.scale.95>
            <div class="slot-bonus-confirm__icon" aria-hidden="true">✦</div>
            <p class="slot-label">CONFIRMAR COMPRA</p>
            <h2 id="slot-bonus-confirm-title">Comprar <span x-text="bonusConfirmation?.label || 'Bónus'"></span>?</h2>
            <p class="slot-bonus-confirm__copy">
                Vais gastar <strong x-text="Number(($wire.bet || 0) * Number(bonusConfirmation?.multiplier || 0)).toLocaleString('pt-PT')"></strong>
                créditos virtuais para <strong x-text="bonusConfirmation?.spins || 0"></strong> giros automáticos.
            </p>

            <div class="slot-bonus-confirm__summary">
                <span><small>Aposta base</small><b x-text="Number($wire.bet || 0).toLocaleString('pt-PT') + ' CR'"></b></span>
                <span><small>Preço</small><b x-text="Number(($wire.bet || 0) * Number(bonusConfirmation?.multiplier || 0)).toLocaleString('pt-PT') + ' CR'"></b></span>
                <span><small>Giros</small><b x-text="bonusConfirmation?.spins || 0"></b></span>
            </div>

            <div class="slot-bonus-confirm__actions">
                <button type="button" class="slot-bonus-confirm__cancel" x-on:click="bonusConfirmation = null">Cancelar</button>
                <button type="button" class="slot-bonus-confirm__accept" x-on:click="confirmBonusPurchase()">Confirmar compra</button>
            </div>
            <small class="slot-bonus-confirm__note">A cobrança acontece uma única vez e o resultado dos giros é gerado no servidor.</small>
        </div>
    </div>

    <aside class="space-y-4">
        <div class="casino-card" aria-live="polite">
            <p class="casino-eyebrow">ÚLTIMA RONDA</p>
            <p x-show="!done" class="mt-2 text-sm text-zinc-400">Os rolos estão a girar…</p>

            <div x-show="done" x-cloak class="mt-2">
                @if ($roundResult !== [])
                    @if ($payout > 0)
                        <p class="slot-payout slot-payout--win">+{{ number_format($payout) }}</p>
                        <p class="text-sm text-zinc-400">
                            {{ count($winningLines) }} linha(s) vencedora(s) · 3+ símbolos consecutivos
                        </p>
                    @else
                        <p class="slot-payout">Sem prémio</p>
                        <p class="text-sm text-zinc-400">É necessário formar pelo menos 3 símbolos consecutivos na linha.</p>
                    @endif

                    @if ($roundPhase === 'completed' && $roundId)
                        <button type="button" class="mt-3 inline-block text-sm text-emerald-300 underline underline-offset-4 hover:text-emerald-200" x-data x-on:click="$dispatch('casino-open-fairness', { roundId: {{ $roundId }} })">Verificar esta ronda</button>
                    @endif
                @else
                    <p class="text-sm text-zinc-400">10 linhas de prémio: 5 horizontais e 5 verticais.</p>
                @endif
            </div>
        </div>

        <div class="casino-card">
            <p class="casino-eyebrow">COMBINAÇÕES QUE PAGAM</p>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-zinc-300">
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">3 consecutivos</div>
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">5 horizontais</div>
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">5 verticais</div>
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">{{ $selectedSlotConfig['tag'] ?? 'ORIGINAL' }}</div>
            </div>
            <p class="mt-3 text-xs leading-5 text-zinc-500">Existem 10 linhas de pagamento: 5 horizontais e 5 verticais. 3, 4 ou 5 símbolos consecutivos podem pagar; várias linhas vencedoras acumulam.</p>
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
