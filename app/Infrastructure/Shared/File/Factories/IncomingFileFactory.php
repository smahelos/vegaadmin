<?php

namespace App\Infrastructure\Shared\File\Factories;

use App\Domain\Shared\File\DTO\IncomingFile;
use Illuminate\Http\UploadedFile;

class IncomingFileFactory
{
    public static function fromUploadedFile(UploadedFile $file): IncomingFile
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = (string) $file->getClientMimeType();
        $path = $file->getRealPath() ?: $file->getPathname();
        $size = 0;
        if ($path && is_file($path)) {
            $size = (int) @filesize($path);
        }
        if ($size <= 0) {
            // Fallback to reported size
            $size = (int) $file->getSize();
            // If reported size seems like KB (small) but file exists, multiply to approximate bytes
            if ($path && is_file($path) && $size > 0 && $size < 4096) {
                $real = (int) @filesize($path);
                if ($real > 0) { $size = $real; }
            }
        }

        return new IncomingFile(
            $originalName,
            $extension,
            $mime,
            $size,
            $path
        );
    }
}
