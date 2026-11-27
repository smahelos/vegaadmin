<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Invoice;

use App\Domain\Invoice\Contracts\DocumentPdfStorageInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;
use RuntimeException;

final class FailingDocumentPdfStorage implements DocumentPdfStorageInterface
{
    public function store(
        UserId $owner,
        InvoiceId $id,
        string $content,
    ): string {
        throw new RuntimeException('The document PDF could not be stored');
    }

    public function read(string $path): ?string
    {
        return null;
    }

    public function delete(string $path): void {}
}
