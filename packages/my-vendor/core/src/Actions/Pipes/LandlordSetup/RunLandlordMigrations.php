<?php

namespace VHAP\Core\Actions\Pipes\LandlordSetup;

use Closure;
use Illuminate\Support\Facades\Artisan;

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
        // Run the migrations against the landlord connection.
        // The migration paths are automatically loaded by CoreServiceProvider.
        Artisan::call('migrate', [
            '--database' => 'landlord',
            '--force'    => true,
        ]);

        return $next($payload);
    }
}
