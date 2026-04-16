<?php

namespace VHAP\Core\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Force Spatie's Role model to ALWAYS use the tenant connection.
     * @var string
     */
    protected $connection = 'tenant';
}