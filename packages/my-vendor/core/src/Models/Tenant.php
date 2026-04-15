<?php

namespace VHAP\Core\Models;

use Spatie\Multitenancy\Models\Tenant as SpatieTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use VHAP\Core\Database\Factories\TenantFactory;

class Tenant extends SpatieTenant
{
    use HasFactory;

    /**
     * Instruct Laravel to use your package's specific factory.
     */
    protected static function newFactory()
    {
        return TenantFactory::new();
    }
}