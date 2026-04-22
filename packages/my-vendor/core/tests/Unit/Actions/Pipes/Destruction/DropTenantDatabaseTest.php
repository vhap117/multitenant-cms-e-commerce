<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\Destruction;

use Mockery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Destruction\DropTenantDatabase;
use VHAP\Core\Tests\TestCase;

class DropTenantDatabaseTest extends TestCase
{
    public function test_it_drops_mysql_database_properly()
    {
        // Arrange
        $tenant = new Tenant();
        $tenant->database = 'tenant_123';

        Config::set('database.connections.tenant.driver', 'mysql');

        // Mock DB purge
        DB::shouldReceive('purge')
            ->once()
            ->with('tenant');

        // Mock Landlord connection and drop statement
        $connectionMock = Mockery::mock();
        $connectionMock->shouldReceive('statement')
            ->once()
            ->with("DROP DATABASE IF EXISTS `tenant_123`");

        DB::shouldReceive('connection')
            ->with('landlord')
            ->once()
            ->andReturn($connectionMock);

        $pipe = new DropTenantDatabase();

        $nextWasCalled = false;
        $nextPipe = function ($passedTenant) use (&$nextWasCalled, $tenant) {
            $nextWasCalled = true;
            $this->assertSame($tenant, $passedTenant);
            return 'success';
        };

        // Act
        $result = $pipe->handle($tenant, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled);
        $this->assertEquals('success', $result);
    }

    public function test_it_deletes_sqlite_database_file_properly()
    {
        // Arrange
        $tenant = new Tenant();
        $tenant->database = '/fake/path/to/database.sqlite';

        Config::set('database.connections.tenant.driver', 'sqlite');

        // Mock DB purge
        DB::shouldReceive('purge')
            ->once()
            ->with('tenant');

        // Mock File facade
        File::shouldReceive('exists')
            ->once()
            ->with('/fake/path/to/database.sqlite')
            ->andReturn(true);

        File::shouldReceive('delete')
            ->once()
            ->with('/fake/path/to/database.sqlite')
            ->andReturn(true);

        $pipe = new DropTenantDatabase();

        $nextWasCalled = false;
        $nextPipe = function ($passedTenant) use (&$nextWasCalled, $tenant) {
            $nextWasCalled = true;
            $this->assertSame($tenant, $passedTenant);
            return 'success';
        };

        // Act
        $result = $pipe->handle($tenant, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled);
        $this->assertEquals('success', $result);
    }
}
