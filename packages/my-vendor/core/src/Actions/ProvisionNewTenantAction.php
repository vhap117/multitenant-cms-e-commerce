<?php

namespace VHAP\Core\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\Pipes\Provision\CreateTenantDatabase;
use VHAP\Core\Actions\Pipes\Provision\RunTenantMigrations;
use VHAP\Core\Actions\Pipes\Provision\SeedTenantDefaultData;
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
                    'email' => $tenantData['email'],
                    'plan' => $tenantData['plan'] ?? \VHAP\Core\Enums\TenantPlan::FREE->value,
                    'domain' => $tenantData['domain'],
                    'database' => $tenantData['database'],
                ]);

                // 2. Wire the pipes together and send the Tenant through them
                return Pipeline::send($tenant)
                    ->through([
                        CreateTenantDatabase::class,
                        RunTenantMigrations::class,
                        SeedTenantDefaultData::class,
                    ])
                    ->then(function (Tenant $provisionedTenant) use ($tenantData) {
                        // This closure only executes if all pipes pass successfully.
                        event(new \VHAP\Core\Events\TenantProvisioned($provisionedTenant, $tenantData));

                        $provisionedTenant->forgetCurrent();
                        
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
        
        if ($driver === 'sqlite' && File::exists($databaseName)) {
            // FORCE PHP/PDO to close the connection and release the OS file lock
            DB::purge('tenant'); 
            
            File::delete($databaseName); 
        }
    }
}