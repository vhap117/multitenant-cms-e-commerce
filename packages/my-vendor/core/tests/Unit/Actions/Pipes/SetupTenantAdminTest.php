<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes;

use Mockery;
use Spatie\Multitenancy\Models\Tenant;
use VHAP\Core\Actions\Pipes\SetupTenantAdmin;
use VHAP\Core\Contracts\TenantAdminProvisioner;
use VHAP\Core\Tests\TestCase;

class SetupTenantAdminTest extends TestCase
{
    public function test_it_switches_connection_and_delegates_admin_provisioning()
    {
        // Arrange
        // We use Mockery::mock() but make it partial so it behaves 
        // like a real model, but allows us to assert makeCurrent() was fired
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('makeCurrent')->once();

        // Mock the Provisioner contract
        $mockProvisioner = Mockery::mock(TenantAdminProvisioner::class);
        $mockProvisioner->shouldReceive('provision')
            ->once()
            ->with($tenant);

        $pipe = new SetupTenantAdmin($mockProvisioner);

        $nextWasCalled = false;
        $nextPipe = function ($passedTenant) use (&$nextWasCalled, $tenant) {
            $nextWasCalled = true;
            $this->assertSame($tenant, $passedTenant);
            return 'perfect';
        };

        // Act
        $result = $pipe->handle($tenant, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled, 'The pipe broke the chain by not calling the closure.');
        $this->assertEquals('perfect', $result);
    }
}
