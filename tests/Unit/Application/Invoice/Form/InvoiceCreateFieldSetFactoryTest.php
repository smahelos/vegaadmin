<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Invoice\Form;

use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceCreateFieldSetFactoryTest extends TestCase
{
    #[Test]
    public function it_builds_field_set_from_trait_output(): void
    {
        $factory = new InvoiceCreateFieldSetFactory;
        $set = $factory->build(
            paymentMethods: ['3' => 'bank_transfer'],
            statuses: ['4' => 'paid'],
            currencies: ['CZK' => 'CZK'],
        );

        $fields = $set->toArray();
        $this->assertGreaterThan(5, count($fields));
        $this->assertContains('invoice_vs', array_column($fields, 'name'));
    }
}
