<?php

namespace VHAP\Core\Tests\Feature\Actions;

use RuntimeException;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\ProvisionNewTenantAction;
use VHAP\Core\Actions\Pipes\Provision\SeedTenantDefaultData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class ProvisionNewTenantActionIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Ensure our landlord DB is set to in-memory SQLite and fully migrated
        config(['database.connections.landlord' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]]);

        // 2. Ensure our tenant DB driver is configured explicitly to create SQLite files
        config(['database.connections.tenant.driver' => 'sqlite']);

        // Spatie stub is dynamically evaluated on-the-fly, no copy needed here.
    }

    protected function tearDown(): void
    {
        // Clean up package default tests state
        
        parent::tearDown();
    }

    #[Test]
    public function it_provisions_a_complete_tenant_environment_end_to_end()
    {
        // 1. Arrange: Define the physical database file path we will create
        $databaseFilename = 'tenant_integration_test.sqlite';
        
        // Clean up from previous ghost runs if any
        if (File::exists($databaseFilename)) {
            File::delete($databaseFilename);
        }

        $action = new ProvisionNewTenantAction();
        $tenantData = new \VHAP\Core\Data\ProvisionTenantData(
            name: 'Integration Store',
            email: 'admin@integration.myapp.com',
            domain: 'integration.myapp.com',
            database: $databaseFilename,
            plan: \VHAP\Core\Enums\TenantPlan::FREE,
            adminUser: new \VHAP\Core\Data\TenantAdminUserData(
                name: 'Integration Store Admin',
                email: 'admin@integration.myapp.com',
                password: 'secret123'
            )
        );

        // 2. Act: We run the Action exactly as a Controller would 
        // Notice we did NOT bind any fake pipes into the container!
        $tenant = $action->execute($tenantData);

        // 3. Assertions
        
        // A. Is the tenant saved in the landlord registry?
        $this->assertDatabaseHas('tenants', [
            'domain'   => 'integration.myapp.com',
            'database' => $databaseFilename,
            'email'    => 'admin@integration.myapp.com',
            'plan'     => \VHAP\Core\Enums\TenantPlan::FREE->value,
        ], 'landlord');

        // B. Did it physically create the database file?
        $this->assertTrue(File::exists($databaseFilename), 'The physical SQLite file was not created.');

        // C. Switch into the brand new tenant database
        $tenant->makeCurrent();

        // D. Did the migrations run successfully? 
        // We check if the 'users' table exists 
        $this->assertTrue(DB::connection('tenant')->getSchemaBuilder()->hasTable('users'));

        // E. Clean up context
        $tenant->forgetCurrent();
        
        // Final Physical cleanup after test finishes
        File::delete($databaseFilename);
    }
    
    #[Test]
    public function it_rolls_back_the_transaction_and_cleans_up_the_physical_database_file_on_failure()
    {
        // 1. Arrange
        $databaseFilename = 'tenant_failure_test.sqlite';
        
        if (File::exists($databaseFilename)) {
            File::delete($databaseFilename);
        }

        // We bind a fake pipe strictly for the LAST step (SeedTenantDefaultData) so that
        // the physical database is definitely created and migrated, but then the pipeline crashes.
        $this->app->bind(SeedTenantDefaultData::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new RuntimeException('Admin seeding failed unexpectedly.');
                }
            };
        });

        $action = new ProvisionNewTenantAction();
        $tenantData = new \VHAP\Core\Data\ProvisionTenantData(
            name: 'Bad Integration Store',
            email: 'admin@bad-integration.myapp.com',
            domain: 'bad-integration.myapp.com',
            database: $databaseFilename,
            plan: \VHAP\Core\Enums\TenantPlan::FREE,
            adminUser: new \VHAP\Core\Data\TenantAdminUserData(
                name: 'Bad Integration Store Admin',
                email: 'admin@bad-integration.myapp.com',
                password: 'secret123'
            )
        );

        // 2. Act & Assert Expectation
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Admin seeding failed unexpectedly.');

        try {
            $action->execute($tenantData);
        } finally {
            // 3. Assert Cleanup happens even if an exception is thrown!
            
            // A. Assert the database transaction rolled back, OR in our new flow, the tenant is marked as failed.
            $this->assertDatabaseHas('tenants', [
                'domain' => 'bad-integration.myapp.com',
                'provisioning_status' => 'failed',
            ], 'landlord');

            // B. Assert the physical SQlite file was deleted by the cleanupFailedDatabase fallback routine.
            $this->assertFalse(File::exists($databaseFilename), 'The physical SQLite file was NOT deleted after a pipeline failure.');
            
            // Clean up manually just in case the test failed to delete it
            if (File::exists($databaseFilename)) {
                File::delete($databaseFilename);
            }
        }
    }
}
