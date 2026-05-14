<?php

namespace VHAP\Core\Database;

use VHAP\Core\Contracts\TenantDatabaseCreator;
use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Support\Facades\File;

class SqliteTenantDatabaseCreator implements TenantDatabaseCreator
{
    public function create(Tenant $tenant): void
    {
        File::put($tenant->database, '');
    }
}
