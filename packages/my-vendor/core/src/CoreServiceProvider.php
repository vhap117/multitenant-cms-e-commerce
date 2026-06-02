<?php

namespace VHAP\Core;

use Illuminate\Support\ServiceProvider;
use VHAP\Core\Contracts\TenantDatabaseCreator;
use VHAP\Core\Contracts\TenantAdminProvisioner;
use VHAP\Core\Provisioners\DefaultTenantAdminProvisioner;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any package-specific services into the container.
     */
    public function register(): void
    {
        // Add auth guard and provider configuration dynamically
        config([
            'auth.guards.landlord' => array_merge([
                'driver' => 'session',
                'provider' => 'landlord_users',
            ], config('auth.guards.landlord', [])),
            
            'auth.providers.landlord_users' => array_merge([
                'driver' => 'eloquent',
                'model' => \VHAP\Core\Models\LandlordUser::class,
            ], config('auth.providers.landlord_users', [])),
        ]);

        // Inject Spatie Multitenancy configuration dynamically
        config([
            'multitenancy.tenant_database_connection_name' => 'tenant',
            'multitenancy.landlord_database_connection_name' => 'landlord',
            'multitenancy.switch_tenant_tasks' => [
                \Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
            ],
        ]);

        // Dynamically inject Landlord and Tenant database connections
        // using the host's default connection as a base, but allowing
        // the host app's database.php to completely override these if defined.
        $defaultConnection = config('database.default');
        $defaultConfig = config("database.connections.{$defaultConnection}", []);

        config([
            'database.connections.landlord' => array_merge(
                $defaultConfig,
                ['database' => env('DB_LANDLORD_DATABASE', 'monorepo')],
                config('database.connections.landlord', [])
            ),
            'database.connections.tenant' => array_merge(
                $defaultConfig,
                ['database' => null], // Spatie will dynamically override this
                config('database.connections.tenant', [])
            ),
        ]);

        // Bind the Database Creator strategy
        $this->app->bind(TenantDatabaseCreator::class, function ($app) {
            $driver = config('database.connections.tenant.driver');

            if ($driver === 'sqlite') {
                return new \VHAP\Core\Database\SqliteTenantDatabaseCreator();
            }

            return new \VHAP\Core\Database\MysqlTenantDatabaseCreator();
        });

        // Bind the Landlord Database Creator strategy
        $this->app->bind(\VHAP\Core\Contracts\LandlordDatabaseCreator::class, function ($app) {
            $driver = config('database.connections.landlord.driver', config('database.default'));

            if ($driver === 'sqlite') {
                return new \VHAP\Core\Database\SqliteLandlordDatabaseCreator();
            }

            return new \VHAP\Core\Database\MysqlLandlordDatabaseCreator();
        });

        // Bind the Admin Provisioner Contract to its default implementation
        $this->app->bind(TenantAdminProvisioner::class, DefaultTenantAdminProvisioner::class);

        // Bind the Landlord Admin Provisioner Contract to its default implementation
        $this->app->bind(\VHAP\Core\Contracts\LandlordAdminProvisioner::class, \VHAP\Core\Provisioners\DefaultLandlordAdminProvisioner::class);

        // Bind the Billing Provider interface to the Cashier Adapter implementation
        $this->app->bind(
            \VHAP\Core\Contracts\BillingProvider::class,
            \VHAP\Core\Services\Billing\CashierAdapter::class
        );
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \VHAP\Core\Events\TenantProvisioned::class,
            \VHAP\Core\Listeners\ProvisionTenantAdminListener::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \Spatie\Multitenancy\Events\MadeTenantCurrentEvent::class,
            \VHAP\Core\Listeners\ConfigureTenantUrlListener::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \Laravel\Cashier\Events\WebhookReceived::class,
            \VHAP\Core\Listeners\StripeWebhookListener::class,
        );

        // 1. Load Landlord Migrations
        // These run automatically when you run `php artisan migrate` in the main app.
        // This is where your 'tenants' table and 'domains' table will live.
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations/landlord');

        // 2. Publish Assets to the Host Application
        if ($this->app->runningInConsole()) {
            
            $this->commands([
                \VHAP\Core\Console\Commands\InstallLandlordCommand::class,
            ]);

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