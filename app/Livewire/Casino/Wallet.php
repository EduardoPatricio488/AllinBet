<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Wallet extends Component
{
    public int $amount = 100;

    public string $feedback = '';

    public function addCredits(WalletService $walletService): void
    {
        $this->feedback = '';
        $this->validate(['amount' => ['required', 'integer', Rule::in([100, 500, 1000, 5000])]]);
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(401);
        }

        $key = 'wallet-top-up:'.$user->getKey();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('amount', 'Aguarda um momento antes de adicionar mais créditos.');

            return;
        }
        RateLimiter::hit($key, 60);

        if ($user->wallet()->doesntExist()) {
            $walletService->initialize($user, 0);
        }

        $transaction = $walletService->credit(
            $user,
            $this->amount,
            'virtual-top-up:'.$user->getKey().':'.bin2hex(random_bytes(16)),
            null,
            TransactionType::Credit,
        );

        $this->feedback = "+{$transaction->amount} créditos adicionados à carteira.";
        $this->dispatch('wallet-updated');
        $this->dispatch('casino-toast', type: 'success', title: 'Carteira atualizada', message: $this->feedback);
    }

    public function render(): View
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(401);
        }
        $wallet = $user->wallet()->first();

        return view('livewire.casino.wallet', [
            'balance' => (int) ($wallet?->balance ?? 0),
            'transactions' => $wallet === null ? collect() : Transaction::query()->where('wallet_id', $wallet->getKey())->latest()->limit(12)->get(),
        ]);
    }
}
