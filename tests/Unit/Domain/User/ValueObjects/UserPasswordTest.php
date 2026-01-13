<?php

namespace Tests\Unit\Domain\User\ValueObjects;

use App\Domain\User\ValueObjects\UserPassword;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserPasswordTest extends TestCase
{
    #[Test]
    public function calculates_password_strength_score(): void
    {
        // Test static method that doesn't require Laravel framework
        $weakScore = UserPassword::getStrengthScore('abc');
        $strongScore = UserPassword::getStrengthScore('Complex123!');
        
        $this->assertIsInt($weakScore);
        $this->assertIsInt($strongScore);
        $this->assertGreaterThan($weakScore, $strongScore);
        $this->assertGreaterThanOrEqual(0, $weakScore);
        $this->assertLessThanOrEqual(100, $strongScore);
    }

    #[Test]
    public function provides_strength_descriptions(): void
    {
        // Test static method for strength descriptions
        $weakDesc = UserPassword::getStrengthDescription('abc');
        $strongDesc = UserPassword::getStrengthDescription('Complex123!');
        
        $this->assertIsString($weakDesc);
        $this->assertIsString($strongDesc);
        $this->assertNotEquals($weakDesc, $strongDesc);
    }

    #[Test]
    public function checks_security_requirements(): void
    {
        // Test static method for password requirements checking
        $weakPassword = 'abc';
        $strongPassword = 'Complex123!';
        
        $this->assertFalse(UserPassword::meetsSecurityRequirements($weakPassword));
        $this->assertTrue(UserPassword::meetsSecurityRequirements($strongPassword));
    }

    #[Test]
    public function handles_edge_case_strength_scores(): void
    {
        // Test boundary conditions for strength scoring
        $emptyScore = UserPassword::getStrengthScore('');
        $maxScore = UserPassword::getStrengthScore('Very$Complex!Password123@');
        
        $this->assertEquals(0, $emptyScore);
        $this->assertLessThanOrEqual(100, $maxScore);
    }

    #[Test]
    public function class_structure_validation(): void
    {
        // Test class structure without instantiation (no Laravel framework)
        $reflection = new \ReflectionClass(UserPassword::class);
        
        $this->assertTrue($reflection->hasMethod('fromPlainText'));
        $this->assertTrue($reflection->hasMethod('fromHash'));
        $this->assertTrue($reflection->hasMethod('getHash'));
        $this->assertTrue($reflection->hasMethod('verify'));
        $this->assertTrue($reflection->hasMethod('getStrengthScore'));
        
        // Test method visibility and static nature
        $fromPlainText = $reflection->getMethod('fromPlainText');
        $this->assertTrue($fromPlainText->isStatic());
        $this->assertTrue($fromPlainText->isPublic());
        
        $getStrengthScore = $reflection->getMethod('getStrengthScore');
        $this->assertTrue($getStrengthScore->isStatic());
        $this->assertTrue($getStrengthScore->isPublic());
    }

    #[Test]
    public function method_return_types_are_properly_defined(): void
    {
        $reflection = new \ReflectionClass(UserPassword::class);
        
        $methodReturnTypes = [
            'fromPlainText' => 'self',
            'fromHash' => 'self',
            'getHash' => 'string',
            'verify' => 'bool',
            'getStrengthScore' => 'int',
            'getStrengthDescription' => 'string',
            'meetsSecurityRequirements' => 'bool',
        ];
        
        foreach ($methodReturnTypes as $methodName => $expectedType) {
            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();
            
            $this->assertNotNull($returnType, "Method {$methodName} should have return type");
            
            $actualType = (string) $returnType;
            if ($returnType->allowsNull()) {
                $actualType = '?' . ltrim($actualType, '?');
            }
                
            $this->assertEquals($expectedType, $actualType, "Method {$methodName} return type mismatch");
        }
    }
}
