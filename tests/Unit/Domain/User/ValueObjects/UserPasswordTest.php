<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User\ValueObjects;

use App\Domain\User\ValueObjects\UserPassword;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\Shared\PrefixHash;

final class UserPasswordTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function unrestrictedPasswords(): array
    {
        return [
            'short' => ['Ab1'],
            'long' => [str_repeat('A', 256) . '1a'],
            'no lowercase' => ['UPPERCASE123'],
            'no uppercase' => ['lowercase123'],
            'no digit' => ['NoDigitsHere'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function commonPasswords(): array
    {
        return [
            'password123' => ['Password123'],
            'admin123' => ['Admin123'],
            'test123' => ['Test123'],
        ];
    }

    #[Test]
    #[DataProvider('unrestrictedPasswords')]
    public function holds_the_hash_of_the_plain_password(string $plainPassword): void
    {
        $password = UserPassword::fromPlainText(
            $plainPassword,
            new PrefixHash,
        );

        $this->assertSame(
            'hash:' . $plainPassword,
            $password->hash,
        );
    }

    #[Test]
    public function rejects_an_empty_password(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserPassword::fromPlainText(
            '',
            new PrefixHash,
        );
    }

    #[Test]
    #[DataProvider('commonPasswords')]
    public function rejects_a_common_password_in_any_letter_case(string $plainPassword): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserPassword::fromPlainText(
            $plainPassword,
            new PrefixHash,
        );
    }
}
