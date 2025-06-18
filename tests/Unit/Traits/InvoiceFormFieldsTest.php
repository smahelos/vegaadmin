<?php

namespace Tests\Unit\Traits;

use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use App\Services\CountryService;
use App\Traits\InvoiceFormFields;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceFormFieldsTest extends TestCase
{
    use InvoiceFormFields;

    #[Test]
    public function trait_can_be_used(): void
    {
        $this->assertTrue(method_exists($this, 'getInvoiceFields'));
    }

    #[Test]
    public function get_invoice_fields_method_exists(): void
    {
        $this->assertTrue(method_exists($this, 'getInvoiceFields'));
    }

    #[Test]
    public function get_invoice_fields_method_is_protected(): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('getInvoiceFields');

        $this->assertTrue($method->isProtected());
    }

    #[Test]
    public function get_invoice_fields_returns_array(): void
    {
        // Mock CountryService
        $countryService = $this->createMock(CountryService::class);
        $countryService->method('getCountryCodesForSelect')
            ->willReturn(['CZ' => 'Czech Republic', 'SK' => 'Slovakia']);

        App::shouldReceive('make')
            ->with(CountryService::class)
            ->andReturn($countryService);

        $result = $this->getInvoiceFields();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function get_invoice_fields_accepts_all_parameters(): void
    {
        // Mock CountryService
        $countryService = $this->createMock(CountryService::class);
        $countryService->method('getCountryCodesForSelect')
            ->willReturn(['CZ' => 'Czech Republic']);

        App::shouldReceive('make')
            ->with(CountryService::class)
            ->andReturn($countryService);

        $clients = [1 => 'Client 1'];
        $suppliers = [1 => 'Supplier 1'];
        $paymentMethods = [1 => 'Cash'];
        $statuses = [1 => 'Paid'];
        $currencies = ['USD' => 'USD'];

        $result = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses, $currencies);

        $this->assertIsArray($result);
    }

    #[Test]
    public function get_invoice_fields_uses_default_currencies_when_empty(): void
    {
        // Mock CountryService
        $countryService = $this->createMock(CountryService::class);
        $countryService->method('getCountryCodesForSelect')
            ->willReturn(['CZ' => 'Czech Republic']);

        App::shouldReceive('make')
            ->with(CountryService::class)
            ->andReturn($countryService);

        $result = $this->getInvoiceFields();

        // Find currency field
        $currencyField = collect($result)->firstWhere('name', 'payment_currency');
        
        $this->assertNotNull($currencyField);
        $this->assertArrayHasKey('options', $currencyField);
        $this->assertArrayHasKey('CZK', $currencyField['options']);
        $this->assertArrayHasKey('EUR', $currencyField['options']);
        $this->assertArrayHasKey('USD', $currencyField['options']);
    }

    #[Test]
    public function get_invoice_fields_contains_required_invoice_fields(): void
    {
        // Mock CountryService
        $countryService = $this->createMock(CountryService::class);
        $countryService->method('getCountryCodesForSelect')
            ->willReturn(['CZ' => 'Czech Republic']);

        App::shouldReceive('make')
            ->with(CountryService::class)
            ->andReturn($countryService);

        $result = $this->getInvoiceFields();
        $fieldNames = collect($result)->pluck('name')->toArray();

        // Check for required invoice fields
        $this->assertContains('invoice_vs', $fieldNames);
        $this->assertContains('invoice_ks', $fieldNames);
        $this->assertContains('invoice_ss', $fieldNames);
        $this->assertContains('issue_date', $fieldNames);
        $this->assertContains('tax_point_date', $fieldNames);
        $this->assertContains('payment_method_id', $fieldNames);
        $this->assertContains('due_in', $fieldNames);
        $this->assertContains('payment_amount', $fieldNames);
        $this->assertContains('payment_currency', $fieldNames);
        $this->assertContains('payment_status_id', $fieldNames);
        $this->assertContains('invoice_text', $fieldNames);
    }

    #[Test]
    public function get_invoice_fields_contains_supplier_fields(): void
    {
        // Mock CountryService
        $countryService = $this->createMock(CountryService::class);
        $countryService->method('getCountryCodesForSelect')
            ->willReturn(['CZ' => 'Czech Republic']);

        App::shouldReceive('make')
            ->with(CountryService::class)
            ->andReturn($countryService);

        $result = $this->getInvoiceFields();
        $fieldNames = collect($result)->pluck('name')->toArray();

        // Check for supplier fields
        $this->assertContains('supplier_id', $fieldNames);
        $this->assertContains('name', $fieldNames);
        $this->assertContains('email', $fieldNames);
        $this->assertContains('phone', $fieldNames);
        $this->assertContains('street', $fieldNames);
        $this->assertContains('city', $fieldNames);
        $this->assertContains('zip', $fieldNames);
        $this->assertContains('country', $fieldNames);
        $this->assertContains('ico', $fieldNames);
        $this->assertContains('dic', $fieldNames);
    }

    #[Test]
    public function get_invoice_fields_contains_client_fields(): void
    {
        // Mock CountryService
        $countryService = $this->createMock(CountryService::class);
        $countryService->method('getCountryCodesForSelect')
            ->willReturn(['CZ' => 'Czech Republic']);

        App::shouldReceive('make')
            ->with(CountryService::class)
            ->andReturn($countryService);

        $result = $this->getInvoiceFields();
        $fieldNames = collect($result)->pluck('name')->toArray();

        // Check for client fields
        $this->assertContains('client_id', $fieldNames);
        $this->assertContains('client_name', $fieldNames);
        $this->assertContains('client_email', $fieldNames);
        $this->assertContains('client_phone', $fieldNames);
        $this->assertContains('client_street', $fieldNames);
        $this->assertContains('client_city', $fieldNames);
        $this->assertContains('client_zip', $fieldNames);
        $this->assertContains('client_country', $fieldNames);
        $this->assertContains('client_ico', $fieldNames);
        $this->assertContains('client_dic', $fieldNames);
    }

    #[Test]
    public function get_invoice_fields_contains_payment_information_fields(): void
    {
        // Mock CountryService
        $countryService = $this->createMock(CountryService::class);
        $countryService->method('getCountryCodesForSelect')
            ->willReturn(['CZ' => 'Czech Republic']);

        App::shouldReceive('make')
            ->with(CountryService::class)
            ->andReturn($countryService);

        $result = $this->getInvoiceFields();
        $fieldNames = collect($result)->pluck('name')->toArray();

        // Check for payment information fields
        $this->assertContains('account_number', $fieldNames);
        $this->assertContains('bank_code', $fieldNames);
        $this->assertContains('bank_name', $fieldNames);
        $this->assertContains('iban', $fieldNames);
        $this->assertContains('swift', $fieldNames);
    }

    #[Test]
    public function get_invoice_fields_has_proper_field_structure(): void
    {
        // Mock CountryService
        $countryService = $this->createMock(CountryService::class);
        $countryService->method('getCountryCodesForSelect')
            ->willReturn(['CZ' => 'Czech Republic']);

        App::shouldReceive('make')
            ->with(CountryService::class)
            ->andReturn($countryService);

        $result = $this->getInvoiceFields();

        // Check first field structure
        $firstField = $result[0];
        
        $this->assertArrayHasKey('name', $firstField);
        $this->assertArrayHasKey('label', $firstField);
        $this->assertArrayHasKey('type', $firstField);
        $this->assertEquals('invoice_vs', $firstField['name']);
    }

    #[Test]
    public function get_invoice_fields_sets_model_classes_correctly(): void
    {
        // Mock CountryService
        $countryService = $this->createMock(CountryService::class);
        $countryService->method('getCountryCodesForSelect')
            ->willReturn(['CZ' => 'Czech Republic']);

        App::shouldReceive('make')
            ->with(CountryService::class)
            ->andReturn($countryService);

        $result = $this->getInvoiceFields();

        // Find fields with model property
        $paymentMethodField = collect($result)->firstWhere('name', 'payment_method_id');
        $statusField = collect($result)->firstWhere('name', 'payment_status_id');
        $supplierField = collect($result)->firstWhere('name', 'supplier_id');
        $clientField = collect($result)->firstWhere('name', 'client_id');

        $this->assertEquals(PaymentMethod::class, $paymentMethodField['model']);
        $this->assertEquals(Status::class, $statusField['model']);
        $this->assertEquals(Supplier::class, $supplierField['model']);
        $this->assertEquals(Client::class, $clientField['model']);
    }

    #[Test]
    public function get_invoice_fields_method_signature_is_correct(): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('getInvoiceFields');
        $parameters = $method->getParameters();

        $this->assertCount(5, $parameters);
        
        $this->assertEquals('clients', $parameters[0]->getName());
        $this->assertEquals('suppliers', $parameters[1]->getName());
        $this->assertEquals('paymentMethods', $parameters[2]->getName());
        $this->assertEquals('statuses', $parameters[3]->getName());
        $this->assertEquals('currencies', $parameters[4]->getName());

        // All parameters should have default values (empty arrays)
        foreach ($parameters as $parameter) {
            $this->assertTrue($parameter->isDefaultValueAvailable());
        }
    }
}
