<?php

namespace VHAP\Core\Tests\Unit\Actions\Billing;

use PHPUnit\Framework\TestCase;
use Mockery;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Billing\SwapSubscriptionPlanAction;
use Laravel\Cashier\Subscription;
use Exception;

class SwapSubscriptionPlanActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_throws_an_exception_if_tenant_does_not_have_subscription_when_swapping()
    {
        $tenantMock = Mockery::mock(Tenant::class);
        $tenantMock->shouldReceive('subscription')->with('default')->andReturn(null);

        $action = new SwapSubscriptionPlanAction();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tenant does not have an active default subscription.');

        $action->execute($tenantMock, 'price_new_123', 'default');
    }

    /** @test */
    public function it_swaps_an_active_subscription_plan_successfully()
    {
        $subscriptionMock = Mockery::mock(Subscription::class);
        $subscriptionMock->shouldReceive('swap')->with('price_new_123')->once()->andReturn($subscriptionMock);

        $tenantMock = Mockery::mock(Tenant::class);
        $tenantMock->shouldReceive('subscription')->with('default')->andReturn($subscriptionMock);

        $action = new SwapSubscriptionPlanAction();
        $result = $action->execute($tenantMock, 'price_new_123', 'default');

        $this->assertSame($subscriptionMock, $result);
    }
}
