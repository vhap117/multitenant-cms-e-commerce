<?php

namespace VHAP\Core\Actions\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetTenantPasswordAction
{
    /**
     * Resets the tenant user's password and marks their email as verified.
     *
     * @param  array  $credentials
     * @return string Password broker status code
     */
    public function execute(array $credentials): string
    {
        return Password::broker('tenant_users')->reset(
            $credentials,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                if (is_null($user->email_verified_at)) {
                    $user->email_verified_at = now();
                }

                $user->save();
            }
        );
    }
}
