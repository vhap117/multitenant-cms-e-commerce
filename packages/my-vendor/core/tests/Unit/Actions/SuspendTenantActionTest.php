<?php

namespace VHAP\Core\Tests\Unit\Actions;

use Exception;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\SuspendTenantAction;
use VHAP\Core\Actions\Pipes\Suspension\DeactivateTenantRecord;
use VHAP\Core\Actions\Pipes\Suspension\TerminateTenantSessions;
use VHAP\Core\Actions\Pipes\Suspension\DispatchSuspensionNotification;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class SuspendTenantActionTest extends TestCase
{
    #[Test]
    public function it_successfully_executes_the_suspension_pipeline_and_returns_tenant()
    {
        // 1. Arrange
        $this->bindFakePipe(DeactivateTenantRecord::class);
        $this->bindFakePipe(TerminateTenantSessions::class);
        $this->bindFakePipe(DispatchSuspensionNotification::class);

        $tenant = new Tenant();
        $tenant->id = 1;
        $tenant->domain = 'test.example.com';

        // We expect Log::info to be called once upon success
        Log::shouldReceive('info')
            ->once()
            ->with("Tenant test.example.com has been successfully suspended.");

        $action = new SuspendTenantAction();

        // 2. Act
        $result = $action->execute($tenant);

        // 3. Assert
        // Ensures that the pipeline completed successfully and returned the original tenant structure.
        $this->assertSame($tenant, $result);
    }

    #[Test]
    public function it_allows_exceptions_to_propagate_if_a_pipe_fails_and_logs_error()
    {
        // 1. Arrange
        $tenant = new Tenant();
        $tenant->id = 99;
        $tenant->domain = 'bad.example.com';

        $this->bindFakePipe(DeactivateTenantRecord::class);

        // The second pipe throws exception
        $this->app->bind(TerminateTenantSessions::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new Exception('Sessions database unreachable!');
                }
            };
        });

        // The third pipe should NOT be executed
        $this->app->bind(DispatchSuspensionNotification::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new Exception('Pipeline did not halt!');
                }
            };
        });

        // We expect Log::error to be called capturing the exception details
        Log::shouldReceive('error')
            ->once()
            ->with('Tenant suspension pipeline failed.', [
                'tenant_id' => 99,
                'domain' => 'bad.example.com',
                'error' => 'Sessions database unreachable!',
            ]);

        $action = new SuspendTenantAction();

        // 2. Act & Assert
        try {
            $action->execute($tenant);
            $this->fail('The exception was swallowed and not re-thrown by the action.');
        } catch (Exception $e) {
            $this->assertEquals('Sessions database unreachable!', $e->getMessage());
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
