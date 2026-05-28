<?php

namespace VHAP\Core\Actions\Billing;

use VHAP\Core\Models\Tenant;
use Throwable;

class CancelTenantSubscriptionAction
{
    /**
     * Gracefully cancel a tenant's subscription.
     *
     * @param Tenant $tenant
     * @param string $subscriptionName
     * @return \Laravel\Cashier\Subscription
     * @throws Throwable
     */
    public function execute(Tenant $tenant, string $subscriptionName = 'default')
    {
        $subscription = $tenant->subscription($subscriptionName);

        if (!$subscription) {
            throw new \Exception("Tenant does not have an active {$subscriptionName} subscription.");
        }

        // Cancel the subscription (it will remain active until the end of the billing period)
        return $subscription->cancel();
    }
}
