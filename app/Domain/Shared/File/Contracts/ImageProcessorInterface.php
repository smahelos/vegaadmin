<?php

namespace App\Domain\Shared\File\Contracts;

interface ImageProcessorInterface
{
    /**
     * Create a resized thumbnail from source path to target path.
     *
     * @param string $source Absolute path to source image file
     * @param string $target Absolute path to target image file (directories ensured by caller or implementation)
     * @param int $width Target width in pixels
     * @param int $height Target height in pixels
     * @param int $quality JPEG/WebP quality (0-100)
     * @return bool True on success, false on failure
     */
    public function createThumbnail(string $source, string $target, int $width, int $height, int $quality = 85): bool;

    /** Determine if given mime type represents an image supported by processor. */
    public function isImageMime(string $mime): bool;
}
