<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes;

use Mockery;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Provision\CreateTenantDatabase;
use VHAP\Core\Contracts\TenantDatabaseCreator;
use VHAP\Core\Tests\TestCase;

class CreateTenantDatabaseTest extends TestCase
{
    public function test_it_creates_database_and_calls_next_pipe()
    {
        // Arrange
        $tenant = new Tenant();
        $tenant->database = 'new_tenant_db';

        // 1. Mock the Creator interface so we don't do real database operations
        $mockCreator = Mockery::mock(TenantDatabaseCreator::class);
        
        // Assert the pipe delegates the DB creation to the injected strategy
        $mockCreator->shouldReceive('create')
            ->once()
            ->with($tenant);

        $pipe = new CreateTenantDatabase($mockCreator);

        // 2. Setup the $next closure to ensure the pipeline continues
        $nextWasCalled = false;
        $nextPipe = function ($passedTenant) use (&$nextWasCalled, $tenant) {
            $nextWasCalled = true;
            
            // Assert the pipe passed the exact same tenant down the line
            $this->assertSame($tenant, $passedTenant);
            
            return 'pipeline_continued';
        };

        // Act
        $result = $pipe->handle($tenant, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled, 'The next closure in the pipeline was never called.');
        $this->assertEquals('pipeline_continued', $result, 'The pipe failed to return the result of the next closure.');
    }
}
