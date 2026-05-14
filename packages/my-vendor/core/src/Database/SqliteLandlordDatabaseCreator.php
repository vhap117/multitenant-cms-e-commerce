<?php

namespace VHAP\Core\Database;

use VHAP\Core\Contracts\LandlordDatabaseCreator;
use Illuminate\Support\Facades\File;

class SqliteLandlordDatabaseCreator implements LandlordDatabaseCreator
{
    public function create(string $databaseName): void
    {
        // if (!File::exists($databaseName)) {
        File::put($databaseName, '');
        // }
    }
}
