<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use Illuminate\View\View;

class Slots extends CasinoGameComponent
{
    public string $selectedSlot = 'classic';

    public function mount(): void
    {
        parent::mount();

        $key = request()->query('slot');
        $variants = array_keys(config('casino.games.slots.variants', []));

        if (is_string($key) && in_array($key, $variants, true)) {
            $this->selectedSlot = $key;
        }
    }

    public function selectSlot(string $slot): void
    {
        if ($this->roundPhase === 'prepared' || $this->roundPhase === 'in_progress') {
            return;
        }

        $variants = array_keys(config('casino.games.slots.variants', []));

        if (in_array($slot, $variants, true)) {
            $this->selectedSlot = $slot;
        }
    }

    public function prepare(): void
    {
        if (! is_numeric($this->bet) || (int) $this->bet < 6 || (int) $this->bet % 6 !== 0) {
            $this->addError('bet', 'A aposta das slots deve ser um múltiplo positivo de 6.');

            return;
        }

        $this->prepareGame(GameType::Slots, ['slot_variant' => $this->selectedSlot]);
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
