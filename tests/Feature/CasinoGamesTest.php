<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GameType;
use App\Games\CoinflipGame;
use App\Games\DiceGame;
use App\Games\GameRegistry;
use App\Games\RouletteGame;
use App\Games\SlotsGame;
use App\Models\GameRound;
use App\Services\CasinoSimulationService;
use App\Services\ProvablyFairService;
use Tests\TestCase;

class CasinoGamesTest extends TestCase
{
    public function test_coinflip_uses_server_seed_and_integer_payouts(): void
    {
        $game = app(CoinflipGame::class);
        $result = $game->play($this->round(GameType::Coinflip, 100), ['side' => 'heads']);

        $this->assertContains($result->payout, [0, 195]);
        $this->assertIsInt($result->payout);
        $this->assertSame($result->result['won'], $result->payout > 0);
    }

    public function test_dice_probability_and_house_edge_produce_integer_gross_payouts(): void
    {
        $game = app(DiceGame::class);

        $underWin = $game->evaluate(4999, 100, 'under', 50);
        $underLoss = $game->evaluate(5000, 100, 'under', 50);
        $overWin = $game->evaluate(5000, 100, 'over', 50);

        $this->assertSame(190, $underWin->payout);
        $this->assertSame(0, $underLoss->payout);
        $this->assertSame(190, $overWin->payout);
        $this->assertSame(5000, $underWin->result['probability_basis_points']);
    }

    public function test_european_roulette_pays_straight_and_outside_bets_correctly(): void
    {
        $game = app(RouletteGame::class);

        $straight = $game->evaluate(0, 100, 'straight', 0);
        $red = $game->evaluate(1, 100, 'color', 'red');
        $dozen = $game->evaluate(12, 100, 'dozen', 1);
        $zeroMiss = $game->evaluate(0, 100, 'color', 'red');

        $this->assertSame(3600, $straight->payout);
        $this->assertSame(200, $red->payout);
        $this->assertSame(300, $dozen->payout);
        $this->assertSame(0, $zeroMiss->payout);
    }

    public function test_slots_paytable_pays_three_consecutive_symbols_on_horizontal_lines(): void
    {
        $grid = [
            [4, 4, 4, 1, 2],
            [0, 1, 2, 3, 5],
            [6, 7, 0, 1, 2],
            [1, 2, 3, 5, 6],
            [7, 0, 1, 2, 3],
        ];

        $result = app(SlotsGame::class)->settle($grid, 300);

        $this->assertSame(1269, $result->payout);
        $this->assertCount(1, $result->result['winning_lines']);
        $this->assertSame('horizontal', $result->result['winning_lines'][0]['direction']);
        $this->assertSame(3, $result->result['winning_lines'][0]['count']);
        $this->assertSame(4, $result->result['winning_lines'][0]['symbol']);
        $this->assertIsInt($result->payout);
    }

    public function test_slots_pay_vertical_lines_on_five_by_five_grid(): void
    {
        $grid = [
            [4, 0, 2, 6, 7],
            [4, 0, 3, 5, 1],
            [4, 0, 5, 6, 2],
            [1, 2, 6, 7, 3],
            [2, 3, 1, 2, 4],
        ];

        $result = app(SlotsGame::class)->settle($grid, 300);

        $this->assertSame(1381, $result->payout);
        $this->assertCount(2, $result->result['winning_lines']);
        $this->assertSame(['vertical', 'vertical'], array_column($result->result['winning_lines'], 'direction'));
        $this->assertSame([3, 3], array_column($result->result['winning_lines'], 'count'));
        $this->assertSame([4, 0], array_column($result->result['winning_lines'], 'symbol'));
    }

    public function test_dice_and_slots_simulations_stay_within_two_percent_of_target_rtp(): void
    {
        $simulation = app(CasinoSimulationService::class);

        foreach ([
            $simulation->run('dice', 1000000),
            $simulation->run('slots', 1000000),
        ] as $result) {
            $difference = abs($result['rtp_basis_points'] - $result['target_basis_points']);

            $this->assertLessThanOrEqual(200, $difference, "{$result['game']} RTP exceeded the configured margin.");
            $this->assertIsInt($result['wagered']);
            $this->assertIsInt($result['paid']);
        }
    }

    public function test_application_registry_resolves_each_implemented_instant_game(): void
    {
        $registry = app(GameRegistry::class);

        $this->assertSame(GameType::Coinflip, $registry->get(GameType::Coinflip)->type());
        $this->assertSame(GameType::Dice, $registry->get(GameType::Dice)->type());
        $this->assertSame(GameType::Roulette, $registry->get(GameType::Roulette)->type());
        $this->assertSame(GameType::Slots, $registry->get(GameType::Slots)->type());
    }

    private function round(GameType $game, int $bet): GameRound
    {
        $round = new GameRound;
        $round->forceFill([
            'game' => $game,
            'bet' => $bet,
            'server_seed' => (new ProvablyFairService)->createServerSeed(),
            'client_seed' => 'test-seed',
            'nonce' => 0,
        ]);

        return $round;
    }
}
