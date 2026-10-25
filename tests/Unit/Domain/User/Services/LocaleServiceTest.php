<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User\Services;

use App\Domain\User\Contracts\Locale;
use App\Domain\User\Services\LocaleService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LocaleServiceTest extends TestCase
{
    private Locale $locale;

    private LocaleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locale = new class implements Locale
        {
            public string $current = 'cs';

            public function getLocale(): string
            {
                return $this->current;
            }

            public function getAvailableLocales(): array
            {
                return ['cs', 'en'];
            }

            public function getFallbackLocale(): string
            {
                return 'en';
            }

            public function getCountryLocaleMap(): array
            {
                return ['CZ' => 'cs'];
            }

            public function setLocale(string $locale): void
            {
                $this->current = $locale;
            }

            public function localeFromCountry(?string $country): string
            {
                return 'en';
            }

            public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string
            {
                return $requestLocale ?? $dataLocale ?? 'cs';
            }
        };
        $this->service = new LocaleService($this->locale);
    }

    #[Test]
    public function locale_from_country_maps_a_known_country_case_insensitively(): void
    {
        $this->assertSame('cs', $this->service->localeFromCountry('cz'));
    }

    #[Test]
    public function locale_from_country_falls_back_for_an_unknown_or_missing_country(): void
    {
        $this->assertSame('en', $this->service->localeFromCountry('US'));
        $this->assertSame('en', $this->service->localeFromCountry(null));
    }
}
