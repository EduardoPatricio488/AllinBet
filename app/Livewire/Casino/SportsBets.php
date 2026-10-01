<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Models\SportsBet;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

final class SportsBets extends Component
{
    public string $filter = 'all';

    public ?int $expandedBet = null;

    #[On('sports-bets-refresh')]
    #[On('sports-bet-updated')]
    public function refreshBets(): void
    {
        // The event triggers a fresh render.
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'pending', 'won', 'lost'], true)) {
            return;
        }

        $this->filter = $filter;
        $this->expandedBet = null;
    }

    public function toggleBet(int $betId): void
    {
        $this->expandedBet = $this->expandedBet === $betId ? null : $betId;
    }

    public function render(): View
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(401);
        }

        $query = SportsBet::query()
            ->where('user_id', $user->getKey())
            ->latest('id');

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        $bets = $query->limit(50)->get();

        return view('livewire.casino.sports-bets', [
            'bets' => $bets,
            'totalBets' => SportsBet::query()->where('user_id', $user->getKey())->count(),
            'openBets' => SportsBet::query()->where('user_id', $user->getKey())->where('status', 'pending')->count(),
        ]);
    }
}
