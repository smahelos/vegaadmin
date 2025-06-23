<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CheckInvoicePaymentStatus;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckInvoicePaymentStatusTest extends TestCase
{
    private CheckInvoicePaymentStatus $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new CheckInvoicePaymentStatus();
    }

    #[Test]
    public function command_extends_console_command(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    #[Test]
    public function command_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        
        $signature = $signatureProperty->getValue($this->command);
        
        $this->assertIsString($signature);
        $this->assertStringContainsString('invoices:check-payment-status', $signature);
        $this->assertStringContainsString('--days-before', $signature);
        $this->assertStringContainsString('--days-after', $signature);
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);
        
        $description = $descriptionProperty->getValue($this->command);
        
        $this->assertIsString($description);
        $this->assertStringContainsString('Check invoice payment status', $description);
        $this->assertStringContainsString('reminders', $description);
    }

    #[Test]
    public function command_has_handle_method(): void
    {
        $this->assertTrue(method_exists($this->command, 'handle'));
        
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('handle');
        
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function handle_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('handle');
        $returnType = $method->getReturnType();
        
        // handle() method may return void or int in console commands
        $this->assertTrue(
            $returnType === null || 
            $returnType->getName() === 'void' || 
            $returnType->getName() === 'int'
        );
    }

    #[Test]
    public function command_uses_has_preferred_locale_trait(): void
    {
        $traits = class_uses($this->command);
        
        $this->assertContains('App\Traits\HasPreferredLocale', $traits);
    }

    #[Test]
    public function command_structure_validation(): void
    {
        $reflection = new \ReflectionClass($this->command);
        
        // Check that class is properly structured
        $this->assertTrue($reflection->hasProperty('signature'));
        $this->assertTrue($reflection->hasProperty('description'));
        $this->assertTrue($reflection->hasMethod('handle'));
        
        // Check properties are protected
        $signatureProperty = $reflection->getProperty('signature');
        $descriptionProperty = $reflection->getProperty('description');
        
        $this->assertTrue($signatureProperty->isProtected());
        $this->assertTrue($descriptionProperty->isProtected());
    }
}
