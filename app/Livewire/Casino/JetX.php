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
