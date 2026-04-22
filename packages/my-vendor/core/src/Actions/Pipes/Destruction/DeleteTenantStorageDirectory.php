<?php

namespace VHAP\Core\Actions\Pipes\Destruction;

use Closure;
use Illuminate\Support\Facades\Storage;
use VHAP\Core\Models\Tenant;

class DeleteTenantStorageDirectory
{
    public function handle(Tenant $tenant, Closure $next)
    {
        // Assuming your files are stored in a 'tenants/{tenant_id}' directory on the local disk.
        // Adjust the disk name if you are using AWS S3 (e.g., Storage::disk('s3'))
        $tenantDirectory = 'tenants/' . $tenant->id;

        if (Storage::disk('local')->exists($tenantDirectory)) {
            Storage::disk('local')->deleteDirectory($tenantDirectory);
        }
        
        if (Storage::disk('public')->exists($tenantDirectory)) {
            Storage::disk('public')->deleteDirectory($tenantDirectory);
        }

        return $next($tenant);
    }
}