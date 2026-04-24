<?php

namespace VHAP\Core\Actions\Pipes\Provision;

use Closure;
use Spatie\Permission\Models\Role;
use VHAP\Core\Models\Tenant;

class SeedTenantDefaultData
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // 1. Ensure Spatie models operate on the newly migrated tenant database
        $tenant->makeCurrent();

        // 2. Safely bootstrap required tenant core roles
        Role::on('tenant')->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        return $next($tenant);
    }
}
