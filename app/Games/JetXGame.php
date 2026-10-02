<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GameType;
use App\Models\GameRound;
use App\Services\ProvablyFairService;
use InvalidArgumentException;

final class JetXGame implements Game
{
    private const int SAMPLE_MAX = 99_999_999;

    public function __construct(private readonly ProvablyFairService $provablyFair) {}

    public function type(): GameType
    {
        return GameType::Jetx;
    }

    /** @param array<string, mixed> $input */
    public function play(GameRound $round, array $input): GameResult
    {
        $action = $input['action'] ?? null;

        if (! is_string($action)) {
            throw new InvalidArgumentException('A JetX action is required.');
        }

        $state = $round->privateGameState();

        if ($state === []) {
            if ($action !== 'launch') {
                throw new InvalidArgumentException('The JetX flight must be launched before playing.');
            }

            return $this->launch($round);
        }

        return match ($action) {
            'tick' => $this->tick($state),
            'cashout' => $this->cashout($round->bet, $state),
            default => throw new InvalidArgumentException('Unsupported JetX action.'),
        };
    }

    public static function multiplierAt(int $elapsedMs): float
    {
        $elapsedMs = max(0, $elapsedMs);
        $timeConstant = max(100, (int) config('casino.games.jetx.multiplier_time_constant_ms', 6500));
        $maximum = (float) config('casino.games.jetx.max_multiplier', 2500.0);

        return min(
            $maximum,
            round(exp($elapsedMs / $timeConstant), 2),
        );
    }

    private function launch(GameRound $round): GameResult
    {
        $sample = $this->provablyFair->integer(
            $round->server_seed,
            $round->client_seed,
            $round->nonce,
            0,
            self::SAMPLE_MAX,
        );

        $crashMultiplier = $this->crashMultiplier($sample);
        $startedAtMs = (int) round(microtime(true) * 1000);

        $state = [
            'crash_multiplier' => $crashMultiplier,
            'started_at_ms' => $startedAtMs,
            'last_multiplier' => 1.00,
        ];

        return new GameResult(
            0,
            [
                'status' => 'flying',
                'multiplier' => 1.00,
                'started_at_ms' => $startedAtMs,
            ],
            false,
            $state,
        );
    }

    /** @param array<string, mixed> $state */
    private function tick(array $state): GameResult
    {
        $startedAtMs = (int) ($state['started_at_ms'] ?? 0);
        $crashMultiplier = (float) ($state['crash_multiplier'] ?? 1.00);

        if ($startedAtMs <= 0 || $crashMultiplier < 1.00) {
            throw new InvalidArgumentException('The JetX round state is invalid.');
        }

        $elapsedMs = max(0, (int) round(microtime(true) * 1000) - $startedAtMs);
        $multiplier = self::multiplierAt($elapsedMs);

        if ($multiplier >= $crashMultiplier) {
            return new GameResult(
                0,
                [
                    'status' => 'crashed',
                    'multiplier' => $crashMultiplier,
                    'crash_multiplier' => $crashMultiplier,
                ],
                true,
                $state,
            );
        }

        $state['last_multiplier'] = $multiplier;

        return new GameResult(
            0,
            [
                'status' => 'flying',
                'multiplier' => $multiplier,
            ],
            false,
            $state,
        );
    }

    /** @param array<string, mixed> $state */
    private function cashout(int $bet, array $state): GameResult
    {
        $startedAtMs = (int) ($state['started_at_ms'] ?? 0);
        $crashMultiplier = (float) ($state['crash_multiplier'] ?? 1.00);

        if ($startedAtMs <= 0 || $crashMultiplier < 1.00) {
            throw new InvalidArgumentException('The JetX round state is invalid.');
        }

        $elapsedMs = max(0, (int) round(microtime(true) * 1000) - $startedAtMs);
        $multiplier = self::multiplierAt($elapsedMs);

        if ($multiplier >= $crashMultiplier) {
            return new GameResult(
                0,
                [
                    'status' => 'crashed',
                    'multiplier' => $crashMultiplier,
                    'crash_multiplier' => $crashMultiplier,
                    'payout' => 0,
                ],
                true,
                $state,
            );
        }

        $payout = max($bet, (int) floor($bet * $multiplier));

        return new GameResult(
            $payout,
            [
                'status' => 'cashed_out',
                'multiplier' => $multiplier,
                'cashout_multiplier' => $multiplier,
                'crash_multiplier' => $crashMultiplier,
                'payout' => $payout,
            ],
            true,
            $state,
        );
    }

    private function crashMultiplier(int $sample): float
    {
        $instantProbability = max(
            0,
            min(10_000, (int) config('casino.games.jetx.instant_crash_probability_basis_points', 300)),
        );

        if ($sample < (int) floor(self::SAMPLE_MAX * ($instantProbability / 10_000))) {
            return 1.00;
        }

        $safeSample = ($sample - (int) floor(self::SAMPLE_MAX * ($instantProbability / 10_000)))
            / max(1, self::SAMPLE_MAX - (int) floor(self::SAMPLE_MAX * ($instantProbability / 10_000)));

        $rtp = max(
            0.01,
            min(1.0, (int) config('casino.games.jetx.target_rtp_basis_points', 9700) / 10_000),
        );
        $maximum = max(1.01, (float) config('casino.games.jetx.max_multiplier', 2500.0));

        $raw = $rtp / max(0.000001, 1 - $safeSample);

        return min(
            $maximum,
            max(1.00, round($raw, (int) config('casino.games.jetx.crash_precision', 2))),
        );
    }
}
