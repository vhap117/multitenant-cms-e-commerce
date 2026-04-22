<?php

namespace VHAP\Core\Actions\Pipes\Destruction;

use Closure;
use VHAP\Core\Models\Tenant;

class DeleteTenantRecord
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // Delete the tenant record from the central platform database
        $tenant->delete();

        return $next($tenant);
    }
}