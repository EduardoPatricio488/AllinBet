<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoundStatus;
use App\Enums\TransactionType;
use App\Exceptions\GameLimitExceededException;
use App\Models\GameRound;
use App\Models\ResponsibleGamingSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class ResponsibleGamingService
{
    public function updateLimits(User $user, ?int $dailyLossLimit, ?int $maximumBet): ResponsibleGamingSetting
    {
        if (($dailyLossLimit !== null && $dailyLossLimit < 1) || ($maximumBet !== null && $maximumBet < 1)) {
            throw new InvalidArgumentException('Responsible gaming limits must be positive whole credits.');
        }

        $globalMaximum = (int) config('casino.bet_limits.max', 10000);

        if ($maximumBet !== null && $maximumBet > $globalMaximum) {
            throw new InvalidArgumentException("The personal maximum bet cannot exceed {$globalMaximum} credits.");
        }

        return ResponsibleGamingSetting::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['daily_loss_limit' => $dailyLossLimit, 'max_bet' => $maximumBet],
        );
    }

    public function pause(User $user, int $minutes): ResponsibleGamingSetting
    {
        if ($minutes < 1 || $minutes > 525600) {
            throw new InvalidArgumentException('A pause must be between 1 minute and 365 days.');
        }

        return ResponsibleGamingSetting::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['paused_until' => now()->addMinutes($minutes)],
        );
    }

    public function autoExclude(User $user): ResponsibleGamingSetting
    {
        return ResponsibleGamingSetting::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['excluded_at' => now(), 'paused_until' => null],
        );
    }

    public function assertCanPlaceBet(User $user, int $bet): void
    {
        if ($bet < 1) {
            throw new InvalidArgumentException('A wager must be at least one virtual credit.');
        }

        $settings = $user->responsibleGamingSetting()->first();

        if ($settings?->excluded_at !== null) {
            throw new GameLimitExceededException('Your account is currently self-excluded from games.');
        }

        if ($settings?->paused_until !== null && $settings->paused_until->isFuture()) {
            throw new GameLimitExceededException('Your gaming pause is still active.');
        }

        if ($settings?->max_bet !== null && $bet > $settings->max_bet) {
            throw new GameLimitExceededException('This wager exceeds your personal maximum bet.');
        }

        if ($settings?->daily_loss_limit !== null) {
            $lostToday = $this->lossesSince($user, now()->startOfDay());

            if ($lostToday >= $settings->daily_loss_limit) {
                throw new GameLimitExceededException('Your daily loss limit has been reached.');
            }

            $availableBeforeLimit = $settings->daily_loss_limit - $lostToday;

            if ($bet > $availableBeforeLimit) {
                throw new GameLimitExceededException('This wager would exceed your daily loss limit.');
            }
        }
    }

    public function lossesSince(User $user, Carbon $start): int
    {
        $walletId = Wallet::query()->where('user_id', $user->getKey())->value('id');

        if ($walletId === null) {
            return 0;
        }

        $netGameChange = Transaction::query()
            ->where('wallet_id', $walletId)
            ->where('created_at', '>=', $start)
            ->whereIn('type', [TransactionType::GameBet->value, TransactionType::GamePayout->value])
            ->sum('amount');

        return max(0, -(int) $netGameChange);
    }

    public function activeRounds(User $user): int
    {
        return GameRound::query()
            ->where('user_id', $user->getKey())
            ->whereIn('status', [RoundStatus::Prepared->value, RoundStatus::InProgress->value])
            ->count();
    }
}
