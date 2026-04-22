<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\Destruction;

use Mockery;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Destruction\DeleteTenantRecord;
use VHAP\Core\Tests\TestCase;

class DeleteTenantRecordTest extends TestCase
{
    public function test_it_deletes_the_tenant_record()
    {
        // Arrange
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $pipe = new DeleteTenantRecord();

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
