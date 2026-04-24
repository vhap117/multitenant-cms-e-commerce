<?php

namespace VHAP\Core\Tests\Unit\Actions;

use Exception;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\ReactivateTenantAction;
use VHAP\Core\Actions\Pipes\Reactivation\MarkTenantActiveRecord;
use VHAP\Core\Actions\Pipes\Reactivation\ClearTenantCache;
use VHAP\Core\Actions\Pipes\Reactivation\DispatchReactivationEmail;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class ReactivateTenantActionTest extends TestCase
{
    #[Test]
    public function it_successfully_executes_the_reactivation_pipeline_and_returns_tenant()
    {
        // 1. Arrange
        $this->bindFakePipe(MarkTenantActiveRecord::class);
        $this->bindFakePipe(ClearTenantCache::class);
        $this->bindFakePipe(DispatchReactivationEmail::class);

        $tenant = new Tenant();
        $tenant->id = 1;
        $tenant->domain = 'test.example.com';

        // We expect Log::info to be called once upon success
        Log::shouldReceive('info')
            ->once()
            ->with("Tenant test.example.com has been successfully reactivated.");

        $action = new ReactivateTenantAction();

        // 2. Act
        $result = $action->execute($tenant);

        // 3. Assert
        // We ensure that the returned tenant instance matches our input exactly.
        $this->assertSame($tenant, $result);
    }

    #[Test]
    public function it_logs_error_and_rethrows_if_a_pipe_fails()
    {
        // 1. Arrange
        $tenant = new Tenant();
        $tenant->id = 99;
        $tenant->domain = 'bad.example.com';

        $this->bindFakePipe(MarkTenantActiveRecord::class);

        // The second pipe throws an exception
        $this->app->bind(ClearTenantCache::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new Exception('Cache unreachable!');
                }
            };
        });

        // The third pipe should NOT be executed
        $this->app->bind(DispatchReactivationEmail::class, function () {
            return new class {
                public function handle($tenant, $next) {
                    throw new Exception('Pipeline did not halt!');
                }
            };
        });

        // We expect Log::error to be called capturing the exception details
        Log::shouldReceive('error')
            ->once()
            ->with('Tenant reactivation pipeline failed.', [
                'tenant_id' => 99,
                'domain' => 'bad.example.com',
                'error' => 'Cache unreachable!',
            ]);

        $action = new ReactivateTenantAction();

        // 2. Act & Assert
        try {
            $action->execute($tenant);
            $this->fail('The exception was swallowed and not re-thrown by the action.');
        } catch (Exception $e) {
            $this->assertEquals('Cache unreachable!', $e->getMessage());
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
