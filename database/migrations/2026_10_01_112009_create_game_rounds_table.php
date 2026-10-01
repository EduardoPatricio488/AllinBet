<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game', 32);
            $table->unsignedBigInteger('bet');
            $table->unsignedBigInteger('payout')->default(0);
            $table->string('status', 16);
            $table->json('result')->nullable();
            $table->char('server_seed_hash', 64);
            $table->text('server_seed');
            $table->string('client_seed', 128);
            $table->unsignedBigInteger('nonce');
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->unique(['user_id', 'game', 'nonce']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rounds');
    }
};
