<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\GameType;
use App\Enums\RoundStatus;
use App\Models\GameRound;
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

        $pending = GameRound::query()
            ->where('user_id', auth()->id())
            ->where('game', GameType::Slots)
            ->where('status', RoundStatus::InProgress)
            ->latest('id')
            ->first();

        if ($pending === null) {
            return;
        }

        $state = $pending->privateGameState();

        if (! array_key_exists('_pending_payout', $state)) {
            return;
        }

        $this->roundId = $pending->id;
        $this->roundPhase = RoundStatus::InProgress->value;
        $this->serverSeedHash = $pending->server_seed_hash;
        $this->clientSeed = $pending->client_seed;
        $this->roundResult = $pending->publicResult();
        $this->roundPayout = 0;
        $this->bet = (int) ($state['bonus_base_bet'] ?? $pending->bet);

        if (isset($state['slot_variant']) && in_array($state['slot_variant'], $variants, true)) {
            $this->selectedSlot = (string) $state['slot_variant'];
        }

        $nowMs = (int) round(microtime(true) * 1000);
        $this->dispatch(
            'casino-settlement-pending',
            roundId: $pending->id,
            afterMs: max(0, (int) ($state['_settle_after_ms'] ?? $nowMs) - $nowMs),
        );
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
        $this->prepareGame(GameType::Slots, ['slot_variant' => $this->selectedSlot]);
    }

    public function spin(): void
    {
        $this->playGame(GameType::Slots, ['defer_payout' => true]);
    }

    public function buyBonus(int $multiplier): void
    {
        if (! in_array($this->roundPhase, ['ready', RoundStatus::Completed->value], true)) {
            return;
        }

        $options = config('casino.games.slots.bonus_buy.options', []);
        $selected = collect($options)->first(
            static fn ($option): bool => is_array($option) && (int) ($option['multiplier'] ?? 0) === $multiplier,
        );

        if (! is_array($selected) || ! (bool) config('casino.games.slots.bonus_buy.enabled', false)) {
            $this->addError('game', 'Este bónus não está disponível.');

            return;
        }

        $baseBet = (int) $this->bet;

        if ($baseBet < 1 || $baseBet > $this->maximumBet()) {
            $this->addError('bet', 'Escolhe uma aposta válida antes de comprar o bónus.');

            return;
        }

        if ($multiplier < 1 || $baseBet > intdiv(PHP_INT_MAX, $multiplier)) {
            $this->addError('bet', 'O valor do bónus excede o limite suportado.');

            return;
        }

        $bonusCost = $baseBet * $multiplier;

        if ($bonusCost > $this->maximumBet()) {
            $this->addError('bet', 'Não tens créditos virtuais suficientes para este bónus.');

            return;
        }

        $this->prepareGame(
            GameType::Slots,
            [
                'slot_variant' => $this->selectedSlot,
                'bonus_buy' => true,
                'bonus_multiplier' => $multiplier,
                'bonus_spin_count' => max(1, (int) ($selected['spins'] ?? 0)),
                'bonus_base_bet' => $baseBet,
                'bonus_label' => (string) ($selected['label'] ?? 'Bónus'),
            ],
            $bonusCost,
        );

        if ($this->roundPhase === RoundStatus::Prepared->value) {
            $this->playGame(GameType::Slots, [
                'action' => 'bonus_buy',
                'defer_payout' => true,
            ]);
        }
    }

    public function bonusSpin(): void
    {
        if (! in_array($this->roundPhase, [RoundStatus::Prepared->value, RoundStatus::InProgress->value], true)) {
            return;
        }

        $this->playGame(GameType::Slots, ['action' => 'bonus_spin']);
    }

    public function render(): View
    {
        return view('livewire.casino.slots');
    }
}
