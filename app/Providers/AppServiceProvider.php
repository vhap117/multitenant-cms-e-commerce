<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\LandlordUser;
use VHAP\Core\Core;

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
    }
}
