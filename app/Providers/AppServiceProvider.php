<?php

namespace App\Providers;

use App\Filament\Support\TranslateFilamentLabels;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Observers\OrderObserver;
use App\Observers\ReturnRequestObserver;
use App\Policies\ActivityPolicy;
use App\Policies\RolePolicy;
use App\Translation\SafeTranslator;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('translator', function (Translator $translator): TranslatorContract {
            $safe = new SafeTranslator($translator->getLoader(), $translator->getLocale());
            $safe->setFallback($translator->getFallback());

            return $safe;
        });
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

        // Spatie's Activity model lives outside App\Models, so auto-discovery cannot map it.
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Order::observe(OrderObserver::class);
        ReturnRequest::observe(ReturnRequestObserver::class);

        TranslateFilamentLabels::register();
    }
}
