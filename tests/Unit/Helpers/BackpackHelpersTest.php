<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackpackHelpersTest extends TestCase
{
    #[Test]
    public function backpack_auth_function_exists(): void
    {
        $this->assertTrue(function_exists('backpack_auth'));
    }

    #[Test]
    public function backpack_guard_name_function_exists(): void
    {
        $this->assertTrue(function_exists('backpack_guard_name'));
    }

    #[Test]
    public function backpack_user_function_exists(): void
    {
        $this->assertTrue(function_exists('backpack_user'));
    }

    #[Test]
    public function backpack_url_function_exists(): void
    {
        $this->assertTrue(function_exists('backpack_url'));
    }

    #[Test]
    public function backpack_pro_function_exists(): void
    {
        $this->assertTrue(function_exists('backpack_pro'));
    }

    #[Test]
    public function backpack_guard_name_has_correct_return_type(): void
    {
        $reflection = new \ReflectionFunction('backpack_guard_name');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    #[Test]
    public function backpack_user_has_correct_return_type(): void
    {
        $reflection = new \ReflectionFunction('backpack_user');
        $returnType = $reflection->getReturnType();
        
        // Should return \App\Models\User|null
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    #[Test]
    public function backpack_url_has_correct_return_type(): void
    {
        $reflection = new \ReflectionFunction('backpack_url');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    #[Test]
    public function backpack_pro_has_correct_return_type(): void
    {
        $reflection = new \ReflectionFunction('backpack_pro');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function backpack_guard_name_has_no_parameters(): void
    {
        $reflection = new \ReflectionFunction('backpack_guard_name');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function backpack_user_has_no_parameters(): void
    {
        $reflection = new \ReflectionFunction('backpack_user');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function backpack_url_has_correct_parameters(): void
    {
        $reflection = new \ReflectionFunction('backpack_url');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('path', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isOptional());
        $this->assertNull($parameters[0]->getDefaultValue());
    }

    #[Test]
    public function backpack_pro_has_no_parameters(): void
    {
        $reflection = new \ReflectionFunction('backpack_pro');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function functions_have_proper_docblocks(): void
    {
        $functions = ['backpack_auth', 'backpack_guard_name', 'backpack_user', 'backpack_url', 'backpack_pro'];
        
        foreach ($functions as $functionName) {
            $reflection = new \ReflectionFunction($functionName);
            $docComment = $reflection->getDocComment();
            
            $this->assertNotEmpty($docComment, "Function {$functionName} should have a docblock");
        }
    }

    #[Test]
    public function helper_file_uses_required_imports(): void
    {
        $fileName = app_path('Helpers/BackpackHelpers.php');
        $content = file_get_contents($fileName);
        
        $this->assertStringContainsString('use Illuminate\Support\Facades\Auth;', $content);
    }

    #[Test]
    public function helper_file_structure_is_correct(): void
    {
        $fileName = app_path('Helpers/BackpackHelpers.php');
        $content = file_get_contents($fileName);
        
        // Check that functions are properly wrapped with function_exists checks
        $this->assertStringContainsString("if (!function_exists('backpack_auth'))", $content);
        $this->assertStringContainsString("if (!function_exists('backpack_guard_name'))", $content);
        $this->assertStringContainsString("if (!function_exists('backpack_user'))", $content);
        $this->assertStringContainsString("if (!function_exists('backpack_url'))", $content);
        $this->assertStringContainsString("if (!function_exists('backpack_pro'))", $content);
    }

    #[Test]
    public function functions_are_declared_as_global(): void
    {
        $functions = ['backpack_auth', 'backpack_guard_name', 'backpack_user', 'backpack_url', 'backpack_pro'];
        
        foreach ($functions as $functionName) {
            $reflection = new \ReflectionFunction($functionName);
            
            // Global functions should not be in a namespace
            $this->assertEmpty($reflection->getNamespaceName(), "Function {$functionName} should be global");
        }
    }
}
