<?php

namespace VHAP\Core\Actions\Billing;

use VHAP\Core\Models\Tenant;
use Throwable;

class CreateTenantSubscriptionAction
{
    /**
     * Subscribe a tenant to a specific Stripe plan using a payment method.
     *
     * @param Tenant $tenant
     * @param string $paymentMethodId The Stripe PaymentMethod ID (pm_12345)
     * @param string $planId The Stripe Price ID (price_12345)
     * @param string $subscriptionName Typically 'default'
     * @return \Laravel\Cashier\Subscription
     * @throws Throwable
     */
    public function execute(Tenant $tenant, string $paymentMethodId, string $planId, string $subscriptionName = 'default')
    {
        // Check if the tenant already has a subscription with this name
        if ($tenant->subscribed($subscriptionName)) {
            throw new \Exception("Tenant is already subscribed to the {$subscriptionName} plan.");
        }

        // We use the 'landlord' connection specifically because billing runs centrally
        return $tenant->newSubscription($subscriptionName, $planId)
                      ->create($paymentMethodId);
    }
}
