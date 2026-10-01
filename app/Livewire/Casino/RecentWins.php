<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\RoundStatus;
use App\Models\GameRound;
use Illuminate\View\View;
use Livewire\Component;

class RecentWins extends Component
{
    public function render(): View
    {
        $wins = GameRound::query()
            ->where('status', RoundStatus::Completed)
            ->where('payout', '>', 0)
            ->latest('created_at')
            ->limit(8)
            ->get(['game', 'payout', 'created_at'])
            ->map(fn (GameRound $round): array => [
                'game' => $round->game->value,
                'payout' => $round->payout,
                'created_at' => $round->created_at,
                'preview' => false,
            ]);

        $isPreview = $wins->isEmpty();

        if ($isPreview) {
            $wins = collect([
                ['game' => 'coinflip', 'payout' => 120, 'created_at' => null, 'preview' => true],
                ['game' => 'roulette', 'payout' => 360, 'created_at' => null, 'preview' => true],
                ['game' => 'slots', 'payout' => 240, 'created_at' => null, 'preview' => true],
            ]);
        }

        return view('livewire.casino.recent-wins', [
            'wins' => $wins,
            'isPreview' => $isPreview,
        ]);
    }
}
