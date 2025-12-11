<?php

namespace App\Providers;

use App\Models\LogbookEntry;
use App\Policies\LogbookEntryPolicy;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        LogbookEntry::class => LogbookEntryPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
