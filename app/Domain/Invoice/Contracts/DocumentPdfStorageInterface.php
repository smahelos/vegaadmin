<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;

/** The PDFs of issued documents, on a disk outside the public root. */
interface DocumentPdfStorageInterface
{
    /** @return string The stored path */
    public function store(
        UserId $owner,
        InvoiceId $id,
        string $content,
    ): string;

    /** Null when the file does not exist. */
    public function read(string $path): ?string;

    public function delete(string $path): void;
}
