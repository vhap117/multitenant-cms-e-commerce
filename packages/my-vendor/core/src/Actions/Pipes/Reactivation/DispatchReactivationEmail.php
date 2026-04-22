<?php

namespace VHAP\Core\Actions\Pipes\Reactivation;

use Closure;
use Illuminate\Support\Facades\Mail;
use VHAP\Core\Models\Tenant;
// use App\Mail\TenantReactivatedMail; // You will create this Mailable in your host app

class DispatchReactivationEmail
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // In a real application, you might query the tenant database for the Super Admin's email
        // Or you might have a 'billing_email' column on the Landlord Tenant model.
        
        $billingEmail = 'admin@' . $tenant->domain; // Fallback example

        // Uncomment when your Mailable is ready:
        // Mail::to($billingEmail)->send(new TenantReactivatedMail($tenant));

        return $next($tenant);
    }
}