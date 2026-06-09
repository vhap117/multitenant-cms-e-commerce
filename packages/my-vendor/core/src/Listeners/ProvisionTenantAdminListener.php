<?php

namespace VHAP\Core\Listeners;

use VHAP\Core\Events\TenantProvisioned;
use VHAP\Core\Contracts\TenantAdminProvisioner;
use VHAP\Core\Models\User;
use Illuminate\Auth\Events\Registered;

class ProvisionTenantAdminListener
{
    public function __construct(
        protected TenantAdminProvisioner $provisioner
    ) {}

    public function handle(TenantProvisioned $event): void
    {
        $tenant = $event->tenant;
        
        $tenant->makeCurrent();

        try {
            $this->provisioner->provision($event->adminData);
        } finally {
            $tenant->forgetCurrent();
        }
    }
}
