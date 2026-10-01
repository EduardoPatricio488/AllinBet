<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GameType;
use App\Models\GameRound;
use App\Services\PayoutCalculator;
use App\Services\ProvablyFairService;
use InvalidArgumentException;

class RouletteGame implements Game
{
    private const array RED_NUMBERS = [1, 3, 5, 7, 9, 12, 14, 16, 18, 19, 21, 23, 25, 27, 30, 32, 34, 36];

    public function __construct(
        private readonly ProvablyFairService $provablyFair,
        private readonly PayoutCalculator $payoutCalculator,
    ) {}

    public function type(): GameType
    {
        return GameType::Roulette;
    }

    /** @param array<string, mixed> $input */
    public function play(GameRound $round, array $input): GameResult
    {
        $betType = $input['bet_type'] ?? null;
        $selection = $input['selection'] ?? null;

        if (! is_string($betType)) {
            throw new InvalidArgumentException('A roulette bet type is required.');
        }

        $outcome = $this->provablyFair->integer(
            $round->server_seed,
            $round->client_seed,
            $round->nonce,
            0,
            36,
        );

        return $this->evaluate($outcome, $round->bet, $betType, $selection);
    }

    public function evaluate(int $outcome, int $bet, string $betType, mixed $selection): GameResult
    {
        if ($outcome < 0 || $outcome > 36 || $bet <= 0) {
            throw new InvalidArgumentException('Invalid roulette game parameters.');
        }

        $multiplier = match ($betType) {
            'straight' => $this->straightMultiplier($outcome, $selection),
            'color' => $this->colorMultiplier($outcome, $selection),
            'parity' => $this->parityMultiplier($outcome, $selection),
            'range' => $this->rangeMultiplier($outcome, $selection),
            'dozen' => $this->dozenMultiplier($outcome, $selection),
            'column' => $this->columnMultiplier($outcome, $selection),
            default => throw new InvalidArgumentException('Unsupported roulette bet type.'),
        };

        $color = $outcome === 0 ? 'green' : (in_array($outcome, self::RED_NUMBERS, true) ? 'red' : 'black');

        return new GameResult(
            $multiplier === 0 ? 0 : $this->payoutCalculator->multiply($bet, $multiplier),
            ['outcome' => $outcome, 'color' => $color, 'bet_type' => $betType, 'selection' => $selection, 'won' => $multiplier > 0],
        );
    }

    private function straightMultiplier(int $outcome, mixed $selection): int
    {
        $number = $this->numberSelection($selection);

        return $outcome === $number ? 36 : 0;
    }

    private function colorMultiplier(int $outcome, mixed $selection): int
    {
        if (! in_array($selection, ['red', 'black'], true)) {
            throw new InvalidArgumentException('Color bets must select red or black.');
        }

        $isRed = in_array($outcome, self::RED_NUMBERS, true);

        return $outcome !== 0 && (($selection === 'red') === $isRed) ? 2 : 0;
    }

    private function parityMultiplier(int $outcome, mixed $selection): int
    {
        if (! in_array($selection, ['even', 'odd'], true)) {
            throw new InvalidArgumentException('Parity bets must select even or odd.');
        }

        return $outcome !== 0 && (($selection === 'even') === ($outcome % 2 === 0)) ? 2 : 0;
    }

    private function rangeMultiplier(int $outcome, mixed $selection): int
    {
        if (! in_array($selection, ['low', 'high'], true)) {
            throw new InvalidArgumentException('Range bets must select low or high.');
        }

        return $outcome !== 0 && (($selection === 'low') === ($outcome <= 18)) ? 2 : 0;
    }

    private function dozenMultiplier(int $outcome, mixed $selection): int
    {
        if (! is_int($selection) || $selection < 1 || $selection > 3) {
            throw new InvalidArgumentException('Dozen bets must select 1, 2, or 3.');
        }

        return $outcome > 0 && intdiv($outcome - 1, 12) + 1 === $selection ? 3 : 0;
    }

    private function columnMultiplier(int $outcome, mixed $selection): int
    {
        if (! is_int($selection) || $selection < 1 || $selection > 3) {
            throw new InvalidArgumentException('Column bets must select 1, 2, or 3.');
        }

        return $outcome > 0 && (($outcome - 1) % 3) + 1 === $selection ? 3 : 0;
    }

    private function numberSelection(mixed $selection): int
    {
        if (! is_int($selection) || $selection < 0 || $selection > 36) {
            throw new InvalidArgumentException('Straight bets must select a number from 0 to 36.');
        }

        return $selection;
    }
}
