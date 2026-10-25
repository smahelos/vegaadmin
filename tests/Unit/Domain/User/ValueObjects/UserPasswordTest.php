<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User\ValueObjects;

use App\Domain\User\ValueObjects\UserPassword;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserPasswordTest extends TestCase
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
}
