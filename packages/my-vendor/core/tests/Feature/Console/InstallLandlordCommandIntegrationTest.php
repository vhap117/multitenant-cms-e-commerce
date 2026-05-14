<?php

namespace VHAP\Core\Tests\Feature\Console;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Models\LandlordUser;
use Illuminate\Console\Command;

class InstallLandlordCommandIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Setup physical SQLite configuration for the landlord connection
        config(['database.connections.landlord' => [
            'driver'   => 'sqlite',
            'database' => __DIR__.'/landlord_command_test.sqlite', 
            'prefix'   => '',
        ]]);

        config(['permission.database_connection' => 'landlord']);

        // 2. Temporarily copy the Spatie stub to ensure migrations pass
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
        
        $databaseFilename = __DIR__.'/landlord_command_test.sqlite';
        if (File::exists($databaseFilename)) {
            File::delete($databaseFilename);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_prompts_for_input_and_orchestrates_a_full_landlord_installation()
    {
        // 1. Arrange
        $databaseFilename = config('database.connections.landlord.database');
        
        if (File::exists($databaseFilename)) {
            File::delete($databaseFilename);
        }

        // 2. Act & Assert Interactive Command
        $this->artisan('landlord:install')
            ->expectsOutput('Starting Landlord Setup Pipeline...')
            ->expectsQuestion('Super Admin Name', 'Command Admin')
            ->expectsQuestion('Super Admin Email', 'command@landlord.local')
            ->expectsQuestion('Super Admin Password (leave blank to generate random)', 'secret123')
            ->expectsOutput('Running Landlord Database Creation, Migrations, and Admin Provisioning...')
            ->expectsOutput('Landlord Environment successfully installed and provisioned!')
            ->assertExitCode(Command::SUCCESS);

        // 3. Assert Backend Systems
        // A. Verify the physical database file exists
        $this->assertTrue(File::exists($databaseFilename), 'The landlord database file was not created by the command.');

        // B. Check if tables are migrated
        $this->assertTrue(DB::connection('landlord')->getSchemaBuilder()->hasTable('landlord_users'));
        $this->assertTrue(DB::connection('landlord')->getSchemaBuilder()->hasTable('roles'));

        // C. Check if user is created
        $this->assertDatabaseHas('landlord_users', [
            'name'  => 'Command Admin',
            'email' => 'command@landlord.local',
        ], 'landlord');

        // D. Check if Spatie role is assigned
        $user = LandlordUser::on('landlord')->where('email', 'command@landlord.local')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Platform Admin'));
    }

    #[Test]
    public function it_generates_a_random_password_if_left_blank()
    {
        // 1. Arrange
        $databaseFilename = config('database.connections.landlord.database');
        if (File::exists($databaseFilename)) {
            File::delete($databaseFilename);
        }

        // 2. Act
        $this->artisan('landlord:install')
            ->expectsQuestion('Super Admin Name', 'Random Admin')
            ->expectsQuestion('Super Admin Email', 'random@landlord.local')
            ->expectsQuestion('Super Admin Password (leave blank to generate random)', '') // Leave blank
            // We can't strictly match the password output since it's random, but we can verify it doesn't fail
            ->assertExitCode(Command::SUCCESS);

        // 3. Assert
        $user = LandlordUser::on('landlord')->where('email', 'random@landlord.local')->first();
        $this->assertNotNull($user);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('', $user->password) === false, 'Password should not be empty string');
    }

    #[Test]
    public function it_catches_exceptions_and_returns_failure_code()
    {
        // 1. Arrange
        $databaseFilename = config('database.connections.landlord.database');
        if (File::exists($databaseFilename)) {
            File::delete($databaseFilename);
        }

        // Deliberately force the Action to throw an exception by mocking it
        $this->mock(\VHAP\Core\Actions\InstallLandlordAction::class, function ($mock) {
            $mock->shouldReceive('execute')->andThrow(new \Exception('Forced testing exception!'));
        });

        // 2. Act & Assert
        $this->artisan('landlord:install')
            ->expectsQuestion('Super Admin Name', 'Fail Admin')
            ->expectsQuestion('Super Admin Email', 'fail@landlord.local')
            ->expectsQuestion('Super Admin Password (leave blank to generate random)', 'secret123')
            ->expectsOutput('Running Landlord Database Creation, Migrations, and Admin Provisioning...')
            ->expectsOutput('Landlord Setup failed: Forced testing exception!')
            ->assertExitCode(Command::FAILURE);
    }
}
