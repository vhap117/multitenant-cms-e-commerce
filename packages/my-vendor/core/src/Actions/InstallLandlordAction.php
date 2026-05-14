<?php

namespace VHAP\Core\Actions;

use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Log;
use VHAP\Core\Actions\Pipes\LandlordSetup\CreateLandlordDatabase;
use VHAP\Core\Actions\Pipes\LandlordSetup\RunLandlordMigrations;
use VHAP\Core\Actions\Pipes\LandlordSetup\SeedLandlordDefaultData;
use VHAP\Core\Actions\Pipes\LandlordSetup\ProvisionPlatformAdmin;
use Throwable;

class InstallLandlordAction
{
    /**
     * Executes the landlord setup pipeline.
     *
     * @param array $payload e.g., ['database' => 'vhap_landlord', 'name' => 'Admin', 'email' => '...', 'password' => '...']
     * @return array
     * @throws Throwable
     */
    public function execute(array $payload): array
    {
        try {
            // Wire the pipes together and send the payload through them
            return Pipeline::send($payload)
                ->through([
                    CreateLandlordDatabase::class,
                    RunLandlordMigrations::class,
                    SeedLandlordDefaultData::class,
                    ProvisionPlatformAdmin::class,
                ])
                ->then(function ($completedPayload) {
                    // This closure only executes if all pipes pass successfully.
                    return $completedPayload;
                });
                
        } catch (Throwable $exception) {
            // Log the critical failure for debugging
            Log::error('Landlord setup pipeline failed.', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Re-throw the exception so the caller can handle it
            throw $exception;
        }
    }
}
