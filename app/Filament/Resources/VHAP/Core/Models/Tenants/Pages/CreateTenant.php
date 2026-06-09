<?php

namespace App\Filament\Resources\VHAP\Core\Models\Tenants\Pages;

use App\Filament\Resources\VHAP\Core\Models\Tenants\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VHAP\Core\Actions\ProvisionNewTenantAction;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * Override Filament's default record creation to run the core package provisioning pipeline.
     */
    protected function handleRecordCreation(array $data): Model
    {
        // 1. Resolve and execute the core package's action
        $dto = \VHAP\Core\Data\ProvisionTenantData::fromArray($data);
        return app(ProvisionNewTenantAction::class)->execute($dto);
    }
}