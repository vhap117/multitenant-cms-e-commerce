<?php

namespace VHAP\Core\Enums;

enum TenantPlan: string
{
    case FREE = 'free';
    case PRO = 'pro';
    case ENTERPRISE = 'enterprise';

    /**
     * Define user-friendly labels for the Filament Admin Panel.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::FREE => 'Free Tier',
            self::PRO => 'Pro Plan (Stripe)',
            self::ENTERPRISE => 'Enterprise Tier',
        };
    }
}
