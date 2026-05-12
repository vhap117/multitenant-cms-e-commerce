<?php

namespace VHAP\Core\Tests\Unit\Listeners;

use Mockery;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Listeners\ProvisionTenantAdminListener;
use VHAP\Core\Events\TenantProvisioned;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Models\User;
use VHAP\Core\Contracts\TenantAdminProvisioner;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;

class ProvisionTenantAdminListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Let's actually use an in-memory database to test the Eloquent query naturally.
        config(['database.connections.tenant' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);

        $this->artisan('migrate', [
            '--database' => 'tenant', 
            '--path'     => __DIR__.'/../../../database/migrations/tenant',
            '--realpath' => true,
        ])->run();

        // 3. Dynamically migrate Spatie's tables from the vendor folder
        $this->migrateSpatiePermissions();
    }

    /**
     * Simulates publishing and migrating Spatie's tables for the testing environment.
     */
    protected function migrateSpatiePermissions(): void
    {
        // Disable the physical DB connection purger so our in-memory SQLite tables survive makeCurrent()
        config(['multitenancy.switch_tenant_tasks' => []]);

        // 1. Tell Spatie's models to look at the tenant connection
        config(['permission.database_connection' => 'tenant']);

        // 2. Temporarily hijack Laravel's default connection so the 
        // manual Schema::create() commands run on the tenant database.
        $originalConnection = config('database.default');
        config(['database.default' => 'tenant']);

        $stubPaths = [
            __DIR__.'/../../../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub',
            __DIR__.'/../../../vendor/spatie/laravel-permission/database/migrations/add_teams_fields.php.stub',
        ];

        foreach ($stubPaths as $stubPath) {
            $migration = include $stubPath;
            $migration->up();
        }
        // 4. Restore the original connection so the rest of the application 
        // doesn't accidentally save landlord data into the tenant DB.
        config(['database.default' => $originalConnection]);
    }

    public function test_it_switches_connection_provisions_admin_and_fires_registered_event()
    {
        // Arrange
        Event::fake();
        
        // We use Mockery::mock() but make it partial so it behaves 
        // like a real model, but allows us to assert makeCurrent() was fired
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('makeCurrent')->once();
        $tenant->shouldReceive('forgetCurrent')->once();

        $adminData = [
            'name' => 'System Admin',
            'email' => 'admin@test.com',
            'password' => 'secret',
        ];

        // Mock the Provisioner contract
        $mockProvisioner = Mockery::mock(TenantAdminProvisioner::class);
        $mockProvisioner->shouldReceive('provision')
            ->once()
            ->with($adminData)
            ->andReturnUsing(function () {
                User::create([
                    'name' => 'System Admin',
                    'email' => 'admin@test.com',
                    'password' => 'secret',
                ]);
            });

        $event = new TenantProvisioned($tenant, $adminData);
        $listener = new ProvisionTenantAdminListener($mockProvisioner);

        // Act
        $listener->handle($event);

        // Assert
        Event::assertDispatched(Registered::class, function ($event) {
            return $event->user->email === 'admin@test.com';
        });
    }
}
