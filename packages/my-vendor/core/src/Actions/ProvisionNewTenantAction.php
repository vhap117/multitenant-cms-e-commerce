<?php

namespace VHAP\Core\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Log;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\CreateTenantDatabase;
use VHAP\Core\Actions\Pipes\RunTenantMigrations;
use VHAP\Core\Actions\Pipes\SetupTenantAdmin;
use Throwable;

class ProvisionNewTenantAction
{
    /**
     * Creates a new tenant record and pushes it through the provisioning pipeline.
     *
     * @param array $tenantData e.g., ['name' => 'Acme', 'domain' => 'acme.myapp.com', 'database' => 'tenant_acme']
     * @return Tenant
     * @throws Throwable
     */
    public function execute(array $tenantData): Tenant
    {
        // Wrap everything in a Landlord database transaction.
        return DB::connection('landlord')->transaction(function () use ($tenantData) {
            try {
                // 1. Create the base record in the landlord database
                $tenant = Tenant::create([
                    'name' => $tenantData['name'],
                    'domain' => $tenantData['domain'],
                    'database' => $tenantData['database'],
                ]);

                // 2. Wire the pipes together and send the Tenant through them
                return Pipeline::send($tenant)
                    ->through([
                        CreateTenantDatabase::class,
                        RunTenantMigrations::class,
                        SetupTenantAdmin::class,
                    ])
                    ->then(function (Tenant $provisionedTenant) {
                        // This closure only executes if all pipes pass successfully.
                        // You can dispatch a success event here if needed.
                        // event(new TenantProvisioned($provisionedTenant));
                        
                        return $provisionedTenant;
                    });
                    
            } catch (Throwable $exception) {
                // Log the critical failure for debugging
                Log::error('Tenant provisioning pipeline failed.', [
                    'domain' => $tenantData['domain'] ?? 'unknown',
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);

                // Optional: If you are using SQLite in local development, 
                // you might want to add custom cleanup logic here to delete 
                // the physical .sqlite file so you don't leave orphaned files after a failure.
                $this->cleanupFailedDatabase($tenantData['database'] ?? null);

                // Re-throw the exception so the DB transaction rolls back the Landlord record
                throw $exception;
            }
        });
    }

    /**
     * Clean up physical files if testing with SQLite.
     */
    protected function cleanupFailedDatabase(?string $databaseName): void
    {
        if (!$databaseName) {
            return;
        }

        $driver = config('database.connections.tenant.driver');
        
        if ($driver === 'sqlite' && file_exists($databaseName)) {
            @unlink($databaseName);
        }
    }
}