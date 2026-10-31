<?php

return [

    /*
    | The host the Filament admin panel is served on. Customers never see it.
    */
    'admin_domain' => env('ADMIN_DOMAIN', 'admin.localhost'),

    /*
    | Proxies whose X-Forwarded-* headers are trusted. Use * behind a tunnel or
    | load balancer so links and assets use the public https address.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
