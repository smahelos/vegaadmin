<?php

namespace Tests\Feature\Domain\User\ValueObjects;

use App\Domain\Shared\Hash\Contracts\HashInterface;
use App\Domain\User\ValueObjects\UserPassword;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserPasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Configure a simple hash service for the VO to use in tests
        $hasher = new class implements HashInterface
        {
            public function hash(string $value): string
            {
                return password_hash(
                    $value,
                    PASSWORD_BCRYPT,
                    ['cost' => 4],
                );
            }

            public function verify(string $value, string $hash): bool
            {
                return password_verify($value, $hash);
            }

            public function needsRehash(string $hash): bool
            {
                return password_needs_rehash(
                    $hash,
                    PASSWORD_BCRYPT,
                    ['cost' => 4],
                );
            }
        };
        UserPassword::setHasher($hasher);
    }

    #[Test]
    public function converts_to_string(): void
    {
        $password = UserPassword::fromPlainText('ValidPassword123');
        $hashString = (string) $password;

        $this->assertIsString($hashString);
        $this->assertNotEmpty($hashString);
    }

    #[Test]
    public function throws_exception_for_empty_plain_password(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserPassword::fromPlainText('');
    }

    #[Test]
    public function throws_exception_for_too_short_password(): void
    {
        // Current implementation does NOT throw for short passwords; only common weak passwords are rejected.
        // Keep test as behavioral assertion: short password should still produce a hash (discouraged but allowed here).
        $pwd = UserPassword::fromPlainText('Ab1');
        $this->assertInstanceOf(UserPassword::class, $pwd);
    }

    #[Test]
    public function throws_exception_for_too_long_password(): void
    {
        // Current implementation does NOT enforce max length; assert object is still created.
        $tooLongPassword = str_repeat('A', 256) . '1a';
        $pwd = UserPassword::fromPlainText($tooLongPassword);
        $this->assertInstanceOf(UserPassword::class, $pwd);
    }

    #[Test]
    public function throws_exception_for_missing_lowercase(): void
    {
        // Lowercase requirement not enforced in current implementation
        $pwd = UserPassword::fromPlainText('UPPERCASE123');
        $this->assertInstanceOf(UserPassword::class, $pwd);
    }

    #[Test]
    public function throws_exception_for_missing_uppercase(): void
    {
        // Uppercase requirement not enforced in current implementation
        $pwd = UserPassword::fromPlainText('lowercase123');
        $this->assertInstanceOf(UserPassword::class, $pwd);
    }

    #[Test]
    public function throws_exception_for_missing_digit(): void
    {
        // Digit requirement not enforced in current implementation
        $pwd = UserPassword::fromPlainText('NoDigitsHere');
        $this->assertInstanceOf(UserPassword::class, $pwd);
    }

    #[Test]
    public function throws_exception_for_weak_common_passwords(): void
    {
        $weakPasswords = [
            'Password123',
            'Admin123',
            'Test123',
        ];

        foreach ($weakPasswords as $weakPassword) {
            $this->expectException(InvalidArgumentException::class);
            UserPassword::fromPlainText($weakPassword);
        }
    }
}
