<?php

namespace VHAP\Core\Actions\Pipes\Domain;

use Closure;
use VHAP\Core\Models\Tenant;
use Exception;

class ValidateDomainAvailability
{
    public function handle(object $payload, Closure $next)
    {
        // Check if the domain exists, excluding the current tenant (in case they submit their current domain)
        $isTaken = Tenant::where('domain', $payload->newDomain)
                         ->where('id', '!=', $payload->tenant->id)
                         ->where('provisioning_status', '!=', 'failed')
                         ->exists();

        if ($isTaken) {
            throw new Exception("The domain '{$payload->newDomain}' is already registered to another store.");
        }

        return $next($payload);
    }
}