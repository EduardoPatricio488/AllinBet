<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\IdempotencyKeyConflictException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use OverflowException;

class WalletService
{
    public function initialize(User $user, int $initialBalance): Wallet
    {
        if ($initialBalance < 0) {
            throw new InvalidArgumentException('Initial virtual credits cannot be negative.');
        }

        try {
            return DB::transaction(function () use ($user, $initialBalance): Wallet {
                $wallet = Wallet::query()
                    ->where('user_id', $user->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($wallet !== null) {
                    return $wallet;
                }

                $wallet = Wallet::query()->create([
                    'user_id' => $user->getKey(),
                    'balance' => 0,
                ]);

                if ($initialBalance > 0) {
                    $this->credit(
                        $user,
                        $initialBalance,
                        "registration:{$user->getKey()}:initial",
                    );
                }

                return $wallet->refresh();
            });
        } catch (QueryException $exception) {
            $wallet = Wallet::query()->where('user_id', $user->getKey())->first();

            if ($wallet !== null) {
                return $wallet;
            }

            throw $exception;
        }
    }

    public function credit(
        User $user,
        int $amount,
        string $idempotencyKey,
        ?Model $reference = null,
        TransactionType $type = TransactionType::Credit,
    ): Transaction {
        if ($amount <= 0 || ! $type->isCredit()) {
            throw new InvalidArgumentException('Credit operations require a positive amount and a credit transaction type.');
        }

        return $this->apply($user, $amount, $type, $idempotencyKey, $reference);
    }

    public function debit(
        User $user,
        int $amount,
        string $idempotencyKey,
        ?Model $reference = null,
        TransactionType $type = TransactionType::Debit,
    ): Transaction {
        if ($amount <= 0 || ! $type->isDebit()) {
            throw new InvalidArgumentException('Debit operations require a positive amount and a debit transaction type.');
        }

        return $this->apply($user, -$amount, $type, $idempotencyKey, $reference);
    }

    public function apply(
        User $user,
        int $amount,
        TransactionType $type,
        string $idempotencyKey,
        ?Model $reference = null,
    ): Transaction {
        if (! $type->acceptsAmount($amount) || $amount === PHP_INT_MIN) {
            throw new InvalidArgumentException('The signed transaction amount does not match its type or supported range.');
        }

        if (trim($idempotencyKey) === '' || strlen($idempotencyKey) > 255) {
            throw new InvalidArgumentException('The idempotency key must contain between 1 and 255 characters.');
        }

        try {
            return DB::transaction(function () use ($user, $amount, $type, $idempotencyKey, $reference): Transaction {
                $existing = $this->findExisting($user, $amount, $type, $idempotencyKey, $reference);

                if ($existing !== null) {
                    return $existing;
                }

                $wallet = Wallet::query()
                    ->where('user_id', $user->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $existing = $this->findExisting($user, $amount, $type, $idempotencyKey, $reference);

                if ($existing !== null) {
                    return $existing;
                }

                if ($amount < 0 && $wallet->balance < -$amount) {
                    throw new InsufficientBalanceException(-$amount, $wallet->balance);
                }

                if ($amount > 0 && $wallet->balance > PHP_INT_MAX - $amount) {
                    throw new OverflowException('The wallet balance exceeds the supported integer range.');
                }

                $newBalance = $wallet->balance + $amount;

                if ($newBalance < 0) {
                    throw new InsufficientBalanceException(-$amount, $wallet->balance);
                }

                $wallet->balance = $newBalance;
                $wallet->save();

                $transaction = $wallet->transactions()->make([
                    'type' => $type,
                    'amount' => $amount,
                    'balance_after' => $newBalance,
                    'idempotency_key' => $idempotencyKey,
                ]);

                if ($reference !== null) {
                    $transaction->reference()->associate($reference);
                }

                $transaction->save();

                return $transaction;
            });
        } catch (QueryException $exception) {
            $existing = $this->findExisting($user, $amount, $type, $idempotencyKey, $reference);

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function findExisting(
        User $user,
        int $amount,
        TransactionType $type,
        string $idempotencyKey,
        ?Model $reference,
    ): ?Transaction {
        $transaction = Transaction::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($transaction === null) {
            return null;
        }

        $walletId = Wallet::query()->where('user_id', $user->getKey())->value('id');
        $referenceType = $reference?->getMorphClass();
        $referenceId = $reference?->getKey();

        if (
            (int) $transaction->wallet_id !== (int) $walletId
            || $transaction->type !== $type
            || $transaction->amount !== $amount
            || $transaction->reference_type !== $referenceType
            || (string) $transaction->reference_id !== (string) $referenceId
        ) {
            throw new IdempotencyKeyConflictException($idempotencyKey);
        }

        return $transaction;
    }
}
