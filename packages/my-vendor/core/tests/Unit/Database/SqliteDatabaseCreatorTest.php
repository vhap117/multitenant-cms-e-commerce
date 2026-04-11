<?php

namespace VHAP\Core\Tests\Unit\Database;

use Illuminate\Support\Facades\File;
use Spatie\Multitenancy\Models\Tenant;
use VHAP\Core\Database\SqliteDatabaseCreator;
use VHAP\Core\Tests\TestCase;

class SqliteDatabaseCreatorTest extends TestCase
{
    public function test_it_creates_an_empty_sqlite_file()
    {
        // Arrange
        $tenant = new Tenant();
        // Spatie Tenant model expects a database column
        $tenant->database = database_path('test_tenant_creation.sqlite');
        
        $creator = new SqliteDatabaseCreator();

        // Ensure clean state before test
        File::delete($tenant->database);
        $this->assertFalse(File::exists($tenant->database));

        // Act
        $creator->create($tenant);

        // Assert
        $this->assertTrue(File::exists($tenant->database));
        $this->assertEquals('', File::get($tenant->database));
        
        // Cleanup after test
        File::delete($tenant->database);
    }
}
