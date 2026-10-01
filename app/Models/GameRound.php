<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameType;
use App\Enums\RoundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @property string $server_seed */
class GameRound extends Model
{
    protected $fillable = [
        'user_id',
        'game',
        'bet',
        'payout',
        'status',
        'result',
        'server_seed_hash',
        'server_seed',
        'client_seed',
        'nonce',
        'idempotency_key',
    ];

    protected $hidden = [
        'server_seed',
        'result',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'game' => GameType::class,
            'status' => RoundStatus::class,
            'bet' => 'integer',
            'payout' => 'integer',
            'result' => 'array',
            'server_seed' => 'encrypted',
            'nonce' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'reference');
    }

    public function revealedServerSeed(): ?string
    {
        return $this->status === RoundStatus::Completed ? $this->server_seed : null;
    }

    /** @return array<string, mixed> */
    public function publicResult(): array
    {
        return $this->result['public'] ?? $this->result ?? [];
    }

    /** @return array<string, mixed> */
    public function privateGameState(): array
    {
        return $this->result['private'] ?? [];
    }
}
