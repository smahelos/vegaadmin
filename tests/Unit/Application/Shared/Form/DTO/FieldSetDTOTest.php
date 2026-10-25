<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Shared\Form\DTO;

use App\Application\Shared\Form\DTO\FieldDTO;
use App\Application\Shared\Form\DTO\FieldSetDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FieldSetDTOTest extends TestCase
{
    #[Test]
    public function it_converts_all_fields_to_arrays(): void
    {
        $set = new FieldSetDTO([
            new FieldDTO(
                name: 'a',
                label: 'A',
                type: 'text',
            ),
            new FieldDTO(
                name: 'b',
                label: 'B',
                type: 'number',
            ),
        ]);

        $arr = $set->toArray();
        $this->assertCount(2, $arr);
        $this->assertSame('a', $arr[0]['name']);
        $this->assertSame('b', $arr[1]['name']);
    }
}
