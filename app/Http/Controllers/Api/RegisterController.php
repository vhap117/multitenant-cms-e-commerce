<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Enums\TenantPlan;
use Illuminate\Support\Str;
use App\Notifications\VerifyTenantEmail;
use Illuminate\Support\Facades\Notification;

class RegisterController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255', // Removed unique:landlord.tenants so they can retry if failed
            'company_name' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
        ]);

        // Check if there is an active/provisioning tenant with this domain
        $existingDomain = Tenant::where('domain', $validated['domain'])
            ->where('provisioning_status', '!=', 'failed')
            ->first();

        if ($existingDomain) {
            return response()->json(['message' => 'The domain is already taken.'], 422);
        }

        // We could also check if the email is already registered and active
        $existingEmail = Tenant::where('email', $validated['email'])
            ->where('provisioning_status', '!=', 'failed')
            ->first();

        if ($existingEmail) {
            return response()->json(['message' => 'The email is already registered.'], 422);
        }

        // Handle retry: If they failed before, find the failed tenant by email or domain and update it.
        // Or simply create a new one. Since domain/email uniqueness ignores failed, creating a new one is fine.
        // However, if we create a new one, we might have multiple failed tenants. Let's just create a new one or update a failed one.
        $tenant = Tenant::where('email', $validated['email'])->where('provisioning_status', 'failed')->first();

        if (!$tenant) {
            $tenant = new Tenant();
        }

        $tenant->fill([
            'name' => $validated['company_name'],
            'email' => $validated['email'],
            'plan' => TenantPlan::FREE->value,
            'domain' => $validated['domain'],
            'database' => 'tenant_' . str_replace('-', '_', Str::slug($validated['domain'])),
            'provisioning_status' => 'pending_verification',
            'provisioning_data' => [
                'user_name' => $validated['user_name'],
            ],
            'is_active' => false,
        ]);
        
        $tenant->save();

        // Send Custom Notification to the Tenant Email
        Notification::route('mail', $tenant->email)->notify(new VerifyTenantEmail($tenant));

        return response()->json([
            'message' => 'Registration successful. Please verify your email to provision your store.',
        ], 201);
    }
}
