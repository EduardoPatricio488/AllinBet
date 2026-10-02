<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use Illuminate\View\View;

class Roulette extends CasinoGameComponent
{
    public string $betType = 'straight';

    public int|string $selection = 17;

    public function prepare(): void
    {
        $this->prepareGame(GameType::Roulette);
    }

    public function spin(): void
    {
        $rules = ['betType' => ['required', 'in:straight,color,parity,range,dozen,column']];

        if (in_array($this->betType, ['straight', 'dozen', 'column'], true)) {
            $rules['selection'] = $this->betType === 'straight'
                ? ['required', 'integer', 'between:0,36']
                : ['required', 'integer', 'between:1,3'];
        } else {
            $rules['selection'] = match ($this->betType) {
                'color' => ['required', 'in:red,black'],
                'parity' => ['required', 'in:even,odd'],
                'range' => ['required', 'in:low,high'],
            };
        }

        $validated = $this->validate($rules);
        $selection = in_array($this->betType, ['straight', 'dozen', 'column'], true)
            ? (int) $validated['selection']
            : $validated['selection'];

        if (in_array($this->roundPhase, ['ready', 'completed'], true)) {
            $this->prepare();
        }

        if ($this->roundPhase !== 'prepared') {
            return;
        }

        $this->playGame(GameType::Roulette, [
            'bet_type' => $validated['betType'],
            'selection' => $selection,
        ]);
    }

    public function render(): View
    {
        return view('livewire.casino.roulette');
    }
}
