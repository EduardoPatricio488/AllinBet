<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'balance_after',
        'idempotency_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Ledger transactions are immutable.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Ledger transactions are immutable.');
        });
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
