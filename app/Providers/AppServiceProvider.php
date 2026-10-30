<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Observers\OrderObserver;
use App\Observers\ReturnRequestObserver;
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
        Gate::before(function ($user, string $ability): ?bool {
            if ($user instanceof User && $user->hasRole('Admin')) {
                return true;
            }

            return null;
        });

        Order::observe(OrderObserver::class);
        ReturnRequest::observe(ReturnRequestObserver::class);
    }
}
