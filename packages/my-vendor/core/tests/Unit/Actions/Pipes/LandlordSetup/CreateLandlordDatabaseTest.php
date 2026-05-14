<?php

namespace VHAP\Core\Tests\Unit\Actions\Pipes\LandlordSetup;

use Mockery;
use VHAP\Core\Tests\TestCase;
use VHAP\Core\Contracts\LandlordDatabaseCreator;
use VHAP\Core\Actions\Pipes\LandlordSetup\CreateLandlordDatabase;

class CreateLandlordDatabaseTest extends TestCase
{
    public function test_it_delegates_creation_to_strategy_and_calls_next_pipe()
    {
        // Arrange
        $payload = ['database' => 'vhap_landlord'];
        
        // 1. Mock the Creator interface so we don't do real database operations
        $mockCreator = Mockery::mock(LandlordDatabaseCreator::class);
        
        // Assert the pipe delegates the DB creation to the injected strategy
        $mockCreator->shouldReceive('create')
            ->once()
            ->with('vhap_landlord');

        $pipe = new CreateLandlordDatabase($mockCreator);

        // 2. Setup the $next closure to ensure the pipeline continues
        $nextWasCalled = false;
        $nextPipe = function ($passedPayload) use (&$nextWasCalled, $payload) {
            $nextWasCalled = true;
            
            // Assert the pipe passed the exact same payload down the line
            $this->assertSame($payload, $passedPayload);
            
            return 'pipeline_continued';
        };

        // Act
        $result = $pipe->handle($payload, $nextPipe);

        // Assert
        $this->assertTrue($nextWasCalled, 'The next closure in the pipeline was never called.');
        $this->assertEquals('pipeline_continued', $result, 'The pipe failed to return the result of the next closure.');
    }
}
