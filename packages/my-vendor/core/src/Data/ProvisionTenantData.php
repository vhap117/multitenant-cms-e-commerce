<?php

namespace VHAP\Core\Data;

use VHAP\Core\Enums\TenantPlan;

readonly class ProvisionTenantData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $domain,
        public string $database,
        public TenantPlan $plan = TenantPlan::FREE,
        public ?TenantAdminUserData $adminUser = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            domain: $data['domain'],
            database: $data['database'],
            plan: isset($data['plan']) 
                ? ($data['plan'] instanceof TenantPlan ? $data['plan'] : TenantPlan::from($data['plan']))
                : TenantPlan::FREE,
            adminUser: isset($data['admin_user']) 
                ? TenantAdminUserData::fromArray($data['admin_user']) 
                : null
        );
    }
}
