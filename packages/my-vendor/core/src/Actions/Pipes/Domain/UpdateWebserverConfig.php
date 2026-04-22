<?php

namespace VHAP\Core\Actions\Pipes\Domain;

use Closure;
use Illuminate\Support\Facades\Http;
// use Laravel\Forge\Forge; // If using the Forge SDK

class UpdateWebserverConfig
{
    public function handle(object $payload, Closure $next)
    {
        /*
         * Example of what you would put here in the future:
         * * $forge = new Forge('your-api-token');
         * $siteId = config('services.forge.site_id');
         * * // 1. Add the domain alias to Nginx
         * $forge->createSiteAlias($siteId, ['name' => $payload->newDomain]);
         * * // 2. Request a new SSL certificate for the new domain
         * $forge->obtainLetsEncryptCertificate($siteId, ['domains' => [$payload->newDomain]]);
         */

        return $next($payload);
    }
}