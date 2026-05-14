<?php

namespace VHAP\Core\Tests\Feature\Actions;

use RuntimeException;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Actions\InstallLandlordAction;
use VHAP\Core\Actions\Pipes\LandlordSetup\ProvisionPlatformAdmin;
use VHAP\Core\Models\LandlordUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class InstallLandlordActionIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Setup the physical SQLite config so the action creates real tables on disk
        config(['database.connections.landlord' => [
            'driver'   => 'sqlite',
            // We set the target file here just like Laravel's config/database.php would
            'database' => __DIR__.'/landlord_integration_test.sqlite', 
            'prefix'   => '',
        ]]);

        config(['permission.database_connection' => 'landlord']);

        // 2. Temporarily copy the Spatie stub directly into the package landlord directory 
        // so the Artisan migrator natively picks it up.
        $spatieStub = __DIR__.'/../../../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
        $packageMigrationPath = __DIR__.'/../../../database/migrations/landlord/0002_02_02_000000_create_permission_tables.php';
        
        if (!File::exists($packageMigrationPath) && File::exists($spatieStub)) {
            File::copy($spatieStub, $packageMigrationPath);
        }
    }

    protected function tearDown(): void
    {
        $packageMigrationPath = __DIR__.'/../../../database/migrations/landlord/0002_02_02_000000_create_permission_tables.php';
        
        if (File::exists($packageMigrationPath)) {
            File::delete($packageMigrationPath);
        }
        
        $databaseFilename = __DIR__.'/landlord_integration_test.sqlite';
        if (File::exists($databaseFilename)) {
            File::delete($databaseFilename);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_installs_the_landlord_environment_end_to_end()
    {
        // 1. Arrange
        $databaseFilename = config('database.connections.landlord.database');
        
        if (File::exists($databaseFilename)) {
            File::delete($databaseFilename);
        }

        $action = new InstallLandlordAction();
        $payload = [
            'database' => $databaseFilename,
            'name'     => 'System Admin',
            'email'    => 'admin@landlord.local',
            'password' => 'secret123',
        ];

        // 2. Act: Execute the action with the real pipeline classes
        $result = $action->execute($payload);

        // 3. Assert
        $this->assertSame($payload, $result);

        // A. Verify the physical database file exists
        $this->assertTrue(File::exists($databaseFilename), 'The landlord database file was not created.');

        // B. Check if tables are migrated
        $this->assertTrue(DB::connection('landlord')->getSchemaBuilder()->hasTable('landlord_users'));
        $this->assertTrue(DB::connection('landlord')->getSchemaBuilder()->hasTable('roles'));

        // C. Check if user is created
        $this->assertDatabaseHas('landlord_users', [
            'name'  => 'System Admin',
            'email' => 'admin@landlord.local',
        ], 'landlord');

        // D. Check if the Spatie role was properly seeded and assigned via the correct Guard
        $user = LandlordUser::on('landlord')->where('email', 'admin@landlord.local')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Platform Admin'));
    }

    #[Test]
    public function it_bubbles_exceptions_when_a_real_pipe_fails()
    {
        // 1. Arrange
        $databaseFilename = config('database.connections.landlord.database');
        
        if (File::exists($databaseFilename)) {
            File::delete($databaseFilename);
        }

        // We bind a fake pipe strictly for the LAST step so we know DB creation, 
        // migrations, and seeding all passed, but the pipeline crashes at the end.
        $this->app->bind(ProvisionPlatformAdmin::class, function () {
            return new class {
                public function handle($payload, $next) {
                    throw new RuntimeException('Admin provisioning failed inexplicably.');
                }
            };
        });

        $action = new InstallLandlordAction();
        $payload = [
            'database' => $databaseFilename,
            'name'     => 'Bad Admin',
            'email'    => 'bad@landlord.local',
            'password' => 'secret123',
        ];

        // 2. Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Admin provisioning failed inexplicably.');

        $action->execute($payload);
    }
}
