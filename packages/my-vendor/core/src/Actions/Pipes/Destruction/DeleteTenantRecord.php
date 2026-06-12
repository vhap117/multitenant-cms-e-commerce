<?php

namespace VHAP\Core\Actions\Pipes\Destruction;

use Closure;
use VHAP\Core\Models\Tenant;

class DeleteTenantRecord
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // Completely wipe the tenant record from the central platform database (bypass soft deletes)
        $tenant->forceDelete();

        return $next($tenant);
    }
}