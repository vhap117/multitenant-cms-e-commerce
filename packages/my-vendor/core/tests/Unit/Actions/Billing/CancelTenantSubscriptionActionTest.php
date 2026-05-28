<?php

namespace VHAP\Core\Tests\Unit\Actions\Billing;

use PHPUnit\Framework\TestCase;
use Mockery;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Billing\CancelTenantSubscriptionAction;
use Laravel\Cashier\Subscription;
use Exception;

class CancelTenantSubscriptionActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_throws_an_exception_if_tenant_does_not_have_subscription()
    {
        $tenantMock = Mockery::mock(Tenant::class);
        $tenantMock->shouldReceive('subscription')->with('default')->andReturn(null);

        $action = new CancelTenantSubscriptionAction();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tenant does not have an active default subscription.');

        $action->execute($tenantMock, 'default');
    }

    /** @test */
    public function it_cancels_an_active_subscription_successfully()
    {
        $subscriptionMock = Mockery::mock(Subscription::class);
        $subscriptionMock->shouldReceive('cancel')->once()->andReturn($subscriptionMock);

        $tenantMock = Mockery::mock(Tenant::class);
        $tenantMock->shouldReceive('subscription')->with('default')->andReturn($subscriptionMock);

        $action = new CancelTenantSubscriptionAction();
        $result = $action->execute($tenantMock, 'default');

        $this->assertSame($subscriptionMock, $result);
    }
}
