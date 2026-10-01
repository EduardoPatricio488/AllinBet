<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Models\GameRound;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class History extends Component
{
    #[On('history-updated')]
    public function refreshHistory(): void {}

    public function render(): View
    {
        $user = Auth::user();
        $walletId = $user->wallet()->value('id');

        return view('livewire.casino.history', [
            'rounds' => GameRound::query()->where('user_id', $user->getKey())->latest()->limit(8)->get(),
            'transactions' => $walletId === null
                ? collect()
                : Transaction::query()->where('wallet_id', $walletId)->latest()->limit(8)->get(),
        ]);
    }
}
