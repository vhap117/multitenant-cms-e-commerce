<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\LandlordSetup;

use Illuminate\Contracts\Console\Kernel;
use VHAP\Core\Actions\Pipes\LandlordSetup\RunLandlordMigrations;
use VHAP\Core\Tests\TestCase;

class RunLandlordMigrationsTest extends TestCase
{
    public function test_it_correctly_triggers_the_artisan_migrate_command_against_landlord_connection()
    {
        // Arrange
        $payload = ['database' => 'vhap_landlord'];
        $pipe = new RunLandlordMigrations();

        // 1. We mock the Artisan facade to intercept the migration command safely
        // Because Landlord migrations are loaded via `$this->loadMigrationsFrom()` in CoreServiceProvider,
        // we don't need a `--path` argument here. Laravel handles it natively.
        $this->mock(Kernel::class, function ($mock) {
            $mock->shouldReceive('call')
                 ->once()
                 ->with('migrate', [
                     '--database' => 'landlord',
                     '--force'    => true,
                 ]);
        });

        // 2. Mock pipeline continuation
        $nextWasCalled = false;
        $nextPipe = function ($passedPayload) use (&$nextWasCalled, $payload) {
            $nextWasCalled = true;
            $this->assertSame($payload, $passedPayload);
            return 'success';
        };

        // Act
        $result = $pipe->handle($payload, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled, 'The pipe did not call the $next closure.');
        $this->assertEquals('success', $result);
    }
}
