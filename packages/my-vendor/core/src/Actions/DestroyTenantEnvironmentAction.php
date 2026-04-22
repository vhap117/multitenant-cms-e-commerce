<?php

namespace VHAP\Core\Actions;

use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Log;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Destruction\DropTenantDatabase;
use VHAP\Core\Actions\Pipes\Destruction\DeleteTenantStorageDirectory;
use VHAP\Core\Actions\Pipes\Destruction\DeleteTenantRecord;
use Throwable;

class DestroyTenantEnvironmentAction
{
    /**
     * Completely and permanently destroys a tenant's environment.
     * WARNING: This action is irreversible.
     *
     * @param Tenant $tenant
     * @return void
     * @throws Throwable
     */
    public function execute(Tenant $tenant): void
    {
        try {
            // Optional: If you use spatie/db-dumper, you would add an 
            // ArchiveTenantDatabase::class pipe here to send a backup to S3 first.

            Pipeline::send($tenant)
                ->through([
                    DropTenantDatabase::class,
                    DeleteTenantStorageDirectory::class,
                    DeleteTenantRecord::class,
                ])
                ->then(function (Tenant $tenant) {
                    Log::info("Tenant environment for {$tenant->domain} has been permanently destroyed.");
                });
                
        } catch (Throwable $exception) {
            Log::critical('CRITICAL FAILURE: Tenant destruction pipeline failed mid-execution.', [
                'tenant_id' => $tenant->id,
                'domain' => $tenant->domain,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}