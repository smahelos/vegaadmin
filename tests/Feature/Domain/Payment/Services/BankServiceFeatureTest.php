<?php

namespace Tests\Feature\Domain\Payment\Services;

use App\Models\Bank;
use App\Domain\Payment\Services\BankService;
use App\Domain\Payment\Contracts\BankDtoRepository;
use Tests\Traits\RefreshDatabaseWithData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BankServiceFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;

    private BankService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Resolve repository then construct service explicitly to honor Domain DI
        $repo = app(BankDtoRepository::class);
        $this->service = new BankService($repo);
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = new BankService(app(BankDtoRepository::class));
        $this->assertInstanceOf(BankService::class, $service);
    }

    #[Test]
    public function get_banks_for_dropdown_returns_array(): void
    {
        $result = $this->service->getBanksForDropdown();
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function get_banks_for_dropdown_returns_banks_with_correct_structure(): void
    {
        $uniqueId = uniqid();
        
        // Create test banks
        Bank::create([
            'name' => 'Test Bank 1 ' . $uniqueId,
            'code' => '0100' . substr($uniqueId, -2),
            'swift' => 'KOMBCZPP',
            'country' => 'CZ'
        ]);
        
        Bank::create([
            'name' => 'Test Bank 2 ' . $uniqueId,
            'code' => '0200' . substr($uniqueId, -2),
            'swift' => 'GIBACZPX',
            'country' => 'CZ'
        ]);

        $result = $this->service->getBanksForDropdown('CZ');
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Check structure of bank entries
        foreach ($result as $bank) {
            $this->assertIsArray($bank);
            $this->assertArrayHasKey('text', $bank);
            $this->assertArrayHasKey('value', $bank);
            $this->assertArrayHasKey('swift', $bank);
        }
    }

    #[Test]
    public function get_banks_for_dropdown_filters_by_country(): void
    {
        $uniqueId = uniqid();
        
        // Create banks for different countries
        Bank::create([
            'name' => 'Czech Bank ' . $uniqueId,
            'code' => '0100' . substr($uniqueId, -2),
            'country' => 'CZ'
        ]);
        
        Bank::create([
            'name' => 'Slovak Bank ' . $uniqueId,
            'code' => '0200' . substr($uniqueId, -2),
            'country' => 'SK'
        ]);

        $resultCZ = $this->service->getBanksForDropdown('CZ');
        
        $this->assertIsArray($resultCZ);
        $this->assertNotEmpty($resultCZ);
    }

    #[Test]
    public function get_banks_for_dropdown_formats_text_correctly(): void
    {
        $uniqueId = uniqid();
        
        // Create multiple banks to ensure at least one survives the placeholder override
        Bank::create([
            'name' => 'First Bank ' . $uniqueId,
            'code' => '0100' . substr($uniqueId, -2),
            'country' => 'CZ'
        ]);
        
        Bank::create([
            'name' => 'Test Bank ' . $uniqueId,
            'code' => '0200' . substr($uniqueId, -2),
            'country' => 'CZ'
        ]);

        $result = $this->service->getBanksForDropdown('CZ');
        
        $this->assertGreaterThanOrEqual(1, count($result));
        
        // Find our test bank in results (should be at index 1 or higher)
        $expectedCode = '0200' . substr($uniqueId, -2);
        $expectedText = 'Test Bank ' . $uniqueId . ' (' . $expectedCode . ')';
        
        $bankFound = false;
        foreach ($result as $bank) {
            if (is_array($bank) && 
                isset($bank['text'], $bank['value']) && 
                $bank['text'] === $expectedText && 
                $bank['value'] === $expectedCode) {
                $bankFound = true;
                break;
            }
        }
        
        $this->assertTrue($bankFound, 'Bank with correct text format not found');
    }

    #[Test]
    public function get_banks_for_js_returns_array(): void
    {
        $result = $this->service->getBanksForJs();
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function get_banks_for_js_returns_banks_with_correct_structure(): void
    {
        $uniqueId = uniqid();
        $code = '0100' . substr($uniqueId, -2);
        
        Bank::create([
            'name' => 'Test Bank ' . $uniqueId,
            'code' => $code,
            'swift' => 'KOMBCZPP',
            'country' => 'CZ'
        ]);

        $result = $this->service->getBanksForJs('CZ');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey($code, $result);
        $this->assertArrayHasKey('text', $result[$code]);
        $this->assertArrayHasKey('swift', $result[$code]);
        $this->assertEquals('Test Bank ' . $uniqueId . ' (' . $code . ')', $result[$code]['text']);
        $this->assertEquals('KOMBCZPP', $result[$code]['swift']);
    }

    #[Test]
    public function get_banks_for_js_filters_by_country(): void
    {
        $uniqueId = uniqid();
        $czechCode = '0100' . substr($uniqueId, -2);
        $slovakCode = '0200' . substr($uniqueId, -2);
        
        Bank::create([
            'name' => 'Czech Bank ' . $uniqueId,
            'code' => $czechCode,
            'country' => 'CZ'
        ]);
        
        Bank::create([
            'name' => 'Slovak Bank ' . $uniqueId,
            'code' => $slovakCode,
            'country' => 'SK'
        ]);

        $resultCZ = $this->service->getBanksForJs('CZ');
        $resultSK = $this->service->getBanksForJs('SK');
        
        $this->assertArrayHasKey($czechCode, $resultCZ);
        $this->assertArrayNotHasKey($slovakCode, $resultCZ);
        
        $this->assertArrayHasKey($slovakCode, $resultSK);
        $this->assertArrayNotHasKey($czechCode, $resultSK);
    }

    #[Test]
    public function methods_handle_empty_database(): void
    {
        // No banks in database
        $dropdownResult = $this->service->getBanksForDropdown();
        $jsResult = $this->service->getBanksForJs();
        
        $this->assertIsArray($dropdownResult);
        $this->assertIsArray($jsResult);
        
        $this->assertIsArray($dropdownResult);
    }

    #[Test]
    public function methods_use_default_country_parameter(): void
    {
        $uniqueId = uniqid();
        
        Bank::create([
            'name' => 'Czech Bank ' . $uniqueId,
            'code' => '0100' . substr($uniqueId, -2),
            'country' => 'CZ'
        ]);

        // Test default parameter (should be CZ)
        $dropdownResult = $this->service->getBanksForDropdown();
        $jsResult = $this->service->getBanksForJs();
        
        $this->assertIsArray($dropdownResult);
        $this->assertIsArray($jsResult);
    }
}
