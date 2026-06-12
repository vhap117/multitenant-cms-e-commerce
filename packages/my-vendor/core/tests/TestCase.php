<?php

namespace VHAP\Core\Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\Multitenancy\MultitenancyServiceProvider;
use VHAP\Core\CoreServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clean up any old test databases
        $this->cleanUpTenantDatabases();

        // Run landlord migrations (creates the 'tenants' table)
        $this->artisan('migrate', ['--database' => 'landlord', '--path' => __DIR__.'/../database/migrations/landlord', '--realpath' => true])->run();
    }

    protected function getPackageProviders($app)
    {
        return [
            CoreServiceProvider::class,
            MultitenancyServiceProvider::class,
            \Spatie\Permission\PermissionServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Require an APP_KEY for password reset tokens and other cryptographic features during tests
        $app['config']->set('app.key', 'base64:3u1j4Jd9r8+t1x/sXbJv0L6Z4oNq7P3B+xLq7fM2R0E=');

        // Prevent Spatie from triggering database queries for cache clearing during tests
        $app['config']->set('cache.default', 'array');
        // 1. Setup the Landlord connection (in-memory)
        $app['config']->set('database.connections.landlord', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('database.connections.tenant', [
            'driver'   => 'sqlite',
            'database' => ':memory:', // Temporarily set to memory instead of null to catch stack traces
            'prefix'   => '',
        ]);

        $app['config']->set('database.default', 'landlord');

        // 3. Configure Spatie Multitenancy
        $app['config']->set('multitenancy.tenant_database_connection_name', 'tenant');
        $app['config']->set('multitenancy.landlord_database_connection_name', 'landlord');

        // Required to dynamically switch the 'tenant' connection based on $tenant->database field
        $app['config']->set('multitenancy.switch_tenant_tasks', [
            \Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
        ]);

        // Tell Spatie to use your custom package models
        $app['config']->set('permission.models.role', \VHAP\Core\Models\Role::class);
        $app['config']->set('permission.models.permission', \VHAP\Core\Models\Permission::class);
        
        // Ensure Spatie's overall DB connection is explicitly set
        $app['config']->set('permission.database_connection', 'tenant');

        // Temporarily redefine the migrations path to point directly 
        // to your raw package directory during the test execution!
        $app['config']->set(
            'core.tenant_migrations_path', 
            realpath(__DIR__.'/../database/migrations/tenant')
        );

    }

    protected function cleanUpTenantDatabases()
    {
        // Delete any leftover SQLite files from previous tests
        $files = File::glob(database_path('*.sqlite'));
        foreach ($files as $file) {
            File::delete($file);
        }
    }
}