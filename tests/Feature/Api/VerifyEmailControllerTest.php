<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProvisionTenantJob;
use Illuminate\Support\Carbon;

class VerifyEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['database.connections.landlord' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);

        $this->artisan('migrate', [
            '--database' => 'landlord', 
            '--path'     => __DIR__.'/../../../packages/my-vendor/core/database/migrations/landlord',
            '--realpath' => true,
        ])->run();
    }

    public function test_it_verifies_email_and_dispatches_job(): void
    {
        Queue::fake();

        $tenant = Tenant::forceCreate([
            'name' => 'Acme Corp',
            'email' => 'admin@acme.com',
            'plan' => 'free',
            'domain' => 'acme.myapp.com',
            'database' => 'tenant_acme',
            'provisioning_status' => 'pending_verification',
            'provisioning_data' => ['user_name' => 'John Doe'],
            'is_active' => false,
        ]);

        $url = URL::temporarySignedRoute(
            'api.tenant.verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'tenant' => $tenant->id,
                'hash' => sha1($tenant->email),
            ]
        );

        $response = $this->getJson($url);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Email verified successfully! We are now provisioning your store. You will receive an email when it is ready.');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'provisioning_status' => 'provisioning',
        ], 'landlord');

        Queue::assertPushed(ProvisionTenantJob::class, function ($job) use ($tenant) {
            return $job->tenant->id === $tenant->id &&
                   $job->dto->adminUser->name === 'John Doe' &&
                   strlen($job->dto->adminUser->password) === 16;
        });
    }

    public function test_it_rejects_invalid_signatures(): void
    {
        $tenant = Tenant::forceCreate([
            'name' => 'Acme Corp',
            'email' => 'admin@acme.com',
            'plan' => 'free',
            'domain' => 'acme.myapp.com',
            'database' => 'tenant_acme',
            'provisioning_status' => 'pending_verification',
            'is_active' => false,
        ]);

        // Wrong hash
        $url = URL::temporarySignedRoute(
            'api.tenant.verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'tenant' => $tenant->id,
                'hash' => sha1('wrong@email.com'),
            ]
        );

        $response = $this->getJson($url);

        $response->assertStatus(403);
    }
}
