<?php

namespace App\Application\Party\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Input validation service for Party operations.
 * 
 * Provides application-layer validation before passing data to Domain layer.
 * Focuses on basic data integrity and security concerns.
 */
class PartyInputValidationService
{
    /**
     * Validate client business rules and constraints.
     * 
     * @param array $data
     * @param int $userId
     * @param int|null $excludeClientId For updates - exclude current client from uniqueness checks
     * @throws ValidationException
     */
    public function validateClientBusinessRules(array $data, int $userId, ?int $excludeClientId = null): void
    {
        // Business Rule: Company clients with ICO must have address
        if (!empty($data['ico']) && (empty($data['street']) || empty($data['city']))) {
            throw ValidationException::withMessages([
                'address' => 'Client with ICO must have complete address (street and city)'
            ]);
        }

        // Business Rule: Shortcut must be unique per user
        if (!empty($data['shortcut']) && $this->isShortcutDuplicateForUser($data['shortcut'], $userId, $excludeClientId)) {
            throw ValidationException::withMessages([
                'shortcut' => 'This shortcut is already used by another client'
            ]);
        }

        // Business Rule: Email must be unique per user (if provided)
        if (!empty($data['email']) && $this->isEmailDuplicateForUser($data['email'], $userId, $excludeClientId)) {
            throw ValidationException::withMessages([
                'email' => 'This email is already used by another client'
            ]);
        }
    }

    /**
     * Validate supplier business rules and constraints.
     * 
     * @param array $data
     * @param int $userId
     * @param int|null $excludeSupplierId For updates - exclude current supplier from uniqueness checks
     * @throws ValidationException
     */
    public function validateSupplierBusinessRules(array $data, int $userId, ?int $excludeSupplierId = null): void
    {
        // Business Rule: Supplier with bank details must have complete banking info
        if (!empty($data['account_number']) && empty($data['bank_code'])) {
            throw ValidationException::withMessages([
                'bank_code' => 'Bank code is required when account number is provided'
            ]);
        }

        // Business Rule: IBAN validation format (basic)
        if (!empty($data['iban']) && !$this->isValidIBANFormat($data['iban'])) {
            throw ValidationException::withMessages([
                'iban' => 'Invalid IBAN format'
            ]);
        }

        // Business Rule: Shortcut must be unique per user
        if (!empty($data['shortcut']) && $this->isSupplierShortcutDuplicateForUser($data['shortcut'], $userId, $excludeSupplierId)) {
            throw ValidationException::withMessages([
                'shortcut' => 'This shortcut is already used by another supplier'
            ]);
        }
    }

    /**
     * Sanitize string input to prevent XSS and other issues.
     */
    private function sanitizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        return trim(strip_tags($value));
    }

    /**
     * Check if shortcut is already used by another client for this user.
     */
    private function isShortcutDuplicateForUser(string $shortcut, int $userId, ?int $excludeClientId = null): bool
    {
        $query = DB::table('clients')
            ->where('shortcut', $shortcut)
            ->where('user_id', $userId);

        if ($excludeClientId) {
            $query->where('id', '!=', $excludeClientId);
        }

        return $query->exists();
    }

    /**
     * Check if email is already used by another client for this user.
     */
    private function isEmailDuplicateForUser(string $email, int $userId, ?int $excludeClientId = null): bool
    {
        $query = DB::table('clients')
            ->where('email', $email)
            ->where('user_id', $userId);

        if ($excludeClientId) {
            $query->where('id', '!=', $excludeClientId);
        }

        return $query->exists();
    }

    /**
     * Check if shortcut is already used by another supplier for this user.
     */
    private function isSupplierShortcutDuplicateForUser(string $shortcut, int $userId, ?int $excludeSupplierId = null): bool
    {
        $query = DB::table('suppliers')
            ->where('shortcut', $shortcut)
            ->where('user_id', $userId);

        if ($excludeSupplierId) {
            $query->where('id', '!=', $excludeSupplierId);
        }

        return $query->exists();
    }

    /**
     * Basic IBAN format validation (simplified).
     */
    private function isValidIBANFormat(string $iban): bool
    {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(str_replace(' ', '', $iban));
        
        // Basic length check (15-34 characters for most countries)
        return preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban) === 1;
    }
}
