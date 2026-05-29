<?php

namespace VHAP\Core\Tests\Feature\Actions;

use RuntimeException;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\DestroyTenantEnvironmentAction;
use VHAP\Core\Actions\Pipes\Destruction\DeleteTenantRecord;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class DestroyTenantEnvironmentActionIntegrationTest extends TestCase
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

        // 2. Setup the tenant DB environment (important for DropTenantDatabase to resolve "sqlite" driver)
        config(['database.connections.tenant.driver' => 'sqlite']);
        
        // 3. Fake the filesystems to avoid polluting the host's actual hard drive during test runs
        Storage::fake('local');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        $dbPath = __DIR__.'/dummy_tenant_destruction.sqlite';
        if (File::exists($dbPath)) {
            File::delete($dbPath);
        }
        parent::tearDown();
    }

    #[Test]
    public function it_destroys_the_tenant_environment_end_to_end()
    {
        // 1. Arrange: Create a real physical file to be demolished
        $dbPath = __DIR__.'/dummy_tenant_destruction.sqlite';
        if (!File::exists($dbPath)) {
            File::put($dbPath, '');
        }

        $tenant = Tenant::forceCreate([
            'name'      => 'Doomed Store',
            'domain'    => 'doomed.myapp.com',
            'database'  => $dbPath,
            'is_active' => false, // Typically you'd suspend a tenant before destroying it
        ]);

        // Put some dummy files in the faked disk directories 
        Storage::disk('local')->put("tenants/{$tenant->id}/private_file.txt", "secret_cache_data");
        Storage::disk('public')->put("tenants/{$tenant->id}/public_logo.png", "fake_image_bytes");

        $action = new DestroyTenantEnvironmentAction();

        // We expect the global log to record this critical action
        Log::shouldReceive('info')
            ->once()
            ->with("Tenant environment for doomed.myapp.com has been permanently destroyed.");

        // 2. Act: Run the real destruction pipeline
        $action->execute($tenant);

        // 3. Assertions
        
        // A. Assert the database file is physically gone!
        $this->assertFalse(File::exists($dbPath));

        // B. Assert the storage directories and internal files are completely wiped on both disks
        $this->assertFalse(Storage::disk('local')->exists("tenants/{$tenant->id}/private_file.txt"));
        $this->assertFalse(Storage::disk('public')->exists("tenants/{$tenant->id}/public_logo.png"));

        // C. Assert the tenant record was successfully soft-deleted from the landlord DB connection
        $this->assertSoftDeleted('tenants', [
            'id' => $tenant->id,
        ], 'landlord');
    }

    #[Test]
    public function it_logs_critical_error_and_rolls_back_landlord_transaction_if_destruction_fails()
    {
        // 1. Arrange
        $dbPath = __DIR__.'/dummy_tenant_destruction.sqlite';
        if (!File::exists($dbPath)) {
            File::put($dbPath, '');
        }

        $tenant = Tenant::forceCreate([
            'name'      => 'Partially Destroyed Store',
            'domain'    => 'partial.myapp.com',
            'database'  => $dbPath,
            'is_active' => false,
        ]);

        // We formally bind a fake pipe strictly for the LAST step (DeleteTenantRecord) 
        // to intentionally force a crash AFTER the physical files drop, exposing the transaction scope.
        $this->app->bind(DeleteTenantRecord::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new RuntimeException('Database locked! Could not delete landlord record.');
                }
            };
        });

        $action = new DestroyTenantEnvironmentAction();
        
        // Because of the crash, the action's catch block should log a critical error Payload
        Log::shouldReceive('critical')
            ->once()
            ->with('CRITICAL FAILURE: Tenant destruction pipeline failed mid-execution.', [
                'tenant_id' => $tenant->id,
                'domain'    => 'partial.myapp.com',
                'error'     => 'Database locked! Could not delete landlord record.',
            ]);

        // 2. Act & Assert Expectation
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database locked! Could not delete landlord record.');

        try {
            $action->execute($tenant);
        } finally {
            // 3. Assertions on Rollback
            
            // Assert that the transaction perfectly rolled back! 
            // The landlord database should STILL contain the tenant because the model deletion failed.
            $this->assertDatabaseHas('tenants', [
                'id' => $tenant->id,
            ], 'landlord');
            
            // NOTE: Even though the landlord database rolled back, the physical `.sqlite` file
            // and the `tenants/id/` Storage directory were already permanently unlinked by the 
            // previous pipes because OS file interactions cannot organically participate in 
            // SQL Transactions! This is an expected un-recoverable state.
            $this->assertFalse(File::exists($dbPath));
        }
    }
}
