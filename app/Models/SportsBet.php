<?php

declare(strict_types=1);

namespace AppModels;

use IlluminateDatabaseEloquentModel;
use IlluminateDatabaseEloquentRelationsBelongsTo;

class SportsBet extends Model
{
    protected $fillable = [
        'user_id',
        'stake',
        'combined_odd',
        'potential_payout',
        'status',
        'selections',
        'idempotency_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stake' => 'integer',
            'combined_odd' => 'decimal:2',
            'potential_payout' => 'integer',
            'selections' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
