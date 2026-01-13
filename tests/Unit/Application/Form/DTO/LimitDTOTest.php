<?php

namespace Tests\Unit\Application\Form\DTO;

use App\Application\Shared\Form\DTO\LimitDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LimitDTOTest extends TestCase
{
    #[Test]
    public function it_builds_from_array_and_exports(): void
    {
        $dto = LimitDTO::fromArray(['limit' => 10, 'current_usage' => 3, 'allowed' => true]);
        $this->assertSame(10, $dto->limit);
        $this->assertSame(3, $dto->currentUsage);
        $this->assertTrue($dto->allowed);

        $arr = $dto->toArray();
        $this->assertSame(['limit' => 10, 'current_usage' => 3, 'allowed' => true], $arr);
    }
}
