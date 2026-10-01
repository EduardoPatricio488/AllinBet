<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ResponsibleGamingSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponsibleGamingSetting extends Model
{
    /** @use HasFactory<ResponsibleGamingSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_loss_limit',
        'max_bet',
        'paused_until',
        'excluded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_loss_limit' => 'integer',
            'max_bet' => 'integer',
            'paused_until' => 'immutable_datetime',
            'excluded_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
