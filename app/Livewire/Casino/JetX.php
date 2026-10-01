<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use Illuminate\View\View;

final class JetX extends CasinoGameComponent
{
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
        if ($this->roundPhase !== 'prepared') {
            $this->prepareGame(GameType::Jetx);
        }

        if ($this->roundPhase !== 'prepared') {
            return;
        }

        $this->launch();
    }

    private function dispatchFlightStarted(): void
    {
        if ($this->roundPhase !== 'in_progress') {
            return;
        }

        $this->dispatch(
            'jetx-flight-started',
            startedAtMs: (int) ($this->roundResult['started_at_ms'] ?? round(microtime(true) * 1000)),
        );
    }

    public function tick(): void
    {
        $this->playGame(GameType::Jetx, ['action' => 'tick']);
    }

    public function cashout(): void
    {
        $this->playGame(GameType::Jetx, ['action' => 'cashout']);
    }

    public function render(): View
    {
        return view('livewire.casino.jetx');
    }
}
