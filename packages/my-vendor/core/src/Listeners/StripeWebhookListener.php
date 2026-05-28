<?php

namespace VHAP\Core\Listeners;

use Laravel\Cashier\Events\WebhookReceived;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\SuspendTenantAction;
use VHAP\Core\Actions\ReactivateTenantAction;
use VHAP\Core\Actions\DestroyTenantEnvironmentAction;
use Illuminate\Support\Facades\Log;

class StripeWebhookListener
{
    protected SuspendTenantAction $suspendAction;
    protected ReactivateTenantAction $reactivateAction;
    protected DestroyTenantEnvironmentAction $destroyAction;

    public function __construct(
        SuspendTenantAction $suspendAction,
        ReactivateTenantAction $reactivateAction,
        DestroyTenantEnvironmentAction $destroyAction
    ) {
        $this->suspendAction = $suspendAction;
        $this->reactivateAction = $reactivateAction;
        $this->destroyAction = $destroyAction;
    }

    /**
     * Handle the incoming Cashier webhook event.
     */
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;
        $type = $payload['type'] ?? '';

        switch ($type) {
            case 'invoice.payment_failed':
                $this->handlePaymentFailed($payload);
                break;
            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($payload);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($payload);
                break;
        }
    }

    protected function handlePaymentFailed(array $payload): void
    {
        $tenant = $this->findTenantByStripeId($payload['data']['object']['customer'] ?? null);

        if ($tenant && $tenant->is_active) {
            Log::warning("Stripe payment failed for Tenant: {$tenant->domain}. Suspending environment.");
            $this->suspendAction->execute($tenant, 'Payment failed.');
        }
    }

    protected function handlePaymentSucceeded(array $payload): void
    {
        // Only reactivate if they were actually suspended for billing reasons
        $tenant = $this->findTenantByStripeId($payload['data']['object']['customer'] ?? null);

        if ($tenant && !$tenant->is_active) {
            Log::info("Stripe payment succeeded for suspended Tenant: {$tenant->domain}. Reactivating environment.");
            $this->reactivateAction->execute($tenant);
        }
    }

    protected function handleSubscriptionDeleted(array $payload): void
    {
        $tenant = $this->findTenantByStripeId($payload['data']['object']['customer'] ?? null);

        if ($tenant) {
            Log::error("Stripe subscription deleted for Tenant: {$tenant->domain}. Destroying environment.");
            // DANGER: In a real app you might want to wait 30 days before full destruction, 
            // but per current requirements, we trigger destruction on subscription delete.
            $this->destroyAction->execute($tenant);
        }
    }

    /**
     * Retrieve the billable Tenant model based on the Stripe Customer ID.
     */
    protected function findTenantByStripeId(?string $stripeId): ?Tenant
    {
        if (!$stripeId) {
            return null;
        }

        return Tenant::on('landlord')->where('stripe_id', $stripeId)->first();
    }
}
