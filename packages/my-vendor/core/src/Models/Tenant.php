<?php

namespace VHAP\Core\Models;

use Spatie\Multitenancy\Models\Tenant as SpatieTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use VHAP\Core\Database\Factories\TenantFactory;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\SoftDeletes;

use VHAP\Core\Contracts\BillableEntity;
use VHAP\Core\Enums\TenantPlan;

class Tenant extends SpatieTenant implements BillableEntity
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
        'email',
        'plan',
        'domain',
        'database',
        'is_active',
        'provisioning_status',
        'provisioning_data',
    ];

    /**
     * Cast database attributes to local types/enums.
     */
    protected function casts(): array
    {
        return [
            'plan' => TenantPlan::class,
            'is_active' => 'boolean',
            'provisioning_data' => 'encrypted:array',
        ];
    }

    /**
     * Unified subscription check: Checks if the user is on the Free tier local plan,
     * or delegates the query to Stripe Cashier if they are on a paid plan.
     */
    public function isSubscribed(string $subscriptionName = 'default'): bool
    {
        // 1. Free plan grants access natively without making Stripe API requests
        if ($this->plan === TenantPlan::FREE) {
            return true;
        }

        // 2. Otherwise, check Stripe subscriptions via Cashier
        return $this->subscribed($subscriptionName);
    }

    /**
     * Instruct Laravel to use your package's specific factory.
     */
    protected static function newFactory()
    {
        return TenantFactory::new();
    }

    /*
    |--------------------------------------------------------------------------
    | BillableEntity Contract Implementation
    |--------------------------------------------------------------------------
    */

    public function getEmailForBilling(): string
    {
        return $this->email;
    }

    public function getNameForBilling(): string
    {
        return $this->name;
    }

    public function getBillingIdentifier(): ?string
    {
        return $this->stripe_id;
    }

    public function updateBillingIdentifier(string $identifier): void
    {
        $this->update(['stripe_id' => $identifier]);
    }
}