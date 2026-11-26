<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Translation;

use App\Domain\Shared\Translation\Contracts\LanguageNamesInterface;
use App\Domain\Shared\Translation\Enums\Language;
use Locale;

/**
 * Language names from ICU, so every language is named in every locale without translation files.
 */
final class IntlLanguageNames implements LanguageNamesInterface
{
    public function name(Language $language, string $locale): string
    {
        $name = Locale::getDisplayLanguage($language->value, $locale);

        // ICU writes language names lowercase in Czech and Slovak; a standalone label starts with a capital
        return mb_strtoupper(
            mb_substr(
                $name,
                0,
                1,
            ),
        ) . mb_substr($name, 1);
    }
}
