<?php

namespace VHAP\Core\Provisioners;

use VHAP\Core\Contracts\TenantAdminProvisioner;
use VHAP\Core\Models\User;
use Illuminate\Support\Facades\Hash;

class DefaultTenantAdminProvisioner implements TenantAdminProvisioner
{
    public function provision(\VHAP\Core\Data\TenantAdminUserData $adminData): void
    {
        $user = User::create([
            'name' => $adminData->name,
            'email' => $adminData->email,
            'password' => Hash::make($adminData->password),
            'email_verified_at' => now(),
        ]);

        // 3. Assign the Spatie Role 
        // (Assumes a 'Super Admin' role was seeded in a previous pipeline step)
        $user->assignRole('Super Admin');

        // Optional: You could log this generated password, fire an event to email it, 
        // or store it temporarily so the platform admin can hand it to the new client.
    }
}