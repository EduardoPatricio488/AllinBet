<div class="casino-win-ticker" wire:poll.10s.visible>
    <div class="casino-win-ticker__heading">
        <span class="casino-badge"><i></i> RONDAS CONCLUÍDAS</span>
        <span>Resultados reais, apresentados sem dados pessoais.</span>
    </div>

    <div wire:loading class="casino-win-ticker__loading" aria-label="A atualizar vitórias">
        <x-casino.skeleton height="h-4" width="w-32" />
        <x-casino.skeleton height="h-4" width="w-40" />
        <x-casino.skeleton height="h-4" width="w-28" />
    </div>

    <div wire:loading.remove class="casino-win-ticker__viewport">
        <div class="casino-win-ticker__track" @if ($wins->count() < 2) data-still @endif>
            @forelse ($wins as $win)
                <div class="casino-win-item">
                    <span class="casino-win-item__mark" aria-hidden="true">✦</span>
                    <span class="casino-win-item__copy">
                        <strong>{{ str($win['game'])->headline() }}</strong>
                        <small>{{ $win['created_at']->diffForHumans() }}</small>
                    </span>
                    <span class="casino-win-item__amount">+{{ $win['payout'] }} <small>CR</small></span>
                </div>
            @empty
                <div class="casino-win-empty" role="status">
                    <span aria-hidden="true">◎</span>
                    <strong>Ainda não existem vitórias reais.</strong>
                    <small>Quando houver rondas vencedoras, aparecem aqui automaticamente.</small>
                </div>
            @endforelse

            @if ($wins->count() > 1)
                @foreach ($wins as $win)
                    <div class="casino-win-item" aria-hidden="true">
                        <span class="casino-win-item__mark">✦</span>
                        <span class="casino-win-item__copy">
                            <strong>{{ str($win['game'])->headline() }}</strong>
                            <small>{{ $win['created_at']->diffForHumans() }}</small>
                        </span>
                        <span class="casino-win-item__amount">+{{ $win['payout'] }} <small>CR</small></span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
