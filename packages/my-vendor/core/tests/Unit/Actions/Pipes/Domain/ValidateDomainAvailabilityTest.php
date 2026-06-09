<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\Domain;

use Exception;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Domain\ValidateDomainAvailability;
use VHAP\Core\Tests\TestCase;

class ValidateDomainAvailabilityTest extends TestCase
{
    public function test_it_validates_successfully_if_domain_is_available()
    {
        // Arrange: Create a real test tenant in the in-memory SQLite database
        $tenant = Tenant::forceCreate([
            'name' => 'Store A',
            'email' => 'admin@store-a.example.com',
            'plan' => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain' => 'store-a.example.com',
            'database' => 'tenant_1',
            'is_active' => true,
            'provisioning_status' => 'active',
        ]);

        $payload = (object)[
            'tenant' => $tenant,
            'newDomain' => 'available-store.example.com',
        ];

        $pipe = new ValidateDomainAvailability();
        
        $nextWasCalled = false;
        $nextPipe = function ($passedPayload) use (&$nextWasCalled, $payload) {
            $nextWasCalled = true;
            $this->assertSame($payload, $passedPayload);
            return 'success';
        };

        // Act
        $result = $pipe->handle($payload, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled);
        $this->assertEquals('success', $result);
    }

    public function test_it_allows_tenant_to_keep_their_current_domain()
    {
        // Arrange
        $tenant = Tenant::forceCreate([
            'name' => 'Store A',
            'email' => 'admin@store-a.example.com',
            'plan' => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain' => 'store-a.example.com',
            'database' => 'tenant_1',
            'is_active' => true,
            'provisioning_status' => 'active',
        ]);

        $payload = (object)[
            'tenant' => $tenant,
            'newDomain' => 'store-a.example.com', // Using their identical domain
        ];

        $pipe = new ValidateDomainAvailability();
        
        $nextWasCalled = false;
        $nextPipe = function () use (&$nextWasCalled) {
            $nextWasCalled = true;
            return 'success';
        };

        // Act
        $result = $pipe->handle($payload, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled);
    }

    public function test_it_throws_exception_if_domain_is_taken_by_another_tenant()
    {
        // Arrange: Create two active tenants
        $tenantA = Tenant::forceCreate([
            'name' => 'Store A',
            'email' => 'admin@taken-domain.example.com',
            'plan' => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain' => 'taken-domain.example.com',
            'database' => 'tenant_1',
            'is_active' => true,
            'provisioning_status' => 'active',
        ]);
        
        $tenantB = Tenant::forceCreate([
            'name' => 'Store B',
            'email' => 'admin@store-b.example.com',
            'plan' => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain' => 'store-b.example.com',
            'database' => 'tenant_2',
            'is_active' => true,
            'provisioning_status' => 'active',
        ]);

        // Attempting to change Tenant B's domain to Tenant A's domain
        $payload = (object)[
            'tenant' => $tenantB,
            'newDomain' => 'taken-domain.example.com',
        ];

        $pipe = new ValidateDomainAvailability();
        
        // Assert Exception Setup
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The domain 'taken-domain.example.com' is already registered to another store.");

        // Act
        $pipe->handle($payload, function () {});
    }

    public function test_it_allows_domain_if_taken_by_failed_tenant()
    {
        // Arrange: Create a failed tenant with the desired domain
        Tenant::forceCreate([
            'name' => 'Failed Store',
            'email' => 'failed@example.com',
            'plan' => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain' => 'desired-domain.example.com',
            'database' => 'tenant_1',
            'is_active' => false,
            'provisioning_status' => 'failed',
        ]);

        $tenantB = Tenant::forceCreate([
            'name' => 'Store B',
            'email' => 'admin@store-b.example.com',
            'plan' => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain' => 'store-b.example.com',
            'database' => 'tenant_2',
            'is_active' => true,
            'provisioning_status' => 'active',
        ]);

        // Attempting to change Tenant B's domain to the failed tenant's domain
        $payload = (object)[
            'tenant' => $tenantB,
            'newDomain' => 'desired-domain.example.com',
        ];

        $pipe = new ValidateDomainAvailability();
        
        $nextWasCalled = false;
        $nextPipe = function () use (&$nextWasCalled) {
            $nextWasCalled = true;
            return 'success';
        };

        // Act
        $result = $pipe->handle($payload, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled);
    }
}
