<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Content\DTO;

use App\Domain\Content\DTO\PageDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PageDTOTest extends TestCase
{
    /** @param array<string, string>|null $name */
    private function page(?array $name): PageDTO
    {
        return new PageDTO(
            id: 1,
            name: $name,
            slug: null,
            description: null,
            published: true,
        );
    }

    #[Test]
    public function takes_the_value_of_the_requested_locale(): void
    {
        $page = $this->page(['cs' => 'Kontakty', 'en' => 'Contacts']);

        $this->assertSame(
            'Contacts',
            $page->localizedName('en'),
        );
    }

    #[Test]
    public function falls_back_to_czech_then_english_then_the_first_value(): void
    {
        $czech = $this->page([
            'en' => 'Contacts',
            'cs' => 'Kontakty',
            'de' => '',
        ]);
        $english = $this->page(['sk' => 'Kontakty SK', 'en' => 'Contacts']);
        $first = $this->page(['sk' => 'Kontakty SK']);

        $this->assertSame(
            'Kontakty',
            $czech->localizedName('de'),
        );
        $this->assertSame(
            'Contacts',
            $english->localizedName('de'),
        );
        $this->assertSame(
            'Kontakty SK',
            $first->localizedName('de'),
        );
    }

    #[Test]
    public function returns_an_empty_string_without_any_value(): void
    {
        $page = $this->page(null);

        $this->assertSame(
            '',
            $page->localizedName('cs'),
        );
        $this->assertSame(
            '',
            $page->localizedSlug('cs'),
        );
        $this->assertSame(
            '',
            $page->localizedDescription('cs'),
        );
    }
}
