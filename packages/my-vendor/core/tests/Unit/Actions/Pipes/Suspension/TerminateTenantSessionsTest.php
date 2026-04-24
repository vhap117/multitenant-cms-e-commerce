<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\Suspension;

use Mockery;
use Illuminate\Support\Facades\DB;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Suspension\TerminateTenantSessions;
use VHAP\Core\Tests\TestCase;

class TerminateTenantSessionsTest extends TestCase
{
    public function test_it_truncates_sessions_on_tenant_database()
    {
        // Arrange
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('makeCurrent')->once();
        $tenant->shouldReceive('forgetCurrent')->once();

        // Mock DB connection, table, and delete calls
        $builderMock = Mockery::mock();
        $builderMock->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $connectionMock = Mockery::mock();
        $connectionMock->shouldReceive('table')
            ->with('sessions')
            ->once()
            ->andReturn($builderMock);

        DB::shouldReceive('connection')
            ->with('tenant')
            ->once()
            ->andReturn($connectionMock);

        $pipe = new TerminateTenantSessions();

        $nextWasCalled = false;
        $nextPipe = function ($passedTenant) use (&$nextWasCalled, $tenant) {
            $nextWasCalled = true;
            $this->assertSame($tenant, $passedTenant);
            return 'success';
        };

        // Act
        $result = $pipe->handle($tenant, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled, 'The pipe did not call the $next closure.');
        $this->assertEquals('success', $result);
    }
}
