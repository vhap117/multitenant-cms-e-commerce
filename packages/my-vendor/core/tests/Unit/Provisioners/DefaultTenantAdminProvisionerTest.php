<?php

namespace VHAP\Core\Tests\Unit\Provisioners;

use VHAP\Core\Tests\TestCase;
use VHAP\Core\Provisioners\DefaultTenantAdminProvisioner;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Models\User;
use Spatie\Permission\Models\Role;

class DefaultTenantAdminProvisionerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Configure the active tenant connection as an in-memory SQLite DB
        config(['database.connections.tenant' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);

        // 2. Run your package's custom tenant migrations (like the users table)
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

    /** @test */
    public function it_provisions_a_super_admin_with_generated_credentials()
    {
        // 1. Arrange
        $tenant = Tenant::factory()->create([
            'name' => 'Acme Corp',
            'domain' => 'acme.myapp.com'
        ]); 
        $tenant->makeCurrent();

        // Since the Spatie tables now exist in SQLite memory, we can create the role
        Role::on('tenant')->create(['name' => 'Super Admin', 'guard_name' => 'web']);

        $provisioner = new DefaultTenantAdminProvisioner();

        // 2. Act
        $provisioner->provision($tenant);

        // 3. Assert
        $this->assertDatabaseHas('users', [
            'name' => 'System Admin',
            'email' => 'admin@acme.myapp.com',
        ], 'tenant'); 

        $user = User::on('tenant')->where('email', 'admin@acme.myapp.com')->first();
        
        $this->assertTrue($user->hasRole('Super Admin'));
        
        // Cleanup
        $tenant->forgetCurrent();
    }
}