<?php

namespace VHAP\Core\Tests\Unit\Actions\Billing;

use PHPUnit\Framework\TestCase;
use Mockery;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Billing\CreateTenantSubscriptionAction;
use Laravel\Cashier\SubscriptionBuilder;
use Exception;

class CreateTenantSubscriptionActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_throws_an_exception_if_tenant_already_has_subscription_with_that_name()
    {
        $tenantMock = Mockery::mock(Tenant::class);
        $tenantMock->shouldReceive('subscribed')->with('default')->andReturn(true);

        $action = new CreateTenantSubscriptionAction();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tenant is already subscribed to the default plan.');

        $action->execute($tenantMock, 'pm_123', 'price_123');
    }

    /** @test */
    public function it_creates_a_new_subscription_successfully()
    {
        $subscriptionBuilderMock = Mockery::mock(SubscriptionBuilder::class);
        $subscriptionBuilderMock->shouldReceive('create')
            ->with('pm_123')
            ->once()
            ->andReturn((object) ['id' => 'sub_123']);

        $tenantMock = Mockery::mock(Tenant::class);
        $tenantMock->shouldReceive('subscribed')->with('default')->andReturn(false);
        $tenantMock->shouldReceive('newSubscription')
            ->with('default', 'price_123')
            ->once()
            ->andReturn($subscriptionBuilderMock);

        $action = new CreateTenantSubscriptionAction();
        $result = $action->execute($tenantMock, 'pm_123', 'price_123');

        $this->assertEquals('sub_123', $result->id);
    }
}
