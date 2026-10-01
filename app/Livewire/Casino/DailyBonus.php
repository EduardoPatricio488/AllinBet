<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Models\Transaction;
use App\Models\User;
use App\Services\DailyBonusService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class DailyBonus extends Component
{
    public ?string $feedback = null;

    public function claim(DailyBonusService $bonusService): void
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->hasVerifiedEmail()) {
            abort(403);
        }

        if ($this->hasClaimedToday($user)) {
            $this->feedback = 'O próximo bónus fica disponível amanhã.';

            return;
        }

        try {
            $transaction = $bonusService->claim($user);
            $this->feedback = "+{$transaction->amount} créditos adicionados à carteira.";
            $this->dispatch('wallet-updated');
            $this->dispatch('history-updated');
            $this->dispatch('daily-bonus-claimed');
            $this->dispatch('casino-toast', type: 'success', title: 'Bónus adicionado', message: "+{$transaction->amount} créditos virtuais.");
        } catch (DomainException $exception) {
            $this->addError('bonus', $exception->getMessage());
            $this->dispatch('casino-toast', type: 'error', title: 'Bónus indisponível', message: $exception->getMessage());
        }
    }

    public function render(): View
    {
        $user = Auth::user();
        $claimedToday = $user instanceof User && $this->hasClaimedToday($user);

        return view('livewire.casino.daily-bonus', [
            'claimedToday' => $claimedToday,
            'bonusCredits' => (int) config('casino.daily_bonus', 100),
        ]);
    }

    private function hasClaimedToday(User $user): bool
    {
        return Transaction::query()
            ->where('idempotency_key', "daily-bonus:{$user->getKey()}:".now()->toDateString())
            ->exists();
    }
}
