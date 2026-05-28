<?php

namespace VHAP\Core\Actions\Pipes\LandlordSetup;

use Closure;
use VHAP\Core\Models\Role;

class SeedLandlordDefaultData
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
        // Safely bootstrap required landlord core roles
        Role::on('landlord')->firstOrCreate([
            'name' => 'Platform Admin', 
            'guard_name' => 'landlord'
        ]);

        return $next($payload);
    }
}
