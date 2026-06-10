<?php

namespace VHAP\Core\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use VHAP\Core\Events\TenantProvisioned;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use VHAP\Core\Notifications\StoreReadyEmail;

class SendStoreReadyNotification implements ShouldQueue, NotTenantAware
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TenantProvisioned $event): void
    {
        $tenant = $event->tenant;
        $adminData = $event->adminData;

        // Run this safely inside the tenant context
        $tenant->execute(function () use ($tenant, $adminData) {
            $user = \VHAP\Core\Models\User::where('email', $adminData->email)->first();

            if ($user) {
                $token = Password::broker('tenant_users')->createToken($user);
                
                // We send the notification to the user but we want the link to point to the tenant domain
                Notification::route('mail', $user->email)->notify(new StoreReadyEmail($tenant, $token, $user->email));
            }
        });
    }
}
