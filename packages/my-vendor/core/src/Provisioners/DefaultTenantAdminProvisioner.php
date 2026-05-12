<?php

namespace VHAP\Core\Provisioners;

use VHAP\Core\Contracts\TenantAdminProvisioner;
use VHAP\Core\Models\User;
use Illuminate\Support\Facades\Hash;

class DefaultTenantAdminProvisioner implements TenantAdminProvisioner
{
    public function provision(array $userData): void
    {
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);

        // 3. Assign the Spatie Role 
        // (Assumes a 'Super Admin' role was seeded in a previous pipeline step)
        $user->assignRole('Super Admin');

        // Optional: You could log this generated password, fire an event to email it, 
        // or store it temporarily so the platform admin can hand it to the new client.
    }
}