<?php

namespace VHAP\Core\Actions\Pipes\Suspension;

use Closure;
use Illuminate\Support\Facades\DB;
use VHAP\Core\Models\Tenant;

class TerminateTenantSessions
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // 1. Switch the active connection to this specific tenant
        $tenant->makeCurrent();

        // 2. Truncate the sessions table in the tenant's database
        // (Assuming you are using the database session driver)
        DB::connection('tenant')->table('sessions')->truncate();

        // 3. Revert the connection back to default
        $tenant->forgetCurrent();

        return $next($tenant);
    }
}