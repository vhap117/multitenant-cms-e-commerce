<?php

namespace VHAP\Core\Actions\Pipes\Provision;

use Closure;
use Illuminate\Support\Facades\Artisan;
use VHAP\Core\Models\Tenant;

class RunTenantMigrations
{
    public function handle(Tenant $tenant, Closure $next)
    {
        $tenant->makeCurrent();

        // 1. Fetch the path dynamically from config, falling back to the default
        $migrationPath = config(
            'core.tenant_migrations_path', 
            database_path('migrations/tenant')
        );

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => $migrationPath, 
            '--realpath' => true,
            '--force'    => true,
        ]);

        return $next($tenant);
    }
}
