<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\IdempotencyKeyConflictException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_credits_and_debits_keep_the_balance_equal_to_the_ledger_sum(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $wallet = $walletService->initialize($user, 100);

        $walletService->credit($user, 40, 'credit:one');
        $walletService->debit($user, 25, 'debit:one');
        $walletService->credit($user, 15, 'credit:two');

        $ledgerTotal = (int) $wallet->transactions()->sum('amount');

        $this->assertSame(130, $wallet->fresh()->balance);
        $this->assertSame($ledgerTotal, $wallet->fresh()->balance);
    }

    public function test_debiting_more_than_the_balance_throws_and_rolls_back(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $wallet = $walletService->initialize($user, 50);

        try {
            $walletService->debit($user, 51, 'debit:too-much');
            $this->fail('Expected an insufficient balance exception.');
        } catch (InsufficientBalanceException $exception) {
            $this->assertSame(51, $exception->requestedAmount);
            $this->assertSame(50, $exception->availableBalance);
        }

        $this->assertSame(50, $wallet->fresh()->balance);
        $this->assertSame(1, $wallet->transactions()->count());
    }

    public function test_replaying_the_same_idempotency_key_returns_the_existing_transaction(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $wallet = $walletService->initialize($user, 0);

        $first = $walletService->credit($user, 20, 'credit:repeat');
        $replay = $walletService->credit($user, 20, 'credit:repeat');

        $this->assertSame($first->id, $replay->id);
        $this->assertSame(20, $wallet->fresh()->balance);
        $this->assertSame(1, $wallet->transactions()->count());
    }

    public function test_reusing_an_idempotency_key_for_a_different_operation_is_rejected(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $walletService->initialize($user, 0);
        $walletService->credit($user, 20, 'credit:conflict');

        $this->expectException(IdempotencyKeyConflictException::class);

        $walletService->credit($user, 30, 'credit:conflict');
    }

    public function test_ledger_transactions_cannot_be_updated_or_deleted(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $wallet = $walletService->initialize($user, 0);
        $transaction = $walletService->credit($user, 20, 'credit:immutable');

        $transaction->amount = 30;

        try {
            $transaction->save();
            $this->fail('Expected ledger update to be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('Ledger transactions are immutable.', $exception->getMessage());
        }

        $transaction = Transaction::query()->findOrFail($transaction->id);

        $this->expectException(LogicException::class);
        $transaction->delete();
    }
}
