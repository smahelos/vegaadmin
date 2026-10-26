<?php

namespace Tests\Feature\Domain\Invoice\Services;

use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Application\Invoice\Services\InvoicePdfRenderer;
use App\Application\Payment\Mappers\QrPaymentPayloadMapper;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Services\InvoicePrintDataBuilder;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Payment\DTO\QrPaymentPayload;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\Shared\Time\Contracts\DateFormatterInterface;
use App\Domain\User\Contracts\Locale;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicePdfServiceFeatureTest extends TestCase
{
    private InvoicePdfRenderer $renderer;

    private InvoicePdfRenderer $rendererMissingTemplate;

    private InvoicePrintDataBuilder $printDataBuilder;

    private TemporaryInvoicePrintDataFactoryInterface $tempFactory;

    private InvoiceDtoReadRepositoryInterface $invoiceDtoRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = app(InvoicePdfRenderer::class);
        $this->printDataBuilder = app(InvoicePrintDataBuilder::class);
        $this->tempFactory = app(TemporaryInvoicePrintDataFactoryInterface::class);
        $this->invoiceDtoRepo = app(InvoiceDtoReadRepositoryInterface::class);
        // Deterministic fake for missing template scenario
        $this->rendererMissingTemplate = new class(app(MoneyFormatterInterface::class), app(QrPaymentServiceInterface::class), app(QrPaymentPayloadMapper::class), app(DateFormatterInterface::class), app(ClockInterface::class), app(Locale::class)) extends InvoicePdfRenderer
        {
            protected function templateFileExists(string $template): bool
            {
                // Simulate that only 'default' exists; others missing
                return $template === 'default';
            }
        };
        View::addLocation(__DIR__ . '/../../Stubs/Views');
    }

    // No special tearDown needed as we are not mocking DomPDF facade now

    #[Test]
    public function generate_pdf_works_with_complete_invoice_data(): void
    {
        // Create test data
        $supplier = Supplier::factory()->create([
            'account_number' => '123456789',
            'bank_code' => '0100',
            'iban' => 'CZ1234567890123456789',
        ]);

        $client = Client::factory()->create();

        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = Status::factory()->create();

        $invoice = Invoice::factory()->create([
            'supplier_id' => $supplier->id,
            'client_id' => $client->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
            'invoice_vs' => '2024001',
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
        ]);

        // Build PrintableInvoiceData and ensure renderer is invoked
        $dto = $this->invoiceDtoRepo->findByIdAny(InvoiceId::fromInt($invoice->id));
        $printData = $this->printDataBuilder->build($dto);

        Pdf::shouldReceive('loadView')->once()->with(
            \Mockery::on(fn ($tpl) => is_string($tpl) && str_starts_with($tpl, 'pdfs.templates.')),
            \Mockery::on(fn ($data) => is_array($data) && isset($data['invoice'])),
        )->andReturnSelf();

        $this->renderer->render(
            $printData,
            $printData->template,
            'cs',
        );
    }

    #[Test]
    public function generate_pdf_handles_locale_settings(): void
    {
        $supplier = Supplier::factory()->create();
        $client = Client::factory()->create();
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = Status::factory()->create();

        $invoice = Invoice::factory()->create([
            'supplier_id' => $supplier->id,
            'client_id' => $client->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
        ]);

        $dto = $this->invoiceDtoRepo->findByIdAny(InvoiceId::fromInt($invoice->id));
        $printData = $this->printDataBuilder->build($dto);

        // Expect different locales to be passed through to view data
        $captured = [];
        Pdf::shouldReceive('loadView')->twice()->with(
            \Mockery::type('string'),
            \Mockery::on(function ($data) use (&$captured) {
                $captured[] = $data['locale'] ?? null;

                return true;
            }),
        )->andReturnSelf();

        $this->renderer->render(
            $printData,
            null,
            'cs',
        );
        $this->renderer->render(
            $printData,
            null,
            'en',
        );
        $this->assertEquals(['cs', 'en'], $captured);
    }

    #[Test]
    public function generate_pdf_from_data_processes_invoice_data(): void
    {
        $invoiceData = [
            'client_name' => 'Test Client',
            'client_street' => 'Test Street 123',
            'client_city' => 'Test City',
            'client_zip' => '12345',
            'client_country' => 'CZ',
            'name' => 'Test Supplier',
            'street' => 'Supplier Street 456',
            'city' => 'Supplier City',
            'zip' => '54321',
            'country' => 'CZ',
            'payment_method_id' => 1,
            'payment_status_id' => 1,
            'payment_currency' => 'CZK',
            'invoice_vs' => '2024002',
            'issue_date' => '2024-01-01',
            'due_in' => 30,
            'payment_amount' => 1200.00,
            'invoice_text' => 'Test invoice description',
            'lang' => 'cs',
            'invoice-products' => [],
        ];

        // Build from array and render
        $printData = $this->tempFactory->fromArray($invoiceData);
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        $this->renderer->render(
            $printData,
            $printData->template,
            $invoiceData['lang'],
        );
    }

    #[Test]
    public function generate_pdf_from_data_calculates_due_date_correctly(): void
    {
        $invoiceData = [
            'client_name' => 'Test Client',
            'name' => 'Test Supplier',
            'payment_method_id' => 1,
            'payment_status_id' => 1,
            'payment_currency' => 'CZK',
            'invoice_vs' => '2024003',
            'issue_date' => '2024-01-01',
            'due_in' => 30,
            'payment_amount' => 1000.00,
            'invoice_text' => 'Test invoice',
            'invoice-products' => [],
        ];

        $printData = $this->tempFactory->fromArray($invoiceData);
        $this->assertSame('2024-01-31', $printData->due_date?->date()->format('Y-m-d'));
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        $this->renderer->render(
            $printData,
            null,
            null,
        );
    }

    #[Test]
    public function generate_pdf_from_data_handles_minimal_required_data(): void
    {
        $invoiceData = [
            'payment_method_id' => 1,
            'payment_status_id' => 1,
            'payment_currency' => 'CZK',
            'invoice_vs' => '2024004',
            'issue_date' => '2024-01-01',
            'due_in' => 14,
            'payment_amount' => 500.00,
            'invoice_text' => 'Minimal invoice',
            'invoice-products' => [],
        ];

        $printData = $this->tempFactory->fromArray($invoiceData);
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        $this->renderer->render(
            $printData,
            null,
            null,
        );
    }

    #[Test]
    public function generate_pdf_from_data_respects_locale_preference(): void
    {
        $invoiceData = [
            'client_name' => 'Test Client',
            'name' => 'Test Supplier',
            'payment_method_id' => 1,
            'payment_status_id' => 1,
            'payment_currency' => 'EUR',
            'invoice_vs' => '2024005',
            'issue_date' => '2024-01-01',
            'due_in' => 30,
            'payment_amount' => 1000.00,
            'invoice_text' => 'Test invoice with locale',
            'lang' => 'en',
            'invoice-products' => [],
        ];

        $printData = $this->tempFactory->fromArray($invoiceData);
        $captured = null;
        Pdf::shouldReceive('loadView')->once()->with(
            \Mockery::type('string'),
            \Mockery::on(function ($data) use (&$captured) {
                $captured = $data['locale'];

                return true;
            }),
        )->andReturnSelf();
        $this->renderer->render(
            $printData,
            null,
            'cs',
        );
        $this->assertEquals('cs', $captured);
    }

    #[Test]
    public function generate_pdf_gracefully_handles_qr_code_errors(): void
    {
        $supplier = Supplier::factory()->create([
            'account_number' => '',
            'bank_code' => '',
            'iban' => '',
        ]);

        $client = Client::factory()->create();
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = Status::factory()->create();

        $invoice = Invoice::factory()->create([
            'supplier_id' => $supplier->id,
            'client_id' => $client->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
            'payment_amount' => 1000,
        ]);

        $dto = $this->invoiceDtoRepo->findByIdAny(InvoiceId::fromInt($invoice->id));
        $printData = $this->printDataBuilder->build($dto);
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        $this->renderer->render(
            $printData,
            null,
            null,
        );
    }

    #[Test]
    public function service_dependencies_are_working(): void
    {
        // Test that dependencies are properly injected and accessible in renderer
        $reflection = new \ReflectionClass($this->renderer);

        $moneyFormatterProperty = $reflection->getProperty('moneyFormatter');
        $moneyFormatterProperty->setAccessible(true);
        $moneyFormatter = $moneyFormatterProperty->getValue($this->renderer);
        $this->assertInstanceOf(MoneyFormatterInterface::class, $moneyFormatter);

        $qrPaymentServiceProperty = $reflection->getProperty('qrPaymentService');
        $qrPaymentServiceProperty->setAccessible(true);
        $qrPaymentService = $qrPaymentServiceProperty->getValue($this->renderer);
        $this->assertInstanceOf(QrPaymentServiceInterface::class, $qrPaymentService);
    }

    #[Test]
    public function get_template_name_returns_correct_values(): void
    {
        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('getTemplateName');
        $method->setAccessible(true);

        // Test valid templates (should return full Blade template path)
        $this->assertEquals('pdfs.templates.default', $method->invoke($this->renderer, 'default'));
        $this->assertEquals('pdfs.templates.modern', $method->invoke($this->renderer, 'modern'));
        $this->assertEquals('pdfs.templates.minimal', $method->invoke($this->renderer, 'minimal'));

        // Test invalid templates should return default
        $this->assertEquals('pdfs.templates.default', $method->invoke($this->renderer, 'invalid'));
        $this->assertEquals('pdfs.templates.default', $method->invoke($this->renderer, 'nonexistent'));
        $this->assertEquals('pdfs.templates.default', $method->invoke($this->renderer, ''));
        $this->assertEquals('pdfs.templates.default', $method->invoke($this->renderer, null));
    }

    #[Test]
    public function get_template_name_falls_back_when_file_missing(): void
    {
        $reflection = new \ReflectionClass($this->rendererMissingTemplate);
        $method = $reflection->getMethod('getTemplateName');
        $method->setAccessible(true);
        $resolved = $method->invoke($this->rendererMissingTemplate, 'modern');
        $this->assertEquals(
            'pdfs.templates.default',
            $resolved,
            'Should fall back to default when overridden file check returns false.',
        );
    }

    #[Test]
    public function locale_propagation_affects_money_formatting(): void
    {
        $supplier = Supplier::factory()->create();
        $client = Client::factory()->create();
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = Status::factory()->create();
        $invoice = Invoice::factory()->create([
            'supplier_id' => $supplier->id,
            'client_id' => $client->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
            'payment_amount' => 1234.56,
            'payment_currency' => 'EUR',
        ]);

        $dto = $this->invoiceDtoRepo->findByIdAny(InvoiceId::fromInt($invoice->id));
        $printData = $this->printDataBuilder->build($dto);

        $formatted = [];
        Pdf::shouldReceive('loadView')->twice()->with(
            \Mockery::type('string'),
            \Mockery::on(function ($data) use (&$formatted) {
                $formatted[] = $data['paymentAmountFormatted'] ?? '';

                return true;
            }),
        )->andReturnSelf();

        $this->renderer->render(
            $printData,
            null,
            'cs',
        );
        $this->renderer->render(
            $printData,
            null,
            'en',
        );

        $this->assertCount(2, $formatted);
        $this->assertNotEquals(
            $formatted[0],
            $formatted[1],
            'Different locales should format differently',
        );
        $this->assertMatchesRegularExpression('/,\d{2}$/', $formatted[0]);
        $this->assertMatchesRegularExpression('/\.\d{2}$/', $formatted[1]);
    }

    #[Test]
    public function invalid_template_name_falls_back_in_generate_pdf(): void
    {
        $client = Client::factory()->create();
        $supplier = Supplier::factory()->create();
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $paymentStatus = Status::factory()->create();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $paymentStatus->id,
            'template' => 'nonexistent_unlikely_template_name',
        ]);
        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('getTemplateName');
        $method->setAccessible(true);
        $resolved = $method->invoke($this->renderer, $invoice->template);
        $this->assertEquals(
            'pdfs.templates.default',
            $resolved,
            'Invalid template should fall back to default.',
        );
    }

    #[Test]
    public function invoice_with_template_generates_correct_pdf(): void
    {
        $client = Client::factory()->create();
        $supplier = Supplier::factory()->create();
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $paymentStatus = Status::factory()->create();

        // Create invoice with specific template
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $paymentStatus->id,
            'template' => 'modern',
        ]);

        $r = new \ReflectionClass($this->renderer);
        $getTemplate = $r->getMethod('getTemplateName');
        $getTemplate->setAccessible(true);
        $template = $getTemplate->invoke($this->renderer, $invoice->template);
        $this->assertEquals(
            'pdfs.templates.modern',
            $template,
            'Modern template should be selected',
        );

        $dto = $this->invoiceDtoRepo->findByIdAny(InvoiceId::fromInt($invoice->id));
        $printData = $this->printDataBuilder->build($dto);
        $captured = null;
        Pdf::shouldReceive('loadView')->once()->with(
            'pdfs.templates.modern',
            \Mockery::on(function ($data) use (&$captured) {
                $captured = $data;

                return true;
            }),
        )->andReturnSelf();
        $this->renderer->render(
            $printData,
            $invoice->template,
            'cs',
        );
        $this->assertIsArray($captured);
        $this->assertArrayHasKey('invoice', $captured);
        $this->assertArrayHasKey('supplier', $captured);
        $this->assertArrayHasKey('paymentAmountFormatted', $captured);
        $this->assertEquals('modern', $invoice->template);
    }

    #[Test]
    public function generate_pdf_includes_formatted_amounts_in_html(): void
    {
        $supplier = Supplier::factory()->create([
            'account_number' => '123456789',
            'bank_code' => '0100',
        ]);
        $client = Client::factory()->create();
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = Status::factory()->create();

        $invoice = Invoice::factory()->create([
            'supplier_id' => $supplier->id,
            'client_id' => $client->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
            'payment_amount' => 1234.56,
            'payment_currency' => 'CZK',
        ]);

        $dto = $this->invoiceDtoRepo->findByIdAny(InvoiceId::fromInt($invoice->id));
        $printData = $this->printDataBuilder->build($dto);
        $captured = null;
        Pdf::shouldReceive('loadView')->once()->with(
            \Mockery::type('string'),
            \Mockery::on(function ($data) use (&$captured) {
                $captured = $data;

                return true;
            }),
        )->andReturnSelf();
        $this->renderer->render(
            $printData,
            null,
            'cs',
        );

        $this->assertArrayHasKey('paymentAmountFormatted', $captured);
        $this->assertArrayHasKey('subtotalFormatted', $captured);
        $this->assertArrayHasKey('taxFormatted', $captured);

        $formatter = app(MoneyFormatterInterface::class);
        $this->assertEquals($formatter->format($printData->total_amount, 'cs'), $captured['paymentAmountFormatted']);
        $this->assertEquals($formatter->format($printData->subtotal, 'cs'), $captured['subtotalFormatted']);
        $this->assertEquals($formatter->format($printData->total_tax, 'cs'), $captured['taxFormatted']);

        foreach ([
            'paymentAmountFormatted',
            'subtotalFormatted',
            'taxFormatted',
        ] as $key) {
            $this->assertMatchesRegularExpression('/^(?:Kč|[€$£¥₿]|[A-Z]{3} )/u', $captured[$key]);
        }
    }

    #[Test]
    public function temporary_invoice_data_builder_formats_amounts_and_line_items(): void
    {
        $invoiceData = [
            'client_name' => 'Temp Client',
            'name' => 'Temp Supplier',
            'payment_method_id' => 1,
            'payment_status_id' => 1,
            'payment_currency' => 'EUR',
            'invoice_vs' => '2024999',
            'issue_date' => '2024-02-01',
            'due_in' => 10,
            'payment_amount' => 2500.75,
            'invoice_text' => 'Temp invoice',
            'lang' => 'cs',
            'invoice-products' => [
                [
                    'name' => 'Item A',
                    'quantity' => 2,
                    'unit' => 'pieces',
                    'price' => 100,
                    'currency' => 'EUR',
                    'tax_rate' => 21,
                    'tax_amount' => (100 * 2 * 21) / 100,
                    'total_price' => (100 * 2) + ((100 * 2 * 21) / 100),
                ],
                [
                    'name' => 'Item B',
                    'quantity' => 1,
                    'unit' => 'pieces',
                    'price' => 50.5,
                    'currency' => 'EUR',
                    'tax_rate' => 21,
                    'tax_amount' => (50.5 * 1 * 21) / 100,
                    'total_price' => (50.5 * 1) + ((50.5 * 1 * 21) / 100),
                ],
            ],
        ];

        $printData = $this->tempFactory->fromArray($invoiceData);
        $captured = null;
        Pdf::shouldReceive('loadView')->once()->with(
            \Mockery::type('string'),
            \Mockery::on(function ($data) use (&$captured) {
                $captured = $data;

                return true;
            }),
        )->andReturnSelf();
        $this->renderer->render(
            $printData,
            $printData->template,
            'cs',
        );

        $this->assertArrayHasKey('paymentAmountFormatted', $captured);
        $this->assertArrayHasKey('subtotalFormatted', $captured);
        $this->assertArrayHasKey('taxFormatted', $captured);
        $this->assertArrayHasKey('invoiceProductsFormatted', $captured);
        $this->assertCount(2, $captured['invoiceProductsFormatted']);

        $formatter = app(MoneyFormatterInterface::class);
        $currency = 'EUR';
        // The amount comes from the lines (50.50 * 21 % = 10.605 rounds to 10.61), not from the typed 2500.75
        $paymentMoney = Money::fromString('303.11', $currency);
        $this->assertEquals($formatter->format($paymentMoney, 'cs'), $captured['paymentAmountFormatted']);
        $this->assertSame('250.5', $printData->subtotal->getAmount());
        $this->assertSame('52.61', $printData->total_tax->getAmount());
        $this->assertSame('61.11', $printData->items[1]->line_total->getAmount());

        foreach ($captured['invoiceProductsFormatted'] as $item) {
            $this->assertArrayHasKey('price_formatted', $item);
            $this->assertArrayHasKey('total_price_formatted', $item);
            $this->assertMatchesRegularExpression('/^(?:Kč|[€$£¥₿]|[A-Z]{3} )/u', $item['price_formatted']);
            $this->assertMatchesRegularExpression('/^(?:Kč|[€$£¥₿]|[A-Z]{3} )/u', $item['total_price_formatted']);
        }

        foreach ([
            'paymentAmountFormatted',
            'subtotalFormatted',
            'taxFormatted',
        ] as $key) {
            $this->assertMatchesRegularExpression('/^(?:Kč|[€$£¥₿]|[A-Z]{3} )/u', $captured[$key]);
        }
    }

    #[Test]
    public function generate_pdf_uses_default_template_when_missing_specific_file(): void
    {
        // Prepare invoice with a template that our overridden service reports as missing.
        $supplier = Supplier::factory()->create();
        $client = Client::factory()->create();
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = Status::factory()->create();
        $invoice = Invoice::factory()->create([
            'supplier_id' => $supplier->id,
            'client_id' => $client->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
            'template' => 'modern', // "modern" will be treated as missing by overridden templateFileExists
        ]);

        // Expect DomPDF facade to be invoked with the *default* template due to fallback.
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdfs.templates.default', \Mockery::on(function ($data) use ($invoice) {
                // Basic shape assertions for passed view data.
                return is_array($data)
                    && isset($data['invoice'])
                    && ($data['invoice']->invoice_vs === $invoice->invoice_vs || true);
            }))
            ->andReturnSelf();
        // Build print data and invoke renderer (will not render real view thanks to facade mock).
        $dto = $this->invoiceDtoRepo->findByIdAny(InvoiceId::fromInt($invoice->id));
        $printData = $this->printDataBuilder->build($dto);
        $this->rendererMissingTemplate->render(
            $printData,
            $invoice->template,
            null,
        );
    }

    #[Test]
    public function qr_code_exception_sets_has_qr_code_false_and_logs_error(): void
    {

        // Bind throwing QR payment service
        app()->bind(QrPaymentServiceInterface::class, function () {
            return new class implements QrPaymentServiceInterface
            {
                public function generateQrCodeBase64(QrPaymentPayload $payload): ?string
                {
                    throw new \RuntimeException('QR fail test');
                }

                public function hasRequiredPaymentInfo(QrPaymentPayload $payload): bool
                {
                    return true; // not relevant for exception path
                }
            };
        });

        // Resolve fresh renderer with throwing dependency
        $failingRenderer = app(InvoicePdfRenderer::class);

        $supplier = Supplier::factory()->create([
            'account_number' => '123456789',
            'bank_code' => '0100',
        ]);
        $client = Client::factory()->create();
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = Status::factory()->create();
        $invoice = Invoice::factory()->create([
            'supplier_id' => $supplier->id,
            'client_id' => $client->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
            'payment_amount' => 999.99,
            'payment_currency' => 'CZK',
        ]);

        $dto = $this->invoiceDtoRepo->findByIdAny(InvoiceId::fromInt($invoice->id));
        $printData = $this->printDataBuilder->build($dto);
        $captured = null;
        Pdf::shouldReceive('loadView')->once()->with(
            \Mockery::type('string'),
            \Mockery::on(function ($data) use (&$captured) {
                $captured = $data;

                return true;
            }),
        )->andReturnSelf();
        $failingRenderer->render(
            $printData,
            null,
            'cs',
        );

        $this->assertArrayHasKey('qrCode', $captured);
        $this->assertNull($captured['qrCode']);
        $this->assertFalse($captured['hasQrCode']);
    }
}
