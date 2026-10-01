<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GameType;
use App\Models\GameRound;
use App\Services\ProvablyFairService;
use InvalidArgumentException;

class SlotsGame implements Game
{
    public function __construct(private readonly ProvablyFairService $provablyFair) {}

    public function type(): GameType
    {
        return GameType::Slots;
    }

    public function play(GameRound $round, array $input): GameResult
    {
        $symbolCount = (int) config('casino.games.slots.symbol_count', 5);
        $rows = (int) config('casino.games.slots.rows', 3);
        $columns = (int) config('casino.games.slots.columns', 3);
        $grid = [];

        for ($row = 0; $row < $rows; $row++) {
            $grid[$row] = [];

            for ($column = 0; $column < $columns; $column++) {
                $grid[$row][$column] = $this->provablyFair->integer(
                    $round->server_seed,
                    $round->client_seed,
                    ($round->nonce * $rows * $columns) + ($row * $columns) + $column,
                    0,
                    $symbolCount - 1,
                );
            }
        }

        return $this->settle($grid, $round->bet);
    }

    /** @param array<int, array<int, int>> $grid */
    public function settle(array $grid, int $bet): GameResult
    {
        $rows = (int) config('casino.games.slots.rows', 3);
        $columns = (int) config('casino.games.slots.columns', 3);
        $paytable = config('casino.games.slots.paytable', []);
        $symbolCount = (int) config('casino.games.slots.symbol_count', 5);

        // A 3x3 board has six valid ways to win:
        // three horizontal lines + three vertical lines.
        // A win is ONLY awarded when all three positions on a line match.
        $paylines = [
            ['direction' => 'horizontal', 'index' => 0],
            ['direction' => 'horizontal', 'index' => 1],
            ['direction' => 'horizontal', 'index' => 2],
            ['direction' => 'vertical', 'index' => 0],
            ['direction' => 'vertical', 'index' => 1],
            ['direction' => 'vertical', 'index' => 2],
        ];

        $lineCount = count($paylines);

        if ($bet <= 0 || $lineCount === 0 || $bet % $lineCount !== 0) {
            throw new InvalidArgumentException('The slots wager must be positive and divisible by the number of paylines.');
        }

        if (count($grid) !== $rows) {
            throw new InvalidArgumentException('The slots grid has an invalid row count.');
        }

        foreach ($grid as $row) {
            if (count($row) !== $columns) {
                throw new InvalidArgumentException('The slots grid has an invalid column count.');
            }

            foreach ($row as $symbol) {
                if (! is_int($symbol) || $symbol < 0 || $symbol >= $symbolCount) {
                    throw new InvalidArgumentException('The slots grid contains an invalid symbol.');
                }
            }
        }

        $lineBet = intdiv($bet, $lineCount);
        $winningLines = [];
        $payout = 0;

        foreach ($paylines as $line) {
            $positions = [];

            if ($line['direction'] === 'horizontal') {
                for ($column = 0; $column < $columns; $column++) {
                    $positions[] = [$line['index'], $column];
                }
            } else {
                for ($row = 0; $row < $rows; $row++) {
                    $positions[] = [$row, $line['index']];
                }
            }

            $firstSymbol = $grid[$positions[0][0]][$positions[0][1]];
            $allMatch = true;

            foreach ($positions as [$row, $column]) {
                if ($grid[$row][$column] !== $firstSymbol) {
                    $allMatch = false;
                    break;
                }
            }

            // No pair prizes, adjacent-symbol prizes or partial-line prizes.
            // Exactly three equal symbols on one horizontal or vertical line is required.
            if (! $allMatch) {
                continue;
            }

            $multiplier = (int) ($paytable[$firstSymbol] ?? 0);

            if ($multiplier <= 0) {
                continue;
            }

            $payout += $lineBet * $multiplier;
            $winningLines[] = [
                'direction' => $line['direction'],
                'line' => $line['index'],
                'symbol' => $firstSymbol,
                'count' => 3,
                'multiplier' => $multiplier,
            ];
        }

        return new GameResult($payout, [
            'grid' => $grid,
            'winning_lines' => $winningLines,
            'wager' => $bet,
            'payline_count' => $lineCount,
        ]);
    }
}
