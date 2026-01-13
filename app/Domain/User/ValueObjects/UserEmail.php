<?php

namespace App\Domain\User\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

/**
 * User Email Value Object
 * 
 * Handles email validation, normalization and email-related operations
 */
class UserEmail implements JsonSerializable
{
    private string $email;

    /**
     * Private constructor to enforce factory methods
     */
    private function __construct(string $email)
    {
        $this->email = $email; // Email is already validated and normalized in fromString
    }

    /**
     * Create from email string
     */
    public static function fromString(string $email): self
    {
        $email = trim($email);
        
        if (empty($email)) {
            throw new InvalidArgumentException('Email cannot be empty');
        }

        $emailLength = strlen($email);
        if ($emailLength > 255) {
            throw new InvalidArgumentException("Email too long, maximum 255 characters, got: {$emailLength}");
        }

        if ($emailLength < 5) {
            throw new InvalidArgumentException("Email too short, minimum 5 characters, got: {$emailLength}");
        }

        // Check for dangerous characters BEFORE validating format
        if (preg_match('/[<>"\'\\\\\/]/', $email)) {
            throw new InvalidArgumentException("Email contains invalid characters: {$email}");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format: {$email}");
        }
        
        return new self(strtolower($email));
    }

    /**
     * Validate email format and constraints
     */
    private function validate(string $email): void
    {
        if (empty($email)) {
            throw new InvalidArgumentException('Email cannot be empty');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format: {$email}");
        }

        $emailLength = strlen($email);
        if ($emailLength > 255) {
            throw new InvalidArgumentException("Email too long, maximum 255 characters, got: {$emailLength}");
        }

        if ($emailLength < 5) {
            throw new InvalidArgumentException("Email too short, minimum 5 characters, got: {$emailLength}");
        }

        // Check for dangerous characters
        if (preg_match('/[<>"\'\\\\\/]/', $email)) {
            throw new InvalidArgumentException("Email contains invalid characters: {$email}");
        }
    }

    /**
     * Normalize email to standard format
     */
    private function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Get email value
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get domain part of email
     */
    public function getDomain(): string
    {
        return substr($this->email, strpos($this->email, '@') + 1);
    }

    /**
     * Get local part of email (before @)
     */
    public function getLocalPart(): string
    {
        return substr($this->email, 0, strpos($this->email, '@'));
    }

    /**
     * Check if email is from a specific domain
     */
    public function isFromDomain(string $domain): bool
    {
        return strtolower($domain) === $this->getDomain();
    }

    /**
     * Check if email uses common email providers
     */
    public function isCommonProvider(): bool
    {
        $commonProviders = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
            'seznam.cz', 'centrum.cz', 'email.cz', 'post.cz'
        ];

        return in_array($this->getDomain(), $commonProviders);
    }

    /**
     * Check if email appears to be business email
     */
    public function isBusinessEmail(): bool
    {
        return !$this->isCommonProvider();
    }

    /**
     * Generate masked email for display (user@ex*****.com)
     */
    public function getMaskedEmail(): string
    {
        $local = $this->getLocalPart();
        $domain = $this->getDomain();
        
        $localMasked = strlen($local) > 3 
            ? substr($local, 0, 2) . str_repeat('*', strlen($local) - 2)
            : str_repeat('*', strlen($local));
            
        $domainParts = explode('.', $domain);
        if (count($domainParts) > 1) {
            $domainMasked = substr($domainParts[0], 0, 2) . str_repeat('*', max(0, strlen($domainParts[0]) - 2));
            $domainMasked .= '.' . end($domainParts);
        } else {
            $domainMasked = substr($domain, 0, 2) . str_repeat('*', max(0, strlen($domain) - 2));
        }
        
        return $localMasked . '@' . $domainMasked;
    }

    /**
     * Check equality with another UserEmail
     */
    public function equals(UserEmail $other): bool
    {
        return $this->email === $other->email;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->email;
    }

    /**
     * For JSON serialization
     */
    public function jsonSerialize(): string
    {
        return $this->email;
    }
}
