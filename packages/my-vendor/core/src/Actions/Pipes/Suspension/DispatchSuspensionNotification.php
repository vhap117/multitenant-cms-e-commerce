<?php

namespace VHAP\Core\Actions\Pipes\Suspension;

use Closure;
use Illuminate\Support\Facades\Mail;
use VHAP\Core\Models\Tenant;
use App\Mail\TenantSuspendedMail; // Your mailable class

class DispatchSuspensionNotification
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // Assuming your tenant has a billing_email or you query the tenant's admin user
        // Mail::to('billing@' . $tenant->domain)->send(new TenantSuspendedMail($tenant));

        return $next($tenant);
    }
}