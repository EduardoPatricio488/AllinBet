<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

class CasinoWalletSeeder extends Seeder
{
    public function __construct(private readonly WalletService $walletService) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (User::query()->cursor() as $user) {
            $this->walletService->initialize($user, (int) config('casino.initial_balance', 0));
        }
    }
}
