<?php

namespace VHAP\Core\Contracts;

interface LandlordAdminProvisioner
{
    /**
     * Bootstraps the initial landlord admin user and assigns roles.
     */
    public function provision(array $userData): void;
}
