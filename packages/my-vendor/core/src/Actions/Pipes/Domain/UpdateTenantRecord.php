<?php

namespace VHAP\Core\Actions\Pipes\Domain;

use Closure;

class UpdateTenantRecord
{
    public function handle(object $payload, Closure $next)
    {
        // Update the domain on the central Landlord database
        $payload->tenant->update([
            'domain' => $payload->newDomain
        ]);

        return $next($payload);
    }
}