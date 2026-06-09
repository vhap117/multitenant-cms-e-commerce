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
    public function execute(\VHAP\Core\Data\ProvisionTenantData $dto, ?Tenant $tenant = null): Tenant
    {
        try {
            if (!$tenant) {
                $tenant = Tenant::where('domain', $dto->domain)->first();
            }

            if (!$tenant) {
                $tenant = Tenant::create([
                    'name' => $dto->name,
                    'email' => $dto->email,
                    'plan' => $dto->plan->value,
                    'domain' => $dto->domain,
                    'database' => $dto->database,
                    'provisioning_status' => 'provisioning',
                ]);
            } else {
                $tenant->update(['provisioning_status' => 'provisioning']);
            }

            // 2. Wire the pipes together and send the Tenant through them
            return Pipeline::send($tenant)
                ->through([
                    CreateTenantDatabase::class,
                    RunTenantMigrations::class,
                    SeedTenantDefaultData::class,
                ])
                ->then(function (Tenant $provisionedTenant) use ($dto) {
                    // This closure only executes if all pipes pass successfully.
                    $adminData = $dto->adminUser ?? new \VHAP\Core\Data\TenantAdminUserData(
                        name: $dto->name,
                        email: $dto->email,
                        password: 'default_placeholder'
                    );

                    $provisionedTenant->update([
                        'provisioning_status' => 'active',
                        'is_active' => true,
                        'provisioning_data' => null,
                    ]);

                    event(new \VHAP\Core\Events\TenantProvisioned($provisionedTenant, $adminData));

                    $provisionedTenant->forgetCurrent();
                    
                    return $provisionedTenant;
                });
                
        } catch (Throwable $exception) {
            // Log the critical failure for debugging
            Log::error('Tenant provisioning pipeline failed.', [
                'domain' => $dto->domain,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            if (isset($tenant)) {
                $tenant->update(['provisioning_status' => 'failed']);
            }

            $this->cleanupFailedDatabase($dto->database);

            // Re-throw the exception
            throw $exception;
        }
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
        $actualPath = config('database.connections.tenant.database');
        
        $pathToDelete = File::exists($actualPath) ? $actualPath : (File::exists($databaseName) ? $databaseName : null);
        
        if ($driver === 'sqlite' && $pathToDelete) {
            // FORCE PHP/PDO to close the connection and release the OS file lock
            DB::disconnect('tenant');
            DB::purge('tenant'); 
            
            try {
                File::delete($pathToDelete); 
            } catch (Throwable $e) {
                Log::warning('Failed to delete SQLite database file during cleanup: ' . $e->getMessage());
            }
        }
    }
}