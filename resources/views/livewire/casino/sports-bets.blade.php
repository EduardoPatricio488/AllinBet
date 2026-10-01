<div class="sports-bets-page">
    <style>
        .sports-bets-page{--sb-gold:#f5c451;--sb-green:#49e0a3;color:#e8edf2}.sports-bets-head{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1.1rem}.sports-bets-kicker{margin:0;color:#748393;font-size:.62rem;font-weight:950;letter-spacing:.18em;text-transform:uppercase}.sports-bets-title{margin:.2rem 0 0;color:#f3f6f8;font-size:1.8rem;font-weight:1000;letter-spacing:-.03em}.sports-bets-sub{margin:.35rem 0 0;color:#8492a1;font-size:.75rem}.sports-bets-filter{display:flex;gap:.4rem}.sports-bets-tab{padding:.5rem .72rem;border:1px solid rgba(255,255,255,.08);border-radius:.7rem;background:rgba(255,255,255,.025);color:#8190a0;font-size:.58rem;font-weight:900;cursor:pointer}.sports-bets-tab.is-active{border-color:rgba(245,196,81,.35);background:rgba(245,196,81,.08);color:#f5d477}.sports-bets-list{display:grid;gap:.7rem}.sports-bet-card{overflow:hidden;border:1px solid rgba(255,255,255,.08);border-radius:1rem;background:linear-gradient(145deg,rgba(14,19,26,.96),rgba(7,11,16,.96))}.sports-bet-card__top{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.8rem 1rem;border-bottom:1px solid rgba(255,255,255,.05)}.sports-bet-card__id{color:#9ba7b3;font-size:.6rem;font-weight:900}.sports-bet-card__status{padding:.28rem .48rem;border:1px solid rgba(245,196,81,.2);border-radius:999px;background:rgba(245,196,81,.07);color:#f5d477;font-size:.5rem;font-weight:950;text-transform:uppercase;letter-spacing:.08em}.sports-bet-card__status.won{border-color:rgba(73,224,163,.2);background:rgba(73,224,163,.07);color:#75ecbb}.sports-bet-card__status.lost{border-color:rgba(255,94,103,.2);background:rgba(255,94,103,.07);color:#ff9da3}.sports-bet-card__body{padding:.8rem 1rem}.sports-bet-selection{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.5rem;padding:.58rem 0;border-bottom:1px solid rgba(255,255,255,.045)}.sports-bet-selection:last-child{border-bottom:0}.sports-bet-selection strong{display:block;color:#eaf0f4;font-size:.65rem;font-weight:900}.sports-bet-selection span{display:block;margin-top:.16rem;color:#707f8e;font-size:.55rem;line-height:1.45}.sports-bet-selection b{align-self:center;color:#f5d477;font-size:.65rem;font-weight:1000}.sports-bet-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:.55rem;margin-top:.7rem;padding-top:.7rem;border-top:1px solid rgba(255,255,255,.06)}.sports-bet-summary span{display:block;color:#718090;font-size:.52rem}.sports-bet-summary strong{display:block;margin-top:.18rem;color:#e4ebef;font-size:.68rem;font-weight:950}.sports-bet-summary .potential strong{color:#7eeabd}.sports-bet-date{display:block;margin-top:.7rem;color:#5f6d7b;font-size:.52rem}.sports-bets-empty{padding:3rem 1rem;text-align:center}.sports-bets-empty__icon{font-size:2.4rem}.sports-bets-empty strong{display:block;margin-top:.7rem;color:#e5ebef;font-size:.78rem}.sports-bets-empty p{margin:.35rem auto 0;max-width:28rem;color:#6c7a89;font-size:.62rem;line-height:1.5}.sports-bets-back{display:inline-flex;margin-top:1rem;padding:.55rem .75rem;border:1px solid rgba(255,255,255,.08);border-radius:.7rem;color:#a6b2bd;font-size:.6rem;font-weight:900}.sports-bets-back:hover{border-color:rgba(245,196,81,.25);color:#f5d477}@media(max-width:650px){.sports-bets-head{align-items:flex-start;flex-direction:column}.sports-bets-filter{width:100%}.sports-bets-tab{flex:1}.sports-bet-summary{grid-template-columns:1fr}.sports-bet-summary>div{display:flex;justify-content:space-between;gap:1rem}.sports-bet-summary strong{margin-top:0}}
    </style>

    <div class="sports-bets-head">
        <div>
            <p class="sports-bets-kicker">ALLINBET · SPORTS</p>
            <h1 class="sports-bets-title">As minhas apostas</h1>
            <p class="sports-bets-sub">Consulta todos os boletins que registaste e os respetivos prognósticos.</p>
        </div>

        <div class="sports-bets-filter" role="tablist" aria-label="Filtrar apostas">
            @foreach (['all' => 'Todas', 'pending' => 'Em aberto', 'won' => 'Ganharam', 'lost' => 'Perderam'] as $key => $label)
                <button type="button"
                        class="sports-bets-tab {{ $filter === $key ? 'is-active' : '' }}"
                        wire:click="$set('filter', '{{ $key }}')">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="sports-bets-list">
        @forelse ($bets as $bet)
            <article class="sports-bet-card">
                <div class="sports-bet-card__top">
                    <span class="sports-bet-card__id">BOLETIM #{{ $bet->id }} · {{ count($bet->selections ?? []) }} seleção(ões)</span>
                    <span class="sports-bet-card__status {{ $bet->status }}">{{ $bet->status === 'pending' ? 'Em aberto' : ($bet->status === 'won' ? 'Ganhou' : 'Perdeu') }}</span>
                </div>

                <div class="sports-bet-card__body">
                    @foreach ($bet->selections ?? [] as $selection)
                        <div class="sports-bet-selection">
                            <div>
                                <strong>{{ $selection['selection'] ?? 'Seleção' }}</strong>
                                <span>{{ $selection['home'] ?? '' }} · {{ $selection['away'] ?? '' }} · {{ $selection['date'] ?? '' }} {{ $selection['time'] ?? '' }}</span>
                            </div>
                            <b>{{ number_format((float) ($selection['odd'] ?? 0), 2, ',', '.') }}</b>
                        </div>
                    @endforeach

                    <div class="sports-bet-summary">
                        <div>
                            <span>Aposta</span>
                            <strong>{{ number_format((int) $bet->stake, 0, ',', '.') }} CR</strong>
                        </div>
                        <div>
                            <span>Odd combinada</span>
                            <strong>{{ number_format((float) $bet->combined_odd, 2, ',', '.') }}×</strong>
                        </div>
                        <div class="potential">
                            <span>Prémio potencial</span>
                            <strong>+{{ number_format((int) $bet->potential_payout, 0, ',', '.') }} CR</strong>
                        </div>
                    </div>

                    <span class="sports-bet-date">Registado {{ $bet->created_at?->format('d/m/Y · H:i') }}</span>
                </div>
            </article>
        @empty
            <section class="casino-card sports-bets-empty">
                <div class="sports-bets-empty__icon">🎟️</div>
                <strong>Ainda não tens apostas registadas</strong>
                <p>Quando selecionares um prognóstico na área de Apostas desportivas e registares o boletim, ele ficará disponível aqui.</p>
                <a href="{{ route('casino.sports') }}" class="sports-bets-back" wire:navigate>Ir para Apostas desportivas →</a>
            </section>
        @endforelse
    </div>
</div>
