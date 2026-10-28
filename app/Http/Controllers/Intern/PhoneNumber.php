<?php

namespace App\Http\Controllers\Intern;

/**
 * Phone numbers are stored without spaces, dashes or brackets so duplicates are caught.
 */
final class PhoneNumber
{
    public const PATTERN = '/^\+?[0-9]{10,15}$/';

    public static function normalize(string $phone): string
    {
        return preg_replace('/[\s\-().]/', '', trim($phone)) ?? '';
    }
}
