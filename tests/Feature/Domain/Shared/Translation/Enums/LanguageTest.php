<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Shared\Translation\Enums;

use App\Domain\Shared\Translation\Enums\Language;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LanguageTest extends TestCase
{
    #[Test]
    public function a_document_can_be_written_in_every_language_of_the_app_and_no_other(): void
    {
        $languages = array_map(
            static fn (Language $language): string => $language->value,
            Language::cases(),
        );
        $locales = Config::array('app.available_locales');
        sort($languages);
        sort($locales);

        $this->assertSame($locales, $languages);
    }
}
