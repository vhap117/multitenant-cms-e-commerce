<?php

namespace VHAP\Core\Contracts;

interface BillingProvider
{
    /**
     * Charge a single payment for a billable entity.
     *
     * @param BillableEntity $billable
     * @param int $amount
     * @param string $paymentMethodId
     * @param array $options
     * @return mixed
     */
    public function charge(BillableEntity $billable, int $amount, string $paymentMethodId, array $options = []): mixed;

    /**
     * Refund a previous charge.
     *
     * @param string $chargeId
     * @param int|null $amount
     * @param array $options
     * @return mixed
     */
    public function refund(string $chargeId, ?int $amount = null, array $options = []): mixed;

    /**
     * Create a new subscription.
     *
     * @param BillableEntity $billable
     * @param string $name
     * @param string $planId
     * @param array $options
     * @return mixed
     */
    public function createSubscription(BillableEntity $billable, string $name, string $planId, array $options = []): mixed;

    /**
     * Swap a subscription to a different plan.
     *
     * @param BillableEntity $billable
     * @param string $name
     * @param string $planId
     * @return mixed
     */
    public function updateSubscription(BillableEntity $billable, string $name, string $planId): mixed;

    /**
     * Cancel an active subscription.
     *
     * @param BillableEntity $billable
     * @param string $name
     * @return void
     */
    public function cancelSubscription(BillableEntity $billable, string $name): void;

    /**
     * Resume a canceled subscription.
     *
     * @param BillableEntity $billable
     * @param string $name
     * @return void
     */
    public function resumeSubscription(BillableEntity $billable, string $name): void;

    /**
     * Check if a subscription is active.
     *
     * @param BillableEntity $billable
     * @param string $name
     * @return bool
     */
    public function hasActiveSubscription(BillableEntity $billable, string $name = 'default'): bool;

    /**
     * Get all invoices for a billable entity.
     *
     * @param BillableEntity $billable
     * @return array
     */
    public function invoices(BillableEntity $billable): array;

    /**
     * Generate a link to the hosted customer billing portal.
     *
     * @param BillableEntity $billable
     * @param string $returnUrl
     * @return string
     */
    public function getCustomerPortalUrl(BillableEntity $billable, string $returnUrl): string;
}
