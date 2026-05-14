<?php

namespace VHAP\Core\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * Dynamically resolve the connection based on Spatie's configuration, 
     * allowing this model to be used in both Tenant and Landlord environments.
     */
    public function getConnectionName()
    {
        return config('permission.database_connection') ?: config('database.default');
    }
}