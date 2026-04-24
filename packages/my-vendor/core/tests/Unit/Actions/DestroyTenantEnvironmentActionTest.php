<?php

namespace VHAP\Core\Tests\Unit\Actions;

use Exception;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\DestroyTenantEnvironmentAction;
use VHAP\Core\Actions\Pipes\Destruction\DropTenantDatabase;
use VHAP\Core\Actions\Pipes\Destruction\DeleteTenantStorageDirectory;
use VHAP\Core\Actions\Pipes\Destruction\DeleteTenantRecord;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class DestroyTenantEnvironmentActionTest extends TestCase
{
    #[Test]
    public function it_successfully_executes_the_destruction_pipeline()
    {
        // 1. Arrange
        $this->bindFakePipe(DropTenantDatabase::class);
        $this->bindFakePipe(DeleteTenantStorageDirectory::class);
        $this->bindFakePipe(DeleteTenantRecord::class);

        // Construct a dummy model. It won't hit the DB because pipes are faked.
        $tenant = new Tenant();
        $tenant->id = 1;
        $tenant->domain = 'test.example.com';

        // We expect Log::info to be called once upon success
        Log::shouldReceive('info')
            ->once()
            ->with("Tenant environment for test.example.com has been permanently destroyed.");

        $action = new DestroyTenantEnvironmentAction();

        // 2. Act
        $action->execute($tenant);

        // 3. Assert
        // If execution reached here without exception and log was verified, it's successful.
        $this->assertTrue(true);
    }

    #[Test]
    public function it_logs_critical_error_and_rethrows_if_a_pipe_fails()
    {
        // 1. Arrange
        $tenant = new Tenant();
        $tenant->id = 99;
        $tenant->domain = 'bad.example.com';

        $this->bindFakePipe(DropTenantDatabase::class);

        // The second pipe throws exception
        $this->app->bind(DeleteTenantStorageDirectory::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new Exception('Storage unreachable!');
                }
            };
        });

        // The third pipe should NOT be executed
        $this->app->bind(DeleteTenantRecord::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new Exception('Pipeline did not halt!');
                }
            };
        });

        // We expect Log::critical to be called capturing the exception details
        Log::shouldReceive('critical')
            ->once()
            ->with('CRITICAL FAILURE: Tenant destruction pipeline failed mid-execution.', [
                'tenant_id' => 99,
                'domain' => 'bad.example.com',
                'error' => 'Storage unreachable!',
            ]);

        $action = new DestroyTenantEnvironmentAction();

        // 2. Act & Assert
        try {
            $action->execute($tenant);
            $this->fail('The exception was swallowed and not re-thrown by the action.');
        } catch (Exception $e) {
            $this->assertEquals('Storage unreachable!', $e->getMessage());
        }
    }

    /**
     * Helper method to bind a fake pipe directly into the Service Container.
     */
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
