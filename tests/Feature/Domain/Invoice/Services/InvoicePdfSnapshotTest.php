<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Invoice\Services;

use App\Domain\Invoice\Services\InvoicePrintDataBuilder;
use App\Domain\Party\DTO\InvoicingSettings;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Translation\Enums\Language;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceMapper;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\PaymentMethod;
use App\Models\VatLine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\RendersInvoicePdfHtml;
use Tests\TestCase;

final class InvoicePdfSnapshotTest extends TestCase
{
    use RendersInvoicePdfHtml;

    /** @return array<string, array{string, bool, bool}> */
    public static function templates(): array
    {
        return [
            'default' => [
                'default',
                true,
                false,
            ],
            'modern' => [
                'modern',
                true,
                false,
            ],
            'minimal' => [
                'minimal',
                true,
                false,
            ],
            'default non-payer' => [
                'default',
                false,
                false,
            ],
            'modern non-payer' => [
                'modern',
                false,
                false,
            ],
            'minimal non-payer' => [
                'minimal',
                false,
                false,
            ],
            'default draft' => [
                'default',
                true,
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('templates')]
    public function pdf_templates_print_both_saved_roles_and_amounts(
        string $template,
        bool $vatPayer,
        bool $draft,
    ): void {
        $invoiceFactory = Invoice::factory();
        if ($draft) {
            // A draft has no number and no dates and prints the business's present settings
            $invoiceFactory = $invoiceFactory->draft();
            $issue = [];
        } else {
            $issue = [
                'invoice_vs' => '20260001',
                'issue_date' => '2026-03-05',
                'tax_point_date' => '2026-03-04',
                'is_vat_payer' => $vatPayer,
            ];
        }
        $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::CASH->value);
        $invoice = $invoiceFactory->create(
            [
                ...$issue,
                'invoice_ks' => '0308',
                'invoice_ss' => '1234',
                'due_in' => 14,
                'invoice_text' => 'Snapshot invoice text',
                'payment_currency' => 'EUR',
                'payment_amount' => $vatPayer ? '1321.99' : '1099.99',
                'payment_method_id' => $paymentMethodQuery->sole()->id,
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
                'template' => $template,
            ],
        );
        $invoiceProductFactory = InvoiceProduct::factory();
        $invoiceProductFactory->create(
            [
                'invoice_id' => $invoice->id,
                'product_id' => null,
                'name' => 'Snapshot consulting',
                'quantity' => '2',
                'unit' => 'hours',
                'price' => '500',
                'currency' => 'EUR',
                'tax_rate' => $vatPayer ? '21' : '0',
            ],
        );
        $invoiceProductFactory->create(
            [
                'invoice_id' => $invoice->id,
                'product_id' => null,
                'name' => 'Snapshot licence',
                'quantity' => '3',
                'unit' => 'pieces',
                'price' => '33.33',
                'currency' => 'EUR',
                'tax_rate' => $vatPayer ? '12' : '0',
            ],
        );
        // Issuing a payer's invoice stored its VAT by rate
        if ($vatPayer && !$draft) {
            $vatLineFactory = VatLine::query();
            $vatLineFactory->create(
                [
                    'invoice_id' => $invoice->id,
                    'rate' => '21',
                    'base' => '1000',
                    'vat' => '210',
                ],
            );
            $vatLineFactory->create(
                [
                    'invoice_id' => $invoice->id,
                    'rate' => '12',
                    'base' => '99.99',
                    'vat' => '12',
                ],
            );
        }
        $invoiceMapper = app(EloquentInvoiceMapper::class);
        $invoice->load(['invoiceProducts', 'vatLines']);
        $dto = $invoiceMapper->toDto($invoice);
        $invoicePrintDataBuilder = app(InvoicePrintDataBuilder::class);
        $html = $this->renderPdfHtml(
            $invoicePrintDataBuilder->build(
                $dto,
                new InvoicingSettings(
                    $vatPayer,
                    'C 12345 vedená u Městského soudu v Praze',
                    Language::CS,
                ),
            ),
        );
        $normalized = trim(
            preg_replace(
                '/[ \t]+$/m',
                '',
                preg_replace(
                    '/[ \t]+/',
                    ' ',
                    str_replace(
                        "\r\n",
                        "\n",
                        $html,
                    ),
                ),
            ),
        );
        $suffix = $vatPayer ? '' : '_non_payer';
        if ($draft) {
            $suffix = '_draft';
        }
        $snapshot = __DIR__ . '/__snapshots__/invoice_' . $template . $suffix . '.html';
        if (getenv('UPDATE_PDF_SNAPSHOTS') === '1') {
            file_put_contents($snapshot, $normalized);
        }
        $this->assertFileExists($snapshot);
        $this->assertSame(
            file_get_contents($snapshot),
            $normalized,
        );
        $this->assertStringContainsString('Snapshot Supplier s.r.o.', $html);
        $this->assertStringContainsString('Snapshot Customer GmbH', $html);
    }
}
