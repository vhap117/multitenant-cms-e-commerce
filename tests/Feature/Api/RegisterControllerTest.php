<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VerifyTenantEmail;
use Illuminate\Support\Facades\Hash;

class RegisterControllerTest extends TestCase
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

    public function test_it_registers_a_new_tenant_and_sends_verification_email(): void
    {
        Notification::fake();

        $payload = [
            'user_name' => 'John Doe',
            'company_name' => 'Acme Corp',
            'email' => 'admin@acme.com',
            'domain' => 'acme.myapp.com',
        ];

        $response = $this->postJson(route('api.tenant.register'), $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('message', 'Registration successful. Please verify your email to provision your store.');

        $this->assertDatabaseHas('tenants', [
            'name' => 'Acme Corp',
            'email' => 'admin@acme.com',
            'domain' => 'acme.myapp.com',
            'provisioning_status' => 'pending_verification',
            'is_active' => false,
        ], 'landlord');

        $tenant = Tenant::where('domain', 'acme.myapp.com')->first();
        
        $this->assertEquals('John Doe', $tenant->provisioning_data['user_name']);

        Notification::assertSentTo(
            $tenant,
            VerifyTenantEmail::class
        );
    }
}
