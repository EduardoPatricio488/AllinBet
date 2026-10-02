<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GameType;
use App\Models\GameRound;
use App\Services\ProvablyFairService;
use InvalidArgumentException;

class SlotsGame implements Game
{
    public function __construct(
        private readonly ProvablyFairService $provablyFair,
    ) {}

    public function type(): GameType
    {
        return GameType::Slots;
    }

    public function play(GameRound $round, array $input): GameResult
    {
        $variant = $this->variantFor($round);

        if (in_array(($input['action'] ?? 'spin'), ['bonus_buy', 'bonus_spin'], true)) {
            return $this->bonusBuy($round, $variant);
        }

        $grid = $this->generateGrid($round, $variant, $round->nonce);

        return $this->settle($grid, $round->bet, $variant);
    }

    /** @param array<string, mixed> $variant */
    private function generateGrid(GameRound $round, array $variant, int $nonce): array
    {
        $rows = (int) config('casino.games.slots.rows', 3);
        $columns = (int) config('casino.games.slots.columns', 3);
        $symbols = $variant['symbols'] ?? [];
        $symbolCount = count($symbols);

        if ($symbolCount < 1) {
            throw new InvalidArgumentException('The slots variant has no symbols.');
        }

        $grid = [];

        for ($row = 0; $row < $rows; $row++) {
            $grid[$row] = [];

            for ($column = 0; $column < $columns; $column++) {
                $grid[$row][$column] = $this->provablyFair->integer(
                    $round->server_seed,
                    $round->client_seed,
                    ($nonce * $rows * $columns) + ($row * $columns) + $column,
                    0,
                    $symbolCount - 1,
                );
            }
        }

        return $grid;
    }

    /** @param array<string, mixed> $variant */
    private function bonusBuy(GameRound $round, array $variant): GameResult
    {
        $state = $round->privateGameState();
        $baseBet = (int) ($state['bonus_base_bet'] ?? 0);
        $spinCount = (int) ($state['bonus_spin_count'] ?? 0);
        $bonusMultiplier = (int) ($state['bonus_multiplier'] ?? 0);

        if (
            ! (bool) ($state['bonus_buy'] ?? false)
            || $baseBet < 1
            || $spinCount < 1
            || $bonusMultiplier < 1
            || $baseBet > intdiv(PHP_INT_MAX, $bonusMultiplier)
            || $round->bet !== $baseBet * $bonusMultiplier
        ) {
            throw new InvalidArgumentException('The slots bonus purchase is invalid.');
        }

        $totalPayout = 0;
        $spinResults = [];
        $finalGrid = [];
        $finalWinningLines = [];

        for ($spin = 1; $spin <= $spinCount; $spin++) {
            $grid = $this->generateGrid($round, $variant, $round->nonce + $spin);
            $result = $this->settle($grid, $baseBet, $variant);
            $totalPayout += $result->payout;
            $finalGrid = $grid;
            $finalWinningLines = $result->result['winning_lines'] ?? [];

            $spinResults[] = [
                'spin' => $spin,
                'payout' => $result->payout,
                'winning_lines' => $finalWinningLines,
            ];
        }

        $state = [
            ...$state,
            'bonus_current_spin' => $spinCount,
            'bonus_total_payout' => $totalPayout,
            'bonus_spin_results' => $spinResults,
        ];

        return new GameResult(
            $totalPayout,
            [
                'grid' => $finalGrid,
                'winning_lines' => $finalWinningLines,
                'wager' => $baseBet,
                'payline_count' => count($this->validPaylines()),
                'slot_variant' => (string) ($variant['key'] ?? 'classic'),
                'bonus_buy' => true,
                'bonus_label' => (string) ($state['bonus_label'] ?? 'Bónus'),
                'bonus_multiplier' => $bonusMultiplier,
                'bonus_spin_count' => $spinCount,
                'bonus_current_spin' => $spinCount,
                'bonus_base_bet' => $baseBet,
                'bonus_cost' => $round->bet,
                'bonus_payout' => $totalPayout,
                'bonus_profit' => $totalPayout - $round->bet,
                'bonus_spin_results' => $spinResults,
            ],
            true,
            $state,
        );
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
        $paylines = $this->validPaylines();
        $paytable = $variant['paytable'] ?? [];
        $symbolCount = count($variant['symbols'] ?? []);
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

            $length = $direction === 'horizontal' ? $columns : $rows;
            $matchCount = 1;

            for ($position = 1; $position < $length; $position++) {
                $symbol = $direction === 'horizontal'
                    ? $grid[$index][$position]
                    : $grid[$position][$index];

                if ($symbol !== $firstSymbol) {
                    break;
                }

                $matchCount++;
            }

            $minimumMatch = max(3, (int) config('casino.games.slots.minimum_match', 3));

            if ($matchCount < $minimumMatch) {
                continue;
            }

            $symbolMultiplier = (int) ($paytable[$firstSymbol] ?? 0);
            $matchMultiplier = (int) config("casino.games.slots.match_multipliers.{$matchCount}", 0);

            if ($symbolMultiplier <= 0 || $matchMultiplier <= 0) {
                continue;
            }

            $payoutFactorBps = max(1, min(10_000, (int) config('casino.games.slots.payout_factor_bps', 10_000)));
            $grossLinePayout = intdiv(
                $bet * $symbolMultiplier * $matchMultiplier * $payoutFactorBps,
                $lineCount * 10_000,
            );
            $linePayout = max(1, $grossLinePayout);
            $payout += $linePayout;
            $winningLines[] = [
                'direction' => $direction,
                'line' => $index,
                'symbol' => $firstSymbol,
                'count' => $matchCount,
                'multiplier' => $symbolMultiplier * $matchMultiplier,
                'base_multiplier' => $symbolMultiplier,
                'match_multiplier' => $matchMultiplier,
                'payout' => $linePayout,
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

    /** @return array<int, array{direction: string, index: int}> */
    private function validPaylines(): array
    {
        $rows = (int) config('casino.games.slots.rows', 3);
        $columns = (int) config('casino.games.slots.columns', 3);
        $paylines = config('casino.games.slots.paylines', []);

        return array_values(array_filter(
            $paylines,
            static fn ($line): bool => is_array($line)
                && isset($line['direction'], $line['index'])
                && in_array($line['direction'], ['horizontal', 'vertical'], true)
                && is_int($line['index'])
                && $line['index'] >= 0
                && $line['index'] < ($line['direction'] === 'horizontal' ? $rows : $columns),
        ));
    }

}
