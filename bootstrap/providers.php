<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\PaymentServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    PaymentServiceProvider::class,
];
