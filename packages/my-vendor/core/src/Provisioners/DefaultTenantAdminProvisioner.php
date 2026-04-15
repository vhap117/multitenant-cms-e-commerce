<?php

namespace VHAP\Core\Provisioners;

use VHAP\Core\Contracts\TenantAdminProvisioner;
use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DefaultTenantAdminProvisioner implements TenantAdminProvisioner
{
    public function provision(Tenant $tenant): void
    {
        // 1. Generate or retrieve the initial user data.
        // Because the interface signature only passes the Tenant model, 
        // you might generate default credentials here or pull from a request/config.
        $defaultPassword = Str::random(16);
        
        // 2. Create the User (Automatically scopes to the tenant DB)
        $user = User::create([
            'name' => 'System Admin',
            'email' => 'admin@' . $tenant->domain,
            'password' => Hash::make($defaultPassword),
        ]);

        // 3. Assign the Spatie Role 
        // (Assumes a 'Super Admin' role was seeded in a previous pipeline step)
        $user->assignRole('Super Admin');

        // Optional: You could log this generated password, fire an event to email it, 
        // or store it temporarily so the platform admin can hand it to the new client.
    }
}