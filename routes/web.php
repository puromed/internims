<?php

use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\ThemePreferenceController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// OAuth Routes
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'google|microsoft')
    ->name('social.redirect');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'google|microsoft')
    ->name('social.callback');

// optional route for gallery smoke test
Route::get('/gallery', function () {
    return view('gallery');
})->name('gallery');

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureStudentProfileComplete::class])->group(function () {
    Route::redirect('settings', 'settings/profile');

    // Student routes
    Volt::route('dashboard', 'dashboard')->name('dashboard');
    Volt::route('eligibility', 'eligibility.index')->name('eligibility.index');

    // Placement requires all eligibility docs to be approved
    Volt::route('placement', 'placement.index')
        ->middleware(\App\Http\Middleware\EnsureEligibilityCompleted::class)
        ->name('placement.index');

    // Logbooks require internship to exist
    Volt::route('logbooks', 'logbooks.index')
        ->middleware(\App\Http\Middleware\EnsureInternshipExists::class)
        ->name('logbooks.index');
    Volt::route('logbooks/{logbook}', 'logbooks.show')
        ->middleware(\App\Http\Middleware\EnsureInternshipExists::class)
        ->name('logbooks.show');

    // Admin routes
    Route::middleware('role:admin')
        ->prefix('admin')
        ->as('admin.')
        ->group(function () {
            Volt::route('dashboard', 'admin.dashboard')->name('dashboard');
            Volt::route('eligibility', 'admin.eligibility.index')->name('eligibility.index');
            Volt::route('companies', 'admin.companies.index')->name('companies.index');
            Volt::route('users', 'admin.users.index')->name('users.index');
            Volt::route('assignments', 'admin.assignments.index')->name('assignments.index');
            Volt::route('dates', 'admin.dates.index')->name('dates.index');
        });

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
