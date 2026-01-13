<?php

namespace App\Application\Invoice\Contracts;

/**
 * Application-level abstraction for temporary (guest) invoice storage.
 * Keeps framework cache concerns out of Domain services.
 */
interface TemporaryInvoiceStoreInterface
{
    /** Store validated invoice data temporarily and return token */
    public function store(array $data, int $minutes = 10): string;

    /** Retrieve previously stored temporary invoice by token */
    public function get(string $token): ?array;

    /** Delete stored temporary invoice */
    public function delete(string $token): bool;
}
