<?php

namespace VHAP\Core\Actions;

use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Domain\ValidateDomainAvailability;
use VHAP\Core\Actions\Pipes\Domain\UpdateTenantRecord;
use VHAP\Core\Actions\Pipes\Domain\UpdateWebserverConfig;
use Throwable;

class UpdateTenantDomainAction
{
    /**
     * Safely updates a tenant's domain and triggers any necessary infrastructure changes.
     *
     * @param Tenant $tenant
     * @param string $newDomain
     * @return Tenant
     * @throws Throwable
     */
    public function execute(Tenant $tenant, string $newDomain): Tenant
    {
        return DB::connection('landlord')->transaction(function () use ($tenant, $newDomain) {
            // 1. We wrap the required data into a single object so it can travel down the pipeline
            $payload = (object) [
                'tenant' => $tenant,
                'newDomain' => $newDomain,
            ];

            try {
                Pipeline::send($payload)
                    ->through([
                        ValidateDomainAvailability::class,
                        UpdateTenantRecord::class,
                        // UpdateWebserverConfig::class, // <-- Uncomment when you implement server API
                    ])
                    ->then(function ($payload) {
                        Log::info("Tenant ID {$payload->tenant->id} successfully changed domain to {$payload->newDomain}.");
                    });

                // Return the fresh tenant model from the database
                return $tenant->fresh();

            } catch (Throwable $exception) {
                Log::error('Tenant domain update failed.', [
                    'tenant_id' => $tenant->id,
                    'attempted_domain' => $newDomain,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        });
    }
}