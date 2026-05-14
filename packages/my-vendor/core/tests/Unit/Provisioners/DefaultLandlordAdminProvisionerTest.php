<?php

namespace VHAP\Core\Tests\Unit\Provisioners;

use VHAP\Core\Tests\TestCase;
use VHAP\Core\Provisioners\DefaultLandlordAdminProvisioner;
use VHAP\Core\Models\LandlordUser;
use Spatie\Permission\Models\Role;

class DefaultLandlordAdminProvisionerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();


        // 2. Run your package's custom landlord migrations (like landlord_users)
        $this->artisan('migrate', [
            '--database' => 'landlord', 
            '--path'     => __DIR__.'/../../../database/migrations/landlord',
            '--realpath' => true,
        ])->run();

        // 3. Dynamically migrate Spatie's tables from the vendor folder
        $this->migrateSpatiePermissions();
    }

    protected function migrateSpatiePermissions(): void
    {
        // 1. Tell Spatie's models to look at the landlord connection
        config(['permission.database_connection' => 'landlord']);

        // 2. Temporarily hijack Laravel's default connection so the 
        // manual Schema::create() commands run on the landlord database.
        $originalConnection = config('database.default');
        config(['database.default' => 'landlord']);

        $stubPaths = [
            __DIR__.'/../../../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub',
            __DIR__.'/../../../vendor/spatie/laravel-permission/database/migrations/add_teams_fields.php.stub',
        ];

        foreach ($stubPaths as $stubPath) {
            $migration = include $stubPath;
            $migration->up();
        }
        
        // 3. Restore the original connection
        config(['database.default' => $originalConnection]);
    }

    public function test_it_provisions_a_platform_admin_with_credentials_and_roles()
    {
        // Arrange
        // Seed the expected Spatie Role into the landlord DB
        Role::on('landlord')->create(['name' => 'Platform Admin', 'guard_name' => 'landlord']);

        $provisioner = new DefaultLandlordAdminProvisioner();

        // Act
        $provisioner->provision([
            'name' => 'Super Boss',
            'email' => 'boss@platform.com',
            'password' => 'secret123',
        ]);

        // Assert
        $this->assertDatabaseHas('landlord_users', [
            'name' => 'Super Boss',
            'email' => 'boss@platform.com',
        ], 'landlord'); 

        $user = LandlordUser::on('landlord')->where('email', 'boss@platform.com')->first();
        
        $this->assertTrue($user->hasRole('Platform Admin'));
    }
}
