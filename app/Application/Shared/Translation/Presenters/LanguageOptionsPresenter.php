<?php

declare(strict_types=1);

namespace App\Application\Shared\Translation\Presenters;

use App\Domain\Shared\Translation\Contracts\LanguageNamesInterface;
use App\Domain\Shared\Translation\Enums\Language;

/**
 * Languages for the document language select and the business detail.
 */
final class LanguageOptionsPresenter
{
    public function __construct(
        private readonly LanguageNamesInterface $names,
    ) {}

    /**
     * @return array<string, string> language name by code, in the order of the enum
     */
    public function options(string $locale): array
    {
        $options = [];
        foreach (Language::cases() as $language) {
            $options[$language->value] = $this->names->name($language, $locale);
        }

        return $options;
    }
}
