<?php

namespace VHAP\Core\Contracts;

use Spatie\Multitenancy\Models\Tenant;

interface TenantAdminProvisioner
{
    /**
     * Bootstraps the initial root user and assigns roles for a new tenant.
     */
    public function provision(Tenant $tenant): void;
}
