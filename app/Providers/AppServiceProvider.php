<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Deny by default: no one enters the staff panel until M1 replaces this
        // with a role check. Never loosen it without a test for the new rule.
        Gate::define('access-admin-panel', fn (User $user): bool => false);
    }
}
