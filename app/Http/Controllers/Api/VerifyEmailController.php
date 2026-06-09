<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\ProvisionNewTenantAction;
use VHAP\Core\Data\ProvisionTenantData;
use VHAP\Core\Data\TenantAdminUserData;
use VHAP\Core\Enums\TenantPlan;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, $id, $hash)
    {
        // 1. Verify Signature
        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired verification link.'], 403);
        }

        $tenant = Tenant::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($tenant->email))) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        // 2. Check if already active/provisioning
        if ($tenant->provisioning_status !== 'pending_verification' && $tenant->provisioning_status !== 'failed') {
            return response()->json(['message' => 'Email already verified and store is ' . $tenant->provisioning_status . '.'], 400);
        }

        // 3. Mark as provisioning
        $tenant->update(['provisioning_status' => 'provisioning']);

        // 4. Construct DTO and dispatch Job
        $userName = $tenant->provisioning_data['user_name'] ?? 'Admin';
        $randomPassword = \Illuminate\Support\Str::password(16);
        
        $adminData = new TenantAdminUserData(
            name: $userName,
            email: $tenant->email,
            password: $randomPassword,
        );

        $dto = new ProvisionTenantData(
            name: $tenant->name,
            email: $tenant->email,
            domain: $tenant->domain,
            database: $tenant->database,
            plan: TenantPlan::from($tenant->plan),
            adminUser: $adminData
        );

        // We can either dispatch a job here or resolve the action and use a queue
        // But ProvisionNewTenantAction is not a Job by itself. We should dispatch it using a queued job.
        // Let's dispatch a generic job that calls the action, or we can use the `dispatch` helper with a closure
        // if closure serialization is enabled, but it's safer to create a dedicated Job class.
        
        dispatch(new \App\Jobs\ProvisionTenantJob($dto, $tenant));

        return response()->json([
            'message' => 'Email verified successfully! We are now provisioning your store. You will receive an email when it is ready.',
        ]);
    }
}
