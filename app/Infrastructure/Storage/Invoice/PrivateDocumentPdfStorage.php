<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage\Invoice;

use App\Domain\Invoice\Contracts\DocumentPdfStorageInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class PrivateDocumentPdfStorage implements DocumentPdfStorageInterface
{
    private const DISK = 'documents';

    public function store(
        UserId $owner,
        InvoiceId $id,
        string $content,
    ): string {
        $path = $owner->value . '/' . $id->value . '.pdf';
        $disk = Storage::disk(self::DISK);
        if (!$disk->put($path, $content)) {
            // A failed write can leave a truncated file behind
            $disk->delete($path);

            throw new RuntimeException('The document PDF could not be stored');
        }

        return $path;
    }

    public function read(string $path): ?string
    {
        $disk = Storage::disk(self::DISK);

        return $disk->get($path);
    }

    public function delete(string $path): void
    {
        $disk = Storage::disk(self::DISK);
        $disk->delete($path);
    }
}
