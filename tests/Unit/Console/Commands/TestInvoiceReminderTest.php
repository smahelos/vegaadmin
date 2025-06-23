<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\TestInvoiceReminder;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestInvoiceReminderTest extends TestCase
{
    private TestInvoiceReminder $command;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->command = new TestInvoiceReminder();
    }

    #[Test]
    public function command_extends_console_command(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    #[Test]
    public function command_has_correct_signature(): void
    {
        $expectedSignature = 'invoices:test-reminder {invoice_id? : Invoice ID to test} {--invoice= : Invoice ID to test (alternative option)} {--type=all : Reminder type (upcoming, due, overdue, all)}';
        
        $reflection = new \ReflectionClass($this->command);
        $property = $reflection->getProperty('signature');
        $property->setAccessible(true);
        
        $this->assertEquals($expectedSignature, $property->getValue($this->command));
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $expectedDescription = 'Invoice reminder test for selected invoice';
        
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
    public function command_has_helper_methods(): void
    {
        $this->assertTrue(method_exists($this->command, 'testUpcomingReminder'));
        $this->assertTrue(method_exists($this->command, 'testDueReminder'));
        $this->assertTrue(method_exists($this->command, 'testOverdueReminder'));
    }

    #[Test]
    public function command_helper_methods_are_private(): void
    {
        $reflection = new \ReflectionClass($this->command);
        
        $upcomingMethod = $reflection->getMethod('testUpcomingReminder');
        $this->assertTrue($upcomingMethod->isPrivate());
        
        $dueMethod = $reflection->getMethod('testDueReminder');
        $this->assertTrue($dueMethod->isPrivate());
        
        $overdueMethod = $reflection->getMethod('testOverdueReminder');
        $this->assertTrue($overdueMethod->isPrivate());
    }

    #[Test]
    public function command_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $fileName = $reflection->getFileName();
        $content = file_get_contents($fileName);
        
        $this->assertStringContainsString('use Illuminate\Console\Command;', $content);
        $this->assertStringContainsString('use App\Models\Invoice;', $content);
        $this->assertStringContainsString('use App\Notifications\InvoiceDueReminder;', $content);
        $this->assertStringContainsString('use App\Notifications\InvoiceOverdueReminder;', $content);
        $this->assertStringContainsString('use App\Notifications\InvoiceUpcomingDueReminder;', $content);
    }

    #[Test]
    public function command_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $docComment = $reflection->getDocComment();
        
        $this->assertNotEmpty($docComment);
    }

    #[Test]
    public function command_supports_different_reminder_types(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $fileName = $reflection->getFileName();
        $content = file_get_contents($fileName);
        
        // Check that command handles different types
        $this->assertStringContainsString('upcoming', $content);
        $this->assertStringContainsString('due', $content);
        $this->assertStringContainsString('overdue', $content);
        $this->assertStringContainsString('all', $content);
    }
}
