<?php

namespace VHAP\Core\Tests\Unit\Database;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;
use VHAP\Core\Database\MysqlDatabaseCreator;
use VHAP\Core\Tests\TestCase;
use Mockery;

class MysqlDatabaseCreatorTest extends TestCase
{
    public function test_it_executes_create_database_statement_on_landlord_connection()
    {
        // Arrange
        $tenant = new Tenant();
        $tenant->database = 'test_tenant_db';
        
        $creator = new MysqlDatabaseCreator();

        // We use Mockery on the DB facade to intercept the SQL call
        // This validates the logic WITHOUT requiring a live MySQL server during tests
        DB::shouldReceive('connection')
            ->once()
            ->with('landlord')
            ->andReturn($connectionMock = Mockery::mock());
            
        $connectionMock->shouldReceive('statement')
            ->once()
            ->with("CREATE DATABASE IF NOT EXISTS `test_tenant_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Act
        $creator->create($tenant);
        
        // Assert: Mockery automatically asserts that these mocked methods were called exactly once.
    }
}
