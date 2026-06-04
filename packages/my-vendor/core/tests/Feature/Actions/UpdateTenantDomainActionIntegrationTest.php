<?php

namespace VHAP\Core\Tests\Feature\Actions;

use RuntimeException;
use Exception;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\UpdateTenantDomainAction;
use VHAP\Core\Actions\Pipes\Domain\UpdateTenantRecord;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class UpdateTenantDomainActionIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure the landlord DB is set to in-memory SQLite and migrated
        // (Since domain updates only strictly query the Landlord DB, 
        // we don't need a physical fake Tenant dummy DB here!)
        config(['database.connections.landlord' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]]);
        $this->artisan('migrate', [
            '--database' => 'landlord', 
            '--path' => __DIR__.'/../../../database/migrations/landlord', 
            '--realpath' => true
        ])->run();
    }

    #[Test]
    public function it_updates_the_tenant_domain_end_to_end()
    {
        // 1. Arrange
        $tenant = Tenant::forceCreate([
            'name'      => 'Domain Update Store',
            'email'     => 'admin@old.myapp.com',
            'plan'      => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain'    => 'old.myapp.com',
            'database'  => 'old_db_schema',
        ]);

        $action = new UpdateTenantDomainAction();

        // We expect the success log to be executed
        Log::shouldReceive('info')
            ->once()
            ->with("Tenant ID {$tenant->id} successfully changed domain to fresh.myapp.com.");

        // 2. Act
        $action->execute($tenant, 'fresh.myapp.com');

        // 3. Assertions
        $this->assertDatabaseHas('tenants', [
            'id'        => $tenant->id,
            'domain'    => 'fresh.myapp.com',
        ], 'landlord');
    }

    #[Test]
    public function it_fails_if_domain_is_already_taken_by_another_tenant()
    {
        // 1. Arrange
        Tenant::forceCreate([
            'name'      => 'Competitor Store',
            'email'     => 'admin@premium.myapp.com',
            'plan'      => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain'    => 'premium.myapp.com',
            'database'  => 'competitor_db_schema',
        ]);

        $tenant = Tenant::forceCreate([
            'name'      => 'My Store',
            'email'     => 'admin@my-store.myapp.com',
            'plan'      => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain'    => 'my-store.myapp.com',
            'database'  => 'my_store_db_schema',
        ]);

        $action = new UpdateTenantDomainAction();

        // Because of the domain conflict, the action's catch block should log an error Payload
        Log::shouldReceive('error')
            ->once()
            ->with('Tenant domain update failed.', [
                'tenant_id' => $tenant->id,
                'attempted_domain' => 'premium.myapp.com',
                'error' => "The domain 'premium.myapp.com' is already registered to another store.",
            ]);

        // 2. Act & Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The domain 'premium.myapp.com' is already registered to another store.");

        $action->execute($tenant, 'premium.myapp.com');
    }

    #[Test]
    public function it_logs_error_and_rolls_back_landlord_transaction_if_update_fails()
    {
        // 1. Arrange
        $tenant = Tenant::forceCreate([
            'name'      => 'Crash Store',
            'email'     => 'admin@crash.myapp.com',
            'plan'      => \VHAP\Core\Enums\TenantPlan::FREE->value,
            'domain'    => 'crash.myapp.com',
            'database'  => 'crash_db_schema',
        ]);

        // Bind a fake pipe strictly for the LAST step to artificially 
        // trigger an exception inside the transaction scope.
        $this->app->bind(UpdateTenantRecord::class, function () {
            return new class {
                public function handle($payload, $next) {
                    throw new RuntimeException('Database locked! Could not update tenant domain.');
                }
            };
        });

        $action = new UpdateTenantDomainAction();
        
        Log::shouldReceive('error')
            ->once()
            ->with('Tenant domain update failed.', [
                'tenant_id' => $tenant->id,
                'attempted_domain' => 'new.myapp.com',
                'error' => 'Database locked! Could not update tenant domain.',
            ]);

        // 2. Act & Assert Expectation
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database locked! Could not update tenant domain.');

        try {
            $action->execute($tenant, 'new.myapp.com');
        } finally {
            // 3. Assertions on Rollback
            
            // Assert that the transaction perfectly rolled back! 
            // The landlord database should STILL firmly contain the original domain.
            $this->assertDatabaseHas('tenants', [
                'id' => $tenant->id,
                'domain' => 'crash.myapp.com',
            ], 'landlord');
        }
    }
}
