<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('stake');
            $table->decimal('combined_odd', 8, 2);
            $table->unsignedBigInteger('potential_payout');
            $table->string('status', 30)->default('pending');
            $table->json('selections');
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_bets');
    }
};
