@php
    $grid = $roundResult['grid'] ?? [[0, 1, 2], [3, 4, 5], [2, 1, 0]];
    $maxBet = max(0, (int) (auth()->user()?->wallet?->balance ?? 0));
    $locked = $roundPhase === 'prepared';
    $slotVariants = config('casino.games.slots.variants', []);
    $selectedSlotConfig = $slotVariants[$selectedSlot] ?? $slotVariants['classic'] ?? [];
    $symbols = $selectedSlotConfig['symbols'] ?? ['🍒', '🍋', '🍊', '🔔', '⭐', '🍀', '💎', '7️⃣'];
    $orders = [
        [0, 3, 6, 1, 4, 7, 2, 5],
        [5, 2, 7, 4, 1, 6, 3, 0],
        [2, 6, 1, 5, 0, 4, 7, 3],
    ];

    $winningLines = $roundPhase === 'completed'
        ? collect($roundResult['winning_lines'] ?? [])->values()->all()
        : [];
    $winRows = collect($winningLines)
        ->where('direction', 'horizontal')
        ->pluck('line')
        ->map(fn ($l) => (int) $l)
        ->all();
    $winCols = collect($winningLines)
        ->where('direction', 'vertical')
        ->pluck('line')
        ->map(fn ($l) => (int) $l)
        ->all();
    $payout = (int) $roundPayout;
    $paytable = $selectedSlotConfig['paytable'] ?? [];
@endphp

<div class="casino-game-play casino-game-screen slot-page" data-casino-game="slots" grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,20rem)]"
     x-data="{
        st: ['idle', 'idle', 'idle'],
        selectedSlot: @js($selectedSlot),
        busy: false,
        done: true,
        overlay: false,
        shown: 0,
        prize: 0,
        tier: '',
        maxBet: {{ $maxBet }},
        wait: (ms) => new Promise((r) => setTimeout(r, ms)),
        sfx(name) { this.$dispatch('casino-sfx', { name }); },
        async selectSlot(key) {
            const allowed = @js(array_keys($slotVariants));

            if (this.busy || !allowed.includes(key) || key === this.selectedSlot) {
                return;
            }

            this.selectedSlot = key;

            const url = new URL(window.location.href);
            url.searchParams.set('slot', key);
            window.history.replaceState({}, '', url);

            await this.$wire.selectSlot(key);
        },
        step(d) {
            const v = Math.round((Number(this.$wire.bet || 6) + d) / 6) * 6;
            this.$wire.bet = Math.min(this.maxBet, Math.max(6, v));
        },
        async go() {
            if (this.busy) return;
            const w = this.$wire;
            const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.busy = true;
            this.done = false;
            this.overlay = false;
            this.st = ['spin', 'spin', 'spin'];
            this.sfx('spin');

            const t0 = Date.now();
            let ok = false;

            try {
                if (w.roundPhase !== 'prepared') await w.prepare();
                if (w.roundPhase === 'prepared') {
                    await w.spin();
                    ok = true;
                }
            } catch (e) {}

            if (!ok) {
                this.st = ['idle', 'idle', 'idle'];
                this.done = true;
                this.busy = false;
                return;
            }

            if (!calm) {
                await this.wait(Math.max(0, 1200 - (Date.now() - t0)));
            }

            for (let i = 0; i < 3; i++) {
                this.st[i] = 'land';
                this.sfx('stop');
                if (!calm) await this.wait(380);
            }

            if (!calm) await this.wait(520);

            this.st = ['idle', 'idle', 'idle'];
            this.done = true;
            this.busy = false;

            const p = Number(w.roundPayout || 0);

            if (w.roundPhase === 'completed' && p > 0) {
                this.win(p, Number(w.bet || 1));
            }
        },
        win(p, b) {
            const m = p / b;
            this.tier = m >= 15 ? 'Mega vitória' : m >= 5 ? 'Grande vitória' : 'Vitória';
            this.prize = p;
            this.shown = 0;
            this.overlay = true;
            this.sfx(m >= 5 ? 'bigwin' : 'win');

            const t0 = performance.now();
            const tick = (t) => {
                const k = Math.min(1, (t - t0) / 1100);
                this.shown = Math.round(p * (1 - Math.pow(1 - k, 3)));
                if (k < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);

            setTimeout(() => { this.overlay = false; }, 3400);

            this.$dispatch('casino-toast', {
                title: 'Vitória!',
                message: '+' + p + ' créditos virtuais'
            });

            if (m >= 10) {
                this.$dispatch('casino-big-win', { amount: p });
            }
        }
     }"
     x-on:keydown.window="if ($event.code === 'Space' && !['INPUT','TEXTAREA','BUTTON','SUMMARY'].includes($event.target.tagName)) { $event.preventDefault(); go(); }">
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

    <x-casino.how-it-works game-key="slots" title="Como funcionam as Slots?" description="Grelha 3×3 com 6 linhas de pagamento: 3 horizontais e 3 verticais. Só três símbolos iguais na mesma linha pagam." :rules="[['title'=>'Escolhe a aposta','text'=>'A aposta total é dividida pelas 6 linhas.'], ['title'=>'Gira os rolos','text'=>'Carrega em Girar ou na barra de espaço.'], ['title'=>'6 linhas pagam','text'=>'Existem 3 linhas horizontais e 3 linhas verticais.'], ['title'=>'3 iguais pagam','text'=>'Os prémios de várias linhas vencedoras acumulam.']]" />

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
        .slot-machine { --cell: clamp(4.2rem, 16vw, 6.6rem); --gold: #f2c14e; --gold-hi: #ffe39a; }
        .allin-slots { --green: #23d99a; --line: rgba(255,255,255,.08); }

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
        .slot-sym { display: grid; place-items: center; width: 100%; height: var(--cell); font-size: clamp(2.3rem, 5.8vw, 4rem); line-height: 1; filter: drop-shadow(0 5px 6px rgba(0, 0, 0, .45)); user-select: none; }
        .slot-spinner { display: none; }
        .slot-landing { display: grid; grid-template-columns: 1fr; align-content: start; }
        .slot-reel.is-spin .slot-landing { display: none; }
        .slot-reel.is-spin .slot-spinner { display: grid; grid-template-columns: 1fr; animation: slot-loop var(--t, .5s) linear infinite; filter: blur(2.2px); }
        .slot-reel:nth-child(2) { --t: .46s; }
        .slot-reel:nth-child(3) { --t: .42s; }
        .slot-reel.is-land .slot-landing { animation: slot-land .55s cubic-bezier(.22, .8, .3, 1) both; }

        @keyframes slot-loop { from { transform: translateY(calc(var(--cell) * -8)); } to { transform: translateY(0); } }
        @keyframes slot-land {
            from { transform: translateY(calc(var(--cell) * -6)); }
            65% { transform: translateY(calc(var(--cell) * .14)); }
            82% { transform: translateY(calc(var(--cell) * -.05)); }
            to { transform: translateY(0); }
        }

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

        <section class="slot-machine" :class="'slot-theme-' + selectedSlot">
        <div class="slot-cabinet">
            <span class="casino-bulbs" aria-hidden="true"></span>

            <div class="slot-prize-overlay" x-show="overlay" x-cloak x-transition.opacity role="status" aria-live="assertive">
                <div class="slot-prize__card">
                    <p x-text="tier"></p>
                    <strong>+<span x-text="Number(shown).toLocaleString('pt-PT')"></span></strong>
                    <small>créditos virtuais</small>
                </div>
            </div>

            <header class="slot-header">
                <h1 class="slot-title">
                    Allinbet <em class="casino-shimmer-text">Slots</em>
                    <span class="slot-title__variant" x-text="@js($slotVariants)[selectedSlot].name"></span>
                </h1>
                <span class="slot-badge" x-text="@js($slotVariants)[selectedSlot].tag"></span>
            </header>

            <div class="slot-window"
                 :class="{
                    'is-done': done,
                    'has-win': done && @js(count($winningLines) > 0)
                 }">
                <div class="slot-markers" aria-hidden="true">
                    @foreach ([0, 1, 2] as $r)
                        <span :class="{ 'is-win': done && @js(in_array($r, $winRows, true)) }">{{ $r + 1 }}</span>
                    @endforeach
                </div>

                <div class="slot-reels" role="img" aria-label="Rolos com 9 posições, 3 colunas e 3 linhas">
                    @foreach ([0, 1, 2] as $c)
                        <div class="slot-reel" :class="'is-' + st[{{ $c }}]">
                            <div class="slot-spinner" aria-hidden="true">
                                @for ($k = 0; $k < 2; $k++)
                                    @foreach ($orders[$c] as $n)
                                        <span class="slot-sym">{{ $symbols[$n % count($symbols)] }}</span>
                                    @endforeach
                                @endfor
                            </div>

                            <div class="slot-landing">
                                @foreach ($grid as $r => $row)
                                    @php
                                        $isWin = in_array($r, $winRows, true) || in_array($c, $winCols, true);
                                    @endphp
                                    <span class="slot-sym {{ $isWin ? 'is-win' : '' }}">
                                        {{ $symbols[((int) ($row[$c] ?? 0)) % count($symbols)] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @foreach ($winRows as $r)
                        <i class="slot-payline" style="--row: {{ $r }}" aria-hidden="true"></i>
                    @endforeach

                    @foreach ($winCols as $c)
                        <i class="slot-payline slot-payline--vertical" style="--col: {{ $c }}" aria-hidden="true"></i>
                    @endforeach
                </div>

                <div class="slot-markers" aria-hidden="true">
                    @foreach ([0, 1, 2] as $r)
                        <span :class="{ 'is-win': done && @js(in_array($r, $winRows, true)) }">{{ $r + 1 }}</span>
                    @endforeach
                </div>
            </div>

            <div class="slot-deck">
                <div>
                    <span class="slot-label">Aposta total</span>
                    <div class="slot-stepper">
                        <button type="button" x-on:click="step(-6)" :disabled="busy || @js($locked)" aria-label="Diminuir aposta">−</button>
                        <input type="number" min="6" step="1" max="{{ $maxBet }}" wire:model="bet" :disabled="busy || @js($locked)" class="slot-bet-input" aria-label="Aposta total em créditos">
                        <button type="button" x-on:click="step(6)" :disabled="busy || @js($locked)" aria-label="Aumentar aposta">+</button>
                    </div>
                    <small class="slot-hint">Por linha: <b x-text="Math.floor(Number($wire.bet || 0) / 6)"></b> créditos · 6 linhas</small>
                </div>

                <div class="slot-chips" aria-label="Apostas rápidas">
                    @foreach ([6, 12, 30, 60, 120] as $chip)
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

            @error('bet')<p role="alert" class="slot-error">{{ $message }}</p>@enderror
            @error('game')<p role="alert" class="slot-error">{{ $message }}</p>@enderror
        </div>
        </section>
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
                            {{ count($winningLines) }} linha(s) vencedora(s) · 3 símbolos iguais
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
                <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-2">{{ $selectedSlotConfig['tag'] ?? 'ORIGINAL' }}</div>
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
