<?php

namespace App\Infrastructure\Shared\Translation;

use App\Domain\Shared\Translation\Contracts\TranslatorInterface;
use Illuminate\Contracts\Translation\Translator as IlluminateTranslator;

/**
 * Adapter to bridge Domain's TranslatorInterface with Laravel's translator.
 */
class LaravelTranslator implements TranslatorInterface
{
    public function __construct(private readonly IlluminateTranslator $translator) {}

    public function has(string $key, ?string $locale = null): bool
    {
        return $this->translator->has($key, $locale);
    }

    public function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        return $this->translator->get($key, $replace, $locale);
    }
}
