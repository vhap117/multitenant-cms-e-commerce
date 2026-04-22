<?php

namespace VHAP\Core\Actions\Pipes\Destruction;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use VHAP\Core\Models\Tenant;

class DropTenantDatabase
{
    public function handle(Tenant $tenant, Closure $next)
    {
        $databaseName = $tenant->database;
        $driver = config('database.connections.tenant.driver');

        // 1. Force Laravel to disconnect from the tenant database if it's currently connected
        DB::purge('tenant');

        if ($driver === 'mysql') {
            // 2. Use the Landlord connection to tell the MySQL server to drop the DB
            DB::connection('landlord')->statement("DROP DATABASE IF EXISTS `{$databaseName}`");
        } 
        elseif ($driver === 'sqlite') {
            // For local testing, we just delete the physical .sqlite file
            if (File::exists($databaseName)) {
                File::delete($databaseName);
            }
        }

        return $next($tenant);
    }
}