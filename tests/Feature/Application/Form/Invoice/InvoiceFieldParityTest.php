<?php

namespace Tests\Feature\Application\Form\Invoice;

use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Infrastructure\Forms\Invoice\InvoiceFormFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ensures factory output matches legacy trait definitions for core properties.
 */
class InvoiceFieldParityTest extends TestCase
{
    use RefreshDatabase;
    use InvoiceFormFields; // access getInvoiceFields directly

    private array $clients = ['1' => 'Client'];
    private array $suppliers = ['2' => 'Supplier'];
    private array $paymentMethods = ['3' => 'bank_transfer'];
    private array $statuses = ['4' => 'paid'];

    #[Test]
    public function factory_matches_trait_field_names_and_types(): void
    {
        $legacy = $this->getInvoiceFields($this->clients, $this->suppliers, $this->paymentMethods, $this->statuses);
        $factory = new InvoiceCreateFieldSetFactory();
        $dtoSet = $factory->build($this->clients, $this->suppliers, $this->paymentMethods, $this->statuses);
        $built = $dtoSet->toArray();

        $this->assertCount(count($legacy), $built, 'Field count mismatch');

        foreach ($legacy as $index => $legacyField) {
            $newField = $built[$index];
            $this->assertSame($legacyField['name'], $newField['name']);
            $this->assertSame($legacyField['type'], $newField['type']);
            // Only compare options when present
            if (isset($legacyField['options'])) {
                $this->assertEquals($legacyField['options'], $newField['options']);
            }
        }
    }
}
