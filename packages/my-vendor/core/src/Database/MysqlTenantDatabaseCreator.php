<?php

namespace VHAP\Core\Database;

use VHAP\Core\Contracts\TenantDatabaseCreator;
use VHAP\Core\Models\Tenant;
use Illuminate\Support\Facades\DB;

class MysqlTenantDatabaseCreator implements TenantDatabaseCreator
{
    public function create(Tenant $tenant): void
    {
        DB::connection('landlord')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$tenant->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        );
    }
}
