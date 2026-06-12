<?php

namespace VHAP\Core\Tests\Unit\Actions;

use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\ProvisionNewTenantAction;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Jobs\BuildTenantEnvironmentJob;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;

class ProvisionNewTenantActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config(['database.connections.landlord' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);

        config(['database.connections.tenant.driver' => 'sqlite']);

        $this->artisan('migrate', [
            '--database' => 'landlord', 
            '--path'     => __DIR__.'/../../../../database/migrations/landlord',
            '--realpath' => true,
        ])->run();
    }

    #[Test]
    public function it_creates_tenant_record_and_dispatches_job()
    {
        Queue::fake();

        $action = new ProvisionNewTenantAction();
        $tenantData = new \VHAP\Core\Data\ProvisionTenantData(
            name: 'Acme Corp',
            email: 'admin@acme.myapp.com',
            domain: 'acme.myapp.com',
            database: 'tenant_acme_db',
            plan: \VHAP\Core\Enums\TenantPlan::FREE,
            adminUser: new \VHAP\Core\Data\TenantAdminUserData(
                name: 'Acme Admin',
                email: 'admin@acme.myapp.com',
                password: 'secret123'
            )
        );

        $tenant = $action->execute($tenantData);

        // Assert DB has record
        $this->assertDatabaseHas('tenants', [
            'name' => 'Acme Corp',
            'domain' => 'acme.myapp.com',
            'database' => 'tenant_acme_db',
            'provisioning_status' => 'pending',
        ], 'landlord');

        // Assert Job was dispatched
        Queue::assertPushed(BuildTenantEnvironmentJob::class, function ($job) use ($tenant) {
            return $job->tenant->id === $tenant->id;
        });
    }
}
