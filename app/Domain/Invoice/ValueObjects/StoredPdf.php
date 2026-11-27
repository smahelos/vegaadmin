<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObjects;

use InvalidArgumentException;

/**
 * The PDF rendered at issue and its SHA-256 hash: the document as delivered (D-050, points 5 and 8).
 */
final readonly class StoredPdf
{
    public function __construct(
        public string $path,
        public string $hash,
    ) {
        if ($path === '') {
            throw new InvalidArgumentException('A stored PDF needs a path');
        }
        if (preg_match('/^[0-9a-f]{64}$/', $hash) !== 1) {
            throw new InvalidArgumentException('A stored PDF hash is a lowercase hex SHA-256');
        }
    }

    public static function of(string $path, string $content): self
    {
        return new self(
            $path,
            hash('sha256', $content),
        );
    }

    public function matches(string $content): bool
    {
        return hash_equals(
            $this->hash,
            hash('sha256', $content),
        );
    }
}
