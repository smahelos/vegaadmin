<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage\Invoice;

use App\Domain\Invoice\Contracts\DocumentLogoStorageInterface;
use App\Domain\Shared\File\DTO\IncomingFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class PublicDocumentLogoStorage implements DocumentLogoStorageInterface
{
    public function copy(string $source): string
    {
        $path = $this->path(pathinfo($source, PATHINFO_EXTENSION));
        if (! Storage::disk('public')->copy($source, $path)) {
            throw new RuntimeException('The document logo could not be copied');
        }

        return $path;
    }

    public function store(IncomingFile $file): string
    {
        $path = $this->path($file->extension);
        $contents = file_get_contents($file->path);
        if ($contents === false || ! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('The document logo could not be stored');
        }

        return $path;
    }

    public function delete(string $path): void
    {
        if (str_starts_with($path, 'documents/logos/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function path(string $extension): string
    {
        return 'documents/logos/' . Str::uuid()->toString() . '.' . strtolower($extension);
    }
}
