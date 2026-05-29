<?php

namespace VHAP\Core\Models;

use Spatie\Multitenancy\Models\Tenant as SpatieTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use VHAP\Core\Database\Factories\TenantFactory;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends SpatieTenant
{
    use HasFactory, Billable, SoftDeletes;

    /**
     * Force this model to ALWAYS use the landlord database connection.
     * * @var string
     */
    protected $connection = 'landlord';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'domain',
        'database',
        'is_active',
    ];

    /**
     * Instruct Laravel to use your package's specific factory.
     */
    protected static function newFactory()
    {
        return TenantFactory::new();
    }
}