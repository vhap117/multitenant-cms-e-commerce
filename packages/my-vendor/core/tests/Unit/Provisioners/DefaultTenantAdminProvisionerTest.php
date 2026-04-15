<?php

namespace VHAP\Core\Tests\Unit\Provisioners;

use VHAP\Core\Tests\TestCase;
use VHAP\Core\Provisioners\DefaultTenantAdminProvisioner;
use VHAP\Core\Models\Tenant;
use Illuminate\Foundation\Auth\User;
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

        // 2. Run the tenant migrations (users, roles, permissions tables)
        // Adjust the path below if your tenant migrations are stored elsewhere
        $this->artisan('migrate', [
            '--database' => 'tenant', 
            '--path' => __DIR__.'/../../../../database/migrations/tenant'
        ])->run();
    }

    /** @test */
    public function it_provisions_a_super_admin_with_generated_credentials()
    {
        // 1. Arrange: Create a fake tenant in the landlord DB and set it as active
        $tenant = Tenant::factory()->create([
            'name' => 'Acme Corp',
            'domain' => 'acme.myapp.com'
        ]); 
        $tenant->makeCurrent(); // Switches Spatie's active connection to 'tenant'

        // Pretend the SeedDefaultRoles pipe has already run
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

        $provisioner = new DefaultTenantAdminProvisioner();

        // 2. Act: Run the provisioner
        $provisioner->provision($tenant);

        // 3. Assert: Check the TENANT database explicitly
        $this->assertDatabaseHas('users', [
            'name' => 'System Admin',
            'email' => 'admin@acme.myapp.com',
        ], 'tenant'); 

        // 4. Assert: The user got the correct role and a generated password
        $user = User::on('tenant')->where('email', 'admin@acme.myapp.com')->first();
        
        $this->assertNotNull($user, 'The user was not created in the tenant database.');
        $this->assertTrue($user->hasRole('Super Admin'), 'The user was not assigned the Super Admin role.');
        $this->assertNotEmpty($user->password, 'A password was not generated for the user.');

        // Cleanup
        $tenant->forgetCurrent();
    }
}