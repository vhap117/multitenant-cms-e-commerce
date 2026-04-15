<?php

namespace VHAP\Core\Contracts;

use VHAP\Core\Models\Tenant;

interface TenantDatabaseCreator
{
    public function create(Tenant $tenant): void;
}