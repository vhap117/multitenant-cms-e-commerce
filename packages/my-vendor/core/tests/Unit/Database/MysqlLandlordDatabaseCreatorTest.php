<?php

namespace VHAP\Core\Tests\Unit\Database;

use Illuminate\Support\Facades\DB;
use VHAP\Core\Database\MysqlLandlordDatabaseCreator;
use VHAP\Core\Tests\TestCase;

class MysqlLandlordDatabaseCreatorTest extends TestCase
{
    public function test_it_executes_create_database_statement()
    {
        // Arrange
        $databaseName = 'test_landlord_db';
        $creator = new MysqlLandlordDatabaseCreator();

        // We use Mockery on the DB facade to intercept the SQL call
        // This validates the logic WITHOUT requiring a live MySQL server during tests
        DB::shouldReceive('statement')
            ->once()
            ->with("CREATE DATABASE IF NOT EXISTS `test_landlord_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Act
        $creator->create($databaseName);
        
        // Assert: Mockery automatically asserts that these mocked methods were called exactly once.
    }
}
