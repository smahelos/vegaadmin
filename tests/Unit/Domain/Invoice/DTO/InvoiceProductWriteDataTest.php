<?php

namespace Tests\Unit\Domain\Invoice\DTO;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Shared\Unit\Enums\Unit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InvoiceProductWriteDataTest extends TestCase
{
    #[Test]
    public function derives_tax_amount_and_total_price_from_quantity_price_and_tax_rate(): void
    {
        $data = InvoiceProductWriteData::fromArray([
            'product_id' => 5,
            'name' => 'Consulting',
            'quantity' => 2,
            'price' => 100,
            'tax_rate' => 21,
            'tax_amount' => 999,
            'total_price' => 999,
        ]);

        $this->assertSame(5, $data->product_id);
        $this->assertFalse($data->is_custom_product);
        $this->assertEqualsWithDelta(42.0, $data->tax_amount, 0.0001);
        $this->assertEqualsWithDelta(242.0, $data->total_price, 0.0001);
    }

    #[Test]
    public function applies_defaults_for_missing_fields(): void
    {
        $data = InvoiceProductWriteData::fromArray(['name' => 'Item', 'price' => 10]);

        $this->assertSame(1.0, $data->quantity);
        $this->assertSame(21.0, $data->tax_rate);
        $this->assertSame(Unit::PIECES, $data->unit);
        $this->assertSame('CZK', $data->currency);
    }

    #[Test]
    public function row_without_product_id_is_custom_product(): void
    {
        $data = InvoiceProductWriteData::fromArray(['name' => 'Custom', 'product_id' => '']);

        $this->assertNull($data->product_id);
        $this->assertTrue($data->is_custom_product);
    }

    #[Test]
    public function custom_flag_drops_product_id(): void
    {
        $data = InvoiceProductWriteData::fromArray(['name' => 'Custom', 'product_id' => 7, 'is_custom_product' => true]);

        $this->assertNull($data->product_id);
        $this->assertTrue($data->is_custom_product);
    }
}
