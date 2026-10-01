<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use App\Enums\RoundStatus;
use App\Models\GameRound;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class JetX extends CasinoGameComponent
{
    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $round = GameRound::query()
            ->where('user_id', $user->getKey())
            ->where('game', GameType::Jetx)
            ->whereIn('status', [
                RoundStatus::Prepared,
                RoundStatus::InProgress,
            ])
            ->latest('id')
            ->first();

        if ($round === null) {
            return;
        }

        $this->roundId = $round->id;
        $this->roundPhase = (string) $round->getRawOriginal('status');
        $this->serverSeedHash = $round->server_seed_hash;
        $this->roundResult = $round->publicResult();
        $this->roundPayout = $round->payout;
        $this->bet = $round->bet;
    }

    public function prepare(): void
    {
        $this->prepareGame(GameType::Jetx);
    }

    public function launch(): void
    {
        $this->playGame(GameType::Jetx, ['action' => 'launch']);

        $this->dispatchFlightStarted();
    }

    public function start(): void
    {
        if ($this->roundPhase !== RoundStatus::Prepared->value) {
            $this->prepareGame(GameType::Jetx);
        }

        if ($this->roundPhase !== RoundStatus::Prepared->value) {
            return;
        }

        $this->launch();
    }

    private function dispatchFlightStarted(): void
    {
        if ($this->roundPhase !== RoundStatus::InProgress->value) {
            return;
        }

        $this->dispatch(
            'jetx-flight-started',
            startedAtMs: (int) (
                $this->roundResult['started_at_ms']
                ?? round(microtime(true) * 1000)
            ),
        );
    }

    public function tick(): void
    {
        if (! $this->restoreActiveRound()) {
            return;
        }

        $this->playGame(GameType::Jetx, ['action' => 'tick']);
    }

    public function cashout(): void
    {
        if (! $this->restoreActiveRound()) {
            return;
        }

        $this->playGame(GameType::Jetx, ['action' => 'cashout']);
    }

    private function restoreActiveRound(): bool
    {
        if (
            $this->roundId !== null
            && in_array($this->roundPhase, [
                RoundStatus::Prepared->value,
                RoundStatus::InProgress->value,
            ], true)
        ) {
            return true;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        $round = GameRound::query()
            ->where('user_id', $user->getKey())
            ->where('game', GameType::Jetx)
            ->whereIn('status', [
                RoundStatus::Prepared,
                RoundStatus::InProgress,
            ])
            ->latest('id')
            ->first();

        if ($round === null) {
            return false;
        }

        $this->roundId = $round->id;
        $this->roundPhase = (string) $round->getRawOriginal('status');
        $this->serverSeedHash = $round->server_seed_hash;
        $this->roundResult = $round->publicResult();
        $this->roundPayout = $round->payout;
        $this->bet = $round->bet;

        return true;
    }

    public function render(): View
    {
        return view('livewire.casino.jetx');
    }
}
