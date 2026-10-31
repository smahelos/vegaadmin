<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Services\InvoicePrintDataBuilder;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\PaymentMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\RendersInvoicePdfHtml;
use Tests\TestCase;

final class InvoicePdfSnapshotTest extends TestCase
{
    use RendersInvoicePdfHtml;

    /** @return array<string, array{string}> */
    public static function templates(): array
    {
        return [
            'default' => ['default'],
            'modern' => ['modern'],
            'minimal' => ['minimal'],
        ];
    }

    #[Test]
    #[DataProvider('templates')]
    public function pdf_templates_print_both_saved_roles_and_amounts(string $template): void
    {
        $this->travelTo(now()->setDate(2026, 3, 20)->setTime(14, 7));
        $invoice = Invoice::factory()->create([
            'invoice_vs' => '20260001',
            'invoice_ks' => '0308',
            'invoice_ss' => '1234',
            'issue_date' => '2026-03-05',
            'tax_point_date' => '2026-03-04',
            'due_in' => 14,
            'invoice_text' => 'Snapshot invoice text',
            'payment_currency' => 'EUR',
            'payment_amount' => '1210.00',
            'payment_method_id' => PaymentMethod::where('slug', PaymentMethodCode::CASH->value)->sole()->id,
            'supplier_name' => 'Snapshot Supplier s.r.o.',
            'supplier_email' => 'supplier@snapshot.test',
            'supplier_phone' => '+420 123 456 789',
            'supplier_company_number' => '27074358',
            'supplier_registration_country' => 'CZ',
            'supplier_vat_number' => 'CZ27074358',
            'supplier_street' => 'Supplier Street 10',
            'supplier_city' => 'Praha',
            'supplier_postal_code' => '110 00',
            'supplier_country' => 'CZ',
            'customer_name' => 'Snapshot Customer GmbH',
            'customer_email' => 'customer@snapshot.test',
            'customer_phone' => '+49 123 456 789',
            'customer_company_number' => 'HRB 12345',
            'customer_registration_country' => 'DE',
            'customer_vat_number' => 'DE123456789',
            'customer_street' => 'Customer Street 20',
            'customer_city' => 'Berlin',
            'customer_postal_code' => '10115',
            'customer_country' => 'DE',
            'supplier_logo' => null,
        ]);
        InvoiceProduct::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => null,
            'name' => 'Snapshot consulting',
            'quantity' => '2',
            'unit' => 'hours',
            'price' => '500',
            'currency' => 'EUR',
            'tax_rate' => '21',
            'tax_amount' => '210',
            'total_price' => '1210',
        ]);
        $dto = app(InvoiceDtoReadRepositoryInterface::class)->findByIdForUser(InvoiceId::fromInt($invoice->id), UserId::fromInt($invoice->user_id));
        $html = $this->renderPdfHtml(app(InvoicePrintDataBuilder::class)->build($dto), $template);
        $normalized = trim(preg_replace('/[ \t]+$/m', '', preg_replace('/[ \t]+/', ' ', str_replace("\r\n", "\n", $html))));
        $snapshot = __DIR__ . '/__snapshots__/invoice_' . $template . '.html';
        if (getenv('UPDATE_PDF_SNAPSHOTS') === '1') {
            file_put_contents($snapshot, $normalized);
        }
        $this->assertFileExists($snapshot);
        $this->assertSame(file_get_contents($snapshot), $normalized);
        $this->assertStringContainsString('Snapshot Supplier s.r.o.', $html);
        $this->assertStringContainsString('Snapshot Customer GmbH', $html);
    }
}
