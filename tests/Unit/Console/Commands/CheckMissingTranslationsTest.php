<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CheckMissingTranslations;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckMissingTranslationsTest extends TestCase
{
    private CheckMissingTranslations $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new CheckMissingTranslations();
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
        $this->assertStringContainsString('translations:check', $signature);
        $this->assertStringContainsString('locale=en', $signature);
        $this->assertStringContainsString('--compare=cs', $signature);
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);
        
        $description = $descriptionProperty->getValue($this->command);
        
        $this->assertIsString($description);
        $this->assertStringContainsString('missing translations', $description);
        $this->assertStringContainsString('locale', $description);
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
        
        // handle() method should return int for console commands
        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
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

    #[Test]
    public function command_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $docComment = $reflection->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('checking missing translations', $docComment);
    }

    #[Test]
    public function handle_method_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('handle');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@return int', $docComment);
    }
}
