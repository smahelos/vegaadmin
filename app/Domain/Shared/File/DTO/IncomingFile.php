<?php

namespace App\Domain\Shared\File\DTO;

/**
 * Framework-agnostic value object representing an incoming file.
 * Carries essential metadata and a temporary filesystem path for reading.
 */
class IncomingFile
{
    public function __construct(
        public readonly string $originalName,
        public readonly string $extension,
        public readonly string $mime,
        public readonly int $sizeBytes,
        public readonly string $path
    ) {
    }
}
