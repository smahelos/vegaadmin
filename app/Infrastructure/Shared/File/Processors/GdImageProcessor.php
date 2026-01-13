<?php

namespace App\Infrastructure\Shared\File\Processors;

use App\Domain\Shared\File\Contracts\ImageProcessorInterface;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/**
 * GD based image processor implementation (Infrastructure layer).
 */
class GdImageProcessor implements ImageProcessorInterface
{
    protected ImageManager $manager;

    public function __construct(?ImageManager $manager = null)
    {
        $this->manager = $manager ?? new ImageManager(new GdDriver());
    }

    public function createThumbnail(string $source, string $target, int $width, int $height, int $quality = 85): bool
    {
        try {
            $dir = \dirname($target);
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }
            $image = $this->manager->read($source);
            $image->resize($width, $height);
            $image->save($target, $quality);
            return true;
        } catch (\Throwable $e) {
            Log::error('ImageProcessor thumbnail error: ' . $e->getMessage());
            return false;
        }
    }

    public function isImageMime(string $mime): bool
    {
        return str_starts_with($mime, 'image/');
    }
}
