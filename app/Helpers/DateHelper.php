<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;

class DateHelper
{
    /**
     * Return date format based on current language
     *
     * @return string
     */
    public static function format(): string
    {
        $locale = App::getLocale();
        
        // Formats by localization
        $dateFormats = [
            'cs' => 'd.m.Y',
            'en' => 'Y-m-d',
            // Additional languages here
        ];
        
        return $dateFormats[$locale] ?? 'd.m.Y';
    }
}
