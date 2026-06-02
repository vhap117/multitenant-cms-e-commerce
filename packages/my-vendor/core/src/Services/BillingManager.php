<?php

namespace VHAP\Core\Services;

use VHAP\Core\Contracts\BillingProvider;
use VHAP\Core\Contracts\BillableEntity;

class BillingManager
{
    /**
     * The billing provider implementation.
     */
    protected BillingProvider $provider;

    /**
     * Composition: The manager holds a reference to the provider strategy.
     */
    public function __construct(BillingProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Charge a single payment for a user.
     */
    public function chargeUser(BillableEntity $user, int $amount, string $paymentMethodId, array $options = []): mixed
    {
        // Pre-charge logic (e.g. logging transaction start, verifying permissions)
        
        $result = $this->provider->charge($user, $amount, $paymentMethodId, $options);
        
        // Post-charge logic (e.g. dispatching billing events)
        
        return $result;
    }

    /**
     * Subscribe a user to a plan.
     */
    public function subscribeUser(BillableEntity $user, string $subscriptionName, string $planId, array $options = []): mixed
    {
        return $this->provider->createSubscription($user, $subscriptionName, $planId, $options);
    }

    /**
     * Swap a user subscription to a different plan.
     */
    public function updateSubscription(BillableEntity $user, string $subscriptionName, string $planId): mixed
    {
        return $this->provider->updateSubscription($user, $subscriptionName, $planId);
    }

    /**
     * Cancel an active user subscription.
     */
    public function cancelUserSubscription(BillableEntity $user, string $subscriptionName): void
    {
        $this->provider->cancelSubscription($user, $subscriptionName);
    }

    /**
     * Resume a canceled user subscription.
     */
    public function resumeUserSubscription(BillableEntity $user, string $subscriptionName): void
    {
        $this->provider->resumeSubscription($user, $subscriptionName);
    }

    /**
     * Check if a user is subscribed.
     */
    public function isSubscribed(BillableEntity $user, string $subscriptionName = 'default'): bool
    {
        return $this->provider->hasActiveSubscription($user, $subscriptionName);
    }

    /**
     * Get all invoices for a user.
     */
    public function getInvoices(BillableEntity $user): array
    {
        return $this->provider->invoices($user);
    }

    /**
     * Generate customer billing portal URL.
     */
    public function getPortalUrl(BillableEntity $user, string $returnUrl): string
    {
        return $this->provider->getCustomerPortalUrl($user, $returnUrl);
    }
}
