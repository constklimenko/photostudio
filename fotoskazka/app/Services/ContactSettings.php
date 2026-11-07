<?php

namespace App\Services;

use App\Models\SiteSetting;

class ContactSettings
{
    public static function phone(): ?string
    {
        return SiteSetting::get('contacts.phone', config('contacts.phone'));
    }

    public static function email(): ?string
    {
        return SiteSetting::get('contacts.email', config('contacts.email'));
    }

    public static function phoneHref(): ?string
    {
        $phone = self::phone();

        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 7) {
            return null;
        }

        return 'tel:'.(str_starts_with(trim($phone), '+') ? '+' : '').$digits;
    }
}
