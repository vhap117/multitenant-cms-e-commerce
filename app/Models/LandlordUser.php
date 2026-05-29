<?php

namespace App\Models;

use VHAP\Core\Models\LandlordUser as BaseLandlordUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class LandlordUser extends BaseLandlordUser implements FilamentUser
{
    /**
     * Determine if the user can access the given panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Add authorization checks here if required.
        return true;
    }
}
