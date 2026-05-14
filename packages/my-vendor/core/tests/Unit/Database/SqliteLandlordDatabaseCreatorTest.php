<?php

namespace VHAP\Core\Tests\Unit\Database;

use Illuminate\Support\Facades\File;
use VHAP\Core\Database\SqliteLandlordDatabaseCreator;
use VHAP\Core\Tests\TestCase;

class SqliteLandlordDatabaseCreatorTest extends TestCase
{
    public function test_it_creates_an_empty_sqlite_file()
    {
        // Arrange
        $databaseName = database_path('test_landlord_creation.sqlite');
        $creator = new SqliteLandlordDatabaseCreator();

        // Ensure clean state before test
        File::delete($databaseName);
        $this->assertFalse(File::exists($databaseName));

        // Act
        $creator->create($databaseName);

        // Assert
        $this->assertTrue(File::exists($databaseName));
        $this->assertEquals('', File::get($databaseName));
        
        // Cleanup after test
        File::delete($databaseName);
    }
}
