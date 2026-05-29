<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\LandlordSetup;

use Illuminate\Contracts\Console\Kernel;
use VHAP\Core\Actions\Pipes\LandlordSetup\RunLandlordMigrations;
use VHAP\Core\Tests\TestCase;

class RunLandlordMigrationsTest extends TestCase
{
    public function test_it_skips_spatie_migration_if_roles_table_exists()
    {
        // Arrange
        $payload = ['database' => 'vhap_landlord'];
        $pipe = new RunLandlordMigrations();

        $schemaMock = \Mockery::mock(\Illuminate\Database\Schema\Builder::class);
        $schemaMock->shouldReceive('hasTable')->with('roles')->once()->andReturn(true);

        $connectionMock = \Mockery::mock(\Illuminate\Database\Connection::class);
        $connectionMock->shouldReceive('getSchemaBuilder')->andReturn($schemaMock);

        \Illuminate\Support\Facades\DB::shouldReceive('connection')
            ->with('landlord')
            ->andReturn($connectionMock);

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

    public function test_it_runs_spatie_migration_if_roles_table_does_not_exist()
    {
        // Arrange
        $payload = ['database' => 'vhap_landlord'];
        $pipe = new RunLandlordMigrations();

        $schemaMock = \Mockery::mock(\Illuminate\Database\Schema\Builder::class);
        $schemaMock->shouldReceive('hasTable')->with('roles')->once()->andReturn(false);
        // Allow create/drop calls inside the eval'd migration
        $schemaMock->shouldReceive('create')->zeroOrMoreTimes();
        $schemaMock->shouldReceive('drop')->zeroOrMoreTimes();
        $schemaMock->shouldReceive('dropIfExists')->zeroOrMoreTimes();

        $connectionMock = \Mockery::mock(\Illuminate\Database\Connection::class);
        $connectionMock->shouldReceive('getSchemaBuilder')->andReturn($schemaMock);

        // The action uses connection('landlord') explicitly and also relies on the default connection 
        // after temporarily changing the config to 'landlord'. So we allow any connection request 
        // to return our mock.
        \Illuminate\Support\Facades\DB::shouldReceive('connection')
            ->andReturn($connectionMock);

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
