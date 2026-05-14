<?php

namespace VHAP\Core\Contracts;

interface LandlordDatabaseCreator
{
    /**
     * Create the landlord database.
     *
     * @param string $databaseName The name or path of the database to create.
     * @return void
     */
    public function create(string $databaseName): void;
}
