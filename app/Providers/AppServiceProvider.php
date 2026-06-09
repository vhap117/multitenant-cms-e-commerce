<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\LandlordUser;
use VHAP\Core\Core;
use Illuminate\Support\Facades\Event;

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
        Core::useLandlordUserModel(LandlordUser::class);

        Event::listen(
            \VHAP\Core\Events\TenantProvisioned::class,
            \App\Listeners\SendStoreReadyNotification::class,
        );
    }
}
