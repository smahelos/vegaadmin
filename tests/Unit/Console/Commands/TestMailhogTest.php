<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\TestMailhog;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestMailhogTest extends TestCase
{
    private TestMailhog $command;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->command = new TestMailhog();
    }

    #[Test]
    public function command_extends_console_command(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    #[Test]
    public function command_has_correct_signature(): void
    {
        $expectedSignature = 'mail:test-mailhog {--to=test@example.com : Email address to send test message to} {--subject=Test Email from VegaAdmin : Subject of the test email}';
        
        $reflection = new \ReflectionClass($this->command);
        $property = $reflection->getProperty('signature');
        $property->setAccessible(true);
        
        $this->assertEquals($expectedSignature, $property->getValue($this->command));
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $expectedDescription = 'Send a test email to verify mail configuration';
        
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
    public function command_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $fileName = $reflection->getFileName();
        $content = file_get_contents($fileName);
        
        $this->assertStringContainsString('use Illuminate\Console\Command;', $content);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Mail;', $content);
    }

    #[Test]
    public function command_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $docComment = $reflection->getDocComment();
        
        $this->assertNotEmpty($docComment);
    }

    #[Test]
    public function command_has_email_functionality(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $fileName = $reflection->getFileName();
        $content = file_get_contents($fileName);
        
        // Check that command handles email sending
        $this->assertStringContainsString('Mail::', $content);
        $this->assertStringContainsString('send', $content);
    }

    #[Test]
    public function command_supports_customization_options(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $fileName = $reflection->getFileName();
        $content = file_get_contents($fileName);
        
        // Check that command supports 'to' and 'subject' options
        $this->assertStringContainsString('--to', $content);
        $this->assertStringContainsString('--subject', $content);
    }
}
