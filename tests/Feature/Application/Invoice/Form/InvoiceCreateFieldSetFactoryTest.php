<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice\Form;

use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceCreateFieldSetFactoryTest extends TestCase
{
    #[Test]
    public function a_draft_gets_its_number_and_dates_at_issue_and_a_guest_enters_them(): void
    {
        $factory = new InvoiceCreateFieldSetFactory;

        $draftFields = $factory->build(['3' => 'bank_transfer'], false);
        $guestFields = $factory->build(['3' => 'bank_transfer'], true);

        $draftNames = array_column($draftFields->toArray(), 'name');
        $guestNames = array_column($guestFields->toArray(), 'name');
        $this->assertNotContains('invoice_vs', $draftNames);
        $this->assertNotContains('issue_date', $draftNames);
        $this->assertNotContains('tax_point_date', $draftNames);
        $this->assertContains('document_language', $draftNames);
        $this->assertContains('invoice_vs', $guestNames);
        $this->assertContains('issue_date', $guestNames);
        $this->assertContains('tax_point_date', $guestNames);
        $this->assertNotContains('document_language', $guestNames);
    }
}
