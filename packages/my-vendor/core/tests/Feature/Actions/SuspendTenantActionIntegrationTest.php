<?php

namespace VHAP\Core\Tests\Feature\Actions;

use RuntimeException;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\SuspendTenantAction;
use VHAP\Core\Actions\Pipes\Suspension\DispatchSuspensionNotification;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class SuspendTenantActionIntegrationTest extends TestCase
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
        $dbPath = __DIR__.'/dummy_tenant_suspension.sqlite';
        if (!File::exists($dbPath)) {
            File::put($dbPath, '');
        }

        // 3. Configure the tenant DB connection to the physical file
        config(['database.connections.tenant' => [
            'driver'   => 'sqlite',
            'database' => $dbPath,
        ]]);

        // 4. Migrate the physical tenant DB so the "sessions" table exists
        $this->artisan('migrate', [
            '--database' => 'tenant', 
            '--path' => __DIR__.'/../../../database/migrations/tenant', 
            '--realpath' => true
        ])->run();
    }

    protected function tearDown(): void
    {
        $dbPath = __DIR__.'/dummy_tenant_suspension.sqlite';
        if (File::exists($dbPath)) {
            File::delete($dbPath);
        }
        parent::tearDown();
    }

    #[Test]
    public function it_suspends_the_tenant_environment_end_to_end_and_truncates_sessions()
    {
        // 1. Arrange: Create a real active tenant in the DB
        $tenant = Tenant::forceCreate([
            'name'      => 'Active Store',
            'email'     => 'admin@active.myapp.com',
            'plan'      => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain'    => 'active.myapp.com',
            'database'  => __DIR__.'/dummy_tenant_suspension.sqlite',
            'is_active' => true,
        ]);

        // Manually push a fake active session into the memory database 
        $tenant->makeCurrent();
        DB::connection('tenant')->table('sessions')->insert([
            'id' => 'dummy_session_123',
            'payload' => 'some_encoded_payload_data',
            'last_activity' => time()
        ]);

        // Confirm the session is actively sitting in the database
        $this->assertEquals(1, DB::connection('tenant')->table('sessions')->count());

        $action = new SuspendTenantAction();

        // We expect the success log to be executed
        Log::shouldReceive('info')
            ->once()
            ->with("Tenant active.myapp.com has been successfully suspended. Reason: Manual suspension");

        // 2. Act: Run the real pipes across the real database
        $action->execute($tenant);

        // 3. Assertions
        // A. Assert the database correctly flipped the tenant state
        $this->assertDatabaseHas('tenants', [
            'id'        => $tenant->id,
            'is_active' => false,
        ], 'landlord');

        // B. Because 'TerminateTenantSessions' ran, the sessions table should be absolutely empty
        $tenant->makeCurrent(); // <-- Add this line to log the Test back into the DB!
        $this->assertEquals(0, DB::connection('tenant')->table('sessions')->count());
    }

    #[Test]
    public function it_logs_error_and_throws_exception_if_suspension_fails()
    {
        // 1. Arrange
        $tenant = Tenant::forceCreate([
            'name'      => 'Broken Store',
            'email'     => 'admin@broken.myapp.com',
            'plan'      => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain'    => 'broken.myapp.com',
            'database'  => __DIR__.'/dummy_tenant_suspension.sqlite',
            'is_active' => true,
        ]);

        // We bind a fake pipe strictly for the LAST step (DispatchSuspensionNotification) 
        // to intentionally force a mid-flight crash after previous pipes fired.
        $this->app->bind(DispatchSuspensionNotification::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new RuntimeException('Notification pushing service offline!');
                }
            };
        });

        $action = new SuspendTenantAction();
        
        // Because of the crash, the action's catch block should log an error Payload
        Log::shouldReceive('error')
            ->once()
            ->with('Tenant suspension pipeline failed.', [
                'tenant_id' => $tenant->id,
                'domain'    => 'broken.myapp.com',
                'error'     => 'Notification pushing service offline!',
            ]);

        // 2. Act & Assert Expectation
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Notification pushing service offline!');

        try {
            $action->execute($tenant);
        } finally {
            // Assert that the transaction perfectly rolled back! 
            // The landlord database should still see the tenant as ACTIVE since the pipeline failed.
            $this->assertDatabaseHas('tenants', [
                'id' => $tenant->id,
                'is_active' => true,
            ], 'landlord');
        }
    }
}
