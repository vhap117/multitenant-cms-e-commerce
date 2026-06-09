<?php

namespace VHAP\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use VHAP\Core\Models\Tenant;

class TenantProvisioned
{
    use Dispatchable, SerializesModels;

    public function __construct(public Tenant $tenant, public \VHAP\Core\Data\TenantAdminUserData $adminData)
    {
    }
}
