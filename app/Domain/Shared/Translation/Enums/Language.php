<?php

declare(strict_types=1);

namespace App\Domain\Shared\Translation\Enums;

/**
 * A language the app is translated into, stored as its locale code. The cases match `app.available_locales`.
 */
enum Language: string
{
    case CS = 'cs';
    case DE = 'de';
    case EN = 'en';
    case SK = 'sk';
}
