<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;

class DailyBonusService
{
    public function __construct(private readonly WalletService $walletService) {}

    public function claim(User $user): Transaction
    {
        $amount = (int) config('casino.daily_bonus', 100);

        if ($amount < 1) {
            throw new DomainException('Daily virtual credits are not configured.');
        }

        $today = CarbonImmutable::now()->format('Y-m-d');

        return $this->walletService->credit(
            $user,
            $amount,
            "daily-bonus:{$user->getKey()}:{$today}",
            null,
            TransactionType::Bonus,
        );
    }
}
