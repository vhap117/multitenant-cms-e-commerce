<?php

namespace VHAP\Core\Tests\Unit\Actions;

use Exception;
use PHPUnit\Framework\Attributes\Test;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\InstallLandlordAction;
use VHAP\Core\Actions\Pipes\LandlordSetup\CreateLandlordDatabase;
use VHAP\Core\Actions\Pipes\LandlordSetup\RunLandlordMigrations;
use VHAP\Core\Actions\Pipes\LandlordSetup\SeedLandlordDefaultData;
use VHAP\Core\Actions\Pipes\LandlordSetup\ProvisionPlatformAdmin;

class InstallLandlordActionTest extends TestCase
{
    #[Test]
    public function it_successfully_executes_the_landlord_setup_pipeline()
    {
        // 1. Arrange - Bind safe fake pipes so we don't hit the real DB or filesystem
        $this->bindFakePipe(CreateLandlordDatabase::class);
        $this->bindFakePipe(RunLandlordMigrations::class);
        $this->bindFakePipe(SeedLandlordDefaultData::class);
        $this->bindFakePipe(ProvisionPlatformAdmin::class);

        $action = new InstallLandlordAction();
        
        $payload = [
            'database' => 'vhap_landlord_test_db',
            'name' => 'System Admin',
            'email' => 'admin@landlord.local',
            'password' => 'secret123',
        ];

        // 2. Act
        $result = $action->execute($payload);

        // 3. Assert - The action should return the original payload after all pipes pass
        $this->assertSame($payload, $result);
    }

    #[Test]
    public function it_halts_the_pipeline_and_throws_exception_if_a_pipe_fails()
    {
        // 1. Arrange
        $this->bindFakePipe(CreateLandlordDatabase::class);
        
        // Make the second pipe fail
        $this->app->bind(RunLandlordMigrations::class, function () {
            return new class {
                public function handle($payload, $next) {
                    throw new Exception('Landlord migration failed!');
                }
            };
        });

        // The third pipe should never be reached
        $this->app->bind(SeedLandlordDefaultData::class, function () {
            return new class {
                public function handle($payload, $next) {
                    throw new Exception('Pipeline did not halt! This pipe should not have executed.');
                }
            };
        });

        $action = new InstallLandlordAction();
        $payload = [
            'database' => 'failed_landlord_db',
            'name' => 'System Admin',
            'email' => 'admin@landlord.local',
            'password' => 'secret123',
        ];

        // 2. Act & Assert
        try {
            $action->execute($payload);
            $this->fail('The exception was swallowed and not re-thrown by the action.');
        } catch (Exception $e) {
            $this->assertEquals('Landlord migration failed!', $e->getMessage());
        }
    }

    /**
     * Helper method to bind a fake pipe directly into the Service Container.
     */
    protected function bindFakePipe(string $pipeClass): void
    {
        $this->app->bind($pipeClass, function () {
            return new class {
                public function handle($payload, $next) {
                    // Just pass the payload through to the next pipe
                    return $next($payload); 
                }
            };
        });
    }
}
