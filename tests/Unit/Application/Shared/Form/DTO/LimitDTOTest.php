<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Shared\Form\DTO;

use App\Application\Shared\Form\DTO\LimitDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LimitDTOTest extends TestCase
{
    #[Test]
    public function exports_the_legacy_array_shape(): void
    {
        $dto = new LimitDTO(
            limit: 10,
            currentUsage: 3,
            allowed: true,
        );

        $this->assertSame([
            'limit' => 10,
            'current_usage' => 3,
            'allowed' => true,
        ], $dto->toArray());
    }
}
