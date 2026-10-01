<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GameType;
use InvalidArgumentException;

class GameRegistry
{
    /** @var array<string, Game> */
    private array $games = [];

    /** @param iterable<Game> $games */
    public function __construct(iterable $games = [])
    {
        foreach ($games as $game) {
            $this->register($game);
        }
    }

    public function register(Game $game): void
    {
        $this->games[$game->type()->value] = $game;
    }

    public function get(GameType $type): Game
    {
        return $this->games[$type->value]
            ?? throw new InvalidArgumentException("Game [{$type->value}] is not registered.");
    }
}
