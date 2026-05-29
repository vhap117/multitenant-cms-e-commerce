<?php

namespace App\Filament\Resources\VHAP\Core\Models\Tenants\Pages;

use App\Filament\Resources\VHAP\Core\Models\Tenants\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
