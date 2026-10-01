<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.⚡profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('settings/appearance', 'settings.⚡appearance')->name('appearance.edit');

    Volt::route('settings/security', 'settings.⚡security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
