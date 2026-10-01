<div class="casino-stats" wire:poll.30s>
    <div class="casino-stat"><strong>{{ number_format($roundsToday) }}</strong><span>rondas hoje</span></div>
    <div class="casino-stat"><strong>{{ number_format($biggestWin) }}</strong><span>maior prémio hoje</span></div>
    <div class="casino-stat"><strong>{{ $rtp !== null ? number_format($rtp, 1).'%' : '—' }}</strong><span>retorno real (7 dias)</span></div>
</div>
