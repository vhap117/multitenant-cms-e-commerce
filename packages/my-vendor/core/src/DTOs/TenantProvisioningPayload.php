<?php

namespace MyVendor\Core\DTOs;

use Spatie\Multitenancy\Models\Tenant;

class TenantProvisioningPayload
{
    public function __construct(
        public Tenant $tenant,
        public array $adminData
    ) {}
}
