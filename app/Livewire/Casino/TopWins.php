<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\RoundStatus;
use App\Models\GameRound;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class TopWins extends Component
{
    public function render(): View
    {
        $wins = GameRound::query()
            ->with('user:id,name')
            ->where('status', RoundStatus::Completed)
            ->whereDate('created_at', today())
            ->where('payout', '>', 0)
            ->orderByDesc('payout')
            ->limit(5)
            ->get()
            ->map(fn (GameRound $round): array => [
                'name' => $round->user ? Str::mask(Str::before($round->user->name, ' '), '*', 2) : 'Jogador',
                'game' => $round->game->value,
                'payout' => $round->payout,
            ]);

        return view('livewire.casino.top-wins', compact('wins'));
    }
}
