<?php

namespace VHAP\Core\Tests\Unit\Jobs;

use VHAP\Core\Tests\TestCase;
use VHAP\Core\Jobs\BuildTenantEnvironmentJob;
use VHAP\Core\Actions\Pipes\Provision\CreateTenantDatabase;
use VHAP\Core\Actions\Pipes\Provision\RunTenantMigrations;
use VHAP\Core\Actions\Pipes\Provision\SeedTenantDefaultData;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Events\TenantProvisioned;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Exception;

class BuildTenantEnvironmentJobTest extends TestCase
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
        // 1. Arrange
        $this->bindFakePipe(CreateTenantDatabase::class);
        $this->bindFakePipe(RunTenantMigrations::class);
        $this->bindFakePipe(SeedTenantDefaultData::class);
        
        Event::fake();

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

        $tenant = Tenant::create([
            'name' => 'Acme Corp',
            'email' => 'admin@acme.myapp.com',
            'domain' => 'acme.myapp.com',
            'database' => 'tenant_acme_db',
            'provisioning_status' => 'pending',
            'plan' => 'free',
        ]);

        $job = new BuildTenantEnvironmentJob($tenant, $tenantData);

        // 2. Act
        $job->handle();

        // 3. Assert
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

        File::shouldReceive('exists')->andReturnUsing(function ($path) use ($dummyDbPath) {
            return $path === $dummyDbPath;
        });
        File::shouldReceive('delete')->with($dummyDbPath)->once()->andReturn(true);

        $this->bindFakePipe(CreateTenantDatabase::class);

        $this->app->bind(RunTenantMigrations::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new Exception('Migration syntax error!');
                }
            };
        });

        Event::fake();

        $this->app->bind(SeedTenantDefaultData::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new Exception('Pipeline did not halt! This pipe should not have executed.');
                }
            };
        });

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

        $tenant = Tenant::create([
            'name' => 'Bad Tenant',
            'email' => 'admin@bad.myapp.com',
            'domain' => 'bad.myapp.com',
            'database' => $dummyDbPath,
            'provisioning_status' => 'pending',
            'plan' => 'free',
        ]);

        $job = new BuildTenantEnvironmentJob($tenant, $tenantData);

        // 2. Act & Assert
        try {
            $job->handle();
            $this->fail('The exception was swallowed and not re-thrown by the job.');
        } catch (Exception $e) {
            $this->assertEquals('Migration syntax error!', $e->getMessage());
        }

        // 3. Verify Failure State
        $this->assertDatabaseHas('tenants', [
            'domain' => 'bad.myapp.com',
            'provisioning_status' => 'failed',
        ], 'landlord');

        Event::assertNotDispatched(TenantProvisioned::class);
    }

    protected function bindFakePipe(string $pipeClass): void
    {
        $this->app->bind($pipeClass, function () {
            return new class {
                public function handle($tenant, $next) {
                    return $next($tenant);
                }
            };
        });
    }
}