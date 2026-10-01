<div class="sports-page">
    <style>
        .sports-page{--sp-gold:#f5c451;--sp-gold-hi:#ffe6a0;--sp-green:#44e0a0;--sp-cyan:#51d8ff;color:#e8edf2}
        .sports-head{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1.25rem}.sports-kicker{margin:0;color:#788696;font-size:.62rem;font-weight:950;letter-spacing:.18em;text-transform:uppercase}.sports-title{margin:.2rem 0 0;font-size:1.85rem;font-weight:1000;letter-spacing:-.03em}.sports-sub{margin:.35rem 0 0;color:#8794a3;font-size:.76rem}
        .sports-balance{min-width:13rem;padding:.8rem 1rem;border:1px solid rgba(245,196,81,.16);border-radius:1rem;background:linear-gradient(145deg,rgba(245,196,81,.08),rgba(255,255,255,.025));text-align:right}.sports-balance span{display:block;color:#768493;font-size:.58rem;font-weight:850;text-transform:uppercase;letter-spacing:.1em}.sports-balance strong{display:block;margin-top:.1rem;color:#fff;font-size:1.25rem;font-weight:1000}.sports-grid{display:grid;grid-template-columns:minmax(0,1fr) 21rem;gap:1rem}.sports-main{min-width:0}.sports-tabs{display:flex;flex-wrap:wrap;gap:.45rem;margin-bottom:.85rem}.sports-tab{padding:.55rem .8rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(255,255,255,.025);color:#8995a3;font-size:.65rem;font-weight:900;cursor:pointer}.sports-tab.is-active{border-color:rgba(245,196,81,.35);background:rgba(245,196,81,.09);color:var(--sp-gold-hi)}
        .sports-list{display:grid;gap:.7rem}.sports-match{overflow:hidden;border:1px solid rgba(255,255,255,.08);border-radius:1.05rem;background:linear-gradient(145deg,rgba(13,18,25,.94),rgba(7,11,16,.94));box-shadow:0 14px 35px rgba(0,0,0,.16)}.sports-match__top{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.7rem .8rem;border-bottom:1px solid rgba(255,255,255,.05)}.sports-meta{color:#718091;font-size:.58rem;font-weight:800}.sports-league{color:#adb8c4;font-size:.62rem;font-weight:900}.sports-status{padding:.28rem .45rem;border-radius:999px;background:rgba(68,224,160,.08);border:1px solid rgba(68,224,160,.18);color:#7df0bd;font-size:.5rem;font-weight:950;letter-spacing:.08em}
        .sports-match__body{display:grid;grid-template-columns:minmax(11rem,1fr) auto minmax(11rem,1fr);align-items:center;gap:1rem;padding:.9rem}.sports-team{display:flex;align-items:center;gap:.65rem}.sports-team.away{justify-content:flex-end;text-align:right}.sports-badge{display:grid;place-items:center;width:2.7rem;height:2.7rem;border-radius:.8rem;border:1px solid rgba(255,255,255,.08);background:linear-gradient(145deg,#141c27,#090d13);color:#dfe7ed;font-size:.58rem;font-weight:1000;letter-spacing:.04em}.sports-team strong{display:block;color:#f0f4f7;font-size:.78rem;font-weight:950}.sports-team small{display:block;margin-top:.12rem;color:#697787;font-size:.56rem}.sports-vs{text-align:center}.sports-vs span{display:block;color:#c1cad4;font-size:.62rem;font-weight:950}.sports-vs small{display:block;margin-top:.12rem;color:#596675;font-size:.5rem}
        .sports-markets{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem;padding:0 .9rem .9rem}.sports-market{display:flex;align-items:center;justify-content:space-between;gap:.5rem;min-width:0;padding:.55rem .6rem;border:1px solid rgba(255,255,255,.07);border-radius:.7rem;background:rgba(255,255,255,.02);color:#9eabb8;text-align:left;cursor:pointer;transition:.15s}.sports-market:hover{border-color:rgba(81,216,255,.3);background:rgba(81,216,255,.045);transform:translateY(-1px)}.sports-market.is-selected{border-color:rgba(245,196,81,.52);background:rgba(245,196,81,.09);box-shadow:0 0 0 1px rgba(245,196,81,.08)}.sports-market span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.58rem;font-weight:800}.sports-market strong{color:#f7d477;font-size:.68rem;font-weight:1000}.sports-filter-note{margin:.75rem 0 0;color:#687686;font-size:.58rem}
        .sports-side{display:grid;align-content:start;gap:.75rem}.sports-slip{position:sticky;top:1rem;border:1px solid rgba(245,196,81,.18);border-radius:1.1rem;background:linear-gradient(145deg,rgba(18,23,31,.97),rgba(8,12,18,.98));box-shadow:0 22px 50px rgba(0,0,0,.24);padding:1rem}.sports-slip__head{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.sports-slip h2{margin:0;color:#f6f8fa;font-size:.9rem;font-weight:950}.sports-clear{border:0;background:transparent;color:#718092;font-size:.58rem;font-weight:800;cursor:pointer}.sports-clear:hover{color:#f6a0a6}.sports-empty{padding:2rem .5rem;text-align:center}.sports-empty__icon{font-size:2rem;opacity:.7}.sports-empty strong{display:block;margin-top:.5rem;color:#dfe6ec;font-size:.72rem}.sports-empty p{margin:.3rem 0 0;color:#697788;font-size:.6rem;line-height:1.5}.sports-selection{position:relative;padding:.7rem 1.8rem .7rem 0;border-bottom:1px solid rgba(255,255,255,.055)}.sports-selection:last-of-type{border-bottom:0}.sports-selection strong{display:block;color:#edf2f5;font-size:.66rem;font-weight:900}.sports-selection span{display:block;margin-top:.18rem;color:#788696;font-size:.56rem}.sports-selection b{display:block;margin-top:.35rem;color:var(--sp-gold);font-size:.66rem}.sports-selection__remove{position:absolute;top:.55rem;right:0;width:1.35rem;height:1.35rem;border:1px solid rgba(255,255,255,.08);border-radius:50%;background:rgba(255,255,255,.025);color:#778493;cursor:pointer}.sports-summary{margin-top:.8rem;padding:.7rem;border:1px solid rgba(255,255,255,.06);border-radius:.75rem;background:rgba(255,255,255,.02)}.sports-summary-row{display:flex;justify-content:space-between;gap:1rem;padding:.28rem 0;color:#758393;font-size:.59rem}.sports-summary-row strong{color:#dfe6eb}.sports-potential{display:flex;justify-content:space-between;gap:1rem;margin-top:.45rem;padding-top:.55rem;border-top:1px solid rgba(255,255,255,.07)}.sports-potential span{color:#8794a2;font-size:.6rem}.sports-potential strong{color:#7df0bd;font-size:1rem;font-weight:1000}.sports-stakes{display:grid;grid-template-columns:repeat(4,1fr);gap:.4rem;margin-top:.65rem}.sports-stake{padding:.48rem .2rem;border:1px solid rgba(255,255,255,.07);border-radius:.6rem;background:rgba(255,255,255,.025);color:#9da9b5;font-size:.58rem;font-weight:900;cursor:pointer}.sports-stake.is-active{border-color:rgba(245,196,81,.4);background:rgba(245,196,81,.08);color:#f7d477}.sports-submit{width:100%;margin-top:.65rem;min-height:2.9rem;border:1px solid rgba(245,196,81,.55);border-radius:.75rem;background:linear-gradient(180deg,#ffeaa9,#e0aa35 56%,#95610f);color:#281c07;font-size:.72rem;font-weight:1000;letter-spacing:.08em;text-transform:uppercase;box-shadow:0 4px 0 #66440a;cursor:pointer}.sports-submit:disabled{opacity:.5;cursor:not-allowed}.sports-error{margin-top:.5rem;color:#ff9da3;font-size:.6rem}.sports-notice{padding:.75rem;border:1px solid rgba(81,216,255,.1);border-radius:.8rem;background:rgba(81,216,255,.035);color:#718293;font-size:.57rem;line-height:1.5}.sports-notice strong{display:block;margin-bottom:.2rem;color:#93e8ff;font-size:.6rem}.sports-recent{padding:1rem}.sports-recent h3{margin:0;color:#eef2f6;font-size:.76rem;font-weight:950}.sports-bet-row{padding:.65rem 0;border-bottom:1px solid rgba(255,255,255,.05)}.sports-bet-row:last-child{border-bottom:0}.sports-bet-row__top{display:flex;justify-content:space-between;gap:.7rem}.sports-bet-row strong{color:#e6edf2;font-size:.6rem}.sports-bet-row b{color:#f5d275;font-size:.6rem}.sports-bet-row small{display:block;margin-top:.2rem;color:#677584;font-size:.52rem}.sports-bet-row em{font-style:normal;color:#72e6b6}
        @media(max-width:980px){.sports-grid{grid-template-columns:1fr}.sports-slip{position:static}.sports-side{grid-template-columns:1fr 1fr}.sports-recent{grid-column:1/-1}}@media(max-width:650px){.sports-head{align-items:flex-start;flex-direction:column}.sports-balance{width:100%;text-align:left}.sports-side{grid-template-columns:1fr}.sports-match__body{grid-template-columns:1fr auto 1fr}.sports-markets{grid-template-columns:1fr}.sports-market{min-height:2.5rem}}
    </style>

    <header class="sports-head">
        <div>
            <p class="sports-kicker">ALLINBET · SPORTS</p>
            <h1 class="sports-title">Apostas desportivas</h1>
            <p class="sports-sub">Escolhe um prognóstico, adiciona-o ao boletim e calcula o potencial prémio com créditos virtuais.</p>
        </div>
        <div class="sports-balance">
            <span>Saldo disponível</span>
            <strong>{{ number_format($balance, 0, ',', '.') }} CR</strong>
        </div>
    </header>

    <div class="sports-grid">
        <main class="sports-main">
            <div class="sports-tabs" role="tablist" aria-label="Desportos">
                @foreach (['all' => 'Todos', 'Futebol' => '⚽ Futebol', 'Basquetebol' => '🏀 Basquetebol', 'Ténis' => '🎾 Ténis'] as $key => $label)
                    <button type="button" class="sports-tab {{ $sport === $key ? 'is-active' : '' }}" wire:click="$set('sport', '{{ $key }}')">{{ $label }}</button>
                @endforeach
            </div>

            <p class="sports-filter-note">Agenda e odds virtuais de demonstração do AllinBet.</p>

            <div class="sports-list">
                @forelse (collect($matches)->where(fn ($match) => $sport === 'all' || $match['sport'] === $sport) as $match)
                    @php
                        $selectedMarket = collect($slip)->firstWhere('match_id', $match['id']);
                    @endphp
                    <article class="sports-match">
                        <div class="sports-match__top">
                            <div>
                                <span class="sports-meta">{{ $match['date'] }} · {{ $match['time'] }}</span>
                                <span class="sports-league">{{ $match['league'] }}</span>
                            </div>
                            <span class="sports-status">{{ $match['status'] }}</span>
                        </div>

                        <div class="sports-match__body">
                            <div class="sports-team">
                                <span class="sports-badge">{{ $match['homeShort'] }}</span>
                                <div><strong>{{ $match['home'] }}</strong><small>Casa</small></div>
                            </div>
                            <div class="sports-vs"><span>VS</span><small>{{ $match['sport'] }}</small></div>
                            <div class="sports-team away">
                                <div><strong>{{ $match['away'] }}</strong><small>Fora</small></div>
                                <span class="sports-badge">{{ $match['awayShort'] }}</span>
                            </div>
                        </div>

                        <div class="sports-markets">
                            @foreach ($match['markets'] as $market)
                                @php
                                    $isSelected = $selectedMarket && $selectedMarket['market_id'] === $market['id'];
                                @endphp
                                <button type="button"
                                        class="sports-market {{ $isSelected ? 'is-selected' : '' }}"
                                        wire:click="select('{{ $match['id'] }}', '{{ $market['id'] }}')">
                                    <span>{{ $market['label'] }} · {{ $market['name'] }}</span>
                                    <strong>{{ number_format($market['odd'], 2, ',', '.') }}</strong>
                                </button>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="casino-card p-8 text-center">
                        <p class="text-sm text-zinc-400">Não existem eventos nesta modalidade.</p>
                    </div>
                @endforelse
            </div>
        </main>

        <aside class="sports-side">
            <section class="sports-slip">
                <div class="sports-slip__head">
                    <h2>Boletim de apostas <span>({{ count($slip) }})</span></h2>
                    @if ($slip)
                        <button type="button" class="sports-clear" wire:click="clearSlip">Limpar</button>
                    @endif
                </div>

                @if ($slip)
                    @foreach ($slip as $selection)
                        <div class="sports-selection">
                            <button type="button" class="sports-selection__remove" wire:click="remove('{{ $selection['match_id'] }}')" aria-label="Remover seleção">×</button>
                            <strong>{{ $selection['selection'] }}</strong>
                            <span>{{ $selection['home'] }} · {{ $selection['away'] }}</span>
                            <span>{{ $selection['date'] }} · {{ $selection['time'] }} · {{ $selection['label'] }}</span>
                            <b>{{ number_format($selection['odd'], 2, ',', '.') }}</b>
                        </div>
                    @endforeach

                    @php
                        $combinedOdd = collect($slip)->reduce(fn ($carry, $selection) => $carry * (float) $selection['odd'], 1.0);
                        $potential = max((int) $stake, (int) floor((int) $stake * round($combinedOdd, 2)));
                    @endphp

                    <div class="sports-summary">
                        <div class="sports-summary-row"><span>Odd combinada</span><strong>{{ number_format($combinedOdd, 2, ',', '.') }}</strong></div>
                        <div class="sports-summary-row">
                            <span>Aposta</span>
                            <strong>{{ number_format((int) $stake, 0, ',', '.') }} CR</strong>
                        </div>
                        <div class="sports-potential"><span>Prémio potencial</span><strong>+{{ number_format($potential, 0, ',', '.') }} CR</strong></div>
                    </div>

                    <div class="sports-stakes">
                        @foreach ([50, 100, 250, 500] as $amount)
                            <button type="button" class="sports-stake {{ (int) $stake === $amount ? 'is-active' : '' }}" wire:click="setStake({{ $amount }})">{{ $amount }}</button>
                        @endforeach
                    </div>

                    <button type="button" class="sports-submit" wire:click="placeBet" wire:loading.attr="disabled" wire:target="placeBet">
                        <span wire:loading.remove wire:target="placeBet">Registar aposta · {{ number_format((int) $stake, 0, ',', '.') }} CR</span>
                        <span wire:loading wire:target="placeBet">A registar…</span>
                    </button>

                    @error('slip') <p class="sports-error">{{ $message }}</p> @enderror
                    @error('stake') <p class="sports-error">{{ $message }}</p> @enderror
                @else
                    <div class="sports-empty">
                        <div class="sports-empty__icon">🎟️</div>
                        <strong>O boletim está vazio</strong>
                        <p>Escolhe uma odd num dos jogos para adicionares o primeiro prognóstico.</p>
                    </div>
                @endif
            </section>

            <section class="sports-notice">
                <strong>Créditos virtuais</strong>
                Os eventos e odds apresentados nesta área são dados de demonstração. As apostas usam apenas créditos virtuais do AllinBet e não representam dinheiro real.
            </section>

            <section class="casino-card sports-recent">
                <p class="casino-eyebrow">AS TUAS APOSTAS</p>
                <h3 class="mt-1">Últimos boletins</h3>

                @forelse ($recentBets as $bet)
                    <div class="sports-bet-row">
                        <div class="sports-bet-row__top">
                            <strong>#{{ $bet->id }} · {{ strtoupper($bet->status) }}</strong>
                            <b>{{ number_format((float) $bet->combined_odd, 2, ',', '.') }}×</b>
                        </div>
                        <small>{{ count($bet->selections ?? []) }} seleção(ões) · {{ number_format($bet->stake, 0, ',', '.') }} CR</small>
                        <small>Potencial: <em>+{{ number_format($bet->potential_payout, 0, ',', '.') }} CR</em></small>
                    </div>
                @empty
                    <p class="mt-3 text-xs text-zinc-500">Ainda não tens apostas registadas.</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
