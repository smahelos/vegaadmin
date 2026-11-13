<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage\Party;

use App\Domain\Party\Contracts\DocumentLogoStorageInterface;
use App\Domain\Shared\File\DTO\IncomingFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class PublicDocumentLogoStorage implements DocumentLogoStorageInterface
{
    public function copy(string $source): string
    {
        $path = $this->path(
            pathinfo($source, PATHINFO_EXTENSION),
        );
        $disk = Storage::disk('public');
        if (!$disk->copy($source, $path)) {
            throw new RuntimeException('The document logo could not be copied');
        }

        return $path;
    }

    public function store(IncomingFile $file): string
    {
        $path = $this->path($file->extension);
        $contents = file_get_contents($file->path);
        $stored = false;
        if ($contents !== false) {
            $disk = Storage::disk('public');
            $stored = $disk->put($path, $contents);
        }
        if (!$stored) {
            throw new RuntimeException('The document logo could not be stored');
        }

        return $path;
    }

    public function delete(string $path): void
    {
        if (str_starts_with($path, 'documents/logos/')) {
            $disk = Storage::disk('public');
            $disk->delete($path);
        }
    }

    private function path(string $extension): string
    {
        $uuid = Str::uuid();

        return 'documents/logos/' . $uuid->toString() . '.' . strtolower($extension);
    }
}
