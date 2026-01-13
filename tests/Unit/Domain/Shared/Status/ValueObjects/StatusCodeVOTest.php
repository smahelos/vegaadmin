<?php

namespace Tests\Unit\Domain\Shared\Status\ValueObjects;

use App\Domain\Shared\Status\ValueObjects\StatusCode;
use App\Domain\Shared\Status\ValueObjects\StatusCodeVO;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusCodeVOTest extends TestCase
{
    #[Test]
    public function can_create_from_valid_slug(): void
    {
        $vo = StatusCodeVO::fromString('approved');
        $this->assertSame('approved', $vo->slug());
        $this->assertSame('Approved', $vo->label());
        $this->assertSame('approved', (string) $vo);
    }

    #[Test]
    public function from_string_throws_for_invalid_slug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StatusCodeVO::fromString('non-existent-slug-' . uniqid());
    }

    #[Test]
    public function enum_and_vo_consistency(): void
    {
        foreach (StatusCode::cases() as $case) {
            $vo = StatusCodeVO::fromString($case->value);
            $this->assertSame($case->value, $vo->slug());
            $this->assertSame($case->label(), $vo->label());
        }
    }
}
