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
            [4, 4, 4],
            [0, 1, 2],
            [3, 5, 6],
        ];

        $result = app(SlotsGame::class)->settle($grid, 300);

        $this->assertSame(2538, $result->payout);
        $this->assertCount(1, $result->result['winning_lines']);
        $this->assertSame('horizontal', $result->result['winning_lines'][0]['direction']);
        $this->assertSame(3, $result->result['winning_lines'][0]['count']);
        $this->assertSame(4, $result->result['winning_lines'][0]['symbol']);
        $this->assertSame(5, $result->result['payline_count']);
        $this->assertSame(3, count($result->result['grid']));
        $this->assertIsInt($result->payout);
    }

    public function test_slots_pay_vertical_lines_on_three_by_three_grid(): void
    {
        $grid = [
            [4, 0, 2],
            [4, 1, 3],
            [4, 2, 5],
        ];

        $result = app(SlotsGame::class)->settle($grid, 300);

        $this->assertSame(2538, $result->payout);
        $this->assertCount(1, $result->result['winning_lines']);
        $this->assertSame(['vertical'], array_column($result->result['winning_lines'], 'direction'));
        $this->assertSame([3], array_column($result->result['winning_lines'], 'count'));
        $this->assertSame([4], array_column($result->result['winning_lines'], 'symbol'));
    }

    public function test_slots_bonus_buy_runs_the_configured_virtual_spins_and_returns_final_grid(): void
    {
        $round = $this->round(GameType::Slots, 500);
        $round->forceFill([
            'result' => [
                'public' => [],
                'private' => [
                    'slot_variant' => 'classic',
                    'bonus_buy' => true,
                    'bonus_multiplier' => 10,
                    'bonus_spin_count' => 10,
                    'bonus_base_bet' => 500,
                    'bonus_label' => 'Mini Bónus',
                ],
            ],
        ]);

        $result = app(SlotsGame::class)->play($round, ['action' => 'bonus_buy']);

        $this->assertTrue($result->completed);
        $this->assertIsInt($result->payout);
        $this->assertArrayHasKey('grid', $result->result);
        $this->assertCount(3, $result->result['grid']);
        $this->assertSame(3, count($result->result['grid'][0]));
        $this->assertSame(5, $result->result['payline_count']);
        $this->assertTrue($result->result['bonus_buy']);
        $this->assertSame(10, $result->result['bonus_spin_count']);
        $this->assertCount(10, $result->result['bonus_spin_results']);
        $this->assertSame($result->payout - 5000, $result->result['bonus_profit']);
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
