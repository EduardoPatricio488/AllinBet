<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        config(['casino.initial_balance' => 750]);

        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $wallet = $user->wallet()->firstOrFail();

        $this->assertSame(750, $wallet->balance);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 750,
            'balance_after' => 750,
            'idempotency_key' => "registration:{$user->id}:initial",
        ]);
    }
}
