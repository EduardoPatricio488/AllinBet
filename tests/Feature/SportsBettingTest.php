<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Casino\Sports;
use App\Models\SportsBet;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SportsBettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sports_page_requires_a_verified_authenticated_user(): void
    {
        $this->get(route('casino.sports'))->assertRedirect(route('login'));

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('casino.sports'))->assertRedirect(route('verification.notice'));
    }

    public function test_sports_page_shows_virtual_events_and_bet_slip(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->initialize($user, 1000);

        $this->actingAs($user)
            ->get(route('casino.sports'))
            ->assertOk()
            ->assertSee('Apostas desportivas')
            ->assertSee('Benfica')
            ->assertSee('FC Porto')
            ->assertSee('Boletim de apostas')
            ->assertSee('Créditos virtuais');
    }

    public function test_user_can_select_an_outcome_and_register_a_virtual_bet(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->initialize($user, 1000);
        $this->actingAs($user);

        Livewire::test(Sports::class)
            ->call('select', 'slb-fcp', 'home')
            ->call('select', 'ars-mci', 'away')
            ->set('stake', 100)
            ->call('placeBet')
            ->assertDispatched('wallet-updated')
            ->assertDispatched('casino-toast')
            ->assertSet('slip', []);

        $bet = SportsBet::query()->where('user_id', $user->getKey())->latest('id')->first();

        self::assertNotNull($bet);
        self::assertSame(100, $bet->stake);
        self::assertSame('pending', $bet->status);
        self::assertCount(2, $bet->selections);
        self::assertGreaterThan(100, $bet->potential_payout);
        self::assertSame(900, $wallet->fresh()->balance);
    }

    public function test_user_can_access_their_sports_bet_history(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->initialize($user, 1000);

        SportsBet::query()->create([
            'user_id' => $user->getKey(),
            'stake' => 100,
            'combined_odd' => 3.68,
            'potential_payout' => 368,
            'status' => 'pending',
            'selections' => [
                [
                    'home' => 'Benfica',
                    'away' => 'FC Porto',
                    'selection' => 'Benfica',
                    'odd' => 1.92,
                    'date' => 'Hoje',
                    'time' => '19:30',
                ],
            ],
            'idempotency_key' => 'sports-history-test',
        ]);

        $this->actingAs($user)
            ->get(route('casino.sports.bets'))
            ->assertOk()
            ->assertSee('As minhas apostas')
            ->assertSee('Benfica')
            ->assertSee('FC Porto')
            ->assertSee('368')
            ->assertSee('3,68');
    }

    public function test_empty_bet_slip_cannot_be_registered(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->initialize($user, 500);
        $this->actingAs($user);

        Livewire::test(Sports::class)
            ->set('stake', 100)
            ->call('placeBet')
            ->assertHasErrors(['slip']);

        self::assertSame(0, SportsBet::query()->count());
    }
}
