<?php

namespace VHAP\Core;

use Illuminate\Support\ServiceProvider;
use VHAP\Core\Contracts\TenantDatabaseCreator;


class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any package-specific services into the container.
     */
    public function register(): void
    {
        $this->app->bind(TenantDatabaseCreator::class, function ($app) {
            $driver = config('database.connections.tenant.driver');

            if ($driver === 'sqlite') {
                return new \VHAP\Core\Database\SqliteDatabaseCreator();
            }

            return new \VHAP\Core\Database\MysqlDatabaseCreator();
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // 1. Load Landlord Migrations
        // These run automatically when you run `php artisan migrate` in the main app.
        // This is where your 'tenants' table and 'domains' table will live.
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations/landlord');

        // 2. Publish Assets to the Host Application
        if ($this->app->runningInConsole()) {
            
            // Publish configurations
            // $this->publishes([
            //     __DIR__ . '/../config/core.php' => config_path('vendor-core.php'),
            // ], 'core-config');

            // Publish Tenant Migrations
            // For a multi-database setup, tenant migrations usually need to be published 
            // into the host application's database folder so the provisioning logic 
            // can run them easily on the newly created tenant databases.
            $this->publishes([
                __DIR__ . '/../database/migrations/tenant' => database_path('migrations/tenant'),
            ], 'core-tenant-migrations');
        }
    }
}