<?php

namespace Tests\Unit\Traits;

use App\Traits\BankFormFields;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BankFormFieldsTest extends TestCase
{
    use BankFormFields;

    #[Test]
    public function trait_can_be_used(): void
    {
        $this->assertTrue(method_exists($this, 'getBankFields'));
    }

    #[Test]
    public function trait_has_get_bank_fields_method(): void
    {
        $this->assertTrue(method_exists($this, 'getBankFields'));
    }

    #[Test]
    public function get_bank_fields_method_is_protected(): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('getBankFields');
        $this->assertTrue($method->isProtected());
    }

    #[Test]
    public function get_bank_fields_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('getBankFields');
        
        // Should have no parameters
        $this->assertCount(0, $method->getParameters());
    }

    #[Test]
    public function trait_has_proper_docblocks(): void
    {
        $reflection = new \ReflectionClass($this);
        
        // Check getBankFields method docblock
        $method = $reflection->getMethod('getBankFields');
        $docComment = $method->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@return array', $docComment);
    }

    #[Test]
    public function trait_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(BankFormFields::class);

        // Check namespace
        $this->assertEquals('App\Traits', $reflection->getNamespaceName());
        
        // Check that it's not abstract, final, etc.
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isFinal());
        $this->assertFalse($reflection->isInstantiable()); // Traits are not instantiable
    }

    #[Test]
    public function trait_has_expected_method_count(): void
    {
        $reflection = new \ReflectionClass(BankFormFields::class);
        $methods = $reflection->getMethods();
        
        // Should have exactly 1 method
        $this->assertCount(1, $methods);
        
        $methodNames = array_map(fn($method) => $method->getName(), $methods);
        $this->assertContains('getBankFields', $methodNames);
    }

    #[Test]
    public function method_visibility_is_correct(): void
    {
        $reflection = new \ReflectionClass(BankFormFields::class);
        $method = $reflection->getMethod('getBankFields');
        
        $this->assertTrue($method->isProtected());
        $this->assertFalse($method->isPublic());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isStatic());
    }

    #[Test]
    public function trait_uses_required_imports(): void
    {
        // Check that trait file contains required use statements
        $traitFile = file_get_contents(__DIR__ . '/../../../app/Traits/BankFormFields.php');
        
        $this->assertStringContainsString('use App\Contracts\CountryServiceInterface;', $traitFile);
        $this->assertStringContainsString('use Illuminate\Support\Facades\App;', $traitFile);
    }
}
