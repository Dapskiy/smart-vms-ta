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
     * =====================================================================================
     * 🔴 [CHEAT SHEET SIDANG] - KIOSK SECURITY (IP WHITELIST & SUBNETTING)
     * Kiosk berjalan di endpoint publik. Untuk mencegah orang luar/hacker 
     * mengakses menu Check-in/Check-out dari rumah mereka, kita menggunakan IP Whitelist.
     * Hanya IP lokal kantor (misal 192.168.1.0/24) yang diizinkan mengakses fitur ini.
     * =====================================================================================
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
        
        // IPv4 Matching
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === false || $subnetLong === false) {
                return false;
            }
            $maskDec = ~((1 << (32 - (int)$mask)) - 1);
            return ($ipLong & $maskDec) == ($subnetLong & $maskDec);
        }

        // IPv6 Matching
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            if ($ipBin === false || $subnetBin === false) {
                return false;
            }
            $maskBits = (int)$mask;
            $bytes = (int)floor($maskBits / 8);
            $bits = $maskBits % 8;
            
            if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }
            if ($bits > 0) {
                $ipByte = ord($ipBin[$bytes]);
                $subnetByte = ord($subnetBin[$bytes]);
                $maskByte = ~(255 >> $bits) & 255;
                if (($ipByte & $maskByte) !== ($subnetByte & $maskByte)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }
}
