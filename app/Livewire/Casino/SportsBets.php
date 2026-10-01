<?php

declare(strict_types=1);

namespace App\Livewire\Casino;

use App\Models\SportsBet;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

final class SportsBets extends Component
{
    public string $filter = 'all';

    public function render(): View
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(401);
        }

        $query = SportsBet::query()
            ->where('user_id', $user->getKey())
            ->latest('id');

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        $bets = $query->limit(50)->get();

        return view('livewire.casino.sports-bets', [
            'bets' => $bets,
        ]);
    }
}
