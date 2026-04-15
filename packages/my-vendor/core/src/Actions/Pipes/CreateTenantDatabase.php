<?php

namespace VHAP\Core\Actions\Pipes;

use VHAP\Core\Contracts\TenantDatabaseCreator;
use VHAP\Core\Models\Tenant;
use Closure;

class CreateTenantDatabase
{
    public function __construct(
        protected TenantDatabaseCreator $creator
    ) {}

    public function handle(Tenant $tenant, Closure $next)
    {
        $this->creator->create($tenant);

        return $next($tenant);
    }
}