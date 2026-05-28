<?php

namespace VHAP\Core\Actions\Billing;

use VHAP\Core\Models\Tenant;
use Throwable;

class SwapSubscriptionPlanAction
{
    /**
     * Swap a tenant's subscription to a new plan (upgrade/downgrade).
     *
     * @param Tenant $tenant
     * @param string $newPlanId The Stripe Price ID (price_12345)
     * @param string $subscriptionName Typically 'default'
     * @return \Laravel\Cashier\Subscription
     * @throws Throwable
     */
    public function execute(Tenant $tenant, string $newPlanId, string $subscriptionName = 'default')
    {
        $subscription = $tenant->subscription($subscriptionName);

        if (!$subscription) {
            throw new \Exception("Tenant does not have an active {$subscriptionName} subscription.");
        }

        // Swap the plan. Stripe will automatically prorate the difference.
        return $subscription->swap($newPlanId);
    }
}
