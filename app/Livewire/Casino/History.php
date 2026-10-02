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

        $summary = [
            'rounds' => $rounds->count(),
            'wagered' => (int) $rounds->sum(fn (GameRound $round): int => (int) ($round->publicResult()['total_wager'] ?? $round->bet)),
            'payout' => (int) $rounds->sum('payout'),
            'wins' => $rounds->filter(function (GameRound $round): bool {
                $effectiveBet = (int) ($round->publicResult()['total_wager'] ?? $round->bet);

                return $round->status->value === 'completed' && $round->payout > $effectiveBet;
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
