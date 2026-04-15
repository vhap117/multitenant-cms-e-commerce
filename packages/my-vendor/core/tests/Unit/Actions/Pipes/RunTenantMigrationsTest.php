<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes;

use Illuminate\Support\Facades\Artisan;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\RunTenantMigrations;
use Illuminate\Contracts\Console\Kernel;
use VHAP\Core\Tests\TestCase;

class RunTenantMigrationsTest extends TestCase
{
    public function test_it_correctly_triggers_the_artisan_migrate_command_against_tenant_connection()
    {
        // Arrange
        $tenant = new Tenant();
        $pipe = new RunTenantMigrations();

        // 1. We mock the Artisan facade to intercept the migration command safely
        // You generally shouldn't run true migrations in a unit test of an action,
        // you just need to know the Action requested Laravel to run them!
        $this->mock(Kernel::class, function ($mock) {
            $mock->shouldReceive('call')
                 ->once()
                 ->with('migrate', [
                     '--database' => 'tenant',
                     '--path'     => database_path('migrations/tenant'),
                     '--force'    => true,
                 ]);
        });

        // 2. Mock pipeline continuation
        $nextWasCalled = false;
        $nextPipe = function ($passedTenant) use (&$nextWasCalled, $tenant) {
            $nextWasCalled = true;
            $this->assertSame($tenant, $passedTenant);
            return 'success';
        };

        // Act
        $result = $pipe->handle($tenant, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled, 'The pipe did not call the $next closure.');
        $this->assertEquals('success', $result);
    }
}
