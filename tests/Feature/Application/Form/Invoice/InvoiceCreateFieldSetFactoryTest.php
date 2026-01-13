<?php

namespace Tests\Feature\Application\Form\Invoice;

use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use Illuminate\Foundation\Testing\RefreshDatabase; // empty DB fine
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceCreateFieldSetFactoryTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertGreaterThan(5, count($arr));
        $this->assertNotEmpty($set->find('invoice_vs'));
    }
}
