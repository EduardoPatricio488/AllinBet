<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use Illuminate\View\View;

class Slots extends CasinoGameComponent
{
    public function prepare(): void
    {
        if (! is_numeric($this->bet) || (int) $this->bet < 3 || (int) $this->bet % 3 !== 0) {
            $this->addError('bet', 'The slots bet must be a positive multiple of 3.');

            return;
        }

        $this->prepareGame(GameType::Slots);
    }

    public function spin(): void
    {
        $this->playGame(GameType::Slots);
    }

    public function render(): View
    {
        return view('livewire.casino.slots');
    }
}
