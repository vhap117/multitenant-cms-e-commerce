<?php

namespace VHAP\Core\Contracts;

interface BillableEntity
{
    /**
     * Get the email address to associate with the billing customer.
     *
     * @return string
     */
    public function getEmailForBilling(): string;

    /**
     * Get the name to associate with the billing customer.
     *
     * @return string
     */
    public function getNameForBilling(): string;

    /**
     * Get the vendor billing identifier (e.g., Stripe Customer ID).
     *
     * @return string|null
     */
    public function getBillingIdentifier(): ?string;

    /**
     * Update the vendor billing identifier locally.
     *
     * @param string $identifier
     * @return void
     */
    public function updateBillingIdentifier(string $identifier): void;
}
