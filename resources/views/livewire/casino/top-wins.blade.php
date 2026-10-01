<div class="casino-card" wire:poll.30s>
    <p class="casino-eyebrow">PÓDIO DO DIA</p>
    <ol class="casino-podium">
        @forelse ($wins as $i => $w)
            <li>
                <span class="casino-podium__pos">{{ $i + 1 }}</span>
                <span>{{ $w['name'] }} <small>{{ ucfirst($w['game']) }}</small></span>
                <strong>+{{ number_format($w['payout']) }}</strong>
            </li>
        @empty
            <li>Ainda não há vitórias registadas hoje.</li>
        @endforelse
    </ol>
</div>
