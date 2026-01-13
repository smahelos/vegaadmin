<?php

namespace Tests\Unit\Domain\Shared\Status\ValueObjects;

use App\Domain\Shared\Status\ValueObjects\StatusCode;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusCodeEnumTest extends TestCase
{
    #[Test]
    public function enum_has_cases(): void
    {
        $this->assertNotEmpty(StatusCode::cases());
    }

    #[Test]
    public function all_slugs_unique(): void
    {
        $slugs = StatusCode::allSlugs();
        $this->assertSame($slugs, array_values(array_unique($slugs)));
    }

    #[Test]
    public function label_is_non_empty_string(): void
    {
        foreach (StatusCode::cases() as $case) {
            $label = $case->label();
            $this->assertIsString($label);
            $this->assertNotSame('', $label);
        }
    }
}
