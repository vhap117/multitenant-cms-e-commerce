<?php

namespace VHAP\Core\Contracts;

use Spatie\Multitenancy\Models\Tenant;

interface TenantDatabaseCreator
{
    public function create(Tenant $tenant): void;
}