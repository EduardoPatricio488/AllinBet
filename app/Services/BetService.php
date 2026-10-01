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
    ): GameRound {
        $this->games->get($game);
        $this->validateBet($bet);

        if (trim($clientSeed) === '' || strlen($clientSeed) > 128) {
            throw new InvalidArgumentException('The client seed must contain between 1 and 128 characters.');
        }

        if (trim($idempotencyKey) === '' || strlen($idempotencyKey) > 255) {
            throw new InvalidArgumentException('The idempotency key must contain between 1 and 255 characters.');
        }

        try {
            return DB::transaction(function () use ($user, $game, $bet, $clientSeed, $idempotencyKey): GameRound {
                $existing = $this->findExistingRound($user, $game, $bet, $clientSeed, $idempotencyKey);

                if ($existing !== null) {
                    return $existing;
                }

                Wallet::query()
                    ->where('user_id', $user->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

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
                    'result' => null,
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

                $totalWager = (int) ($gameResult->state['total_wager'] ?? ($round->bet + $gameResult->additionalBet));
                $maximum = (int) config('casino.bet_limits.max', 10000);

                if ($totalWager > $maximum) {
                    throw new InvalidArgumentException('The total wager exceeds the configured maximum.');
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

    private function validateBet(int $bet): void
    {
        $minimum = (int) config('casino.bet_limits.min', 1);
        $maximum = (int) config('casino.bet_limits.max', 10000);

        if ($minimum < 1 || $maximum < $minimum || $bet < $minimum || $bet > $maximum) {
            throw new InvalidArgumentException("The bet must be between {$minimum} and {$maximum} virtual credits.");
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
