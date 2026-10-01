<div class="sports-bets-modal-content">
    <style>
        .sports-bets-modal-content{--sb-gold:#f5c451;--sb-green:#49e0a3;color:#e8edf2}
        .sports-bets-modal-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.15rem 1.2rem 1rem;border-bottom:1px solid rgba(255,255,255,.06)}
        .sports-bets-modal-kicker{margin:0;color:#718090;font-size:.58rem;font-weight:950;letter-spacing:.17em;text-transform:uppercase}
        .sports-bets-modal-title{margin:.25rem 0 0;color:#f5f7f9;font-size:1.3rem;font-weight:1000;letter-spacing:-.03em}
        .sports-bets-modal-sub{margin:.25rem 0 0;color:#7e8b99;font-size:.61rem}
        .sports-bets-modal-tools{display:flex;align-items:center;gap:.5rem}
        .sports-bets-refresh,.sports-bets-close{display:grid;place-items:center;width:2.25rem;height:2.25rem;border:1px solid rgba(255,255,255,.08);border-radius:.7rem;background:rgba(255,255,255,.025);color:#94a2b0;cursor:pointer;transition:.15s}
        .sports-bets-refresh:hover,.sports-bets-close:hover{border-color:rgba(245,196,81,.35);color:#f5d477;background:rgba(245,196,81,.07)}
        .sports-bets-refresh svg{width:.85rem;height:.85rem}
        .sports-bets-filter{display:flex;gap:.4rem;padding:.9rem 1.2rem .1rem;overflow-x:auto}
        .sports-bets-tab{flex:0 0 auto;padding:.5rem .72rem;border:1px solid rgba(255,255,255,.08);border-radius:999px;background:rgba(255,255,255,.025);color:#8190a0;font-size:.57rem;font-weight:900;cursor:pointer;transition:.15s}
        .sports-bets-tab:hover{border-color:rgba(245,196,81,.25);color:#cbd4dc}.sports-bets-tab.is-active{border-color:rgba(245,196,81,.4);background:rgba(245,196,81,.09);color:#f5d477;box-shadow:0 0 0 1px rgba(245,196,81,.06)}
        .sports-bets-list{display:grid;gap:.55rem;padding:1rem 1.2rem 1.2rem;max-height:min(65vh,620px);overflow-y:auto}
        .sports-bet-card{overflow:hidden;border:1px solid rgba(255,255,255,.075);border-radius:.95rem;background:linear-gradient(145deg,rgba(15,20,27,.98),rgba(8,12,18,.98));transition:border-color .15s,transform .15s,box-shadow .15s}
        .sports-bet-card:hover{border-color:rgba(245,196,81,.2);box-shadow:0 12px 30px rgba(0,0,0,.16);transform:translateY(-1px)}
        .sports-bet-card__trigger{width:100%;padding:.78rem .85rem;border:0;background:transparent;color:inherit;text-align:left;cursor:pointer}
        .sports-bet-card__top{display:flex;align-items:center;justify-content:space-between;gap:.8rem}.sports-bet-card__id{color:#9ba7b3;font-size:.57rem;font-weight:950}
        .sports-bet-card__status{padding:.25rem .45rem;border:1px solid rgba(245,196,81,.2);border-radius:999px;background:rgba(245,196,81,.07);color:#f5d477;font-size:.48rem;font-weight:950;text-transform:uppercase;letter-spacing:.08em}
        .sports-bet-card__status.won{border-color:rgba(73,224,163,.2);background:rgba(73,224,163,.07);color:#75ecbb}.sports-bet-card__status.lost{border-color:rgba(255,94,103,.2);background:rgba(255,94,103,.07);color:#ff9da3}
        .sports-bet-card__headline{display:flex;align-items:center;justify-content:space-between;gap:.7rem;margin-top:.55rem}.sports-bet-card__headline strong{color:#eef3f6;font-size:.67rem;font-weight:950}.sports-bet-card__headline span{color:#728090;font-size:.54rem}
        .sports-bet-card__preview{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.6rem;margin-top:.5rem}.sports-bet-card__preview-text{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#798795;font-size:.54rem}.sports-bet-card__odd{color:#f5d477;font-size:.58rem;font-weight:1000}
        .sports-bet-card__chevron{display:inline-block;margin-left:.35rem;color:#647381;transition:transform .18s}.sports-bet-card__chevron.is-open{transform:rotate(180deg)}
        .sports-bet-card__details{padding:0 .85rem .85rem;border-top:1px solid rgba(255,255,255,.05)}
        .sports-bet-selection{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.55rem;padding:.58rem 0;border-bottom:1px solid rgba(255,255,255,.045)}.sports-bet-selection:last-child{border-bottom:0}
        .sports-bet-selection strong{display:block;color:#eaf0f4;font-size:.6rem;font-weight:900}.sports-bet-selection span{display:block;margin-top:.15rem;color:#707f8e;font-size:.52rem;line-height:1.4}.sports-bet-selection b{align-self:center;color:#f5d477;font-size:.62rem;font-weight:1000}
        .sports-bet-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:.45rem;margin-top:.55rem;padding-top:.6rem;border-top:1px solid rgba(255,255,255,.06)}.sports-bet-summary span{display:block;color:#718090;font-size:.5rem}.sports-bet-summary strong{display:block;margin-top:.14rem;color:#e4ebef;font-size:.62rem;font-weight:950}.sports-bet-summary .potential strong{color:#7eeabd}
        .sports-bet-date{display:block;margin-top:.55rem;color:#5f6d7b;font-size:.49rem}
        .sports-bets-stats{display:flex;gap:.45rem;padding:.8rem 1.2rem .1rem}.sports-bets-stat{flex:1;padding:.5rem .6rem;border:1px solid rgba(255,255,255,.055);border-radius:.7rem;background:rgba(255,255,255,.02)}.sports-bets-stat span{display:block;color:#687787;font-size:.48rem;text-transform:uppercase;letter-spacing:.08em}.sports-bets-stat strong{display:block;margin-top:.12rem;color:#e5ebef;font-size:.68rem;font-weight:1000}
        .sports-bets-empty{padding:2.7rem 1rem;text-align:center}.sports-bets-empty__icon{font-size:2.2rem}.sports-bets-empty strong{display:block;margin-top:.6rem;color:#e5ebef;font-size:.74rem}.sports-bets-empty p{margin:.3rem auto 0;max-width:27rem;color:#6c7a89;font-size:.57rem;line-height:1.5}
        @media(max-width:600px){.sports-bets-modal-head{align-items:flex-start}.sports-bets-modal-title{font-size:1.1rem}.sports-bets-modal-sub{max-width:16rem}.sports-bets-list,.sports-bets-filter,.sports-bets-stats{padding-left:.8rem;padding-right:.8rem}.sports-bet-summary{grid-template-columns:1fr}.sports-bet-summary>div{display:flex;justify-content:space-between;gap:1rem}.sports-bet-summary strong{margin-top:0}}
    </style>

    <div class="sports-bets-modal-head">
        <div>
            <p class="sports-bets-modal-kicker">ALLINBET · SPORTS</p>
            <h2 id="casino-sports-bets-title" class="sports-bets-modal-title">As minhas apostas</h2>
            <p class="sports-bets-modal-sub">Os teus boletins aparecem aqui sem sair da área de desporto.</p>
        </div>
        <div class="sports-bets-modal-tools">
            <button type="button" class="sports-bets-refresh" wire:click="$refresh" wire:loading.attr="disabled" title="Atualizar apostas" aria-label="Atualizar apostas">
                <span wire:loading.remove>↻</span>
                <span wire:loading>…</span>
            </button>
        </div>
    </div>

    <div class="sports-bets-stats">
        <div class="sports-bets-stat"><span>Total</span><strong>{{ $totalBets }}</strong></div>
        <div class="sports-bets-stat"><span>Em aberto</span><strong>{{ $openBets }}</strong></div>
        <div class="sports-bets-stat"><span>A mostrar</span><strong>{{ $bets->count() }}</strong></div>
    </div>

    <div class="sports-bets-filter" role="tablist" aria-label="Filtrar apostas">
        @foreach (['all' => 'Todas', 'pending' => 'Em aberto', 'won' => 'Ganharam', 'lost' => 'Perderam'] as $key => $label)
            <button type="button" class="sports-bets-tab {{ $filter === $key ? 'is-active' : '' }}" wire:click="setFilter('{{ $key }}')" wire:loading.attr="disabled">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="sports-bets-list">
        @forelse ($bets as $bet)
            @php
                $selections = $bet->selections ?? [];
                $firstSelection = $selections[0] ?? [];
                $status = $bet->status;
                $statusLabel = $status === 'pending' ? 'Em aberto' : ($status === 'won' ? 'Ganhou' : 'Perdeu');
            @endphp
            <article class="sports-bet-card">
                <button type="button"
                        class="sports-bet-card__trigger"
                        wire:click="toggleBet({{ $bet->id }})"
                        aria-expanded="{{ $expandedBet === $bet->id ? 'true' : 'false' }}">
                    <div class="sports-bet-card__top">
                        <span class="sports-bet-card__id">BOLETIM #{{ $bet->id }} · {{ count($selections) }} seleção(ões)</span>
                        <span class="sports-bet-card__status {{ $status }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="sports-bet-card__headline">
                        <strong>{{ number_format((int) $bet->stake, 0, ',', '.') }} CR</strong>
                        <span>{{ $bet->created_at?->format('d/m/Y · H:i') }} <span class="sports-bet-card__chevron {{ $expandedBet === $bet->id ? 'is-open' : '' }}">⌄</span></span>
                    </div>
                    <div class="sports-bet-card__preview">
                        <span class="sports-bet-card__preview-text">{{ $firstSelection['home'] ?? '' }} · {{ $firstSelection['away'] ?? '' }} — {{ $firstSelection['selection'] ?? 'Seleção' }}</span>
                        <span class="sports-bet-card__odd">{{ number_format((float) $bet->combined_odd, 2, ',', '.') }}×</span>
                    </div>
                </button>

                @if ($expandedBet === $bet->id)
                    <div class="sports-bet-card__details">
                        @foreach ($selections as $selection)
                            <div class="sports-bet-selection">
                                <div>
                                    <strong>{{ $selection['selection'] ?? 'Seleção' }}</strong>
                                    <span>{{ $selection['home'] ?? '' }} · {{ $selection['away'] ?? '' }} · {{ $selection['date'] ?? '' }} {{ $selection['time'] ?? '' }}</span>
                                </div>
                                <b>{{ number_format((float) ($selection['odd'] ?? 0), 2, ',', '.') }}</b>
                            </div>
                        @endforeach
                        <div class="sports-bet-summary">
                            <div><span>Aposta</span><strong>{{ number_format((int) $bet->stake, 0, ',', '.') }} CR</strong></div>
                            <div><span>Odd combinada</span><strong>{{ number_format((float) $bet->combined_odd, 2, ',', '.') }}×</strong></div>
                            <div class="potential"><span>Prémio potencial</span><strong>+{{ number_format((int) $bet->potential_payout, 0, ',', '.') }} CR</strong></div>
                        </div>
                        <span class="sports-bet-date">Registado {{ $bet->created_at?->format('d/m/Y · H:i:s') }}</span>
                    </div>
                @endif
            </article>
        @empty
            <div class="sports-bets-empty">
                <div class="sports-bets-empty__icon">🎟️</div>
                <strong>Ainda não tens apostas registadas</strong>
                <p>Seleciona um prognóstico na área de desporto e regista o boletim. O registo aparecerá aqui automaticamente.</p>
            </div>
        @endforelse
    </div>
</div>
