<?php

namespace VHAP\Core\Actions\Pipes\Reactivation;

use Closure;
use VHAP\Core\Models\Tenant;

class MarkTenantActiveRecord
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // Update the record on the landlord database
        $tenant->update(['is_active' => true]);

        return $next($tenant);
    }
}