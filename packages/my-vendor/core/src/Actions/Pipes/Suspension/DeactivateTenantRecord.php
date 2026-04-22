<?php

namespace VHAP\Core\Actions\Pipes\Suspension;

use Closure;
use VHAP\Core\Models\Tenant;

class DeactivateTenantRecord
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // Update the record on the landlord database
        $tenant->update(['is_active' => false]);

        // Pass the updated tenant to the next pipe
        return $next($tenant);
    }
}