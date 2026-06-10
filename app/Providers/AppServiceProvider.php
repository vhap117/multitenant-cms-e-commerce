<?php

namespace App\Providers;

use VHAP\Core\Core;
use Illuminate\Support\ServiceProvider;
use App\Models\LandlordUser;

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
