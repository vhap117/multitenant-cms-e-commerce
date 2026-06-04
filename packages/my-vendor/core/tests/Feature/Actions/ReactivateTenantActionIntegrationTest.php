<?php

namespace VHAP\Core\Tests\Feature\Actions;

use RuntimeException;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\ReactivateTenantAction;
use VHAP\Core\Actions\Pipes\Reactivation\DispatchReactivationEmail;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class ReactivateTenantActionIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Ensure the landlord DB is set to in-memory SQLite and migrated
        config(['database.connections.landlord' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]]);
        $this->artisan('migrate', [
            '--database' => 'landlord', 
            '--path' => __DIR__.'/../../../database/migrations/landlord', 
            '--realpath' => true
        ])->run();

        // 2. Setup a physical SQLite file for the tenant integration test
        $dbPath = __DIR__.'/dummy_tenant_reactivation.sqlite';
        if (!File::exists($dbPath)) {
            File::put($dbPath, '');
        }

        // 3. Configure the tenant DB connection to the physical file
        config(['database.connections.tenant' => [
            'driver'   => 'sqlite',
            'database' => $dbPath,
        ]]);

        // 4. Migrate the physical tenant DB. Even though Reactivation doesn't strictly 
        // run physical queries against tenant tables like Suspend does, doing this 
        // ensures our Spatie cache flushing doesn't encounter weird anomalies!
        $this->artisan('migrate', [
            '--database' => 'tenant', 
            '--path' => __DIR__.'/../../../database/migrations/tenant', 
            '--realpath' => true
        ])->run();
    }

    protected function tearDown(): void
    {
        $dbPath = __DIR__.'/dummy_tenant_reactivation.sqlite';
        if (File::exists($dbPath)) {
            File::delete($dbPath);
        }
        parent::tearDown();
    }

    #[Test]
    public function it_reactivates_the_tenant_environment_end_to_end()
    {
        // 1. Arrange: Create a real suspended tenant in the DB
        $tenant = Tenant::forceCreate([
            'name'      => 'Suspended Store',
            'email'     => 'admin@suspended.myapp.com',
            'plan'      => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain'    => 'suspended.myapp.com',
            'database'  => __DIR__.'/dummy_tenant_reactivation.sqlite',
            'is_active' => false,
        ]);

        $action = new ReactivateTenantAction();

        // We expect the success log to be executed
        Log::shouldReceive('info')
            ->once()
            ->with("Tenant suspended.myapp.com has been successfully reactivated.");

        // 2. Act: Run the real pipes 
        $action->execute($tenant);

        // 3. Assertions
        // Assert the landlord database successfully flipped the tenant state back to active
        $this->assertDatabaseHas('tenants', [
            'id'        => $tenant->id,
            'is_active' => true, // Thanks to our previous fix, this shouldn't silently fail anymore!
        ], 'landlord');
    }

    #[Test]
    public function it_logs_error_and_throws_exception_if_reactivation_fails()
    {
        // 1. Arrange
        $tenant = Tenant::forceCreate([
            'name'      => 'Broken Reactivation Store',
            'email'     => 'admin@broken.myapp.com',
            'plan'      => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain'    => 'broken.myapp.com',
            'database'  => __DIR__.'/dummy_tenant_reactivation.sqlite',
            'is_active' => false,
        ]);

        // We bind a fake pipe strictly for the LAST step (DispatchReactivationEmail) 
        // to intentionally force a mid-flight crash after previous pipes fired.
        $this->app->bind(DispatchReactivationEmail::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new RuntimeException('Email dispatching service offline!');
                }
            };
        });

        $action = new ReactivateTenantAction();
        
        // Because of the crash, the action's catch block should log an error Payload
        Log::shouldReceive('error')
            ->once()
            ->with('Tenant reactivation pipeline failed.', [
                'tenant_id' => $tenant->id,
                'domain'    => 'broken.myapp.com',
                'error'     => 'Email dispatching service offline!',
            ]);

        // 2. Act & Assert Expectation
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Email dispatching service offline!');

        try {
            $action->execute($tenant);
        } finally {
            // Assert that the transaction perfectly rolled back! 
            // The landlord database should still see the tenant as SUSPENDED since the pipeline failed.
            $this->assertDatabaseHas('tenants', [
                'id' => $tenant->id,
                'is_active' => false,
            ], 'landlord');
        }
    }
}
