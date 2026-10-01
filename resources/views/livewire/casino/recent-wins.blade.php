<div class="casino-win-ticker" wire:poll.10s.visible>
    <div class="casino-win-ticker__heading">
        @if ($isPreview)
            <span class="casino-badge casino-badge--preview">DEMO · PRÉ-VISUALIZAÇÃO</span>
            <span>Os exemplos não representam utilizadores ou rondas reais.</span>
        @else
            <span class="casino-badge"><i></i> RONDAS CONCLUÍDAS</span>
            <span>Resultados recentes, apresentados sem dados pessoais.</span>
        @endif
    </div>

    <div wire:loading class="casino-win-ticker__loading" aria-label="A atualizar vitórias">
        <x-casino.skeleton height="h-4" width="w-32" />
        <x-casino.skeleton height="h-4" width="w-40" />
        <x-casino.skeleton height="h-4" width="w-28" />
    </div>

    <div wire:loading.remove class="casino-win-ticker__viewport">
        <div class="casino-win-ticker__track" @if ($wins->count() < 2) data-still @endif>
            @foreach ($wins as $win)
                <div class="casino-win-item">
                    <span class="casino-win-item__mark" aria-hidden="true">✦</span>
                    <span class="casino-win-item__copy">
                        <strong>{{ str($win['game'])->headline() }}</strong>
                        <small>{{ $win['preview'] ? 'Exemplo demonstrativo' : $win['created_at']->diffForHumans() }}</small>
                    </span>
                    <span class="casino-win-item__amount">+{{ $win['payout'] }} <small>CR</small></span>
                    @if ($win['preview']) <span class="casino-win-item__demo">DEMO</span> @endif
                </div>
            @endforeach

            @if ($wins->count() > 1)
                @foreach ($wins as $win)
                    <div class="casino-win-item" aria-hidden="true">
                        <span class="casino-win-item__mark">✦</span>
                        <span class="casino-win-item__copy"><strong>{{ str($win['game'])->headline() }}</strong><small>{{ $win['preview'] ? 'Exemplo demonstrativo' : $win['created_at']->diffForHumans() }}</small></span>
                        <span class="casino-win-item__amount">+{{ $win['payout'] }} <small>CR</small></span>
                        @if ($win['preview']) <span class="casino-win-item__demo">DEMO</span> @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
