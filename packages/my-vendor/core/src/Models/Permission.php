<?php

namespace VHAP\Core\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * Force Spatie's Permission model to ALWAYS use the tenant connection.
     * @var string
     */
    protected $connection = 'tenant';
}