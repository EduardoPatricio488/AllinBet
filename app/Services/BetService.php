<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameType;
use App\Enums\RoundStatus;
use App\Enums\TransactionType;
use App\Exceptions\IdempotencyKeyConflictException;
use App\Games\GameRegistry;
use App\Models\GameRound;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class BetService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly GameRegistry $games,
        private readonly ProvablyFairService $provablyFair,
        private readonly ResponsibleGamingService $responsibleGaming,
    ) {}

    public function prepare(
        User $user,
        GameType $game,
        int $bet,
        string $clientSeed,
        string $idempotencyKey,
        array $metadata = [],
    ): GameRound {
        $this->games->get($game);
        if (trim($clientSeed) === '' || strlen($clientSeed) > 128) {
            throw new InvalidArgumentException('The client seed must contain between 1 and 128 characters.');
        }

        if (trim($idempotencyKey) === '' || strlen($idempotencyKey) > 255) {
            throw new InvalidArgumentException('The idempotency key must contain between 1 and 255 characters.');
        }

        try {
            return DB::transaction(function () use ($user, $game, $bet, $clientSeed, $idempotencyKey, $metadata): GameRound {
                $existing = $this->findExistingRound($user, $game, $bet, $clientSeed, $idempotencyKey);

                if ($existing !== null) {
                    return $existing;
                }

                $wallet = Wallet::query()
                    ->where('user_id', $user->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->validateBet($bet, (int) $wallet->balance);

                $this->responsibleGaming->assertCanPlaceBet($user, $bet);

                $lastNonce = GameRound::query()
                    ->where('user_id', $user->getKey())
                    ->where('game', $game)
                    ->max('nonce');
                $serverSeed = $this->provablyFair->createServerSeed();

                return GameRound::query()->create([
                    'user_id' => $user->getKey(),
                    'game' => $game,
                    'bet' => $bet,
                    'payout' => 0,
                    'status' => RoundStatus::Prepared,
                    'result' => [
                        'public' => [],
                        'private' => $metadata,
                    ],
                    'server_seed_hash' => $this->provablyFair->commitment($serverSeed),
                    'server_seed' => $serverSeed,
                    'client_seed' => $clientSeed,
                    'nonce' => $lastNonce === null ? 0 : (int) $lastNonce + 1,
                    'idempotency_key' => $idempotencyKey,
                ]);
            });
        } catch (QueryException $exception) {
            $existing = $this->findExistingRound($user, $game, $bet, $clientSeed, $idempotencyKey);

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $input */
    public function play(User $user, int $roundId, array $input = []): GameRound
    {
        return DB::transaction(function () use ($user, $roundId, $input): GameRound {
            $round = GameRound::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->findOrFail($roundId);

            if ($round->status === RoundStatus::Completed) {
                return $round;
            }

            if (array_key_exists('_pending_payout', $round->privateGameState())) {
                throw new LogicException('The game result is waiting for payout settlement.');
            }

            $isFirstAction = $round->status === RoundStatus::Prepared;
            $isInProgress = $round->status === RoundStatus::InProgress;

            if (! $isFirstAction && ! $isInProgress) {
                throw new LogicException('Only prepared or in-progress game rounds can be played.');
            }

            $actionId = $input['action_id'] ?? null;
            $actionHash = hash('sha256', json_encode($input, JSON_THROW_ON_ERROR));

            if ($actionId !== null && (! is_string($actionId) || trim($actionId) === '' || strlen($actionId) > 128)) {
                throw new InvalidArgumentException('Action identifiers must contain between 1 and 128 characters.');
            }

            $previousActions = $round->result['_actions'] ?? [];

            if ($isInProgress) {
                if (! is_string($actionId)) {
                    throw new InvalidArgumentException('An action identifier is required for an in-progress round.');
                }

                if (array_key_exists($actionId, $previousActions)) {
                    if ($previousActions[$actionId] === $actionHash) {
                        return $round;
                    }

                    throw new IdempotencyKeyConflictException($actionId);
                }
            }

            Wallet::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($isFirstAction) {
                $this->responsibleGaming->assertCanPlaceBet($user, $round->bet);

                $this->walletService->debit(
                    $user,
                    $round->bet,
                    "game-round:{$round->getKey()}:bet",
                    $round,
                    TransactionType::GameBet,
                );
            }

            $gameResult = $this->games->get($round->game)->play($round, $input);

            if ($gameResult->additionalBet > 0) {
                if (! is_string($actionId)) {
                    throw new InvalidArgumentException('An action identifier is required for additional wagers.');
                }

                $walletBalance = (int) Wallet::query()
                    ->where('user_id', $user->getKey())
                    ->value('balance');

                if ($gameResult->additionalBet > $walletBalance) {
                    throw new InvalidArgumentException('The additional wager exceeds your available virtual credits.');
                }

                $this->responsibleGaming->assertCanPlaceBet($user, $gameResult->additionalBet);

                $this->walletService->debit(
                    $user,
                    $gameResult->additionalBet,
                    "game-round:{$round->getKey()}:action:".hash('sha256', $actionId),
                    $round,
                    TransactionType::GameBet,
                );
            }

            if (! $gameResult->completed) {
                if (! is_string($actionId)) {
                    throw new InvalidArgumentException('An action identifier is required to continue the round.');
                }

                $previousActions[$actionId] = $actionHash;

                $round->forceFill([
                    'payout' => 0,
                    'result' => [
                        'public' => $gameResult->result,
                        'private' => $gameResult->state,
                        '_actions' => $previousActions,
                    ],
                    'status' => RoundStatus::InProgress,
                ])->save();

                return $round->refresh();
            }

            $deferPayout = $gameResult->completed && ($input['defer_payout'] ?? false);

            if ($deferPayout) {
                $delayMs = $this->settlementDelayMs($round);
                $settleAfterMs = (int) round(microtime(true) * 1000) + $delayMs;

                $publicResult = $gameResult->result;
                $privateState = [
                    ...$gameResult->state,
                    '_pending_payout' => $gameResult->payout,
                    '_settle_after_ms' => $settleAfterMs,
                ];

                if (! isset($publicResult['settlement_pending'])) {
                    $publicResult['settlement_pending'] = true;
                }

                $round->forceFill([
                    'payout' => 0,
                    'result' => [
                        'public' => $publicResult,
                        'private' => $privateState,
                    ],
                    'status' => RoundStatus::InProgress,
                ])->save();

                return $round->refresh();
            }

            if ($gameResult->payout > 0) {
                $this->walletService->credit(
                    $user,
                    $gameResult->payout,
                    "game-round:{$round->getKey()}:payout",
                    $round,
                    TransactionType::GamePayout,
                );
            }

            $round->forceFill([
                'payout' => $gameResult->payout,
                'result' => $gameResult->result,
                'status' => RoundStatus::Completed,
            ])->save();

            return $round->refresh();
        });
    }

    public function settlePayout(User $user, int $roundId): GameRound
    {
        return DB::transaction(function () use ($user, $roundId): GameRound {
            $round = GameRound::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->findOrFail($roundId);

            if ($round->status === RoundStatus::Completed) {
                return $round;
            }

            if ($round->status !== RoundStatus::InProgress) {
                throw new LogicException('Only rounds awaiting payout settlement can be settled.');
            }

            $state = $round->privateGameState();

            if (! array_key_exists('_pending_payout', $state)) {
                throw new LogicException('This round has no pending payout.');
            }

            $settleAfterMs = (int) ($state['_settle_after_ms'] ?? 0);
            $nowMs = (int) round(microtime(true) * 1000);

            if ($settleAfterMs > $nowMs) {
                throw new LogicException('The payout is not ready yet.');
            }

            $payout = max(0, (int) $state['_pending_payout']);

            if ($payout > 0) {
                $this->walletService->credit(
                    $user,
                    $payout,
                    "game-round:{$round->getKey()}:payout",
                    $round,
                    TransactionType::GamePayout,
                );
            }

            $publicResult = $round->publicResult();
            unset($publicResult['settlement_pending']);

            $round->forceFill([
                'payout' => $payout,
                'result' => $publicResult,
                'status' => RoundStatus::Completed,
            ])->save();

            return $round->refresh();
        });
    }

    private function settlementDelayMs(GameRound $round): int
    {
        $public = $round->publicResult();

        return match ($round->game) {
            GameType::Coinflip => 4300,
            GameType::Dice => 1850,
            GameType::Roulette => 2450,
            GameType::Blackjack => 3000,
            GameType::Slots => (bool) ($public['bonus_buy'] ?? false)
                ? max(10_800, 8_900 + (((int) ($public['bonus_spin_count'] ?? 10)) * 190))
                : 6_900,
            GameType::Jetx => in_array(($public['status'] ?? ''), ['crashed', 'cashed_out'], true) ? 900 : 0,
        };
    }

    private function validateBet(int $bet, int $walletBalance): void
    {
        $minimum = (int) config('casino.bet_limits.min', 1);

        if ($minimum < 1 || $bet < $minimum) {
            throw new InvalidArgumentException("The bet must be at least {$minimum} virtual credits.");
        }

        if ($bet > $walletBalance) {
            throw new InvalidArgumentException('The bet cannot exceed your available virtual credits.');
        }
    }

    private function findExistingRound(
        User $user,
        GameType $game,
        int $bet,
        string $clientSeed,
        string $idempotencyKey,
    ): ?GameRound {
        $round = GameRound::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($round === null) {
            return null;
        }

        if (
            (int) $round->user_id !== (int) $user->getKey()
            || $round->game !== $game
            || $round->bet !== $bet
            || $round->client_seed !== $clientSeed
        ) {
            throw new IdempotencyKeyConflictException($idempotencyKey);
        }

        return $round;
    }
}
