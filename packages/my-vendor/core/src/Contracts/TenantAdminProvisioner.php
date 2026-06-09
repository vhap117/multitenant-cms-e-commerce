<?php

namespace VHAP\Core\Contracts;

interface TenantAdminProvisioner
{
    /**
     * Bootstraps the initial root user and assigns roles for a new tenant.
     */
    public function provision(\VHAP\Core\Data\TenantAdminUserData $adminData): void;
}
