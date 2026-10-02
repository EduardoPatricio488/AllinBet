<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use Illuminate\View\View;

class Dice extends CasinoGameComponent
{
    public string $direction = 'under';

    public int|string $threshold = 50;

    public function prepare(): void
    {
        $this->prepareGame(GameType::Dice);
    }

    public function roll(): void
    {
        $validated = $this->validate([
            'direction' => ['required', 'in:under,over'],
            'threshold' => ['required', 'integer', 'between:1,99'],
        ]);

        if (in_array($this->roundPhase, ['ready', 'completed'], true)) {
            $this->prepare();
        }

        if ($this->roundPhase !== 'prepared') {
            return;
        }

        $this->playGame(GameType::Dice, [
            'direction' => $validated['direction'],
            'threshold' => (int) $validated['threshold'],
        ]);
    }

    public function render(): View
    {
        return view('livewire.casino.dice');
    }
}
