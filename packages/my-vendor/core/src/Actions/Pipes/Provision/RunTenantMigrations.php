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

        // 1. Run Spatie Permission migration on the fly in memory if roles table does not exist inside tenant DB
        if (!\Illuminate\Support\Facades\Schema::connection('tenant')->hasTable('roles')) {
            // Check host application first, then fall back to core package
            $stubPath = base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub');
            if (!file_exists($stubPath)) {
                $stubPath = __DIR__ . '/../../../../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
            }

            if (file_exists($stubPath)) {
                $code = file_get_contents($stubPath);
                
                // Strip the opening php tag so it can be evaluated
                $code = preg_replace('/^<\?php/', '', $code);
                
                // Temporarily override default connection to 'tenant'
                $originalDefault = config('database.default');
                config(['database.default' => 'tenant']);
                
                try {
                    $migration = eval($code);
                    if ($migration instanceof \Illuminate\Database\Migrations\Migration) {
                        $migration->up();
                    }
                } finally {
                    config(['database.default' => $originalDefault]);
                }
            }
        }

        // 2. Fetch the path dynamically from config, falling back to the default
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
