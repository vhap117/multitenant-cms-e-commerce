<?php

namespace VHAP\Core\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
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