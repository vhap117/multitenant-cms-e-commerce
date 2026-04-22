<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\Destruction;

use Illuminate\Support\Facades\Storage;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Destruction\DeleteTenantStorageDirectory;
use VHAP\Core\Tests\TestCase;

class DeleteTenantStorageDirectoryTest extends TestCase
{
    public function test_it_deletes_tenant_storage_directories_if_they_exist()
    {
        // Arrange
        $tenant = new Tenant();
        $tenant->id = 123;
        $tenantDirectory = 'tenants/' . $tenant->id;

        // Fake the disks so we don't manipulate real files
        Storage::fake('local');
        Storage::fake('public');

        // Create dummy directories/files to ensure they get deleted
        Storage::disk('local')->makeDirectory($tenantDirectory);
        Storage::disk('public')->makeDirectory($tenantDirectory);

        $this->assertTrue(Storage::disk('local')->exists($tenantDirectory));
        $this->assertTrue(Storage::disk('public')->exists($tenantDirectory));

        $pipe = new DeleteTenantStorageDirectory();

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

        // Verify the directories are gone
        $this->assertFalse(Storage::disk('local')->exists($tenantDirectory));
        $this->assertFalse(Storage::disk('public')->exists($tenantDirectory));
    }
}
