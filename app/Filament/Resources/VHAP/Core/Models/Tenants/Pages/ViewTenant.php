<?php

namespace App\Filament\Resources\VHAP\Core\Models\Tenants\Pages;

use App\Filament\Resources\VHAP\Core\Models\Tenants\TenantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
