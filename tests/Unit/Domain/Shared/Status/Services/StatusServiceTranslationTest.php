<?php

namespace Tests\Unit\Domain\Shared\Status\Services;

use Tests\TestCase;
use App\Domain\Shared\Status\Contracts\StatusServiceInterface;
use App\Domain\Shared\Status\ValueObjects\StatusCode;

class StatusServiceTranslationTest extends TestCase
{
    public function test_translate_slug_uses_lang_file(): void
    {
        $service = app(StatusServiceInterface::class);
        // Force english locale for deterministic assertion
        app()->setLocale('en');
        $this->assertSame('Approved', $service->translateSlug(StatusCode::APPROVED->value));
    }

    public function test_translate_slug_fallbacks_to_enum_label(): void
    {
        $service = app(StatusServiceInterface::class);
        $slug = 'non-existing-slug';
        $this->assertSame($slug, $service->translateSlug($slug));
    }
}
