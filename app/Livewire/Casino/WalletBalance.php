<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class WalletBalance extends Component
{
    #[Locked]
    public int $balance = 0;

    public function mount(): void
    {
        $this->balance = $this->currentBalance();
    }

    #[On('wallet-updated')]
    public function refreshBalance(): void
    {
        $previousBalance = $this->balance;
        $this->balance = $this->currentBalance();
        $delta = $this->balance - $previousBalance;

        if ($delta !== 0) {
            $this->dispatch('casino-balance-changed', balance: $this->balance, delta: $delta);
        }
    }

    public function render(): View
    {
        return view('livewire.casino.wallet-balance');
    }

    private function currentBalance(): int
    {
        return (int) (Auth::user()->wallet()->value('balance') ?? 0);
    }
}
