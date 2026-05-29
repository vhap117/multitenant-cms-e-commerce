<?php

namespace VHAP\Core\Actions\Pipes\LandlordSetup;

use Closure;
use Illuminate\Support\Facades\Schema;

class RunLandlordMigrations
{
    /**
     * Handle the pipeline execution.
     *
     * @param array $payload
     * @param Closure $next
     * @return mixed
     */
    public function handle(array $payload, Closure $next)
    {
        // 1. Run Spatie Permission migration on the fly in memory if roles table does not exist
        if (!Schema::connection('landlord')->hasTable('roles')) {
            // 1. Try host application's vendor directory
            $stubPath = base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub');
            // 2. Fallback: package's own vendor directory (for local package testing)
            if (!file_exists($stubPath)) {
                $stubPath = __DIR__ . '/../../../../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
            }
            if (file_exists($stubPath)) {
                $code = file_get_contents($stubPath);
                
                // Strip the opening php tag so it can be evaluated
                $code = preg_replace('/^<\?php/', '', $code);
                
                // Temporarily override the default connection to landlord so Schema queries run on the correct connection
                $originalDefault = config('database.default');
                config(['database.default' => 'landlord']);
                
                try {
                    $migration = eval($code);
                    if ($migration instanceof \Illuminate\Database\Migrations\Migration) {
                        $migration->up();
                    }
                } finally {
                    // Restore original default connection
                    config(['database.default' => $originalDefault]);
                }
            }
        }

        return $next($payload);
    }
}