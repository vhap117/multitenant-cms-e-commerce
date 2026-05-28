<?php

namespace VHAP\Core\Tests\Unit\Listeners;

use VHAP\Core\Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;
use VHAP\Core\Listeners\StripeWebhookListener;
use VHAP\Core\Actions\SuspendTenantAction;
use VHAP\Core\Actions\ReactivateTenantAction;
use VHAP\Core\Actions\DestroyTenantEnvironmentAction;
use VHAP\Core\Models\Tenant;

class StripeWebhookListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_ignores_webhooks_without_matching_tenant()
    {
        // Mock the listener and override findTenantByStripeId to avoid hitting DB
        $suspendMock = Mockery::mock(SuspendTenantAction::class);
        $reactivateMock = Mockery::mock(ReactivateTenantAction::class);
        $destroyMock = Mockery::mock(DestroyTenantEnvironmentAction::class);

        // Expect no actions to be executed
        $suspendMock->shouldNotReceive('execute');
        $reactivateMock->shouldNotReceive('execute');
        $destroyMock->shouldNotReceive('execute');

        $listener = Mockery::mock(StripeWebhookListener::class, [$suspendMock, $reactivateMock, $destroyMock])->makePartial();
        $listener->shouldAllowMockingProtectedMethods();
        $listener->shouldReceive('findTenantByStripeId')->andReturn(null);

        $event = new WebhookReceived(['type' => 'invoice.payment_failed', 'data' => ['object' => ['customer' => 'cus_invalid']]]);
        $listener->handle($event);

        $this->assertTrue(true); // Satisfy PHPUnit's risky test warning
    }

    /** @test */
    public function it_suspends_tenant_on_payment_failed()
    {
        Log::shouldReceive('warning')->once(); // Mock Log facade

        $tenantMock = Mockery::mock(Tenant::class)->makePartial();
        $tenantMock->is_active = true;
        $tenantMock->domain = 'test.com';

        $suspendMock = Mockery::mock(SuspendTenantAction::class);
        $suspendMock->shouldReceive('execute')->with($tenantMock, 'Payment failed.')->once();
        
        $reactivateMock = Mockery::mock(ReactivateTenantAction::class);
        $destroyMock = Mockery::mock(DestroyTenantEnvironmentAction::class);

        $listener = Mockery::mock(StripeWebhookListener::class, [$suspendMock, $reactivateMock, $destroyMock])->makePartial();
        $listener->shouldAllowMockingProtectedMethods();
        $listener->shouldReceive('findTenantByStripeId')->with('cus_123')->andReturn($tenantMock);

        $event = new WebhookReceived(['type' => 'invoice.payment_failed', 'data' => ['object' => ['customer' => 'cus_123']]]);
        $listener->handle($event);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_reactivates_suspended_tenant_on_payment_succeeded()
    {
        Log::shouldReceive('info')->once(); // Mock Log facade

        $tenantMock = Mockery::mock(Tenant::class)->makePartial();
        $tenantMock->is_active = false;
        $tenantMock->domain = 'test.com';

        $suspendMock = Mockery::mock(SuspendTenantAction::class);
        $reactivateMock = Mockery::mock(ReactivateTenantAction::class);
        $reactivateMock->shouldReceive('execute')->with($tenantMock)->once();
        $destroyMock = Mockery::mock(DestroyTenantEnvironmentAction::class);

        $listener = Mockery::mock(StripeWebhookListener::class, [$suspendMock, $reactivateMock, $destroyMock])->makePartial();
        $listener->shouldAllowMockingProtectedMethods();
        $listener->shouldReceive('findTenantByStripeId')->with('cus_123')->andReturn($tenantMock);

        $event = new WebhookReceived(['type' => 'invoice.payment_succeeded', 'data' => ['object' => ['customer' => 'cus_123']]]);
        $listener->handle($event);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_destroys_tenant_on_subscription_deleted()
    {
        Log::shouldReceive('error')->once(); // Mock Log facade

        $tenantMock = Mockery::mock(Tenant::class)->makePartial();
        $tenantMock->domain = 'test.com';

        $suspendMock = Mockery::mock(SuspendTenantAction::class);
        $reactivateMock = Mockery::mock(ReactivateTenantAction::class);
        
        $destroyMock = Mockery::mock(DestroyTenantEnvironmentAction::class);
        $destroyMock->shouldReceive('execute')->with($tenantMock)->once();

        $listener = Mockery::mock(StripeWebhookListener::class, [$suspendMock, $reactivateMock, $destroyMock])->makePartial();
        $listener->shouldAllowMockingProtectedMethods();
        $listener->shouldReceive('findTenantByStripeId')->with('cus_123')->andReturn($tenantMock);

        $event = new WebhookReceived(['type' => 'customer.subscription.deleted', 'data' => ['object' => ['customer' => 'cus_123']]]);
        $listener->handle($event);
        $this->assertTrue(true);
    }
}
