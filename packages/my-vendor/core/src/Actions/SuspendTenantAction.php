<?php

namespace VHAP\Core\Actions;

use Illuminate\Support\Facades\Pipeline;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Suspension\DeactivateTenantRecord;
use VHAP\Core\Actions\Pipes\Suspension\TerminateTenantSessions;
use VHAP\Core\Actions\Pipes\Suspension\DispatchSuspensionNotification;

class SuspendTenantAction
{
    /**
     * Executes the suspension pipeline for a given tenant.
     */
    public function execute(Tenant $tenant): Tenant
    {
        return Pipeline::send($tenant)
            ->through([
                DeactivateTenantRecord::class,
                TerminateTenantSessions::class,
                DispatchSuspensionNotification::class,
            ])
            ->then(function (Tenant $suspendedTenant) {
                // The pipeline finished successfully
                return $suspendedTenant;
            });
    }
}