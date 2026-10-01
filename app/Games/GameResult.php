<?php

declare(strict_types=1);

namespace App\Games;

use InvalidArgumentException;

final readonly class GameResult
{
    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $state
     */
    public function __construct(
        public int $payout,
        public array $result,
        public bool $completed = true,
        public array $state = [],
        public int $additionalBet = 0,
    ) {
        if ($payout < 0 || $additionalBet < 0 || (! $completed && $payout !== 0)) {
            throw new InvalidArgumentException('A game payout cannot be negative.');
        }
    }
}
