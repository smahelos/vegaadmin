<?php

namespace Tests\Unit\Application\Form\Invoice;

use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InvoiceCreateFieldSetFactoryTest extends TestCase
{
    #[Test]
    public function it_builds_field_set_from_trait_output(): void
    {
        $factory = new InvoiceCreateFieldSetFactory();
        $set = $factory->build(
            clients: ['1' => 'Client A'],
            suppliers: ['2' => 'Supplier B'],
            paymentMethods: ['3' => 'bank_transfer'],
            statuses: ['4' => 'paid'],
            currencies: ['CZK' => 'CZK']
        );

        $arr = $set->toArray();
        $this->assertGreaterThan(5, count($arr)); // ensure fields present
        $first = $arr[0];
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('type', $first);

        // Ensure key invoice field exists
        $found = false;
        foreach ($arr as $f) {
            if ($f['name'] === 'invoice_vs') { $found = true; break; }
        }
        $this->assertTrue($found, 'Expected invoice_vs field');
    }
}
