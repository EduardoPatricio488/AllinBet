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
        $this->playGame(GameType::Blackjack, [
            'action' => 'start',
            'defer_payout' => true,
        ]);
    }

    public function hit(): void
    {
        $this->playGame(GameType::Blackjack, [
            'action' => 'hit',
            'defer_payout' => true,
        ]);
    }

    public function stand(): void
    {
        $this->playGame(GameType::Blackjack, [
            'action' => 'stand',
            'defer_payout' => true,
        ]);
    }

    public function double(): void
    {
        $this->playGame(GameType::Blackjack, [
            'action' => 'double',
            'defer_payout' => true,
        ]);
    }

    public function render(): View
    {
        return view('livewire.casino.blackjack');
    }
}
