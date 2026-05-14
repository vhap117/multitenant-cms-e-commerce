<?php

namespace VHAP\Core\Actions\Pipes\LandlordSetup;

use Closure;
use VHAP\Core\Contracts\LandlordDatabaseCreator;

class CreateLandlordDatabase
{
    protected LandlordDatabaseCreator $creator;

    /**
     * Inject the strategy bound in CoreServiceProvider based on DB driver.
     */
    public function __construct(LandlordDatabaseCreator $creator)
    {
        $this->creator = $creator;
    }

    public function handle(array $payload, Closure $next)
    {
        // Delegate the database creation to the specific DB driver strategy
        $this->creator->create($payload['database']);

        // Pass the payload down to the next pipe
        return $next($payload);
    }
}
