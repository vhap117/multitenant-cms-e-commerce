<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\Reactivation;

use Mockery;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Reactivation\MarkTenantActiveRecord;
use VHAP\Core\Tests\TestCase;

class MarkTenantActiveRecordTest extends TestCase
{
    public function test_it_marks_the_tenant_record_as_active()
    {
        // Arrange
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('update')
            ->once()
            ->with(['is_active' => true])
            ->andReturn(true);

        $pipe = new MarkTenantActiveRecord();

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
