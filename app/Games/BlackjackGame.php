<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GameType;
use App\Models\GameRound;
use App\Services\PayoutCalculator;
use App\Services\ProvablyFairService;
use InvalidArgumentException;
use RuntimeException;

class BlackjackGame implements Game
{
    private const array SUITS = ['clubs', 'diamonds', 'hearts', 'spades'];

    public function __construct(
        private readonly PayoutCalculator $payoutCalculator,
        private readonly ProvablyFairService $provablyFair,
    ) {}

    public function type(): GameType
    {
        return GameType::Blackjack;
    }

    /** @param array<string, mixed> $input */
    public function play(GameRound $round, array $input): GameResult
    {
        $action = $input['action'] ?? null;
        $state = $round->privateGameState();

        if (! is_string($action)) {
            throw new InvalidArgumentException('A blackjack action is required.');
        }

        if ($state === []) {
            if ($action !== 'start') {
                throw new InvalidArgumentException('A blackjack round must start with a deal action.');
            }

            return $this->deal($round);
        }

        return match ($action) {
            'hit' => $this->hit($state),
            'stand' => $this->stand($state),
            'double' => $this->double($state, $round->bet),
            default => throw new InvalidArgumentException('Unsupported blackjack action.'),
        };
    }

    /**
     * @return array<int, array{rank: int, suit: string}>
     */
    protected function shuffledDeck(GameRound $round): array
    {
        $deck = [];

        foreach (self::SUITS as $suit) {
            for ($rank = 1; $rank <= 13; $rank++) {
                $deck[] = ['rank' => $rank, 'suit' => $suit];
            }
        }

        for ($index = count($deck) - 1; $index > 0; $index--) {
            $swapIndex = $this->provablyFair->integer(
                $round->server_seed,
                $round->client_seed,
                ($round->nonce * 52) + (51 - $index),
                0,
                $index,
            );

            [$deck[$index], $deck[$swapIndex]] = [$deck[$swapIndex], $deck[$index]];
        }

        return $deck;
    }

    private function deal(GameRound $round): GameResult
    {
        $bet = $round->bet;
        $deck = $this->shuffledDeck($round);
        $state = [
            'deck' => $deck,
            'player' => [],
            'dealer' => [],
            'total_wager' => $bet,
            'action_count' => 0,
            'doubled' => false,
        ];

        $state['player'][] = $this->draw($state['deck']);
        $state['dealer'][] = $this->draw($state['deck']);
        $state['player'][] = $this->draw($state['deck']);
        $state['dealer'][] = $this->draw($state['deck']);

        $playerNatural = $this->score($state['player']) === 21;
        $dealerNatural = $this->score($state['dealer']) === 21;

        if ($playerNatural || $dealerNatural) {
            $payout = match (true) {
                $playerNatural && $dealerNatural => $bet,
                $playerNatural => $this->payoutCalculator->grossPayout($bet, 4000, 0),
                default => 0,
            };

            return new GameResult($payout, $this->publicResult($state, true));
        }

        return new GameResult(0, $this->publicResult($state, false), false, $state);
    }

    /** @param array<string, mixed> $state */
    private function hit(array $state): GameResult
    {
        $state['player'][] = $this->draw($state['deck']);
        $state['action_count']++;

        if ($this->score($state['player']) >= 21) {
            return $this->settle($state);
        }

        return new GameResult(0, $this->publicResult($state, false), false, $state);
    }

    /** @param array<string, mixed> $state */
    private function stand(array $state): GameResult
    {
        $state['action_count']++;

        return $this->settle($state);
    }

    /** @param array<string, mixed> $state */
    private function double(array $state, int $initialBet): GameResult
    {
        if ($state['action_count'] !== 0 || count($state['player']) !== 2 || $state['doubled']) {
            throw new InvalidArgumentException('Double is only allowed on the first action of a two-card hand.');
        }

        $state['doubled'] = true;
        $state['total_wager'] += $initialBet;
        $state['player'][] = $this->draw($state['deck']);
        $state['action_count']++;
        $result = $this->settle($state);

        return new GameResult($result->payout, $result->result, true, $state, $initialBet);
    }

    /** @param array<string, mixed> $state */
    private function settle(array $state): GameResult
    {
        $playerScore = $this->score($state['player']);

        if ($playerScore <= 21) {
            while ($this->score($state['dealer']) < 17) {
                $state['dealer'][] = $this->draw($state['deck']);
            }
        }

        $dealerScore = $this->score($state['dealer']);
        $outcome = match (true) {
            $playerScore > 21 => 'dealer',
            $dealerScore > 21, $playerScore > $dealerScore => 'player',
            $playerScore < $dealerScore => 'dealer',
            default => 'push',
        };
        $payout = match ($outcome) {
            'player' => $this->payoutCalculator->multiply($state['total_wager'], 2),
            'push' => $state['total_wager'],
            default => 0,
        };

        return new GameResult($payout, $this->publicResult($state, true, $outcome), true, $state);
    }

    /** @param array<int, array{rank: int, suit: string}> $cards */
    private function score(array $cards): int
    {
        $score = 0;
        $aces = 0;

        foreach ($cards as $card) {
            if ($card['rank'] === 1) {
                $score += 11;
                $aces++;
            } else {
                $score += min($card['rank'], 10);
            }
        }

        while ($score > 21 && $aces > 0) {
            $score -= 10;
            $aces--;
        }

        return $score;
    }

    /**
     * @param  array<int, array{rank: int, suit: string}>  $deck
     * @return array{rank: int, suit: string}
     */
    private function draw(array &$deck): array
    {
        $card = array_pop($deck);

        if ($card === null) {
            throw new RuntimeException('The blackjack deck is empty.');
        }

        return $card;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function publicResult(array $state, bool $completed, ?string $outcome = null): array
    {
        $dealerHand = $state['dealer'];

        if (! $completed) {
            $dealerHand[1] = ['hidden' => true];
        }

        $visibleDealerCard = $state['dealer'][0];

        return [
            'player_hand' => $state['player'],
            'player_total' => $this->score($state['player']),
            'dealer_hand' => $dealerHand,
            'dealer_visible_total' => $completed
                ? $this->score($state['dealer'])
                : ($visibleDealerCard['rank'] === 1 ? 11 : min($visibleDealerCard['rank'], 10)),
            'total_wager' => $state['total_wager'],
            'outcome' => $outcome,
            'available_actions' => $completed ? [] : ['hit', 'stand', ...($state['action_count'] === 0 ? ['double'] : [])],
        ];
    }
}
