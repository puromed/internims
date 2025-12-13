<?php

use App\Http\Controllers\ThemePreferenceController;
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

    // Student routes
    Volt::route('dashboard', 'dashboard')->name('dashboard');
    Volt::route('eligibility', 'eligibility.index')->name('eligibility.index');
    Volt::route('placement', 'placement.index')->name('placement.index');
    Volt::route('logbooks', 'logbooks.index')->name('logbooks.index');
    Volt::route('logbooks/{logbook}', 'logbooks.show')->name('logbooks.show');

    // Faculty routes
    Route::middleware('role:faculty,admin')
        ->prefix('faculty')
        ->as('faculty.')
        ->group(function () {
            Volt::route('dashboard', 'faculty.dashboard')->name('dashboard');
            Volt::route('students', 'faculty.students.index')->name('students.index');
            Volt::route('logbooks', 'faculty.logbooks.index')->name('logbooks.index');
            Volt::route('logbooks/{logbook}', 'faculty.logbooks.show')->name('logbooks.show');
        });

    // Volt User Settings Routes
    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
    Route::post('appearance/theme', ThemePreferenceController::class)->name('appearance.update');

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
