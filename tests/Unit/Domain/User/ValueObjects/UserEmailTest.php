<?php

namespace Tests\Unit\Domain\User\ValueObjects;

use App\Domain\User\ValueObjects\UserEmail;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class UserEmailTest extends TestCase
{
    #[Test]
    public function creates_valid_email(): void
    {
        $email = UserEmail::fromString('test@example.com');
        
        $this->assertInstanceOf(UserEmail::class, $email);
        $this->assertEquals('test@example.com', $email->getEmail());
    }

    #[Test]
    public function normalizes_email_to_lowercase(): void
    {
        $email = UserEmail::fromString('TEST@EXAMPLE.COM');
        
        $this->assertEquals('test@example.com', $email->getEmail());
    }

    #[Test]
    public function trims_whitespace(): void
    {
        $email = UserEmail::fromString('  test@example.com  ');
        
        $this->assertEquals('test@example.com', $email->getEmail());
    }

    #[Test]
    public function throws_exception_for_empty_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email cannot be empty');
        
        UserEmail::fromString('');
    }

    #[Test]
    public function throws_exception_for_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        
        UserEmail::fromString('invalid-email');
    }

    #[Test]
    public function throws_exception_for_too_long_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email too long');
        
        $longEmail = str_repeat('a', 250) . '@example.com';
        UserEmail::fromString($longEmail);
    }

    #[Test]
    public function throws_exception_for_too_short_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email too short');
        
        UserEmail::fromString('a@b');
    }

    #[Test]
    public function throws_exception_for_dangerous_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email contains invalid characters');
        
        UserEmail::fromString('test<script>@example.com');
    }

    #[Test]
    public function extracts_domain_correctly(): void
    {
        $email = UserEmail::fromString('user@example.com');
        
        $this->assertEquals('example.com', $email->getDomain());
    }

    #[Test]
    public function extracts_local_part_correctly(): void
    {
        $email = UserEmail::fromString('user@example.com');
        
        $this->assertEquals('user', $email->getLocalPart());
    }

    #[Test]
    public function checks_domain_match(): void
    {
        $email = UserEmail::fromString('user@example.com');
        
        $this->assertTrue($email->isFromDomain('example.com'));
        $this->assertTrue($email->isFromDomain('EXAMPLE.COM')); // Case insensitive
        $this->assertFalse($email->isFromDomain('other.com'));
    }

    #[Test]
    public function detects_common_providers(): void
    {
        $gmail = UserEmail::fromString('user@gmail.com');
        $business = UserEmail::fromString('user@company.com');
        $seznam = UserEmail::fromString('user@seznam.cz');
        
        $this->assertTrue($gmail->isCommonProvider());
        $this->assertTrue($seznam->isCommonProvider());
        $this->assertFalse($business->isCommonProvider());
    }

    #[Test]
    public function detects_business_emails(): void
    {
        $gmail = UserEmail::fromString('user@gmail.com');
        $business = UserEmail::fromString('user@company.com');
        
        $this->assertFalse($gmail->isBusinessEmail());
        $this->assertTrue($business->isBusinessEmail());
    }

    #[Test]
    public function generates_masked_email(): void
    {
        $email = UserEmail::fromString('username@example.com');
        $masked = $email->getMaskedEmail();
        
        $this->assertStringContainsString('us****', $masked);
        $this->assertStringContainsString('ex*****.com', $masked);
        $this->assertStringContainsString('@', $masked);
    }

    #[Test]
    public function handles_short_email_masking(): void
    {
        $email = UserEmail::fromString('ab@cd.com');
        $masked = $email->getMaskedEmail();
        
        $this->assertStringContainsString('**@cd.com', $masked);
    }

    #[Test]
    public function checks_equality(): void
    {
        $email1 = UserEmail::fromString('test@example.com');
        $email2 = UserEmail::fromString('test@example.com');
        $email3 = UserEmail::fromString('other@example.com');
        
        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    #[Test]
    public function converts_to_string(): void
    {
        $email = UserEmail::fromString('test@example.com');
        
        $this->assertEquals('test@example.com', (string) $email);
    }

    #[Test]
    public function json_serializes_correctly(): void
    {
        $email = UserEmail::fromString('test@example.com');
        
        $this->assertEquals('test@example.com', $email->jsonSerialize());
        $this->assertEquals('"test@example.com"', json_encode($email));
    }

    #[Test]
    public function handles_complex_email_formats(): void
    {
        $complexEmails = [
            'user.name+tag@example.com',
            'user_name@example-domain.com',
            'user123@sub.example.com',
            'first.last@example.co.uk'
        ];

        foreach ($complexEmails as $emailString) {
            $email = UserEmail::fromString($emailString);
            $this->assertEquals(strtolower($emailString), $email->getEmail());
        }
    }
}
