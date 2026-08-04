<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Request;

class KioskHelper
{
    /**
     * Get real client IP considering reverse proxies / Cloudflare headers.
     */
    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return trim($_SERVER['HTTP_X_REAL_IP']);
        }

        return Request::ip() ?? '127.0.0.1';
    }

    /**
     * Check if the current request is from an authorized Kiosk IP or subnet.
     */
    public static function isKioskLocal(): bool
    {
        // Must use config() instead of env() to support php artisan config:cache
        $allowedIpsConfig = config('kiosk.allowed_ips', '*');
        
        if (empty($allowedIpsConfig) || $allowedIpsConfig === '*') {
            return true;
        }

        $clientIp = self::getClientIp();
        $allowedIps = array_map('trim', explode(',', (string) $allowedIpsConfig));

        foreach ($allowedIps as $allowedIp) {
            if (empty($allowedIp)) continue;

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
        if (!str_contains($cidr, '/')) {
            return false;
        }

        list($subnet, $mask) = explode('/', $cidr);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === false || $subnetLong === false) {
                return false;
            }
            $maskDec = ~((1 << (32 - (int)$mask)) - 1);
            return ($ipLong & $maskDec) == ($subnetLong & $maskDec);
        }

        return false;
    }
}
