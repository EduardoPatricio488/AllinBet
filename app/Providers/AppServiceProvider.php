<?php

namespace App\Providers;

use App\Games\BlackjackGame;
use App\Games\CoinflipGame;
use App\Games\DiceGame;
use App\Games\GameRegistry;
use App\Games\RouletteGame;
use App\Games\SlotsGame;
use App\Livewire\Casino\Blackjack;
use App\Livewire\Casino\Coinflip;
use App\Livewire\Casino\Dice;
use App\Livewire\Casino\History;
use App\Livewire\Casino\Roulette;
use App\Livewire\Casino\Slots;
use App\Livewire\Casino\WalletBalance;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Livewire\Volt\Volt;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GameRegistry::class, function (Application $app): GameRegistry {
            return new GameRegistry([
                $app->make(CoinflipGame::class),
                $app->make(DiceGame::class),
                $app->make(RouletteGame::class),
                $app->make(BlackjackGame::class),
                $app->make(SlotsGame::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Volt::mount(resource_path('views/pages'));
        $this->loadViewsFrom(resource_path('views/pages'), 'pages');
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');
        Blade::anonymousComponentPath(resource_path('views/pages'), 'pages');

        Livewire::component('casino.coinflip', Coinflip::class);
        Livewire::component('casino.dice', Dice::class);
        Livewire::component('casino.roulette', Roulette::class);
        Livewire::component('casino.slots', Slots::class);
        Livewire::component('casino.blackjack', Blackjack::class);
        Livewire::component('casino.wallet-balance', WalletBalance::class);
        Livewire::component('casino.history', History::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
