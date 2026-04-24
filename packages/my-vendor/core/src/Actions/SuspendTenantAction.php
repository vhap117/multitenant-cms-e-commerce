<?php

namespace VHAP\Core\Actions;

use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Suspension\DeactivateTenantRecord;
use VHAP\Core\Actions\Pipes\Suspension\TerminateTenantSessions;
use VHAP\Core\Actions\Pipes\Suspension\DispatchSuspensionNotification;
use Throwable;

class SuspendTenantAction
{
    /**
     * Executes the suspension pipeline for a given tenant.
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
                        DeactivateTenantRecord::class,
                        TerminateTenantSessions::class,
                        DispatchSuspensionNotification::class,
                    ])
                    ->then(function (Tenant $suspendedTenant) {
                        Log::info("Tenant {$suspendedTenant->domain} has been successfully suspended.");
                        return $suspendedTenant;
                    });
            } catch (Throwable $exception) {
                Log::error('Tenant suspension pipeline failed.', [
                    'tenant_id' => $tenant->id,
                    'domain' => $tenant->domain,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        });
    }
}