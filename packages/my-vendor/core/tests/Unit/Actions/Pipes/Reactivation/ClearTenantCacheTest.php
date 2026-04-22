<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\Reactivation;

use Mockery;
use Spatie\Permission\PermissionRegistrar;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Reactivation\ClearTenantCache;
use VHAP\Core\Tests\TestCase;

class ClearTenantCacheTest extends TestCase
{
    public function test_it_clears_the_tenant_permission_cache()
    {
        // Arrange
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('makeCurrent')->once();
        $tenant->shouldReceive('forgetCurrent')->once();

        // Mock the Spatie PermissionRegistrar
        $registrarMock = Mockery::mock(PermissionRegistrar::class);
        $registrarMock->shouldReceive('forgetCachedPermissions')
            ->once();
        
        // Bind our mock into the service container
        $this->app->instance(PermissionRegistrar::class, $registrarMock);

        $pipe = new ClearTenantCache();

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
