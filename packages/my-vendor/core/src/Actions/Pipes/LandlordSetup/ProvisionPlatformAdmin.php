<?php

namespace VHAP\Core\Actions\Pipes\LandlordSetup;

use Closure;
use VHAP\Core\Contracts\LandlordAdminProvisioner;

class ProvisionPlatformAdmin
{
    protected LandlordAdminProvisioner $provisioner;

    /**
     * Inject the provisioner bound in CoreServiceProvider.
     */
    public function __construct(LandlordAdminProvisioner $provisioner)
    {
        $this->provisioner = $provisioner;
    }

    public function handle(array $payload, Closure $next)
    {
        // Delegate user creation to the provisioner strategy
        $this->provisioner->provision($payload);

        // Pass the payload down to the next pipe
        return $next($payload);
    }
}
