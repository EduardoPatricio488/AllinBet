<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameType;
use App\Games\DiceGame;
use App\Games\SlotsGame;
use InvalidArgumentException;

class CasinoSimulationService
{
    public function __construct(
        private readonly DiceGame $dice,
        private readonly SlotsGame $slots,
    ) {}

    /** @return array{game: string, rounds: int, wagered: int, paid: int, rtp_basis_points: int, target_basis_points: int} */
    public function run(string $game, int $rounds): array
    {
        $gameType = GameType::tryFrom($game);

        if (! in_array($gameType, [GameType::Dice, GameType::Slots], true)) {
            throw new InvalidArgumentException('Only dice and slots have RTP simulations.');
        }

        if ($rounds < 1 || $rounds > 10000000) {
            throw new InvalidArgumentException('The number of rounds must be between 1 and 10000000.');
        }

        $wagerPerRound = $gameType === GameType::Dice ? 100 : count(config('casino.games.slots.paylines', [])) * 100;
        $paid = 0;

        for ($round = 0; $round < $rounds; $round++) {
            if ($gameType === GameType::Dice) {
                $paid += $this->dice->evaluate(random_int(0, 9999), $wagerPerRound, 'under', 50)->payout;

                continue;
            }

            $rows = (int) config('casino.games.slots.rows', 3);
            $columns = (int) config('casino.games.slots.columns', 3);
            $symbolCount = (int) config('casino.games.slots.symbol_count', 5);
            $grid = [];

            for ($row = 0; $row < $rows; $row++) {
                $grid[$row] = [];

                for ($column = 0; $column < $columns; $column++) {
                    $grid[$row][$column] = random_int(0, $symbolCount - 1);
                }
            }

            $paid += $this->slots->settle($grid, $wagerPerRound)->payout;
        }

        $wagered = $rounds * $wagerPerRound;
        $target = (int) config("casino.games.{$game}.target_rtp_basis_points", 9500);

        return [
            'game' => $game,
            'rounds' => $rounds,
            'wagered' => $wagered,
            'paid' => $paid,
            'rtp_basis_points' => intdiv($paid * 10000, $wagered),
            'target_basis_points' => $target,
        ];
    }
}
