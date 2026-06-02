<?php

namespace VHAP\Core\Services\Billing;

use VHAP\Core\Contracts\BillingProvider;
use VHAP\Core\Contracts\BillableEntity;

class CashierAdapter implements BillingProvider
{
    /**
     * Charge a single payment for a billable entity.
     */
    public function charge(BillableEntity $billable, int $amount, string $paymentMethodId, array $options = []): mixed
    {
        $this->ensureCustomerExists($billable);
        
        return $billable->charge($amount, $paymentMethodId, $options);
    }

    /**
     * Refund a previous charge.
     */
    public function refund(string $chargeId, ?int $amount = null, array $options = []): mixed
    {
        if ($amount) {
            return \Laravel\Cashier\Cashier::stripe()->refunds->create(array_merge([
                'charge' => $chargeId,
                'amount' => $amount,
            ], $options));
        }

        return \Laravel\Cashier\Cashier::stripe()->refunds->create(array_merge([
            'charge' => $chargeId,
        ], $options));
    }

    /**
     * Create a new subscription.
     */
    public function createSubscription(BillableEntity $billable, string $name, string $planId, array $options = []): mixed
    {
        $this->ensureCustomerExists($billable);

        return $billable->newSubscription($name, $planId)->create(null, $options);
    }

    /**
     * Swap a subscription to a different plan.
     */
    public function updateSubscription(BillableEntity $billable, string $name, string $planId): mixed
    {
        return $billable->subscription($name)->swap($planId);
    }

    /**
     * Cancel an active subscription.
     */
    public function cancelSubscription(BillableEntity $billable, string $name): void
    {
        $billable->subscription($name)->cancel();
    }

    /**
     * Resume a canceled subscription.
     */
    public function resumeSubscription(BillableEntity $billable, string $name): void
    {
        $billable->subscription($name)->resume();
    }

    /**
     * Check if a subscription is active.
     */
    public function hasActiveSubscription(BillableEntity $billable, string $name = 'default'): bool
    {
        return $billable->subscribed($name);
    }

    /**
     * Get all invoices for a billable entity.
     */
    public function invoices(BillableEntity $billable): array
    {
        return $billable->invoices()->toArray();
    }

    /**
     * Generate a link to the hosted customer billing portal.
     */
    public function getCustomerPortalUrl(BillableEntity $billable, string $returnUrl): string
    {
        return $billable->billingPortalUrl($returnUrl);
    }

    /**
     * Internal helper to link BillableEntity interface to Cashier's internal structures.
     */
    protected function ensureCustomerExists(BillableEntity $billable): void
    {
        if (! $billable->getBillingIdentifier()) {
            $customer = $billable->createAsStripeCustomer([
                'email' => $billable->getEmailForBilling(),
                'name' => $billable->getNameForBilling(),
            ]);

            $billable->updateBillingIdentifier($customer->id);
        }
    }
}
