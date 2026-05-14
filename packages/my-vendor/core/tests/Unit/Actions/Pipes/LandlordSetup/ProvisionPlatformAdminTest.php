<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\LandlordSetup;

use Mockery;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Contracts\LandlordAdminProvisioner;
use VHAP\Core\Actions\Pipes\LandlordSetup\ProvisionPlatformAdmin;

class ProvisionPlatformAdminTest extends TestCase
{
    public function test_it_delegates_to_provisioner_and_calls_next_pipe()
    {
        // Arrange
        $payload = [
            'name' => 'System Admin',
            'email' => 'admin@platform.com',
            'password' => 'secret',
        ];
        
        $mockProvisioner = Mockery::mock(LandlordAdminProvisioner::class);
        $mockProvisioner->shouldReceive('provision')
            ->once()
            ->with($payload);

        $pipe = new ProvisionPlatformAdmin($mockProvisioner);

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
