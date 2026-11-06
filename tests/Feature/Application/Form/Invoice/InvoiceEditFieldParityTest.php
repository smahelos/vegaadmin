<?php

namespace Tests\Feature\Application\Form\Invoice;

use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Infrastructure\Forms\Invoice\InvoiceFormFields;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Parity test for edit scenario (factory vs legacy trait output).
 */
class InvoiceEditFieldParityTest extends TestCase
{
    use InvoiceFormFields;

    #[Test]
    public function factory_matches_trait_for_edit(): void
    {
        // Simulated option lists (would be same source in real app)
        $paymentMethods = ['3' => 'bank_transfer'];

        $legacy = $this->getInvoiceFields($paymentMethods);
        $factory = new InvoiceCreateFieldSetFactory;
        $fieldSet = $factory->build($paymentMethods);
        $built = $fieldSet->toArray();

        $this->assertCount(
            count($legacy),
            $built,
            'Field count mismatch',
        );
        foreach ($legacy as $i => $legacyField) {
            $this->assertSame($legacyField['name'], $built[$i]['name']);
            $this->assertSame($legacyField['type'], $built[$i]['type']);
        }
    }
}
