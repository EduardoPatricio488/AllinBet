<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GameType;
use App\Models\GameRound;

interface Game
{
    public function type(): GameType;

    /** @param array<string, mixed> $input */
    public function play(GameRound $round, array $input): GameResult;
}
