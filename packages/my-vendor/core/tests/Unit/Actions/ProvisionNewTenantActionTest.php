<?php

namespace VHAP\Core\Tests\Unit\Actions;

use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\ProvisionNewTenantAction;
use VHAP\Core\Actions\Pipes\Provision\CreateTenantDatabase;
use VHAP\Core\Actions\Pipes\Provision\RunTenantMigrations;
use VHAP\Core\Actions\Pipes\Provision\SeedTenantDefaultData;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Events\TenantProvisioned;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Exception;

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
    public function it_successfully_executes_the_pipeline_and_saves_the_tenant()
    {
        // 1. Arrange - Use native anonymous classes instead of Mockery
        $this->bindFakePipe(CreateTenantDatabase::class);
        $this->bindFakePipe(RunTenantMigrations::class);
        $this->bindFakePipe(SeedTenantDefaultData::class);
        
        Event::fake();

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

        // 2. Act
        $tenant = $action->execute($tenantData);

        // 3. Assert
        $this->assertInstanceOf(Tenant::class, $tenant);
        
        $this->assertDatabaseHas('tenants', [
            'name' => 'Acme Corp',
            'domain' => 'acme.myapp.com',
            'database' => 'tenant_acme_db',
            'provisioning_status' => 'active',
            'is_active' => true,
        ], 'landlord');

        Event::assertDispatched(TenantProvisioned::class, function ($event) use ($tenant, $tenantData) {
            return $event->tenant->id === $tenant->id && $event->adminData === $tenantData->adminUser;
        });
    }

    #[Test]
    public function it_updates_status_to_failed_and_cleans_up_files_if_a_pipe_fails()
    {
        // 1. Arrange
        $dummyDbPath = 'dummy_tenant.sqlite';

        // MOCK THE FILE SYSTEM: 
        // We tell Laravel to expect a deletion attempt, without touching the physical OS.
        // This guarantees zero Error Handler leaks from physical OS file locks.
        File::shouldReceive('exists')->andReturnUsing(function ($path) use ($dummyDbPath) {
            return $path === $dummyDbPath;
        });
        File::shouldReceive('delete')->with($dummyDbPath)->once()->andReturn(true);

        // The first pipe succeeds
        $this->bindFakePipe(CreateTenantDatabase::class);

        // NATIVE EXCEPTION BINDING:
        // We use a pure PHP anonymous class instead of Mockery to throw the error.
        // This guarantees zero Exception Handler leaks from Mockery's internal tracking.
        $this->app->bind(RunTenantMigrations::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new Exception('Migration syntax error!');
                }
            };
        });

        Event::fake();

        // The third pipe should never be reached. If it is, this forces a failure.
        $this->app->bind(SeedTenantDefaultData::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new Exception('Pipeline did not halt! This pipe should not have executed.');
                }
            };
        });

        $action = new ProvisionNewTenantAction();
        $tenantData = new \VHAP\Core\Data\ProvisionTenantData(
            name: 'Bad Tenant',
            email: 'admin@bad.myapp.com',
            domain: 'bad.myapp.com',
            database: $dummyDbPath,
            plan: \VHAP\Core\Enums\TenantPlan::FREE,
            adminUser: new \VHAP\Core\Data\TenantAdminUserData(
                name: 'Bad Admin',
                email: 'admin@bad.myapp.com',
                password: 'secret123'
            )
        );

        // 2. Act & Assert
        try {
            $action->execute($tenantData);
            $this->fail('The exception was swallowed and not re-thrown by the action.');
        } catch (Exception $e) {
            $this->assertEquals('Migration syntax error!', $e->getMessage());
        }

        // 3. Verify Failure State
        // The Tenant record should remain, but marked as failed
        $this->assertDatabaseHas('tenants', [
            'domain' => 'bad.myapp.com',
            'provisioning_status' => 'failed',
        ], 'landlord');

        Event::assertNotDispatched(TenantProvisioned::class);
        
        // Note: We no longer need to assert File::exists() because the 
        // File::shouldReceive()->once() mock at the top of the test already 
        // guarantees the deletion logic was triggered successfully.
    }

    /**
     * Helper method to bind a fake pipe directly into the Service Container.
     * This is safer than Mockery for pipeline testing because it avoids handler conflicts.
     */
    protected function bindFakePipe(string $pipeClass): void
    {
        $this->app->bind($pipeClass, function () {
            return new class {
                public function handle($tenant, $next) {
                    return $next($tenant); // Move to the next pipe
                }
            };
        });
    }
}