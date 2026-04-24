<?php

namespace VHAP\Core\Actions;

use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Reactivation\MarkTenantActiveRecord;
use VHAP\Core\Actions\Pipes\Reactivation\ClearTenantCache;
use VHAP\Core\Actions\Pipes\Reactivation\DispatchReactivationEmail;
use Throwable;

class ReactivateTenantAction
{
    /**
     * Executes the reactivation pipeline for a suspended tenant.
     *
     * @param Tenant $tenant
     * @return Tenant
     * @throws Throwable
     */
    public function execute(Tenant $tenant): Tenant
    {
        return DB::connection('landlord')->transaction(function () use ($tenant) {
            try {
                return Pipeline::send($tenant)
                    ->through([
                        MarkTenantActiveRecord::class,
                        ClearTenantCache::class,
                        DispatchReactivationEmail::class,
                    ])
                    ->then(function (Tenant $reactivatedTenant) {
                        Log::info("Tenant {$reactivatedTenant->domain} has been successfully reactivated.");
                        
                        return $reactivatedTenant;
                    });
            } catch (Throwable $exception) {
                Log::error('Tenant reactivation pipeline failed.', [
                    'tenant_id' => $tenant->id,
                    'domain' => $tenant->domain,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        });
    }
}