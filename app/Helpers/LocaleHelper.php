<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class LocaleHelper 
{
    /**
     * Generate URL with current language
     * 
     * @param string $name Route name
     * @param array $parameters Additional route parameters
     * @return string URL with language parameter
     */
    public static function route($name, $parameters = [])
    {
        if (is_array($parameters)) {
            $parameters['lang'] = App::getLocale();
        } else {
            $parameters = ['lang' => App::getLocale(), $parameters];
        }
        
        return route($name, $parameters);
    }
}