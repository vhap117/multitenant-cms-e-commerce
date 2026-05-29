<?php

namespace App\Filament\Resources\VHAP\Core\Models\Tenants\Pages;

use App\Filament\Resources\VHAP\Core\Models\Tenants\TenantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
