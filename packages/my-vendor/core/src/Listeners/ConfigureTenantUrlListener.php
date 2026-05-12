<?php

namespace VHAP\Core\Listeners;

use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;
use Illuminate\Support\Facades\URL;

class ConfigureTenantUrlListener
{
    public function handle(MadeTenantCurrentEvent $event): void
    {
        $scheme = request()->secure() ? 'https://' : 'http://';
        
        $url = $scheme . $event->tenant->domain;

        URL::forceRootUrl($url);
        config(['app.url' => $url]);
    }
}
