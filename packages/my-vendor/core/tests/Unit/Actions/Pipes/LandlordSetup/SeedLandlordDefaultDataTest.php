<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\LandlordSetup;

use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\Pipes\LandlordSetup\SeedLandlordDefaultData;

class SeedLandlordDefaultDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateSpatiePermissions();
    }

    protected function migrateSpatiePermissions(): void
    {
        config(['permission.database_connection' => 'landlord']);
        
        $originalConnection = config('database.default');
        config(['database.default' => 'landlord']);

        $stubPaths = [
            __DIR__.'/../../../../../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub',
            __DIR__.'/../../../../../vendor/spatie/laravel-permission/database/migrations/add_teams_fields.php.stub',
        ];

        foreach ($stubPaths as $stubPath) {
            $migration = include $stubPath;
            $migration->up();
        }
        
        config(['database.default' => $originalConnection]);
    }

    public function test_it_creates_platform_admin_role_and_calls_next_pipe()
    {
        // Arrange
        $payload = ['database' => 'vhap_landlord'];
        $pipe = new SeedLandlordDefaultData();

        $nextWasCalled = false;
        $nextPipe = function ($passedPayload) use (&$nextWasCalled, $payload) {
            $nextWasCalled = true;
            $this->assertSame($payload, $passedPayload);
            return 'pipeline_continued';
        };

        // Act
        $result = $pipe->handle($payload, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled, 'The next closure in the pipeline was never called.');
        $this->assertEquals('pipeline_continued', $result);
        
        // Assert the role was created in the landlord database
        $this->assertDatabaseHas('roles', [
            'name' => 'Platform Admin',
            'guard_name' => 'landlord',
        ], 'landlord');
    }
}
