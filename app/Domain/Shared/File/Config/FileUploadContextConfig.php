<?php

namespace App\Domain\Shared\File\Config;

/**
 * Immutable value object representing merged file upload configuration for a given context.
 */
class FileUploadContextConfig
{
    public function __construct(
        private readonly int $maxKb,
        private readonly array $allowedExtensions,
        private readonly array $allowedMimeGroups,
        private readonly array $thumbnail, // ['enabled'=>bool, 'width'?, 'height'?, 'path'?, 'quality'?
        private readonly bool $enableHashDeduplication = false
    ) {}

    public function getMaxKb(): int { return $this->maxKb; }
    /** @return string[] */
    public function getAllowedExtensions(): array { return $this->allowedExtensions; }
    /** @return string[] */
    public function getAllowedMimeGroups(): array { return $this->allowedMimeGroups; }
    public function isThumbnailEnabled(): bool { return (bool)($this->thumbnail['enabled'] ?? false); }
    public function getThumbnailWidth(): ?int { return $this->thumbnail['width'] ?? null; }
    public function getThumbnailHeight(): ?int { return $this->thumbnail['height'] ?? null; }
    public function getThumbnailPath(): ?string { return $this->thumbnail['path'] ?? null; }
    public function getThumbnailQuality(): ?int { return $this->thumbnail['quality'] ?? null; }
    public function isHashDeduplicationEnabled(): bool { return $this->enableHashDeduplication; }
}
