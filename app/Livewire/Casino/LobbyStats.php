<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\RoundStatus;
use App\Models\GameRound;
use Illuminate\View\View;
use Livewire\Component;

class LobbyStats extends Component
{
    public function render(): View
    {
        $today = GameRound::query()->where('status', RoundStatus::Completed)->whereDate('created_at', today());
        $week = GameRound::query()->where('status', RoundStatus::Completed)->where('created_at', '>=', now()->subDays(7));
        $bet = (clone $week)->sum('bet');
        $payout = (clone $week)->sum('payout');

        return view('livewire.casino.lobby-stats', [
            'roundsToday' => (clone $today)->count(),
            'biggestWin' => (clone $today)->max('payout') ?? 0,
            'rtp' => $bet > 0 ? round(($payout / $bet) * 100, 1) : null,
        ]);
    }
}
