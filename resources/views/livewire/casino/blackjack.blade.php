<div class="blackjack-page relative" x-data="{ resultVisible: false, resultType: '', resultAmount: 0, resultTimer: null, playSound(type) { const A = window.AudioContext || window.webkitAudioContext; if (!A) return; const ctx = new A(); const now = ctx.currentTime; const gain = ctx.createGain(); const osc = ctx.createOscillator(); osc.connect(gain); gain.connect(ctx.destination); gain.gain.setValueAtTime(0.0001, now); gain.gain.exponentialRampToValueAtTime(0.16, now + 0.02); gain.gain.exponentialRampToValueAtTime(0.0001, now + (type === 'win' ? 1.05 : 0.7)); if (type === 'win') { osc.type = 'sine'; osc.frequency.setValueAtTime(660, now); osc.frequency.exponentialRampToValueAtTime(990, now + 0.25); osc.frequency.exponentialRampToValueAtTime(1320, now + 0.55); } else { osc.type = 'sawtooth'; osc.frequency.setValueAtTime(220, now); osc.frequency.exponentialRampToValueAtTime(85, now + 0.55); } osc.start(now); osc.stop(now + (type === 'win' ? 1.05 : 0.7)); }, showResult(type, amount) { this.resultType = type; this.resultAmount = Number(amount || 0); this.resultVisible = true; this.playSound(type); clearTimeout(this.resultTimer); this.resultTimer = setTimeout(() => this.resultVisible = false, 3600); } }" x-on:casino-round-result.window="showResult($event.detail.outcome, $event.detail.amount)">
    <style>
        .blackjack-page{--gold:#f2c14e;--green:#0b5a45;--green2:#06372c}
        .bj-hero{display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;margin-bottom:1rem}
        .bj-title{display:flex;gap:.8rem;align-items:center}.bj-mark{display:grid;place-items:center;width:3rem;height:3rem;border:1px solid rgba(242,193,78,.3);border-radius:.9rem;background:rgba(242,193,78,.1);color:var(--gold);font-size:1.3rem}
        .bj-eyebrow{margin:0 0 .15rem;color:#9ba2aa;font-size:.65rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase}.bj-title h2{margin:0;font-size:1.55rem;font-weight:800}.bj-hint{margin:.2rem 0 0;color:#858c95;font-size:.8rem}
        .bj-intro{margin-bottom:1rem;padding:1.25rem 1.35rem;border:1px solid rgba(242,193,78,.16);border-radius:1.15rem;background:linear-gradient(135deg,rgba(242,193,78,.07),rgba(255,255,255,.025));box-shadow:0 15px 40px rgba(0,0,0,.12)}
        .bj-intro-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem}.bj-intro h3{margin:.1rem 0 0;color:white;font-size:1.15rem;font-weight:850}.bj-intro-lead{max-width:62rem;margin:.7rem 0 1rem;color:#aeb5bd;font-size:.8rem;line-height:1.65}.bj-intro-badge{white-space:nowrap;padding:.35rem .65rem;border:1px solid rgba(242,193,78,.2);border-radius:999px;background:rgba(242,193,78,.08);color:#f2c14e;font-size:.62rem;font-weight:800}.bj-rules-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem}.bj-rules-grid>div{padding:.85rem;border:1px solid rgba(255,255,255,.06);border-radius:.8rem;background:rgba(0,0,0,.14)}.bj-rules-grid span{display:block;margin-bottom:.45rem;color:#f2c14e;font-size:.58rem;font-weight:900;letter-spacing:.12em}.bj-rules-grid strong{display:block;color:#e9edf0;font-size:.73rem}.bj-rules-grid p{margin:.35rem 0 0;color:#858d96;font-size:.67rem;line-height:1.5}.bj-intro-note{margin-top:.75rem;color:#777f89;font-size:.65rem}.bj-intro-note strong{color:#cdd2d7}
        .bj-layout{display:grid;grid-template-columns:minmax(0,1fr) 18rem;gap:1rem}
        .bj-table{position:relative;min-height:31rem;overflow:hidden;border:1px solid rgba(255,255,255,.08);border-radius:1.25rem;background:radial-gradient(circle at 50% 25%,rgba(35,151,110,.2),transparent 45%),linear-gradient(145deg,var(--green),var(--green2));box-shadow:inset 0 1px 0 rgba(255,255,255,.05),0 22px 55px rgba(0,0,0,.2)}
        .bj-table:before{content:"";position:absolute;inset:1rem;border:1px solid rgba(242,193,78,.14);border-radius:1rem;pointer-events:none}
        .bj-label{position:relative;padding:1.3rem 1.5rem 0;color:rgba(255,255,255,.5);font-size:.62rem;font-weight:800;letter-spacing:.18em;text-transform:uppercase}
        .bj-hand{position:relative;z-index:1;padding:1rem 1.5rem}.bj-player{margin-top:3rem;border-top:1px solid rgba(255,255,255,.08)}
        .bj-handhead{display:flex;justify-content:space-between;align-items:center;margin-bottom:.7rem}.bj-name{color:rgba(255,255,255,.72);font-size:.68rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.bj-total{padding:.25rem .55rem;border:1px solid rgba(242,193,78,.25);border-radius:999px;background:rgba(0,0,0,.18);color:white;font-size:.72rem;font-weight:800}
        .bj-cards{display:flex;flex-wrap:wrap;gap:.65rem;min-height:6rem}.bj-card{display:flex;width:4.25rem;height:6rem;flex-direction:column;justify-content:space-between;padding:.55rem;border:1px solid rgba(0,0,0,.14);border-radius:.7rem;background:linear-gradient(145deg,#fff,#f1f1ed);color:#111820;box-shadow:0 9px 18px rgba(0,0,0,.2);font-weight:800}.bj-rank{font-size:1.2rem;line-height:1}.bj-center{align-self:center;font-size:1.5rem;opacity:.16}.bj-corner{align-self:flex-end;font-size:.7rem}.bj-hidden{display:grid;place-items:center;background:#18252d;color:var(--gold);border-color:rgba(242,193,78,.3);font-size:1.7rem}.bj-empty{display:flex;min-height:6rem;align-items:center;color:rgba(255,255,255,.34);font-size:.8rem}
        .bj-prize-overlay{position:absolute;inset:0;z-index:40;display:grid;place-items:center;pointer-events:none;background:rgba(0,0,0,.52);backdrop-filter:blur(3px)}.bj-prize-card{min-width:min(88%,28rem);padding:1.6rem 2rem;border:2px solid rgba(242,193,78,.75);border-radius:1.3rem;background:linear-gradient(145deg,rgba(9,34,27,.98),rgba(31,25,9,.98));box-shadow:0 0 55px rgba(242,193,78,.35),0 25px 70px rgba(0,0,0,.6);text-align:center;animation:bjPrizeIn .45s cubic-bezier(.2,.9,.25,1.2)}.bj-prize-label{margin:0;color:#f2c14e;font-size:.72rem;font-weight:900;letter-spacing:.2em;text-transform:uppercase}.bj-prize-amount{margin:.25rem 0 0;color:#fff;font-size:clamp(2.8rem,8vw,5rem);font-weight:950;line-height:1}.bj-prize-sub{margin:.6rem 0 0;color:#b8f7d8;font-size:.9rem;font-weight:800}.bj-prize-card.loss{border-color:rgba(255,111,111,.55);background:linear-gradient(145deg,rgba(38,12,15,.98),rgba(22,18,18,.98));box-shadow:0 0 45px rgba(255,80,80,.2),0 25px 70px rgba(0,0,0,.6)}.bj-prize-card.loss .bj-prize-label{color:#ff9999}.bj-prize-card.loss .bj-prize-sub{color:#ffcccc}@keyframes bjPrizeIn{0%{opacity:0;transform:scale(.65) translateY(1rem)}60%{opacity:1;transform:scale(1.05)}100%{opacity:1;transform:scale(1) translateY(0)}}.bj-status{position:absolute;right:1.4rem;bottom:1.2rem;left:1.4rem;z-index:2;display:flex;justify-content:center}.bj-result{padding:.6rem 1rem;border:1px solid rgba(242,193,78,.25);border-radius:999px;background:rgba(0,0,0,.32);color:white;font-size:.75rem;font-weight:800;backdrop-filter:blur(10px)}.bj-win{color:#b8f7d8;border-color:rgba(79,214,145,.35)}.bj-loss{color:#ffb6b6;border-color:rgba(255,111,111,.28)}
        .bj-panel{border:1px solid rgba(255,255,255,.08);border-radius:1.1rem;background:rgba(15,18,22,.75);box-shadow:0 18px 45px rgba(0,0,0,.18);backdrop-filter:blur(14px)}.bj-section{padding:1rem}.bj-section+.bj-section{border-top:1px solid rgba(255,255,255,.07)}.bj-paneltitle{margin:0 0 .7rem;color:white;font-size:.8rem;font-weight:800}
        .bj-field{display:block;margin-bottom:.75rem}.bj-field span{display:block;margin-bottom:.35rem;color:#8e959d;font-size:.65rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}.bj-input{width:100%;min-height:2.6rem;border:1px solid rgba(255,255,255,.1);border-radius:.65rem;background:rgba(0,0,0,.22);padding:.6rem .7rem;color:white;outline:none}.bj-input:focus{border-color:rgba(242,193,78,.55);box-shadow:0 0 0 3px rgba(242,193,78,.08)}
        .bj-button{width:100%;min-height:2.65rem;border-radius:.65rem;padding:.6rem .75rem;font-size:.76rem;font-weight:800;transition:transform .15s,filter .15s}.bj-button:hover{transform:translateY(-1px);filter:brightness(1.05)}.bj-button:disabled{opacity:.45;transform:none}.bj-gold{background:linear-gradient(135deg,#f4ca61,#dca938);color:#15130e}.bj-green{background:#2dba78;color:#06150f}.bj-dark{border:1px solid rgba(255,255,255,.11);background:rgba(255,255,255,.045);color:white}
        .bj-actions{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}.bj-actions .bj-button:first-child{grid-column:1/-1}.bj-info{color:#858d96;font-size:.7rem;line-height:1.55}.bj-info strong{color:#cdd2d7}.bj-seed{margin-top:.6rem;overflow:hidden;border:1px solid rgba(255,255,255,.07);border-radius:.6rem;background:rgba(0,0,0,.18);padding:.6rem;color:#8e959d;font: .6rem/1.45 ui-monospace,SFMono-Regular,Menlo,monospace;word-break:break-all}.bj-notes{display:flex;flex-wrap:wrap;gap:.5rem 1rem;margin-top:.8rem;color:#717983;font-size:.65rem}
        @media(max-width:900px){.bj-layout{grid-template-columns:1fr}.bj-panel{order:-1}.bj-rules-grid{grid-template-columns:1fr 1fr}}@media(max-width:640px){.bj-intro-head{flex-direction:column}.bj-intro-badge{white-space:normal}.bj-rules-grid{grid-template-columns:1fr}.bj-hero{align-items:flex-start;flex-direction:column}.bj-table{min-height:28rem}.bj-card{width:3.65rem;height:5.2rem}.bj-hand{padding-right:1rem;padding-left:1rem}}
    </style>

    <x-casino.loading-overlay target="prepare,deal,hit,stand,double" />

    <div class="bj-hero">
        <div class="bj-title">
            <span class="bj-mark" aria-hidden="true">♠</span>
            <div>
                <p class="bj-eyebrow">Mesa privada · Créditos virtuais</p>
                <h2>Blackjack</h2>
                <p class="bj-hint">Aproxime-se de 21 sem ultrapassar o dealer.</p>
            </div>
        </div>
        <a href="{{ route('casino.help') }}" class="text-xs text-zinc-400 hover:text-casino-gold-bright" wire:navigate>Regras e jogo responsável →</a>
    </div>

    <section class="bj-intro" aria-labelledby="blackjack-rules-title">
        <div class="bj-intro-head">
            <div>
                <p class="bj-eyebrow">Antes de começar</p>
                <h3 id="blackjack-rules-title">Como funciona o Blackjack?</h3>
            </div>
            <span class="bj-intro-badge">Objetivo: chegar a 21</span>
        </div>
        <p class="bj-intro-lead">No Blackjack, joga contra o dealer. O objetivo é ficar o mais perto possível de 21 sem ultrapassar esse valor. Uma mão com mais de 21 perde automaticamente.</p>
        <div class="bj-rules-grid">
            <div><span>01</span><strong>Receba as cartas</strong><p>Começa com duas cartas. O dealer também recebe cartas, sendo uma delas inicialmente escondida.</p></div>
            <div><span>02</span><strong>Escolha a jogada</strong><p><b>Pedir carta</b> adiciona uma carta; <b>Parar</b> mantém a pontuação; <b>Dobrar</b> duplica a aposta e recebe mais uma carta.</p></div>
            <div><span>03</span><strong>Compare as mãos</strong><p>Depois da sua jogada, o dealer completa a mão. A mão mais próxima de 21 vence.</p></div>
            <div><span>04</span><strong>Valores das cartas</strong><p>Ás vale 1 ou 11, figuras valem 10 e as restantes cartas valem o seu número.</p></div>
        </div>
        <div class="bj-intro-note"><strong>Empate:</strong> se você e o dealer terminarem com a mesma pontuação, a ronda é considerada empate e a aposta é devolvida de acordo com as regras da mesa.</div>
    </section>

    <div class="bj-layout">
        <section class="bj-table" aria-label="Mesa de Blackjack">
            <div x-show="resultVisible" x-cloak x-transition.opacity class="bj-prize-overlay" role="status" aria-live="assertive"><div class="bj-prize-card" :class="{ loss: resultType === 'loss' }"><p class="bj-prize-label" x-text="resultType === 'win' ? '🎉 Vitória' : 'Derrota'"></p><p class="bj-prize-amount" x-text="(resultType === 'win' ? '+' : '−') + Number(resultAmount).toLocaleString('pt-PT')"></p><p class="bj-prize-sub" x-text="resultType === 'win' ? 'créditos virtuais ganhos' : 'créditos virtuais perdidos'"></p></div></div><div class="bj-label">AllinBet · Blackjack</div>

            <div class="bj-hand">
                <div class="bj-handhead">
                    <span class="bj-name">Dealer</span>
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
                            @endphp
                            @if ($hidden)
                                <div class="bj-card bj-hidden" aria-label="Carta escondida">?</div>
                            @else
                                <div class="bj-card"><span class="bj-rank">{{ $rank }}</span><span class="bj-center">♠</span><span class="bj-corner">{{ $rank }}</span></div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="bj-empty">As cartas do dealer aparecem quando a mesa for iniciada.</div>
                @endif
            </div>

            <div class="bj-hand bj-player">
                <div class="bj-handhead">
                    <span class="bj-name">A sua mão</span>
                    @if ($roundResult !== []) <span class="bj-total">{{ $roundResult['player_total'] ?? '—' }}</span> @endif
                </div>
                @if ($roundResult !== [])
                    <div class="bj-cards">
                        @foreach ($roundResult['player_hand'] ?? [] as $card)
                            @php $rank = match ($card['rank']) { 1=>'A',11=>'J',12=>'Q',13=>'K',default=>$card['rank'] }; @endphp
                            <div class="bj-card"><span class="bj-rank">{{ $rank }}</span><span class="bj-center">♠</span><span class="bj-corner">{{ $rank }}</span></div>
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
                            <input type="number" min="1" max="{{ config('casino.bet_limits.max') }}" wire:model="bet" class="bj-input" inputmode="numeric">
                        </label>
                        @error('bet') <p class="mb-3 text-xs text-rose-300">{{ $message }}</p> @enderror
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
                    <p class="bj-paneltitle">A sua jogada</p>
                    <div class="bj-actions">
                        @if (in_array('hit', $roundResult['available_actions'] ?? [], true))
                            <button type="button" wire:click="hit" wire:loading.attr="disabled" class="bj-button bj-green">Pedir carta</button>
                        @endif
                        @if (in_array('stand', $roundResult['available_actions'] ?? [], true))
                            <button type="button" wire:click="stand" wire:loading.attr="disabled" class="bj-button bj-dark">Parar</button>
                        @endif
                        @if (in_array('double', $roundResult['available_actions'] ?? [], true))
                            <button type="button" wire:click="double" wire:loading.attr="disabled" class="bj-button bj-dark">Dobrar aposta</button>
                        @endif
                    </div>
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