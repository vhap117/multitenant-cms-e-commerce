<?php

namespace VHAP\Core\Actions;

use VHAP\Core\Models\Tenant;
use Throwable;

class ProvisionNewTenantAction
{
    /**
     * Creates a new tenant record and pushes it through the provisioning pipeline.
     *
     * @param array $tenantData e.g., ['name' => 'Acme', 'domain' => 'acme.myapp.com', 'database' => 'tenant_acme']
     * @return Tenant
     * @throws Throwable
     */
    public function execute(\VHAP\Core\Data\ProvisionTenantData $dto, ?Tenant $tenant = null): Tenant
    {
        if (!$tenant) {
            $tenant = Tenant::where('domain', $dto->domain)->first();
        }

        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'plan' => $dto->plan->value,
                'domain' => $dto->domain,
                'database' => $dto->database,
                'provisioning_status' => 'pending',
            ]);
        } else {
            $tenant->update(['provisioning_status' => 'pending']);
        }

        // Dispatch the heavy lifting to the queue
        \VHAP\Core\Jobs\BuildTenantEnvironmentJob::dispatch($tenant, $dto);

        return $tenant;
    }
}