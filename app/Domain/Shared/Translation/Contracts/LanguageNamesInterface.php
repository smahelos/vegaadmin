<?php

declare(strict_types=1);

namespace App\Domain\Shared\Translation\Contracts;

use App\Domain\Shared\Translation\Enums\Language;

interface LanguageNamesInterface
{
    public function name(Language $language, string $locale): string;
}
