<?php

namespace VHAP\Core\Tests\Unit\Actions;

use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\ProvisionNewTenantAction;
use VHAP\Core\Actions\Pipes\CreateTenantDatabase;
use VHAP\Core\Actions\Pipes\RunTenantMigrations;
use VHAP\Core\Actions\Pipes\SetupTenantAdmin;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Exception;

class ProvisionNewTenantActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure landlord connection is explicitly set to SQLite memory
        config(['database.connections.landlord' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);

        // Ensure tenant driver is sqlite to test the physical file cleanup logic
        config(['database.connections.tenant.driver' => 'sqlite']);

        // Run the landlord migration to create the 'tenants' table in memory
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
        // We mock all the pipes to simply pass the $tenant to the $next closure.
        // This proves the pipeline connects them without actually running their heavy logic.
        $this->mockPipe(CreateTenantDatabase::class);
        $this->mockPipe(RunTenantMigrations::class);
        $this->mockPipe(SetupTenantAdmin::class);

        $action = new ProvisionNewTenantAction();
        $tenantData = [
            'name' => 'Acme Corp',
            'domain' => 'acme.myapp.com',
            'database' => 'tenant_acme_db',
        ];

        // 2. Act
        $tenant = $action->execute($tenantData);

        // 3. Assert
        $this->assertInstanceOf(Tenant::class, $tenant);
        
        // Verify the transaction committed the record to the landlord database
        $this->assertDatabaseHas('tenants', [
            'name' => 'Acme Corp',
            'domain' => 'acme.myapp.com',
            'database' => 'tenant_acme_db',
        ], 'landlord');
    }

    #[Test]
    public function it_rolls_back_the_database_and_cleans_up_files_if_a_pipe_fails()
    {
        // 1. Arrange
        $dummyDbPath = __DIR__ . '/dummy_tenant.sqlite';
        File::put($dummyDbPath, ''); // Create a physical file to simulate the first pipe working

        // The first pipe succeeds (we simulate it just passing the data)
        $this->mockPipe(CreateTenantDatabase::class);
        
        // The second pipe FAILS (simulating a migration error)
        $this->mock(RunTenantMigrations::class, function ($mock) {
            $mock->shouldReceive('handle')->once()->andThrow(new Exception('Migration syntax error!'));
        });

        // The third pipe should never be reached
        $this->mock(SetupTenantAdmin::class, function ($mock) {
            $mock->shouldNotReceive('handle');
        });

        $action = new ProvisionNewTenantAction();
        $tenantData = [
            'name' => 'Bad Tenant',
            'domain' => 'bad.myapp.com',
            'database' => $dummyDbPath,
        ];

        // 2. Act & Assert
        try {
            $action->execute($tenantData);
            $this->fail('The exception was swallowed and not re-thrown by the action.');
        } catch (Exception $e) {
            $this->assertEquals('Migration syntax error!', $e->getMessage());
        }

        // 3. Verify Rollback & Cleanup
        // The database transaction should have rolled back the insert
        $this->assertDatabaseMissing('tenants', [
            'domain' => 'bad.myapp.com',
        ], 'landlord');

        // The catch block should have deleted the physical sqlite file
        $this->assertFalse(File::exists($dummyDbPath), 'The physical SQLite file was not cleaned up after the pipeline failed.');
    }

    /**
     * Helper method to quickly mock a pipe that just passes data forward.
     */
    protected function mockPipe(string $pipeClass): void
    {
        $this->mock($pipeClass, function ($mock) {
            $mock->shouldReceive('handle')->once()->andReturnUsing(function ($tenant, $next) {
                return $next($tenant); // Move to the next pipe
            });
        });
    }
}