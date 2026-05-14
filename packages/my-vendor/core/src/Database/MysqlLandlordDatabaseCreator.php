<?php

namespace VHAP\Core\Database;

use VHAP\Core\Contracts\LandlordDatabaseCreator;
use Illuminate\Support\Facades\DB;

class MysqlLandlordDatabaseCreator implements LandlordDatabaseCreator
{
    public function create(string $databaseName): void
    {
        // Use a generic connection (or the configured landlord connection) to create the database.
        // Assuming the connection has permissions to run CREATE DATABASE.
        DB::statement(
            "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        );
    }
}
