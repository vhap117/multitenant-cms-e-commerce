<?php

namespace VHAP\Core\Provisioners;

use VHAP\Core\Contracts\LandlordAdminProvisioner;
use VHAP\Core\Models\LandlordUser;
use Illuminate\Support\Facades\Hash;

class DefaultLandlordAdminProvisioner implements LandlordAdminProvisioner
{
    public function provision(array $userData): void
    {
        $user = LandlordUser::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);

        // Assign the Spatie Role 
        // (Assumes a 'Platform Admin' role was seeded in a previous pipeline step)
        $user->assignRole('Platform Admin');
    }
}
