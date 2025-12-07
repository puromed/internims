<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// optional route for gallery smoke test
Route::get('/gallery', function () {
    return view('gallery');
})->name('gallery');



Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    // User Settings
    Volt::route('dashboard', 'dashboard')->name('dashboard');
    Volt::route('eligibility', 'eligibility.index')->name('eligibility.index');
    Volt::route('placement', 'placement.index')->name('placement.index');
    Volt::route('logbooks', 'logbooks.index')->name('logbooks.index');
    Volt::route('logbooks/{logbook}', 'logbooks.show')->name('logbooks.show');

    // Volt User Settings Routes
    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
