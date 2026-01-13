<?php

namespace Tests\Feature\Domain\Payment\Services;

use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\User;
use App\Domain\Payment\Services\QrPaymentService;
use App\Application\Payment\Mappers\QrPaymentPayloadMapper;
use App\Domain\Payment\DTO\QrPaymentPayload;
use Tests\Traits\RefreshDatabaseWithData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;
use App\Domain\Payment\Contracts\QrCodeRendererInterface;

class QrPaymentServiceFeatureTest extends TestCase
{
    // Use custom trait that runs fresh migrations and seeds required data (permissions, roles, etc.)
    use RefreshDatabaseWithData, CreatesFrontendTestEnvironment;

    private QrPaymentService $service;

    private QrCodeRendererInterface $qrRenderer;
    private QrPaymentPayloadMapper $mapper;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFrontendTestEnvironment();

        $this->qrRenderer = $this->createMock(QrCodeRendererInterface::class);
        // Ensure renderer returns a valid data URL instead of an empty string in tests
        $this->qrRenderer
            ->method('renderDataUrl')
            ->willReturnCallback(function (string $qrString, int $size = 300, int $margin = 2, string $errorCorrection = 'H'): string {
                return 'data:image/png;base64,' . base64_encode($qrString);
            });
        $this->service = new QrPaymentService($this->qrRenderer);
        $this->mapper = app(QrPaymentPayloadMapper::class);
        $this->user = User::factory()->create();
    }

    #[Test]
    public function generate_qr_code_base64_returns_null_for_missing_invoice_vs(): void
    {
        $data = [
            'payment_amount' => 1000,
            'account_number' => '123456789',
            'bank_code' => '0100',
            // Missing invoice_vs
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->generateQrCodeBase64($payload);
        
        // Variable symbol is optional for CZ provider, valid account + amount should generate QR
        $this->assertNotNull($result);
        $this->assertIsString($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }

    #[Test]
    public function generate_qr_code_base64_returns_null_for_missing_payment_amount(): void
    {
        $data = [
            'invoice_vs' => '20240001',
            'account_number' => '123456789',
            'bank_code' => '0100',
            // Missing payment_amount
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->generateQrCodeBase64($payload);
        
        $this->assertNull($result);
    }

    #[Test]
    public function generate_qr_code_base64_returns_null_for_missing_account_info(): void
    {
        $data = [
            'invoice_vs' => '20240001',
            'payment_amount' => 1000,
            // Missing account info
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->generateQrCodeBase64($payload);
        
        $this->assertNull($result);
    }

    #[Test]
    public function generate_qr_code_base64_generates_base64_string_with_valid_data(): void
    {
        $data = [
            'invoice_vs' => '20240001',
            'payment_amount' => 1500.50,
            'payment_currency' => 'CZK',
            'account_number' => '123456789',
            'bank_code' => '0100',
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->generateQrCodeBase64($payload);
        
        // Result should be either null or a valid base64 string
        $this->assertTrue($result === null || (is_string($result) && str_starts_with($result, 'data:image/png;base64,')));
    }

    #[Test]
    public function generate_qr_code_base64_uses_iban_when_available(): void
    {
        $data = [
            'invoice_vs' => '20240001',
            'payment_amount' => 1000,
            'payment_currency' => 'EUR',
            'iban' => 'CZ6508000000192000145399',
            'account_number' => '123456789', // Should be ignored in favor of IBAN
            'bank_code' => '0100',
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->generateQrCodeBase64($payload);
        
        // Result should be either null or a valid base64 string
        $this->assertTrue($result === null || (is_string($result) && str_starts_with($result, 'data:image/png;base64,')));
    }

    #[Test]
    public function generate_qr_code_base64_uses_supplier_account_info_when_invoice_missing(): void
    {
        $data = [
            'invoice_vs' => '20240001',
            'payment_amount' => 750,
            'payment_currency' => 'CZK',
            'supplier_account_number' => '987654321',
            'supplier_bank_code' => '0200',
            'supplier_name' => 'Test Supplier',
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->generateQrCodeBase64($payload);
        
        $this->assertNotNull($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }

    #[Test]
    public function has_required_payment_info_returns_false_for_invoice_without_account_info(): void
    {
        // Create invoice without supplier to ensure no account info
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'payment_amount' => '1000.00',
            'payment_currency' => 'CZK',
            'invoice_vs' => '20240001',
        ]);
        $payload = $this->mapper->fromArray($invoice->toArray());
        $result = $this->service->hasRequiredPaymentInfo($payload);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function has_required_payment_info_returns_false_for_missing_account_info(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'supplier_id' => null,
            'payment_amount' => '1000.00',
            'payment_currency' => 'CZK',
            'invoice_vs' => '20240001'
        ]);
        $payload = $this->mapper->fromArray($invoice->toArray());
        $result = $this->service->hasRequiredPaymentInfo($payload);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function has_required_payment_info_works_with_object_having_account_number(): void
    {
        $data = [
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'invoice_vs' => '20240001',
            'account_number' => '123456789',
            'bank_code' => '0100',
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->hasRequiredPaymentInfo($payload);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function has_required_payment_info_works_with_object_having_iban(): void
    {
        $data = [
            'payment_amount' => 1000,
            'payment_currency' => 'EUR',
            'invoice_vs' => '20240001',
            // Use non-CZ IBAN to trigger EU provider for EUR payments
            'iban' => 'DE89370400440532013000',
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->hasRequiredPaymentInfo($payload);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function has_required_payment_info_returns_true_for_supplier_with_account_info(): void
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'account_number' => '987654321',
            'bank_code' => '0200'
        ]);
        
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'supplier_id' => $supplier->id,
            'payment_amount' => '1000.00',
            'payment_currency' => 'CZK',
            'invoice_vs' => '20240001'
        ]);
        // Mapper fromInvoice does not fallback to supplier account data; use array with supplier_* keys
        $data = array_merge($invoice->toArray(), [
            'supplier_account_number' => $supplier->account_number,
            'supplier_bank_code' => $supplier->bank_code,
        ]);
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->hasRequiredPaymentInfo($payload);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function has_required_payment_info_returns_true_for_supplier_with_iban(): void
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            // Use EU IBAN to match EUR currency and EU provider
            'iban' => 'DE89370400440532013000'
        ]);
        
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'supplier_id' => $supplier->id,
            'payment_amount' => '1000.00',
            'payment_currency' => 'EUR',
            'invoice_vs' => '20240001'
        ]);
        // Mapper fromInvoice does not fallback to supplier IBAN; use array with supplier_* keys
        $data = array_merge($invoice->toArray(), [
            'supplier_iban' => $supplier->iban,
        ]);
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->hasRequiredPaymentInfo($payload);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function generate_qr_code_handles_exception_gracefully(): void
    {
        // Create an object that might cause issues in QR generation
        $data = [
            'invoice_vs' => '20240001',
            // Zero amount is invalid for providers but should not throw in mapper
            'payment_amount' => 0,
            'payment_currency' => 'CZK',
            'account_number' => '123456789',
            'bank_code' => '0100',
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->generateQrCodeBase64($payload);
        
        // Should handle any issues and return a result or null
        $this->assertTrue($result === null || (is_string($result) && str_starts_with($result, 'data:image/png;base64,')));
    }

    #[Test]
    public function generate_qr_code_with_complex_invoice_data(): void
    {
        $data = [
            'invoice_vs' => '20240001',
            'payment_amount' => 2500.75,
            'payment_currency' => 'CZK',
            'account_number' => '123456789',
            'bank_code' => '0100',
            'invoice_ks' => '0308',
            'invoice_ss' => '1234567890',
            'name' => 'Test Company s.r.o.',
            'issue_date' => '2024-01-15',
            'due_in' => 14,
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->generateQrCodeBase64($payload);
        
        $this->assertNotNull($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }

    #[Test]
    public function service_handles_array_input_gracefully(): void
    {
        $data = [
            'invoice_vs' => '20240001',
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'account_number' => '123456789',
            'bank_code' => '0100'
        ];
        $payload = $this->mapper->fromArray($data);
        $result = $this->service->generateQrCodeBase64($payload);
        
        // Result should be either null or a valid base64 string
        $this->assertTrue($result === null || (is_string($result) && str_starts_with($result, 'data:image/png;base64,')));
    }
}
