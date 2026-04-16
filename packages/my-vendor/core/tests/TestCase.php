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
        // 1. Setup the Landlord connection (in-memory)
        $app['config']->set('database.connections.landlord', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // 2. Setup the empty Tenant connection template
        $app['config']->set('database.connections.tenant', [
            'driver'   => 'sqlite',
            'database' => null, // This gets populated dynamically
            'prefix'   => '',
        ]);

        $app['config']->set('database.default', 'landlord');

        // 3. Configure Spatie Multitenancy
        $app['config']->set('multitenancy.tenant_database_connection_name', 'tenant');
        $app['config']->set('multitenancy.landlord_database_connection_name', 'landlord');

        // Tell Spatie to use your custom package models
        $app['config']->set('permission.models.role', \VHAP\Core\Models\Role::class);
        $app['config']->set('permission.models.permission', \VHAP\Core\Models\Permission::class);
        
        // Ensure Spatie's overall DB connection is explicitly set
        $app['config']->set('permission.database_connection', 'tenant');
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