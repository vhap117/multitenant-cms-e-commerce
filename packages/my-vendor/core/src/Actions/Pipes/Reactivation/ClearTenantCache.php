<?php

namespace VHAP\Core\Actions\Pipes\Reactivation;

use Closure;
use VHAP\Core\Models\Tenant;
use Spatie\Permission\PermissionRegistrar;

class ClearTenantCache
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // 1. Switch into the tenant's specific context
        $tenant->makeCurrent();

        // 2. Clear Spatie's permission cache for this specific database
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Note: If you are using Redis with a tenant-specific prefix, 
        // you could also call Cache::flush() here to clear their application cache.

        // 3. Revert back to the landlord context
        $tenant->forgetCurrent();

        return $next($tenant);
    }
}