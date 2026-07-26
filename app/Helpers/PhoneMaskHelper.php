<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class PhoneMaskHelper
{
    /**
     * Mask nomor telepon: 0857****7059
     * Menampilkan 4 karakter pertama dan 4 karakter terakhir, sisanya bintang.
     */
    public static function mask(?string $phone): string
    {
        if (empty($phone) || $phone === '-') {
            return '-';
        }

        return Str::mask($phone, '*', 4, -4);
    }

    /**
     * Cek apakah user saat ini berhak melihat nomor asli (reveal).
     */
    public static function canReveal(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('reveal_visitor_phone');
    }

    /**
     * Format nomor untuk tampilan: selalu masked.
     * Gunakan canReveal() terpisah untuk toggle di UI.
     */
    public static function display(?string $phone): string
    {
        return self::mask($phone);
    }
}
