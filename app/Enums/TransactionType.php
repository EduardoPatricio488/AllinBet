<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Bonus = 'bonus';
    case GameBet = 'game_bet';
    case GamePayout = 'game_payout';
    case AdminAdjustment = 'admin_adjustment';

    public function isCredit(): bool
    {
        return match ($this) {
            self::Credit, self::Bonus, self::GamePayout => true,
            default => false,
        };
    }

    public function isDebit(): bool
    {
        return match ($this) {
            self::Debit, self::GameBet => true,
            default => false,
        };
    }

    public function acceptsAmount(int $amount): bool
    {
        return match ($this) {
            self::Credit, self::Bonus, self::GamePayout => $amount > 0,
            self::Debit, self::GameBet => $amount < 0,
            self::AdminAdjustment => $amount !== 0,
        };
    }
}
