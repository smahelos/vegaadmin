<?php

namespace Tests\Unit\Domain\Content\Services;

use App\Domain\Content\Services\PageService;
use App\Domain\Content\Contracts\PageDtoReadRepositoryInterface;
use App\Domain\Content\DTO\PageDTO;
use App\Domain\Content\DTO\PageCategoryDTO;
use Illuminate\Support\Collection;
use Locale;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageServiceTest extends TestCase
{
    private PageService $service;

    private Locale $localeProvider;

    protected function setUp(): void
    {
        parent::setUp();
        // stub Locale provider
        $localeProvider = new class implements \App\Domain\User\Contracts\Locale {
            public function getLocale(): string
            {
                return 'en';
            }

            public function getAvailableLocales(): array
            {
                return ['en', 'fr', 'de'];
            }

            public function getFallbackLocale(): string
            {
                return 'en';
            }

            public function getCountryLocaleMap(): array
            {
                return [
                    'US' => 'en',
                    'FR' => 'fr',
                    'DE' => 'de',
                ];
            }

            public function setLocale(string $locale): void
            {
                // Stub implementation
            }

            public function localeFromCountry(?string $country): string
            {
                $map = $this->getCountryLocaleMap();
                $fallback = $this->getFallbackLocale();
                if ($country && isset($map[$country]) && in_array($map[$country], $this->getAvailableLocales())) {
                    return $map[$country];
                }
                return $fallback;
            }

            public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string
            {
                if ($requestLocale && in_array($requestLocale, $this->getAvailableLocales())) {
                    return $requestLocale;
                }
                if ($dataLocale && in_array($dataLocale, $this->getAvailableLocales())) {
                    return $dataLocale;
                }
                return $this->getFallbackLocale();
            }
        };

        // Provide a minimal stub for repository dependency; tests only reflect method signatures
        $repoStub = new class implements PageDtoReadRepositoryInterface {
            public function findHomepageBySlugs(array $preferredSlugs, string $locale): ?PageDTO { return null; }
            public function firstPublished(): ?PageDTO { return null; }
            public function findPublishedBySlug(string $slug, string $locale): ?PageDTO { return null; }
            public function findPublishedById(int $id): ?PageDTO { return null; }
            public function findPublishedBySlugInLocales(string $slug, array $locales, ?string $skipLocale = null): ?PageDTO { return null; }
            public function getRootNavigationPages(): array { return []; }
            public function getPublishedByCategory(int $categoryId): array { return []; }
            public function findAnyById(int $id): ?PageDTO { return null; }
            public function getCategoriesWithPublishedPages(): array { return []; }
            public function searchPublished(string $keyword, string $locale): array { return []; }
            public function getRelatedByCategory(int $categoryId, int $excludePageId, int $limit): array { return []; }
            public function getAllPublishedWithCategory(): array { return []; }
            public function findCategoryBySlug(string $slug, string $locale): ?PageCategoryDTO { return null; }
        };
        $this->service = new PageService($repoStub, $localeProvider);
    }

    /**
     * Test return types of PageService methods.
     */
    #[Test]
    public function service_methods_return_correct_types(): void
    {
        $reflection = new \ReflectionClass(PageService::class);

        // getHomepage returns ?PageDTO
        $method = $reflection->getMethod('getHomepage');
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertEquals('?App\\Domain\\Content\\DTO\\PageDTO', (string)$returnType);
        $this->assertTrue($returnType->allowsNull());

        // getPageBySlug returns ?PageDTO
        $method = $reflection->getMethod('getPageBySlug');
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertEquals('?App\\Domain\\Content\\DTO\\PageDTO', (string)$returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    /**
     * Test parameter signatures of PageService methods.
     */
    #[Test]
    public function method_parameter_signatures_are_correct(): void
    {
        $reflection = new \ReflectionClass(PageService::class);
        $method = $reflection->getMethod('getPageBySlug');
        $params = $method->getParameters();

        // First parameter must be string
        $this->assertInstanceOf(\ReflectionNamedType::class, $params[0]->getType());
        $this->assertEquals('string', (string)$params[0]->getType());
        $this->assertEquals('slug', $params[0]->getName());
        $this->assertFalse($params[0]->allowsNull());

        // Second parameter is optional nullable string
        $this->assertInstanceOf(\ReflectionNamedType::class, $params[1]->getType());
        $this->assertEquals('?string', (string)$params[1]->getType());
        $this->assertEquals('locale', $params[1]->getName());
        $this->assertTrue($params[1]->allowsNull());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertNull($params[1]->getDefaultValue());
    }

    /**
     * Ensure all public methods have explicit return types.
     */
    #[Test]
    public function public_methods_have_return_types(): void
    {
        $reflection = new \ReflectionClass(PageService::class);
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor()) { continue; }
            $this->assertNotNull($method->getReturnType(), 'Missing return type on ' . $method->getName());
        }
    }
}
