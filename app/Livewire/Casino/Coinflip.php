<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use App\Enums\RoundStatus;
use App\Models\GameRound;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;

class Coinflip extends CasinoGameComponent
{
    /** @var array<int, array{outcome:string,won:bool,payout:int,bet:int,time:string}> */
    public array $recentFlips = [];

    public string $side = 'heads';

    public int $headsCount = 0;

    public int $tailsCount = 0;

    public int $winsCount = 0;

    public int $currentStreak = 0;

    public string $streakSide = '';

    public int $walletBalance = 0;

    public function mount(): void
    {
        parent::mount();
        $this->loadRecentStats();
        $this->walletBalance = $this->currentBalance();
    }

    public function prepare(): void
    {
        $this->walletBalance = $this->currentBalance();
        $this->prepareGame(GameType::Coinflip);
    }

    #[On('wallet-updated')]
    public function refreshWallet(): void
    {
        $this->walletBalance = $this->currentBalance();
    }

    public function flip(): void
    {
        $this->validate(['side' => ['required', 'in:heads,tails']]);
        $this->playGame(GameType::Coinflip, [
            'side' => $this->side,
            'defer_payout' => true,
        ]);
        $this->loadRecentStats();
        $this->walletBalance = $this->currentBalance();
    }

    private function loadRecentStats(): void
    {
        $rounds = GameRound::query()
            ->where('user_id', auth()->id())
            ->where('game', GameType::Coinflip)
            ->where('status', RoundStatus::Completed)
            ->latest('id')
            ->limit(50)
            ->get(['id', 'result', 'bet', 'payout', 'created_at']);

        $this->headsCount = 0;
        $this->tailsCount = 0;
        $this->winsCount = 0;
        $this->currentStreak = 0;
        $this->streakSide = '';

        foreach ($rounds as $round) {
            $result = $round->publicResult();
            $outcome = $result['outcome'] ?? null;

            if (in_array($outcome, ['heads', 'tails'], true) === false) {

                continue;
            }

            if ($outcome === 'heads') {
                $this->headsCount++;
            } else {
                $this->tailsCount++;
            }

            if ((bool) ($result['won'] ?? false)) {
                $this->winsCount++;
            }

            if ($this->streakSide === '') {
                $this->streakSide = $outcome;
                $this->currentStreak = 1;

                continue;
            }

            if ($this->streakSide === $outcome) {
                $this->currentStreak++;
            } else {
                break;
            }
        }

        $this->recentFlips = $rounds
            ->map(function (GameRound $round): array {
                $result = $round->publicResult();

                return [
                    'outcome' => ($result['outcome'] ?? '') === 'heads' ? 'heads' : 'tails',
                    'won' => (bool) ($result['won'] ?? false),
                    'payout' => (int) $round->payout,
                    'bet' => (int) $round->bet,
                    'time' => $round->created_at?->format('H:i') ?? '',
                ];
            })
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.casino.coinflip');
    }

    private function currentBalance(): int
    {
        $user = Auth::user();

        if (! $user instanceof \App\Models\User) {
            abort(401);
        }

        return (int) ($user->wallet()->value('balance') ?? 0);
    }
}
