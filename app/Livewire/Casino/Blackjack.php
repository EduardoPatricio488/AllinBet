<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use Illuminate\View\View;

class Blackjack extends CasinoGameComponent
{
    public function prepare(): void
    {
        $this->prepareGame(GameType::Blackjack);
    }

    public function deal(): void
    {
        $this->playGame(GameType::Blackjack, ['action' => 'start']);
    }

    public function hit(): void
    {
        $this->playGame(GameType::Blackjack, ['action' => 'hit']);
    }

    public function stand(): void
    {
        $this->playGame(GameType::Blackjack, ['action' => 'stand']);
    }

    public function double(): void
    {
        $this->playGame(GameType::Blackjack, ['action' => 'double']);
    }

    public function render(): View
    {
        return view('livewire.casino.blackjack');
    }
}
