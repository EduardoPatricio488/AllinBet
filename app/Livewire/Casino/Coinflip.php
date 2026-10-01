<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use Illuminate\View\View;

class Coinflip extends CasinoGameComponent
{
    public string $side = 'heads';

    public function prepare(): void
    {
        $this->prepareGame(GameType::Coinflip);
    }

    public function flip(): void
    {
        $this->validate(['side' => ['required', 'in:heads,tails']]);
        $this->playGame(GameType::Coinflip, ['side' => $this->side]);
    }

    public function render(): View
    {
        return view('livewire.casino.coinflip');
    }
}
