@php
    $maxBet = max(0, (int) (auth()->user()?->wallet?->balance ?? 0));
    $settlementPending = $roundPhase === 'in_progress' && (bool) ($roundResult['settlement_pending'] ?? false);
@endphp

<div class="blackjack-page relative casino-game-screen" data-casino-game="blackjack" x-data="{ resultVisible: false, resultType: '', resultAmount: 0, resultTimer: null, playSound(type) { this.$dispatch('casino-sfx', { name: type === 'win' ? 'win' : 'lose' }); }, showResult(type, amount) { this.resultType = type; this.resultAmount = Number(amount || 0); this.resultVisible = true; this.playSound(type); clearTimeout(this.resultTimer); this.resultTimer = setTimeout(() => this.resultVisible = false, 3600); } }" x-on:casino-round-result.window="showResult($event.detail.outcome, $event.detail.amount)">
    <style>
.blackjack-page { --gold: #f2c14e; --cw: clamp(3.6rem, 11vw, 5.2rem); }
.bj-hero { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem; margin-bottom: 1rem; }
.bj-h { font-size: 1.6rem; font-weight: 900; color: #fff; } .bj-hint { margin-top: .15rem; font-size: .8rem; color: #8a929a; }
.bj-layout { display: grid; gap: 1rem; margin-top: 1rem; grid-template-columns: minmax(0, 1fr) 18.5rem; }

/* Mesa */
.bj-table {
    position: relative; overflow: hidden; display: grid; align-content: space-between; min-height: 33rem; padding: 1.2rem 1.4rem; border-radius: 1.6rem;
    background: radial-gradient(80% 60% at 50% 40%, rgba(40, 160, 115, .3), transparent 70%), linear-gradient(145deg, #0c6048, #06372c);
    box-shadow: inset 0 0 0 .55rem #1b120a, inset 0 0 0 .65rem rgba(242, 193, 78, .5), 0 24px 60px rgba(0, 0, 0, .3);
}
.bj-arc { text-align: center; font-size: .7rem; font-weight: 800; letter-spacing: .5em; color: rgba(242, 193, 78, .35); }
.bj-seat { position: relative; z-index: 1; padding: .6rem 0; }
.bj-player { border-top: 1px dashed rgba(255, 255, 255, .15); padding-top: 1rem; }
.bj-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .6rem; font-size: .7rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: rgba(255, 255, 255, .7); }
.bj-total { padding: .25rem .7rem; border-radius: 9999px; font-size: .75rem; letter-spacing: 0; color: #fff; background: rgba(0, 0, 0, .35); border: 1px solid rgba(242, 193, 78, .3); }
.bj-total.is-bust { color: #fecaca; border-color: rgba(248, 113, 113, .6); background: rgba(127, 29, 29, .5); }
.bj-total.is-bj { color: #2b1c05; background: linear-gradient(135deg, #ffe9a8, #e4ae39); box-shadow: 0 0 18px rgba(242, 193, 78, .6); }

/* Cartas */
.bj-cards { display: flex; align-items: center; min-height: calc(var(--cw) * 1.4); }
.bj-card + .bj-card { margin-left: calc(var(--cw) * -.4); }
.bj-card {
    position: relative; flex: none; width: var(--cw); height: calc(var(--cw) * 1.4); border-radius: .6rem; color: #151b22;
    background: linear-gradient(150deg, #fff, #ecebe5); border: 1px solid rgba(0, 0, 0, .15); box-shadow: -4px 6px 14px rgba(0, 0, 0, .35);
    animation: bj-deal .55s cubic-bezier(.2, .8, .3, 1) both; animation-delay: calc(var(--i, 0) * .14s);
}
.bj-card.is-red { color: #c81e2e; }
.bj-card b { position: absolute; top: .3rem; left: .4rem; display: grid; justify-items: center; font-size: calc(var(--cw) * .27); line-height: 1; font-weight: 800; }
.bj-card b i { font-style: normal; font-size: .8em; }
.bj-card b.bj-flip { top: auto; left: auto; right: .4rem; bottom: .3rem; transform: rotate(180deg); }
.bj-card em { position: absolute; inset: 0; display: grid; place-items: center; font-style: normal; font-size: calc(var(--cw) * .6); opacity: .9; }
.bj-back {
    display: grid; place-items: center; color: #f2c14e; border: 3px solid #f3efe0;
    background: repeating-linear-gradient(45deg, #12303a 0 6px, #0b222a 6px 12px);
}
.bj-back span { display: grid; place-items: center; width: 55%; aspect-ratio: 1; border-radius: 50%; border: 2px solid rgba(242, 193, 78, .6); font-weight: 900; font-size: 1.2rem; background: rgba(0, 0, 0, .3); }
.bj-empty { font-size: .8rem; color: rgba(255, 255, 255, .4); }
@keyframes bj-deal { from { opacity: 0; transform: translate(35vw, -22vh) rotate(24deg) scale(.8); } to { opacity: 1; transform: none; } }

/* Estado e resultado */
.bj-settlement{display:flex;align-items:center;gap:.7rem;padding:.8rem .9rem;border:1px solid rgba(242,193,78,.25);border-radius:.85rem;background:rgba(242,193,78,.055);color:#ede4c9}.bj-settlement__dot{width:.55rem;height:.55rem;flex:none;border-radius:50%;background:#f2c14e;box-shadow:0 0 16px rgba(242,193,78,.75);animation:bj-settlement-pulse .75s ease-in-out infinite alternate}.bj-settlement strong{font-size:.74rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em}.bj-settlement p{margin-top:.18rem;font-size:.62rem;line-height:1.45;color:#958f7b}@keyframes bj-settlement-pulse{to{transform:scale(1.35);opacity:.55}}

.bj-status { position: relative; z-index: 2; display: flex; justify-content: center; padding-top: .4rem; }
.bj-turn { padding: .45rem 1.1rem; border-radius: 9999px; font-size: .75rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #2b1c05; background: linear-gradient(135deg, #ffe9a8, #e4ae39); animation: bj-pulse 1.4s ease-in-out infinite; }
@keyframes bj-pulse { 50% { box-shadow: 0 0 22px rgba(242, 193, 78, .7); } }
.bj-result { padding: .55rem 1.1rem; border-radius: 9999px; font-size: .78rem; font-weight: 800; color: #fff; background: rgba(0, 0, 0, .4); border: 1px solid rgba(242, 193, 78, .3); backdrop-filter: blur(8px); }
.bj-win { color: #b8f7d8; border-color: rgba(79, 214, 145, .5); } .bj-loss { color: #ffb6b6; border-color: rgba(255, 111, 111, .4); }
.bj-prize { position: absolute; inset: 0; z-index: 30; display: grid; place-items: center; pointer-events: none; background: rgba(0, 0, 0, .55); backdrop-filter: blur(3px); }
.bj-prize__card { min-width: min(88%, 24rem); padding: 1.4rem 2rem; text-align: center; border-radius: 1.3rem; border: 2px solid rgba(242, 193, 78, .8); background: linear-gradient(145deg, rgba(9, 34, 27, .98), rgba(43, 31, 8, .98)); box-shadow: 0 0 55px rgba(242, 193, 78, .35); animation: bj-in .45s cubic-bezier(.2, .9, .25, 1.2); }
.bj-prize__card p { font-size: .85rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: var(--gold); }
.bj-prize__card strong { display: block; font-size: clamp(2.6rem, 8vw, 4.6rem); line-height: 1.05; color: #fff; font-variant-numeric: tabular-nums; }
.bj-prize__card small { color: #b8f7d8; font-weight: 700; }
.bj-prize__card.loss { border-color: rgba(255, 111, 111, .6); background: linear-gradient(145deg, rgba(38, 12, 15, .98), rgba(22, 18, 18, .98)); box-shadow: 0 0 45px rgba(255, 80, 80, .2); }
.bj-prize__card.loss p, .bj-prize__card.loss small { color: #ffb3b3; }
@keyframes bj-in { from { opacity: 0; transform: scale(.65) translateY(1rem); } }

/* Painel */
.bj-panel { align-self: start; border-radius: 1.2rem; border: 1px solid rgba(255, 255, 255, .08); background: rgba(12, 16, 20, .78); backdrop-filter: blur(12px); }
.bj-section { display: block; padding: 1rem; } .bj-section + .bj-section { border-top: 1px solid rgba(255, 255, 255, .07); }
.bj-paneltitle { margin-bottom: .7rem; font-size: .85rem; font-weight: 800; color: #fff; }
.bj-field { display: block; margin-bottom: .6rem; } .bj-field span { display: block; margin-bottom: .3rem; font-size: .65rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #8e959d; }
.bj-input { width: 100%; min-height: 2.6rem; padding: .55rem .7rem; color: #fff; border-radius: .65rem; border: 1px solid rgba(255, 255, 255, .12); background: rgba(0, 0, 0, .25); outline: 0; }
.bj-input:focus { border-color: rgba(242, 193, 78, .6); box-shadow: 0 0 0 3px rgba(242, 193, 78, .1); }
.bj-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .8rem; }
.bj-chips button { padding: .4rem .7rem; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, .1); background: rgba(255, 255, 255, .04); color: #b7c1bc; font-size: .72rem; font-weight: 700; transition: transform .15s, color .15s; }
.bj-chips button:hover:not(:disabled) { transform: translateY(-2px); color: var(--gold); }
.bj-fair { margin-bottom: .8rem; font-size: .8rem; color: #cfd5da; } .bj-fair summary { cursor: pointer; font-weight: 700; }
.bj-info { font-size: .72rem; line-height: 1.55; color: #858d96; } .bj-info strong { color: #cdd2d7; }
.bj-seed { margin-top: .5rem; padding: .55rem; border-radius: .6rem; border: 1px solid rgba(255, 255, 255, .08); background: rgba(0, 0, 0, .2); font: .62rem/1.45 ui-monospace, Menlo, monospace; color: #8e959d; word-break: break-all; }
.bj-button { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; min-height: 2.8rem; padding: .6rem .8rem; border-radius: .75rem; font-size: .8rem; font-weight: 800; transition: transform .12s, filter .15s; }
.bj-button:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.06); } .bj-button:disabled { opacity: .5; cursor: not-allowed; }
.bj-button:focus-visible, .bj-chips button:focus-visible { outline: 2px solid #ffe39a; outline-offset: 2px; }
.bj-gold { color: #2b1c05; background: linear-gradient(180deg, #ffe9a8, #e4ae39 55%, #b07a14); box-shadow: 0 4px 0 #6b470b; }
.bj-gold:active:not(:disabled) { transform: translateY(3px); box-shadow: 0 1px 0 #6b470b; }
.bj-green { color: #06150f; background: #2dba78; } .bj-dark { color: #fff; border: 1px solid rgba(255, 255, 255, .12); background: rgba(255, 255, 255, .05); }
.bj-actions { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; } .bj-actions .bj-button:first-child { grid-column: 1 / -1; }
.bj-button kbd { padding: .05rem .4rem; border-radius: .3rem; font: 700 .65rem ui-monospace, monospace; background: rgba(0, 0, 0, .25); }
.bj-notes { display: flex; flex-wrap: wrap; gap: .4rem 1rem; margin-top: .9rem; font-size: .68rem; color: #717983; }

@media (max-width: 900px) { .bj-layout { grid-template-columns: 1fr; } }
@media (max-width: 560px) { .bj-table { min-height: 29rem; padding: 1rem; } .bj-arc { letter-spacing: .25em; } }
@media (prefers-reduced-motion: reduce) { .bj-card, .bj-turn, .bj-prize__card { animation: none; } }
    </style>

    <x-casino.loading-overlay target="prepare,deal,hit,stand,double" />

    <div class="bj-hero">
        <div class="bj-title">
            <span class="bj-mark" aria-hidden="true">♠</span>
            <div>
                <p class="bj-eyebrow">Mesa privada · Créditos virtuais</p>
                <h2 class="bj-h">Blackjack</h2>
                <p class="bj-hint">Aproxime-se de 21 sem ultrapassar o dealer.</p>
            </div>
        </div>
        <a href="{{ route('casino.help') }}" class="text-xs text-zinc-400 hover:text-casino-gold-bright" wire:navigate>Regras e jogo responsável →</a>
    </div>

    <x-casino.how-it-works
        game-key="blackjack"
        title="Como funciona o Blackjack?"
        description="Joga contra o dealer e tenta ficar o mais perto possível de 21 sem ultrapassar esse valor."
        :rules="[
            ['title' => 'Recebe as cartas', 'text' => 'Começas com duas cartas. O dealer também recebe cartas, sendo uma delas inicialmente escondida.'],
            ['title' => 'Escolhe a jogada', 'text' => 'Pedir carta adiciona uma carta; Parar mantém a pontuação; Dobrar duplica a aposta e recebe mais uma carta.'],
            ['title' => 'Compara as mãos', 'text' => 'Depois da tua jogada, o dealer completa a mão. A mão mais próxima de 21 vence.'],
            ['title' => 'Valores das cartas', 'text' => 'Ás vale 1 ou 11, figuras valem 10 e as restantes cartas valem o seu número.'],
        ]"
        badge="Objetivo: chegar a 21"
    />

    <div class="bj-layout">
        <section class="bj-table" aria-label="Mesa de Blackjack">
            <div x-show="resultVisible" x-cloak x-transition.opacity class="bj-prize-overlay" role="status" aria-live="assertive"><div class="bj-prize__card" :class="{ loss: resultType === 'loss' }"><p x-text="resultType === 'win' ? '🎉 Vitória' : 'Derrota'"></p><strong x-text="(resultType === 'win' ? '+' : '−') + Number(resultAmount).toLocaleString('pt-PT')"></strong><small x-text="resultType === 'win' ? 'créditos virtuais ganhos' : 'créditos virtuais perdidos'"></small></div></div><div class="bj-arc">♠ · ALLINBET BLACKJACK · ♠</div>

            <div class="bj-hand bj-seat">
                <div class="bj-head">
                    <span>Dealer</span>
                    @if ($roundResult !== []) <span class="bj-total">{{ $roundResult['dealer_visible_total'] ?? '—' }}</span> @endif
                </div>
                @if ($roundResult !== [])
                    <div class="bj-cards">
                        @foreach ($roundResult['dealer_hand'] ?? [] as $card)
                            @php
                                $hidden = (bool) ($card['hidden'] ?? false);
                                $rankValue = $card['rank'] ?? null;
                                $rank = $rankValue === null ? '—' : match ($rankValue) {
                                    1 => 'A',
                                    11 => 'J',
                                    12 => 'Q',
                                    13 => 'K',
                                    default => $rankValue,
                                };
                                $suit = $card['suit'] ?? 'spades';
                                $suitSymbol = match ($suit) {
                                    'clubs' => '♣',
                                    'diamonds' => '♦',
                                    'hearts' => '♥',
                                    default => '♠',
                                };
                                $isRed = in_array($suit, ['diamonds', 'hearts'], true);
                            @endphp
                            @if ($hidden)
                                <div class="bj-card bj-back" style="--i: {{ $loop->index }}" aria-label="Carta escondida"><span>♠</span></div>
                            @else
                                <div class="bj-card {{ $isRed ? 'is-red' : '' }}" style="--i: {{ $loop->index }}">
                                    <b>{{ $rank }}<i>{{ $suitSymbol }}</i></b>
                                    <em>{{ $suitSymbol }}</em>
                                    <b class="bj-flip">{{ $rank }}<i>{{ $suitSymbol }}</i></b>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="bj-empty">As cartas do dealer aparecem quando a mesa for iniciada.</div>
                @endif
            </div>

            <div class="bj-hand bj-seat bj-player">
                <div class="bj-handhead">
                    <span>A sua mão</span>
                    @if ($roundResult !== []) <span class="bj-total">{{ $roundResult['player_total'] ?? '—' }}</span> @endif
                </div>
                @if ($roundResult !== [])
                    <div class="bj-cards">
                        @foreach ($roundResult['player_hand'] ?? [] as $card)
                            @php
                                $rank = match ($card['rank']) {
                                    1 => 'A',
                                    11 => 'J',
                                    12 => 'Q',
                                    13 => 'K',
                                    default => $card['rank'],
                                };
                                $suit = $card['suit'] ?? 'spades';
                                $suitSymbol = match ($suit) {
                                    'clubs' => '♣',
                                    'diamonds' => '♦',
                                    'hearts' => '♥',
                                    default => '♠',
                                };
                                $isRed = in_array($suit, ['diamonds', 'hearts'], true);
                            @endphp
                            <div class="bj-card {{ $isRed ? 'is-red' : '' }}" style="--i: {{ $loop->index }}">
                                <b>{{ $rank }}<i>{{ $suitSymbol }}</i></b>
                                <em>{{ $suitSymbol }}</em>
                                <b class="bj-flip">{{ $rank }}<i>{{ $suitSymbol }}</i></b>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bj-empty">Prepare a mesa para receber as suas cartas.</div>
                @endif
            </div>

            @if ($roundPhase === 'completed')
                @php $outcome = $roundResult['outcome'] ?? ''; $resultLabel = match ($outcome) { 'player'=>'Vitória', 'push'=>'Empate', default=>'Dealer venceu' }; @endphp
                <div class="bj-status"><div class="bj-result {{ $outcome === 'player' ? 'bj-win' : ($outcome === 'push' ? '' : 'bj-loss') }}">{{ $resultLabel }} · {{ $roundPayout }} créditos</div></div>
            @endif
        </section>

        <aside class="bj-panel">
            @if (in_array($roundPhase, ['ready', 'completed'], true))
                <form wire:submit="prepare">
                    <div class="bj-section">
                        <p class="bj-paneltitle">Nova ronda</p>
                        <label class="bj-field">
                            <span>Aposta em créditos</span>
                            <input type="number" min="1" max="{{ $maxBet }}" wire:model="bet" class="bj-input" inputmode="numeric">
                        </label>
                        @error('bet') <p class="mb-3 text-xs text-rose-300">{{ $message }}</p> @enderror
                        <div class="bj-chips">
                            <button type="button" x-on:click="$wire.bet = {{ $maxBet }}" :disabled="resultVisible || {{ $maxBet < 1 ? 'true' : 'false' }}">ALL IN · {{ number_format($maxBet, 0, ',', ' ') }}</button>
                        </div>
                        <label class="bj-field">
                            <span>Seed do cliente</span>
                            <input type="text" maxlength="128" wire:model="clientSeed" class="bj-input font-mono text-xs" placeholder="Opcional">
                        </label>
                        <button type="submit" wire:loading.attr="disabled" :disabled="resultVisible" class="bj-button bj-gold">Preparar mesa</button>
                    </div>
                </form>
            @endif

            @if ($roundPhase === 'prepared')
                <div class="bj-section">
                    <p class="bj-paneltitle">Mesa preparada</p>
                    <p class="bj-info">O hash do servidor é apresentado antes das cartas serem dadas para permitir a verificação da ronda.</p>
                    <div class="bj-seed">{{ $serverSeedHash }}</div>
                    <button type="button" wire:click="deal" wire:loading.attr="disabled" class="bj-button bj-gold mt-3">Dar cartas</button>
                </div>
            @endif

            @if ($roundPhase === 'in_progress')
                <div class="bj-section">
                    @if ($settlementPending)
                        <div class="bj-settlement" role="status" aria-live="polite">
                            <span class="bj-settlement__dot" aria-hidden="true"></span>
                            <div>
                                <strong>Resultado confirmado</strong>
                                <p>As cartas já terminaram. Estamos a confirmar o payout e a actualizar o saldo.</p>
                            </div>
                        </div>
                    @else
                        <p class="bj-paneltitle">A sua jogada</p>
                        <div class="bj-actions">
                            @if (in_array('hit', $roundResult['available_actions'] ?? [], true))
                                <button type="button" wire:click="hit" wire:loading.attr="disabled" :disabled="{{ $settlementPending ? 'true' : 'false' }}" class="bj-button bj-green">Pedir carta</button>
                            @endif
                            @if (in_array('stand', $roundResult['available_actions'] ?? [], true))
                                <button type="button" wire:click="stand" wire:loading.attr="disabled" :disabled="{{ $settlementPending ? 'true' : 'false' }}" class="bj-button bj-dark">Parar</button>
                            @endif
                            @if (in_array('double', $roundResult['available_actions'] ?? [], true))
                                <button type="button" wire:click="double" wire:loading.attr="disabled" :disabled="{{ $settlementPending ? 'true' : 'false' }}" class="bj-button bj-dark">Dobrar aposta</button>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if ($roundPhase === 'completed')
                <div class="bj-section">
                    <p class="bj-paneltitle">Ronda terminada</p>
                    <p class="bj-info">Resultado: <strong>{{ $resultLabel }}</strong><br>Pagamento: <strong>{{ $roundPayout }} créditos</strong></p>
                    <button type="button" class="bj-button bj-dark mt-3 inline-flex items-center justify-center" x-data x-on:click="$dispatch('casino-open-fairness', { roundId: {{ $roundId }} })">Verificar esta ronda</button>
                </div>
            @endif

            <div class="bj-section">
                <p class="bj-paneltitle">Como jogar</p>
                <p class="bj-info"><strong>Pedir carta</strong> adiciona uma carta. <strong>Parar</strong> mantém a pontuação. <strong>Dobrar</strong> aumenta a aposta e recebe mais uma carta.</p>
            </div>
        </aside>
    </div>

    @error('game') <div class="mt-3 rounded-lg border border-rose-400/20 bg-rose-400/5 px-3 py-2 text-xs text-rose-300" role="alert">{{ $message }}</div> @enderror
    <div class="bj-notes"><span>● Créditos virtuais sem valor monetário</span><span>● Sem depósitos, pagamentos ou levantamentos</span><span>● Jogue de forma responsável</span></div>
    <p wire:loading class="mt-2 text-center text-xs text-zinc-500">A processar mão…</p>
</div>