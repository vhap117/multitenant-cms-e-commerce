<?php

namespace VHAP\Core\Actions\Pipes;

use Closure;
use Illuminate\Support\Facades\Artisan;
use VHAP\Core\Models\Tenant;

class RunTenantMigrations
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // This is the magic Spatie method!
        // It dynamically updates the 'tenant' connection in config/database.php
        // to point to $tenant->database behind the scenes.
        $tenant->makeCurrent();

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => database_path('migrations/tenant'),
            '--force'    => true,
        ]);

        return $next($tenant);
    }
}
