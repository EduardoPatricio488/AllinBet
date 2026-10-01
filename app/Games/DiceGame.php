<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GameType;
use App\Models\GameRound;
use App\Services\PayoutCalculator;
use App\Services\ProvablyFairService;
use InvalidArgumentException;

class DiceGame implements Game
{
    public function __construct(
        private readonly ProvablyFairService $provablyFair,
        private readonly PayoutCalculator $payoutCalculator,
    ) {}

    public function type(): GameType
    {
        return GameType::Dice;
    }

    /** @param array<string, mixed> $input */
    public function play(GameRound $round, array $input): GameResult
    {
        $direction = $input['direction'] ?? null;
        $threshold = $input['threshold'] ?? null;

        if (! is_string($direction) || ! is_int($threshold)) {
            throw new InvalidArgumentException('A dice direction and integer threshold are required.');
        }

        $roll = $this->provablyFair->integer(
            $round->server_seed,
            $round->client_seed,
            $round->nonce,
            0,
            9999,
        );

        return $this->evaluate($roll, $round->bet, $direction, $threshold);
    }

    public function evaluate(int $roll, int $bet, string $direction, int $threshold): GameResult
    {
        if ($bet <= 0 || $roll < 0 || $roll > 9999 || $threshold < 1 || $threshold > 99) {
            throw new InvalidArgumentException('Invalid dice game parameters.');
        }

        if (! in_array($direction, ['under', 'over'], true)) {
            throw new InvalidArgumentException('Choose either under or over.');
        }

        $probability = $direction === 'under' ? $threshold * 100 : (100 - $threshold) * 100;
        $won = $direction === 'under' ? $roll < $threshold * 100 : $roll >= $threshold * 100;
        $houseEdge = (int) config('casino.games.dice.house_edge_bps', 500);
        $payout = $won ? $this->payoutCalculator->grossPayout($bet, $probability, $houseEdge) : 0;

        return new GameResult($payout, [
            'roll' => $roll,
            'direction' => $direction,
            'threshold' => $threshold,
            'probability_basis_points' => $probability,
            'won' => $won,
        ]);
    }
}
