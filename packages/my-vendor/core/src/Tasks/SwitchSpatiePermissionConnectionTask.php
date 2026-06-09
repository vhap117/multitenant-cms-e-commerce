<?php

namespace VHAP\Core\Tasks;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Spatie\Permission\PermissionRegistrar;

class SwitchSpatiePermissionConnectionTask implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        config(['permission.database_connection' => 'tenant']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function forgetCurrent(): void
    {
        config(['permission.database_connection' => config('database.default')]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
