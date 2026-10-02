@php
    $maxBet = (int) config('casino.bet_limits.max', 10000);
    $wheel = [0, 32, 15, 19, 4, 21, 2, 25, 17, 34, 6, 27, 13, 36, 11, 30, 8, 23, 10, 5, 24, 16, 33, 1, 20, 14, 31, 9, 22, 18, 29, 7, 28, 12, 35, 3, 26];
    $reds = [1, 3, 5, 7, 9, 12, 14, 16, 18, 19, 21, 23, 25, 27, 30, 32, 34, 36];
    $tone = fn (int $n) => $n === 0 ? 'green' : (in_array($n, $reds, true) ? 'red' : 'black');
    $has = $roundResult !== [];
    $resultNumber = $has ? (int) ($roundResult['outcome'] ?? 0) : 0;
    $resultColor = $has ? ($roundResult['color'] ?? $tone($resultNumber)) : 'green';
    $initRot = $has ? round(-(int) array_search($resultNumber, $wheel, true) * 360 / 37, 4) : 0;
    $outside = [['1–18', 'range', 'low'], ['Par', 'parity', 'even'], ['Vermelho', 'color', 'red'], ['Preto', 'color', 'black'], ['Ímpar', 'parity', 'odd'], ['19–36', 'range', 'high']];
@endphp

<div class="casino-game-play roulette-page"
     x-data="{
        wheel: @js($wheel), deg: 360 / 37, wheelRot: {{ $initRot }}, ballRot: 0, dur: 0, ballIn: true,
        spinning: false, done: {{ $has ? 'true' : 'false' }}, hit: {{ $has ? $resultNumber : -1 }},
        rn: {{ $resultNumber }}, rc: @js($resultColor), history: [], won: false, overlay: false, shown: 0, maxBet: {{ $maxBet }},
        get off() { return this.spinning || this.$wire.roundPhase === 'prepared'; },
        get mult() { const t = this.$wire.betType; return t === 'straight' ? 36 : (t === 'dozen' || t === 'column') ? 3 : 2; },
        get label() { const t = this.$wire.betType, s = this.$wire.selection; return t === 'straight' ? String(s) : t === 'color' ? (s === 'red' ? 'Vermelho' : 'Preto') : t === 'parity' ? (s === 'even' ? 'Par' : 'Ímpar') : t === 'range' ? (s === 'low' ? '1–18' : '19–36') : t === 'dozen' ? 'Dúzia ' + s : t === 'column' ? 'Coluna ' + s : '—'; },
        sfx(name) { this.$dispatch('casino-sfx', { name }); },
        wait(ms) { return new Promise((r) => setTimeout(r, ms)); },
        pick(t, s) { return this.$wire.betType === t && String(this.$wire.selection) === String(s); },
        choose(t, s) { if (this.off) return; this.$wire.betType = t; this.$wire.selection = s; this.sfx('chip'); },
        bump(d) { this.$wire.bet = Math.min(this.maxBet, Math.max(1, Math.round((Number(this.$wire.bet || 1) + d) / 5) * 5)); },
        count(p) { const t0 = performance.now(); const tick = (t) => { const k = Math.min(1, (t - t0) / 1000); this.shown = Math.round(p * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(tick); }; requestAnimationFrame(tick); },
        async spinNow() {
            if (this.spinning || this.$wire.roundPhase !== 'prepared') return;
            const w = this.$wire, calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.spinning = true; this.done = false; this.hit = -1; this.overlay = false; this.ballIn = false; this.sfx('spin');
            try { await w.spin(); } catch (e) {}
            if (w.roundPhase !== 'completed') { this.spinning = false; this.ballIn = true; return; }
            const n = Number(w.roundResult?.outcome ?? 0), idx = Math.max(0, this.wheel.indexOf(n)), mod = (a) => ((a % 360) + 360) % 360;
            this.rn = n; this.rc = w.roundResult?.color ?? 'green'; this.dur = calm ? 0 : 5200;
            this.wheelRot += 1800 + mod(-idx * this.deg - this.wheelRot); this.ballRot -= 2160;
            if (calm) { this.ballIn = true; } else { await this.wait(4000); this.ballIn = true; this.sfx('drop'); await this.wait(1300); }
            this.spinning = false; this.done = true; this.hit = n;
            this.history = [{ n, c: this.rc }, ...this.history].slice(0, 12);
            const p = Number(w.roundPayout || 0); this.won = !!w.roundResult?.won;
            if (this.won && p > 0) {
                this.overlay = true; this.count(p); this.sfx('win'); setTimeout(() => { this.overlay = false; }, 3400);
                this.$dispatch('casino-toast', { title: 'Vitória!', message: '+' + p + ' créditos virtuais' });
                if (p >= Number(w.bet || 1) * 10) this.$dispatch('casino-big-win', { amount: p });
            } else { this.sfx('lose'); }
        }
     }"
     x-on:keydown.window="if ($event.code === 'Space' && !['INPUT','TEXTAREA','BUTTON','SELECT','SUMMARY'].includes($event.target.tagName)) { $event.preventDefault(); spinNow(); }">

    <x-casino.how-it-works game-key="roulette" title="Como funciona a Roleta Europeia?" description="Escolhe um número ou um tipo de aposta e gira uma roleta europeia com os números 0 a 36." :rules="[['title'=>'Escolhe a aposta','text'=>'Clica na mesa: número, cor, paridade, intervalo, dúzia ou coluna.'], ['title'=>'Define o valor','text'=>'Escolhe quantos créditos virtuais queres apostar.'], ['title'=>'Gira a roleta','text'=>'Confirma a aposta e carrega em Girar (ou na barra de espaço).'], ['title'=>'Confere o resultado','text'=>'A aposta ganha quando o número corresponde à tua escolha.']]" />

    <div class="rl-layout">
        <section class="rl-main">
            <div class="rl-stage">
                <div class="rl-topbar">
                    <div><p class="rl-eyebrow">Jogo 03 · Mesa</p><h2 class="rl-title">Roleta europeia</h2></div>
                    <div class="rl-history" aria-label="Últimos números">
                        <template x-for="(h, i) in history" :key="i"><span class="rl-dot" :class="'rl-dot--' + h.c" x-text="h.n"></span></template>
                        <span class="rl-status" x-show="!history.length" x-text="spinning ? 'A girar…' : 'Mesa aberta'"></span>
                    </div>
                </div>

                <div class="rl-scene">
                    <div class="rl-tilt">
                        <div class="rl-base"></div>
                        <div class="rl-wheel" :style="'--dur:' + dur + 'ms; transform: rotate(' + wheelRot + 'deg)'">
                            <div class="rl-rim"></div><div class="rl-track"></div>
                            @foreach ($wheel as $i => $n)
                                <span class="rl-pocket rl-pocket--{{ $tone($n) }}" style="--a: {{ round($i * 360 / 37, 4) }}deg" :class="{ 'is-hit': hit === {{ $n }} }"><b>{{ $n }}</b></span>
                            @endforeach
                            <div class="rl-bowl"></div>
                            @foreach ([0, 90, 180, 270] as $s)<i class="rl-spoke" style="--s: {{ $s }}deg"></i>@endforeach
                            <div class="rl-hub"></div>
                        </div>
                        <div class="rl-orbit" :style="'--dur:' + dur + 'ms; transform: rotate(' + ballRot + 'deg)'"><i class="rl-ball" :class="{ 'is-in': ballIn }"></i></div>
                        <div class="rl-gloss"></div>
                    </div>
                </div>

                <div class="rl-result" x-show="done" x-cloak x-transition.opacity>
                    <span>Número vencedor</span>
                    <strong :class="'rl-c-' + rc" x-text="rn"></strong>
                    <small x-text="rc === 'red' ? 'Vermelho' : rc === 'black' ? 'Preto' : 'Zero · verde'"></small>
                </div>

                <div class="rl-prize" x-show="overlay" x-cloak x-transition.opacity role="status" aria-live="assertive">
                    <div><p>Vitória</p><strong>+<span x-text="Number(shown).toLocaleString('pt-PT')"></span></strong><small>créditos virtuais</small></div>
                </div>
            </div>

            {{-- Mesa real: colunas de 3 (3-2-1), zero à esquerda, "2:1" à direita --}}
            <div class="rl-scroll">
                <div class="rl-table">
                    <button type="button" class="rl-cell rl-green rl-zero" :class="{ 'is-picked': pick('straight', 0) }" x-on:click="choose('straight', 0)" :disabled="off">0</button>
                    @foreach (range(1, 12) as $c)
                        @foreach ([3, 2, 1] as $r => $o)
                            @php $n = ($c - 1) * 3 + $o; @endphp
                            <button type="button" class="rl-cell rl-{{ $tone($n) }}" style="grid-column: {{ $c + 1 }}; grid-row: {{ $r + 1 }}" :class="{ 'is-picked': pick('straight', {{ $n }}), 'is-hit': hit === {{ $n }} }" x-on:click="choose('straight', {{ $n }})" :disabled="off">{{ $n }}</button>
                        @endforeach
                    @endforeach
                    @foreach ([3, 2, 1] as $r => $colN)
                        <button type="button" class="rl-cell rl-out" style="grid-column: 14; grid-row: {{ $r + 1 }}" :class="{ 'is-picked': pick('column', {{ $colN }}) }" x-on:click="choose('column', {{ $colN }})" :disabled="off">2:1</button>
                    @endforeach
                    @foreach ([1, 2, 3] as $d)
                        <button type="button" class="rl-cell rl-out" style="grid-column: {{ ($d - 1) * 4 + 2 }} / span 4; grid-row: 4" :class="{ 'is-picked': pick('dozen', {{ $d }}) }" x-on:click="choose('dozen', {{ $d }})" :disabled="off">{{ $d }}ª dúzia</button>
                    @endforeach
                    @foreach ($outside as $k => [$txt, $type, $sel])
                        <button type="button" class="rl-cell rl-out {{ $type === 'color' ? 'rl-'.$sel : '' }}" style="grid-column: {{ $k * 2 + 2 }} / span 2; grid-row: 5" :class="{ 'is-picked': pick(@js($type), @js($sel)) }" x-on:click="choose(@js($type), @js($sel))" :disabled="off">{{ $txt }}</button>
                    @endforeach
                </div>
            </div>
        </section>

        <aside class="space-y-3">
            <div class="rl-panel">
                <p class="rl-eyebrow">Aposta atual</p>
                <div class="rl-sel">
                    <div><strong x-text="label"></strong><span x-text="$wire.betType"></span></div>
                    <div class="text-right"><strong class="rl-gold" x-text="mult + '×'"></strong><span>pagamento bruto</span></div>
                </div>

                <div class="rl-stepper">
                    <button type="button" x-on:click="bump(-5)" :disabled="off" aria-label="Diminuir aposta">−</button>
                    <input type="number" min="1" max="{{ $maxBet }}" step="1" wire:model="bet" :disabled="off" class="rl-input" aria-label="Aposta em créditos">
                    <button type="button" x-on:click="bump(5)" :disabled="off" aria-label="Aumentar aposta">+</button>
                </div>
                <div class="rl-chips">
                    @foreach ([25, 50, 100, 250, 500, 1000] as $chip)
                        @if ($chip <= $maxBet)<button type="button" x-on:click="$wire.bet = {{ $chip }}" :disabled="off">{{ $chip }}</button>@endif
                    @endforeach
                    <button type="button" x-on:click="$wire.bet = {{ $maxBet }}" :disabled="off">Máx.</button>
                </div>

                <div class="rl-payout">
                    <div><strong x-text="'+' + Math.max(0, Number($wire.bet || 0) * mult).toLocaleString('pt-PT')"></strong><span>se acertar</span></div>
                    <div class="text-right"><strong x-text="mult + '×'"></strong><span>multiplicador</span></div>
                </div>

                @if ($roundPhase === 'prepared')
                    <button type="button" class="rl-spin" x-on:click="spinNow()" :disabled="spinning"><span x-text="spinning ? 'A girar…' : 'Girar roleta'"></span><small>ou barra de espaço</small></button>
                @else
                    <button type="button" class="rl-spin" wire:click="prepare" wire:loading.attr="disabled">{{ $roundPhase === 'completed' ? 'Confirmar nova aposta' : 'Confirmar aposta' }}</button>
                @endif

                @error('bet')<p class="rl-error" role="alert">{{ $message }}</p>@enderror
                @error('selection')<p class="rl-error" role="alert">{{ $message }}</p>@enderror
                @error('game')<p class="rl-error" role="alert">{{ $message }}</p>@enderror
            </div>

            @if ($has)
                <div class="rl-panel" x-show="done" x-cloak>
                    <p class="rl-eyebrow">Última ronda</p>
                    <div class="mt-2 flex items-end justify-between gap-3">
                        <strong class="rl-last rl-c-{{ $resultColor }}">{{ $resultNumber }}</strong>
                        <span class="text-sm font-bold {{ ($roundResult['won'] ?? false) ? 'text-emerald-300' : 'text-rose-300' }}">{{ ($roundResult['won'] ?? false) ? '+'.number_format((int) $roundPayout) : 'Sem prémio' }}</span>
                    </div>
                    @if ($roundPhase === 'completed' && $roundId)
                        <button type="button" class="mt-3 text-xs font-bold text-emerald-300 hover:text-emerald-200" x-on:click="$dispatch('casino-open-fairness', { roundId: {{ $roundId }} })">Verificar esta ronda</button>
                    @endif
                </div>
            @endif

            <details class="rl-panel">
                <summary class="rl-eyebrow cursor-pointer">Jogo transparente</summary>
                <p class="mt-3 text-xs leading-5 text-zinc-500">O hash do servidor é fixado antes do resultado e a ronda pode ser verificada.</p>
                @if ($serverSeedHash)<p class="mt-3 break-all font-mono text-[0.66rem] leading-5 text-zinc-300">{{ $serverSeedHash }}</p>@endif
                <label class="mt-3 block text-[0.65rem] text-zinc-500">Seed do cliente
                    <input type="text" maxlength="128" wire:model="clientSeed" :disabled="off" class="rl-seed">
                </label>
            </details>

            <p class="px-1 text-center text-[0.68rem] text-zinc-600">Créditos virtuais — sem valor monetário</p>
        </aside>
    </div>
</div>