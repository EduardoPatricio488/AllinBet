<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\RoundStatus;
use App\Livewire\Casino\Coinflip;
use App\Livewire\Casino\DailyBonus;
use App\Livewire\Casino\RecentWins;
use App\Livewire\Casino\WalletBalance;
use App\Models\GameRound;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CasinoUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lobby_is_public_and_discloses_virtual_credits(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Casino lobby')
            ->assertSee('Créditos virtuais — sem valor monetário');
    }

    public function test_recent_wins_marks_examples_as_demo_when_there_are_no_real_wins(): void
    {
        Livewire::test(RecentWins::class)
            ->assertSee('DEMO · PRÉ-VISUALIZAÇÃO')
            ->assertSee('Exemplo demonstrativo');
    }

    public function test_recent_wins_ticker_uses_real_completed_rounds_without_player_data(): void
    {
        $user = User::factory()->create();

        GameRound::query()->create([
            'user_id' => $user->id,
            'game' => GameType::Dice,
            'bet' => 100,
            'payout' => 185,
            'status' => RoundStatus::Completed,
            'result' => ['roll' => 4100],
            'server_seed_hash' => str_repeat('a', 64),
            'server_seed' => str_repeat('b', 64),
            'client_seed' => 'ticker-test',
            'nonce' => 1,
            'idempotency_key' => 'ticker:real-win',
        ]);

        Livewire::test(RecentWins::class)
            ->assertSee('Dice')
            ->assertSee('+185')
            ->assertDontSee('DEMO');
    }

    public function test_daily_bonus_card_claims_once_and_updates_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->initialize($user, 250);
        $this->actingAs($user);

        $bonus = Livewire::test(DailyBonus::class)
            ->call('claim')
            ->assertDispatched('casino-toast')
            ->assertSee('+100 créditos adicionados à carteira.');

        $bonus->call('claim')
            ->assertSee('O próximo bónus fica disponível amanhã.');

        $this->assertSame(350, $wallet->fresh()->balance);
        $this->assertSame(1, $wallet->transactions()->where('type', 'bonus')->count());
    }

    public function test_wallet_balance_announces_only_the_server_confirmed_delta(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->initialize($user, 100);
        $this->actingAs($user);

        $balance = Livewire::test(WalletBalance::class)
            ->assertSee('100');

        app(WalletService::class)->credit($user, 25, 'wallet-ui:credit');

        $balance->call('refreshBalance')
            ->assertDispatched('casino-balance-changed');

        $this->assertSame(125, $wallet->fresh()->balance);
    }

    public function test_game_pages_require_a_verified_authenticated_user(): void
    {
        $this->get(route('casino.coinflip'))->assertRedirect(route('login'));

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('casino.coinflip'))->assertRedirect(route('verification.notice'));
    }

    public function test_every_game_page_renders_for_a_verified_user(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->initialize($user, 500);

        foreach (['casino.coinflip', 'casino.dice', 'casino.roulette', 'casino.slots', 'casino.blackjack'] as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
    }

    public function test_livewire_game_recovers_a_prepared_round_after_refresh(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->initialize($user, 500);
        $this->actingAs($user);

        $first = Livewire::test(Coinflip::class)
            ->set('bet', 100)
            ->set('side', 'tails')
            ->call('prepare')
            ->assertSet('roundPhase', 'prepared');

        $roundId = $first->get('roundId');

        Livewire::test(Coinflip::class)
            ->assertSet('roundPhase', 'prepared')
            ->assertSet('roundId', $roundId)
            ->assertNotSet('serverSeedHash', '');
    }

    public function test_livewire_coinflip_validates_input_and_runs_through_bet_service(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->initialize($user, 500);
        $this->actingAs($user);

        Livewire::test(Coinflip::class)
            ->call('flip')
            ->assertHasErrors(['game'])
            ->assertDispatched('casino-toast');

        Livewire::test(Coinflip::class)
            ->set('bet', 0)
            ->call('prepare')
            ->assertHasErrors(['bet']);

        $component = Livewire::test(Coinflip::class)
            ->set('bet', 100)
            ->set('side', 'heads')
            ->call('prepare')
            ->assertSet('roundPhase', 'prepared');

        $this->assertNotEmpty($component->get('serverSeedHash'));

        $component->call('flip')
            ->assertSet('roundPhase', 'in_progress')
            ->assertSet('roundResult.settlement_pending', true)
            ->assertDispatched('casino-settlement-pending')
            ->assertDispatched('wallet-updated');

        $round = GameRound::query()->findOrFail($component->get('roundId'));
        $public = $round->publicResult();
        $private = $round->privateGameState();
        $private['_settle_after_ms'] = (int) round(microtime(true) * 1000) - 1;

        $round->forceFill([
            'result' => [
                'public' => $public,
                'private' => $private,
            ],
        ])->save();

        $component->call('settlePayout')
            ->assertSet('roundPhase', 'completed')
            ->assertDispatched('history-updated');

        $this->assertSame((int) $wallet->transactions()->sum('amount'), $wallet->fresh()->balance);
    }
}
