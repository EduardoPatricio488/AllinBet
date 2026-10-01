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
        $paylines = config('casino.games.slots.paylines', [0, 1, 2]);
        $paytable = config('casino.games.slots.paytable', []);
        $pairPaytable = config('casino.games.slots.pair_paytable', [1, 2, 3, 6, 20]);
        $symbolCount = (int) config('casino.games.slots.symbol_count', 5);
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

        foreach ($paylines as $lineNumber => $rowNumber) {
            if (! is_int($rowNumber) || $rowNumber < 0 || $rowNumber >= $rows) {
                throw new InvalidArgumentException('The slots configuration contains an invalid payline.');
            }

            $symbols = $grid[$rowNumber];
            $firstSymbol = $symbols[0];
            $allMatch = true;

            for ($column = 1; $column < $columns; $column++) {
                if ($symbols[$column] !== $firstSymbol) {
                    $allMatch = false;
                    break;
                }
            }

            if ($allMatch) {
                $multiplier = (int) ($paytable[$firstSymbol] ?? 0);
                $payout += $lineBet * $multiplier;
                $winningLines[] = [
                    'line' => $lineNumber,
                    'symbol' => $firstSymbol,
                    'count' => $columns,
                    'multiplier' => $multiplier,
                ];

                continue;
            }

            $counts = array_count_values($symbols);
            $pairSymbol = null;

            foreach ($counts as $symbol => $count) {
                if ($count === 2) {
                    $pairSymbol = (int) $symbol;
                    break;
                }
            }

            if ($pairSymbol !== null) {
                $multiplier = (int) ($pairPaytable[$pairSymbol] ?? 0);

                if ($multiplier > 0) {
                    $payout += $lineBet * $multiplier;
                    $winningLines[] = [
                        'line' => $lineNumber,
                        'symbol' => $pairSymbol,
                        'count' => 2,
                        'multiplier' => $multiplier,
                    ];
                }
            }
        }

        return new GameResult($payout, [
            'grid' => $grid,
            'winning_lines' => $winningLines,
            'wager' => $bet,
        ]);
    }
}
