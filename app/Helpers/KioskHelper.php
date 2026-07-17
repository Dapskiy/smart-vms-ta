<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Request;

class KioskHelper
{
    /**
     * Check if the current request is from an authorized Kiosk IP or subnet.
     */
    public static function isKioskLocal(): bool
    {
        // By default, allow everything if KIOSK_ALLOWED_IPS is not set or set to *
        $allowedIpsConfig = env('KIOSK_ALLOWED_IPS', '*');
        
        if ($allowedIpsConfig === '*') {
            return true;
        }

        $clientIp = Request::ip();
        $allowedIps = array_map('trim', explode(',', $allowedIpsConfig));

        foreach ($allowedIps as $allowedIp) {
            if ($allowedIp === $clientIp) {
                return true;
            }

            // Check CIDR block (e.g. 192.168.1.0/24)
            if (str_contains($allowedIp, '/')) {
                if (self::ipInCidr($clientIp, $allowedIp)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Helper to match IP to CIDR range (IPv4).
     */
    private static function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === false || $subnetLong === false) {
                return false;
            }
            $maskDec = ~((1 << (32 - $mask)) - 1);
            return ($ipLong & $maskDec) == ($subnetLong & $maskDec);
        }
        
        return false;
    }
}
