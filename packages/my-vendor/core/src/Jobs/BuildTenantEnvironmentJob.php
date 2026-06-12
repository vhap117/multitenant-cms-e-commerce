<?php

namespace VHAP\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Pipeline;
use Throwable;
use VHAP\Core\Actions\Pipes\Provision\CreateTenantDatabase;
use VHAP\Core\Actions\Pipes\Provision\RunTenantMigrations;
use VHAP\Core\Actions\Pipes\Provision\SeedTenantDefaultData;
use VHAP\Core\Data\ProvisionTenantData;
use VHAP\Core\Models\Tenant;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class BuildTenantEnvironmentJob implements ShouldQueue, NotTenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public ProvisionTenantData $dto
    ) {}

    public function handle(): void
    {
        try {
            $this->tenant->update(['provisioning_status' => 'provisioning']);

            Pipeline::send($this->tenant)
                ->through([
                    CreateTenantDatabase::class,
                    RunTenantMigrations::class,
                    SeedTenantDefaultData::class,
                ])
                ->then(function (Tenant $provisionedTenant) {
                    $adminData = $this->dto->adminUser ?? new \VHAP\Core\Data\TenantAdminUserData(
                        name: $this->dto->name,
                        email: $this->dto->email,
                        password: 'default_placeholder'
                    );

                    $provisionedTenant->update([
                        'provisioning_status' => 'active',
                        'is_active' => true,
                        'provisioning_data' => null,
                    ]);

                    event(new \VHAP\Core\Events\TenantProvisioned($provisionedTenant, $adminData));

                    $provisionedTenant->forgetCurrent();
                });
                
        } catch (Throwable $exception) {
            Log::error('Tenant provisioning pipeline failed in job.', [
                'domain' => $this->dto->domain,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->tenant->update(['provisioning_status' => 'failed']);

            $this->cleanupFailedDatabase($this->dto->database);

            throw $exception;
        }
    }

    protected function cleanupFailedDatabase(?string $databaseName): void
    {
        if (!$databaseName) {
            return;
        }

        $driver = config('database.connections.tenant.driver');
        $actualPath = config('database.connections.tenant.database');
        
        $pathToDelete = File::exists($actualPath) ? $actualPath : (File::exists($databaseName) ? $databaseName : null);
        
        if ($driver === 'sqlite' && $pathToDelete) {
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
