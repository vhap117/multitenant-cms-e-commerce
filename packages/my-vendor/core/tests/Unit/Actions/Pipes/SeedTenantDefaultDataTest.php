<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes;

use Mockery;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Models\Tenant;
use Spatie\Permission\Models\Role;
use VHAP\Core\Actions\Pipes\Provision\SeedTenantDefaultData;

class SeedTenantDefaultDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config(['database.connections.tenant' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);

        $this->migrateSpatiePermissions();
    }

    protected function migrateSpatiePermissions(): void
    {
        config(['permission.database_connection' => 'tenant']);
        
        $originalConnection = config('database.default');
        config(['database.default' => 'tenant']);

        $stubPaths = [
            __DIR__.'/../../../../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub',
            __DIR__.'/../../../../vendor/spatie/laravel-permission/database/migrations/add_teams_fields.php.stub',
        ];

        foreach ($stubPaths as $stubPath) {
            $migration = include $stubPath;
            $migration->up();
        }
        
        config(['database.default' => $originalConnection]);
    }

    public function test_it_creates_super_admin_role_and_calls_next_pipe()
    {
        // Arrange
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('makeCurrent')->once();

        $pipe = new SeedTenantDefaultData();

        $nextWasCalled = false;
        $nextPipe = function ($passedTenant) use (&$nextWasCalled, $tenant) {
            $nextWasCalled = true;
            $this->assertSame($tenant, $passedTenant);
            return 'pipeline_continued';
        };

        // Act
        $result = $pipe->handle($tenant, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled, 'The next closure in the pipeline was never called.');
        $this->assertEquals('pipeline_continued', $result, 'The pipe failed to return the result of the next closure.');
        
        // Assert the role was created in the tenant database
        $this->assertDatabaseHas('roles', [
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ], 'tenant');
    }
}
