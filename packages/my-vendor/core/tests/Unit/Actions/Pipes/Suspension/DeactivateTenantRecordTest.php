<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\Suspension;

use Mockery;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Suspension\DeactivateTenantRecord;
use VHAP\Core\Tests\TestCase;

class DeactivateTenantRecordTest extends TestCase
{
    public function test_it_deactivates_the_tenant_record()
    {
        // Arrange
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('update')
            ->once()
            ->with(['is_active' => false])
            ->andReturn(true);

        $pipe = new DeactivateTenantRecord();

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
