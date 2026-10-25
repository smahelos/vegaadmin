<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User\Services;

use App\Domain\Shared\Session\Contracts\SessionInterface;
use App\Domain\User\Contracts\Locale;
use App\Domain\User\Services\LocaleService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LocaleServiceTest extends TestCase
{
    private Locale $locale;

    private SessionInterface $session;

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
        $this->session = new class implements SessionInterface
        {
            /** @var array<string, mixed> */
            public array $values = [];

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->values[$key] ?? $default;
            }

            public function put(string $key, mixed $value): void
            {
                $this->values[$key] = $value;
            }

            public function forget(string $key): void
            {
                unset($this->values[$key]);
            }
        };
        $this->service = new LocaleService($this->session, $this->locale);
    }

    #[Test]
    public function set_locale_applies_a_supported_locale_and_stores_it_in_the_session(): void
    {
        $this->service->setLocale('en');

        $this->assertSame('en', $this->locale->getLocale());
        $this->assertSame('en', $this->session->get('locale'));
    }

    #[Test]
    public function set_locale_falls_back_for_an_unsupported_locale(): void
    {
        $this->service->setLocale('xx');

        $this->assertSame('en', $this->locale->getLocale());
        $this->assertSame('en', $this->session->get('locale'));
    }

    #[Test]
    public function determine_locale_is_resolved_by_the_locale_adapter(): void
    {
        $this->assertSame('sk', $this->service->determineLocale(null, 'sk'));
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
