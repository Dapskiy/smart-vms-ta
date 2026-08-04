<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kiosk Allowed IPs & Subnets
    |--------------------------------------------------------------------------
    |
    | Comma-separated IP addresses or CIDR ranges allowed to access full
    | Kiosk features and Admin Panel. Set to '*' to allow all.
    |
    */
    'allowed_ips' => env('KIOSK_ALLOWED_IPS', '*'),
];
