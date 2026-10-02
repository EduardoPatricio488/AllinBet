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
        $variant = $this->variantFor($round);
        $symbolCount = count($variant['symbols'] ?? []);
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

        return $this->settle($grid, $round->bet, $variant);
    }

    /** @return array<string, mixed> */
    private function variantFor(GameRound $round): array
    {
        $key = (string) ($round->privateGameState()['slot_variant'] ?? 'classic');
        $variant = config("casino.games.slots.variants.{$key}");

        if (! is_array($variant) || ! isset($variant['symbols'], $variant['paytable'])) {
            throw new InvalidArgumentException('The selected slots variant is invalid.');
        }

        $variant['key'] = $key;

        return $variant;
    }

    /** @param array<int, array<int, int>> $grid */
    public function settle(array $grid, int $bet, ?array $variant = null): GameResult
    {
        $variant ??= config('casino.games.slots.variants.classic', []);
        $rows = (int) config('casino.games.slots.rows', 3);
        $columns = (int) config('casino.games.slots.columns', 3);
        $paylines = config('casino.games.slots.paylines', []);
        $paytable = $variant['paytable'] ?? [];
        $symbolCount = count($variant['symbols'] ?? []);

        $paylines = array_values(array_filter(
            $paylines,
            static fn ($line): bool => is_array($line)
                && isset($line['direction'], $line['index'])
                && in_array($line['direction'], ['horizontal', 'vertical'], true)
                && is_int($line['index'])
                && $line['index'] >= 0
                && $line['index'] < ($line['direction'] === 'horizontal' ? $rows : $columns),
        ));

        $lineCount = count($paylines);

        if ($bet <= 0 || $lineCount === 0) {
            throw new InvalidArgumentException('The slots wager must be a positive number of virtual credits.');
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

        $winningLines = [];
        $payout = 0;

        foreach ($paylines as $line) {
            $direction = $line['direction'];
            $index = $line['index'];
            $firstSymbol = $direction === 'horizontal'
                ? $grid[$index][0]
                : $grid[0][$index];
            $allMatch = true;

            $length = $direction === 'horizontal' ? $columns : $rows;

            for ($position = 1; $position < $length; $position++) {
                $symbol = $direction === 'horizontal'
                    ? $grid[$index][$position]
                    : $grid[$position][$index];

                if ($symbol !== $firstSymbol) {
                    $allMatch = false;
                    break;
                }
            }

            if (! $allMatch) {
                continue;
            }

            $multiplier = (int) ($paytable[$firstSymbol] ?? 0);

            if ($multiplier <= 0) {
                continue;
            }

            $payout += intdiv($bet * $multiplier, $lineCount);
            $winningLines[] = [
                'direction' => $direction,
                'line' => $index,
                'symbol' => $firstSymbol,
                'count' => $length,
                'multiplier' => $multiplier,
            ];
        }

        return new GameResult($payout, [
            'grid' => $grid,
            'winning_lines' => $winningLines,
            'wager' => $bet,
            'payline_count' => $lineCount,
            'slot_variant' => (string) ($variant['key'] ?? 'classic'),
        ]);
    }
}
