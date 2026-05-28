<?php

namespace VHAP\Core\Tests\Feature\Listeners;

use VHAP\Core\Tests\TestCase;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookReceived;

class StripeWebhookIntegrationTest extends TestCase
{
    /** @test */
    public function it_suspends_a_tenant_when_payment_fails()
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'domain' => 'active-tenant.com',
            'is_active' => true,
            'stripe_id' => 'cus_integration_123'
        ]);

        $suspendMock = \Mockery::mock(\VHAP\Core\Actions\SuspendTenantAction::class);
        $suspendMock->shouldReceive('execute')->once();
        $this->app->instance(\VHAP\Core\Actions\SuspendTenantAction::class, $suspendMock);

        // Act: Fire the event exactly as Cashier would
        $payload = [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'customer' => 'cus_integration_123'
                ]
            ]
        ];

        Event::dispatch(new WebhookReceived($payload));
    }

    /** @test */
    public function it_reactivates_a_tenant_when_payment_succeeds()
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'domain' => 'suspended-tenant.com',
            'is_active' => false,
            'stripe_id' => 'cus_integration_456'
        ]);

        $reactivateMock = \Mockery::mock(\VHAP\Core\Actions\ReactivateTenantAction::class);
        $reactivateMock->shouldReceive('execute')->once();
        $this->app->instance(\VHAP\Core\Actions\ReactivateTenantAction::class, $reactivateMock);

        // Act: Fire the event exactly as Cashier would
        $payload = [
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'customer' => 'cus_integration_456'
                ]
            ]
        ];

        Event::dispatch(new WebhookReceived($payload));
    }
}
