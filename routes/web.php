<?php

use App\Http\Controllers\GameRoundVerificationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'casino.lobby')->name('home');
Route::get('fairness/{gameRound}', GameRoundVerificationController::class)->name('fairness.verify');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'casino.lobby')->name('dashboard');
    Route::view('casino/coinflip', 'casino.games.coinflip')->name('casino.coinflip');
    Route::view('casino/dice', 'casino.games.dice')->name('casino.dice');
    Route::view('casino/roulette', 'casino.games.roulette')->name('casino.roulette');
    Route::view('casino/slots', 'casino.games.slots')->name('casino.slots');
    Route::view('casino/jetx', 'casino.games.jetx')->name('casino.jetx');
    Route::view('casino/blackjack', 'casino.games.blackjack')->name('casino.blackjack');
    Route::view('casino/history', 'casino.history')->name('casino.history');
    Route::view('casino/wallet', 'casino.wallet')->name('casino.wallet');
    Route::view('casino/help', 'casino.help')->name('casino.help');
});

require __DIR__.'/settings.php';
