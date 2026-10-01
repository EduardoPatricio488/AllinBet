<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GameType;
use App\Models\GameRound;
use App\Services\PayoutCalculator;
use App\Services\ProvablyFairService;
use InvalidArgumentException;

class CoinflipGame implements Game
{
    public function __construct(
        private readonly ProvablyFairService $provablyFair,
        private readonly PayoutCalculator $payoutCalculator,
    ) {}

    public function type(): GameType
    {
        return GameType::Coinflip;
    }

    public function play(GameRound $round, array $input): GameResult
    {
        $side = $input['side'] ?? null;

        if (! in_array($side, ['heads', 'tails'], true)) {
            throw new InvalidArgumentException('Choose either heads or tails.');
        }

        $outcome = $this->provablyFair->integer(
            $round->server_seed,
            $round->client_seed,
            $round->nonce,
            0,
            1,
        );
        $result = $outcome === 0 ? 'heads' : 'tails';
        $won = $side === $result;
        $houseEdge = (int) config('casino.games.coinflip.house_edge_bps', 250);

        return new GameResult(
            $won ? $this->payoutCalculator->grossPayout($round->bet, 5000, $houseEdge) : 0,
            ['side' => $side, 'outcome' => $result, 'won' => $won],
        );
    }
}
