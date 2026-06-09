<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use VHAP\Core\Data\ProvisionTenantData;
use VHAP\Core\Models\Tenant;
use VHAP\Core\Actions\ProvisionNewTenantAction;
use Throwable;

class ProvisionTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ProvisionTenantData $dto;
    public Tenant $tenant;

    /**
     * Create a new job instance.
     */
    public function __construct(ProvisionTenantData $dto, Tenant $tenant)
    {
        $this->dto = $dto;
        $this->tenant = $tenant;
    }

    /**
     * Execute the job.
     */
    public function handle(ProvisionNewTenantAction $action): void
    {
        $action->execute($this->dto, $this->tenant);
    }
}
