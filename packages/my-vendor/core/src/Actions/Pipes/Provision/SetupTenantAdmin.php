<?php

namespace VHAP\Core\Actions\Pipes\Provision;

use Closure;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Contracts\TenantAdminProvisioner;

class SetupTenantAdmin
{
    public function __construct(
        protected TenantAdminProvisioner $provisioner
    ) {}

    public function handle(Tenant $tenant, Closure $next)
    {
        // Spatie intercepts this, binding the tenant database to the runtime memory
        $tenant->makeCurrent();

        // Delegate the actual User creation and Spatie Permission logic 
        // to a dedicated provisioner strategy so the pipe stays clean
        $this->provisioner->provision($tenant);

        return $next($tenant);
    }
}
