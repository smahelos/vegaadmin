<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\SyncInvoiceProducts;
use App\Services\InvoiceProductSyncService;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncInvoiceProductsTest extends TestCase
{
    private SyncInvoiceProducts $command;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->command = new SyncInvoiceProducts();
    }

    #[Test]
    public function command_extends_console_command(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    #[Test]
    public function command_has_correct_signature(): void
    {
        $expectedSignature = 'invoices:sync-products {--invoice-id= : Sync specific invoice ID}';
        
        $reflection = new \ReflectionClass($this->command);
        $property = $reflection->getProperty('signature');
        $property->setAccessible(true);
        
        $this->assertEquals($expectedSignature, $property->getValue($this->command));
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $expectedDescription = 'Synchronize products from invoice_text JSON to pivot table';
        
        $reflection = new \ReflectionClass($this->command);
        $property = $reflection->getProperty('description');
        $property->setAccessible(true);
        
        $this->assertEquals($expectedDescription, $property->getValue($this->command));
    }

    #[Test]
    public function command_has_handle_method(): void
    {
        $this->assertTrue(method_exists($this->command, 'handle'));
    }

    #[Test]
    public function handle_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('handle');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
    }

    #[Test]
    public function command_structure_validation(): void
    {
        $reflection = new \ReflectionClass($this->command);
        
        // Check that class uses proper namespace
        $this->assertEquals('App\Console\Commands', $reflection->getNamespaceName());
        
        // Check that signature property exists and is protected
        $signatureProperty = $reflection->getProperty('signature');
        $this->assertTrue($signatureProperty->isProtected());
        
        // Check that description property exists and is protected  
        $descriptionProperty = $reflection->getProperty('description');
        $this->assertTrue($descriptionProperty->isProtected());
    }

    #[Test]
    public function command_uses_required_service(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('handle');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('syncService', $parameters[0]->getName());
        $this->assertEquals(InvoiceProductSyncService::class, $parameters[0]->getType()->getName());
    }

    #[Test]
    public function command_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $docComment = $reflection->getDocComment();
        
        $this->assertNotEmpty($docComment);
    }

    #[Test]
    public function command_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $fileName = $reflection->getFileName();
        $content = file_get_contents($fileName);
        
        $this->assertStringContainsString('use Illuminate\Console\Command;', $content);
        $this->assertStringContainsString('use App\Services\InvoiceProductSyncService;', $content);
    }
}
