<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\Domain;

use Mockery;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Domain\UpdateTenantRecord;
use VHAP\Core\Tests\TestCase;

class UpdateTenantRecordTest extends TestCase
{
    public function test_it_updates_the_tenant_domain()
    {
        // Arrange
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('update')
            ->once()
            ->with(['domain' => 'new-store.example.com'])
            ->andReturn(true);

        $payload = (object)[
            'tenant' => $tenant,
            'newDomain' => 'new-store.example.com',
        ];

        $pipe = new UpdateTenantRecord();

        $nextWasCalled = false;
        $nextPipe = function ($passedPayload) use (&$nextWasCalled, $payload) {
            $nextWasCalled = true;
            $this->assertSame($payload, $passedPayload);
            return 'success';
        };

        // Act
        $result = $pipe->handle($payload, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled, 'The pipe did not call the $next closure.');
        $this->assertEquals('success', $result);
    }
}
