<?php

namespace Tests\Unit\Application\Shared\Form\DTO;

use App\Application\Shared\Form\DTO\FieldDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FieldDTOTest extends TestCase
{
    #[Test]
    public function it_exports_legacy_array_structure(): void
    {
        $dto = new FieldDTO(
            name: 'invoice_vs',
            label: 'Invoice Number',
            type: 'text',
            options: [],
            required: true,
            hint: 'Hint',
            placeholder: 'Enter number',
            default: '2025-0001',
            entity: 'invoice',
            attribute: 'invoice_vs',
            model: 'App\\Models\\Invoice',
            accept: null,
        );

        $arr = $dto->toArray();
        $this->assertSame('invoice_vs', $arr['name']);
        $this->assertSame('Invoice Number', $arr['label']);
        $this->assertSame('text', $arr['type']);
        $this->assertTrue($arr['required']);
        $this->assertSame('Hint', $arr['hint']);
        $this->assertSame('Enter number', $arr['placeholder']);
        $this->assertSame('2025-0001', $arr['default']);
        $this->assertSame('invoice', $arr['entity']);
        $this->assertSame('invoice_vs', $arr['attribute']);
        $this->assertSame('App\\Models\\Invoice', $arr['model']);
    }
}
