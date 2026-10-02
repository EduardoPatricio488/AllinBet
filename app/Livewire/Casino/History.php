<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Models\GameRound;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class History extends Component
{
    #[On('history-updated')]
    public function refreshHistory(): void {}

    public function render(): View
    {
        $user = Auth::user();
        $walletId = $user->wallet()->value('id');

        $rounds = GameRound::query()
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->limit(20)
            ->get();

        $completedRounds = $rounds->filter(fn (GameRound $round): bool => $round->status->value === 'completed');

        $summary = [
            'rounds' => $rounds->count(),
            'completed' => $completedRounds->count(),
            'wagered' => (int) $completedRounds->sum(fn (GameRound $round): int => (int) ($round->publicResult()['total_wager'] ?? $round->bet)),
            'payout' => (int) $completedRounds->sum('payout'),
            'wins' => $completedRounds->filter(function (GameRound $round): bool {
                $effectiveBet = (int) ($round->publicResult()['total_wager'] ?? $round->bet);

                return $round->payout > $effectiveBet;
            })->count(),
        ];

        return view('livewire.casino.history', [
            'rounds' => $rounds,
            'summary' => $summary,
            'transactions' => $walletId === null
                ? collect()
                : Transaction::query()->where('wallet_id', $walletId)->latest('id')->limit(20)->get(),
        ]);
    }
}
