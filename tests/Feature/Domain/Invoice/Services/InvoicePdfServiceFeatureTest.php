<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Invoice\Services;

use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Domain\Invoice\Services\InvoicePrintDataBuilder;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Payment\DTO\QrPaymentDTO;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Renderers\Invoice\InvoicePdfRenderer;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\RendersInvoicePdfHtml;
use Tests\TestCase;

final class InvoicePdfServiceFeatureTest extends TestCase
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
    public function prints_saved_parties_and_account_even_after_sources_change(string $template): void
    {
        $invoice = $this->invoice();
        Business::whereKey($invoice->business_id)->update([
            'name' => 'Changed live business',
            'logo' => 'missing.png',
            'archived_at' => now(),
        ]);
        $contact = Contact::factory()->create([
            'user_id' => $invoice->user_id,
            'name' => 'Changed live contact',
            'archived_at' => now(),
        ]);
        $invoice->update(['contact_id' => $contact->id]);
        $html = $this->renderPdfHtml($this->printable($invoice), $template);
        foreach ([
            'Saved supplier',
            'Saved customer',
            'Saved street',
            'Saved city',
            '27074358',
            'CZ27074358',
            '2001234561',
            '2010',
            'Saved bank',
        ] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        $this->assertStringNotContainsString('Changed live', $html);
    }

    #[Test]
    public function qr_uses_the_saved_account_country_amount_and_symbols(): void
    {
        $qr = new class implements QrPaymentServiceInterface
        {
            public ?QrPaymentDTO $payload = null;

            public function generateQrCodeBase64(QrPaymentDTO $payload): ?string
            {
                $this->payload = $payload;

                return 'data:image/png;base64,cXItc25hcHNob3Q=';
            }
        };
        $this->app->instance(QrPaymentServiceInterface::class, $qr);
        $invoice = $this->invoice();
        $html = $this->renderPdfHtml($this->printable($invoice));
        $this->assertNotNull($qr->payload);
        $this->assertSame('20260001', $qr->payload->variableSymbol);
        $this->assertSame('1210', $qr->payload->amount->getAmount());
        $this->assertSame('CZK', $qr->payload->amount->getCurrency());
        $this->assertSame('2001234561', $qr->payload->account?->number);
        $this->assertSame('2010', $qr->payload->account?->bankCode);
        $this->assertSame('CZ', $qr->payload->account?->country->value);
        $this->assertSame('Saved notes', $qr->payload->message);
        $this->assertStringContainsString('data:image/png;base64,cXItc25hcHNob3Q=', $html);
    }

    #[Test]
    public function cash_does_not_generate_a_payment_qr_even_with_a_saved_account(): void
    {
        $qr = $this->createMock(QrPaymentServiceInterface::class);
        $qr->expects($this->never())->method('generateQrCodeBase64');
        $this->app->instance(QrPaymentServiceInterface::class, $qr);
        $invoice = $this->invoice();
        $invoice->update(['payment_method_id' => PaymentMethod::where('slug', PaymentMethodCode::CASH->value)->sole()->id]);
        $html = $this->renderPdfHtml($this->printable($invoice));
        $this->assertStringNotContainsString('data:image/png;base64,', $html);
    }

    #[Test]
    public function print_uses_only_the_document_logo_and_tolerates_a_missing_file(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('logo.png');
        Storage::disk('public')->put('documents/logos/saved.png', file_get_contents($file->getPathname()));
        $invoice = $this->invoice();
        $invoice->update(['supplier_logo' => 'documents/logos/saved.png']);
        $html = $this->renderPdfHtml($this->printable($invoice));
        $this->assertStringContainsString(Storage::disk('public')->path('documents/logos/saved.png'), $html);
        Storage::disk('public')->delete('documents/logos/saved.png');
        $html = $this->renderPdfHtml($this->printable($invoice));
        $this->assertStringNotContainsString('documents/logos/saved.png', $html);
    }

    #[Test]
    public function prints_dates_and_money_in_the_requested_locale_and_restores_the_previous_locale(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 20)->setTime(14, 7));
        $invoice = $this->invoice();
        $previous = app()->getLocale();
        $print = $this->printable($invoice);
        $german = $this->renderPdfHtml(
            $print,
            'default',
            'de',
        );
        $this->assertSame($previous, app()->getLocale());
        $english = $this->renderPdfHtml(
            $print,
            'default',
            'en',
        );
        $this->assertSame($previous, app()->getLocale());
        $this->assertStringContainsString('05.03.2026', $german);
        $this->assertStringContainsString('19.03.2026', $german);
        $this->assertStringContainsString('20.03.2026, 14:07', $german);
        $this->assertStringContainsString('Mar 5, 2026', $english);
        $this->assertStringContainsString(app(MoneyFormatterInterface::class)->format($print->totalAmount, 'en'), $english);
    }

    #[Test]
    #[DataProvider('templates')]
    public function a_guest_prints_the_same_snapshot_shape_and_server_totals(string $template): void
    {
        $invoice = $this->invoice();
        $dto = app(InvoiceDtoReadRepositoryInterface::class)->findByIdForUser(InvoiceId::fromInt($invoice->id), UserId::fromInt($invoice->user_id));
        $print = app(TemporaryInvoicePrintDataFactoryInterface::class)->fromArray([
            'supplier' => $dto->parties->supplier,
            'customer' => $dto->parties->customer,
            'account' => $dto->parties->account,
            'invoice_vs' => '20260002',
            'issue_date' => '2026-03-05',
            'tax_point_date' => '2026-03-04',
            'due_in' => 14,
            'payment_currency' => 'CZK',
            'payment_method_id' => $invoice->payment_method_id,
            'payment_amount' => '999999',
            'invoice-products' => [[
                'name' => 'Guest service',
                'quantity' => '2.5',
                'price' => '100',
                'currency' => 'CZK',
                'unit' => 'hours',
                'tax_rate' => '21',
            ]],
        ]);
        $this->assertSame('250', $print->subtotal->getAmount());
        $this->assertSame('52.5', $print->totalTax->getAmount());
        $this->assertSame('302.5', $print->totalAmount->getAmount());
        $html = $this->renderPdfHtml($print, $template);
        $this->assertStringContainsString('Guest service', $html);
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $lastCell = (new DOMXPath($dom))->query("//tr[td[1][normalize-space()='Guest service']]/td[last()]")->item(0);
        $this->assertNotNull($lastCell);
        $this->assertSame(app(MoneyFormatterInterface::class)->format($print->totalAmount, 'cs'), trim($lastCell->textContent));
        $this->assertStringContainsString('Saved supplier', $html);
        $this->assertStringContainsString('Saved customer', $html);
        $this->assertStringNotContainsString('999999', $html);
    }

    #[Test]
    public function renders_a_downloadable_pdf_and_falls_back_from_an_unknown_template(): void
    {
        $print = $this->printable($this->invoice());
        $pdf = app(InvoicePdfRenderer::class)->render(
            $print,
            'unknown-template',
            'cs',
        );
        $this->assertStringStartsWith('%PDF-', $pdf->output());
    }

    private function invoice(): Invoice
    {
        return Invoice::factory()->create([
            'invoice_vs' => '20260001',
            'issue_date' => '2026-03-05',
            'tax_point_date' => '2026-03-04',
            'due_in' => 14,
            'invoice_text' => 'Saved notes',
            'payment_currency' => 'CZK',
            'payment_amount' => '1210',
            'supplier_name' => 'Saved supplier',
            'supplier_street' => 'Saved street',
            'supplier_city' => 'Saved city',
            'supplier_postal_code' => '11000',
            'supplier_country' => 'CZ',
            'supplier_company_number' => '27074358',
            'supplier_registration_country' => 'CZ',
            'supplier_vat_number' => 'CZ27074358',
            'customer_name' => 'Saved customer',
            'account_number' => '2001234561',
            'account_bank_code' => '2010',
            'account_country' => 'CZ',
            'account_bank_name' => 'Saved bank',
        ]);
    }

    private function printable(Invoice $invoice): PrintableInvoiceDTO
    {
        $dto = app(InvoiceDtoReadRepositoryInterface::class)->findByIdForUser(InvoiceId::fromInt($invoice->id), UserId::fromInt($invoice->user_id));

        return app(InvoicePrintDataBuilder::class)->build($dto);
    }
}
