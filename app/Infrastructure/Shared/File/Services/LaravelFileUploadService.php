<?php

namespace App\Infrastructure\Shared\File\Services;

use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Domain\Shared\File\Contracts\ImageProcessorInterface;
use App\Domain\Shared\File\DTO\IncomingFile;
use Illuminate\Http\File as IlluminateHttpFile;
use App\Domain\Shared\File\Config\FileUploadConfig;
use App\Models\FileHash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Infrastructure implementation of FileUploadServiceInterface.
 *
 * Contains framework dependent logic (Storage, Log, request()) thus lives in Infrastructure layer.
 */
class LaravelFileUploadService implements FileUploadServiceInterface
{
    /** Image processor (strategy). */
    protected ImageProcessorInterface $imageProcessor;

    /** Config wrapper */
    protected FileUploadConfig $config;

    public function __construct(?FileUploadConfig $config = null, ?ImageProcessorInterface $imageProcessor = null)
    {
        $this->config = $config ?? app(FileUploadConfig::class);
        $this->imageProcessor = $imageProcessor ?? app(ImageProcessorInterface::class);
    }

    /** @inheritDoc */
    public function handleFileUpload(IncomingFile|string|null $value, string $attributeName, string $destinationPath, array $options = [], ?string $oldValue = null, ?string $context = null): ?string
    {
        /** @var array{disk?:string,createThumbnails?:bool,thumbnailWidth?:int,thumbnailHeight?:int,thumbnailPath?:string,randomizeFilename?:bool,sanitizeFilename?:bool,allowedFileTypes?:string[],allowedMimeGroups?:string[],maxFileSize?:int} $options */
        $defaultOptions = [
            'disk' => 'public',
            'createThumbnails' => false,
            'thumbnailWidth' => 200,
            'thumbnailHeight' => 200,
            'thumbnailPath' => 'thumbnails',
            'randomizeFilename' => true,
            'sanitizeFilename' => false,
            'allowedFileTypes' => [],
            'allowedMimeGroups' => [],
            'maxFileSize' => 10240, // KB
        ];

        if ($context) {
            $ctx = $this->config->forContext($context);
            $contextOptions = [
                'maxFileSize' => $ctx->getMaxKb(),
                'allowedFileTypes' => $ctx->getAllowedExtensions(),
                'allowedMimeGroups' => $ctx->getAllowedMimeGroups(),
                'createThumbnails' => $ctx->isThumbnailEnabled(),
                'thumbnailWidth' => $ctx->getThumbnailWidth() ?? $defaultOptions['thumbnailWidth'],
                'thumbnailHeight' => $ctx->getThumbnailHeight() ?? $defaultOptions['thumbnailHeight'],
                'thumbnailPath' => $ctx->getThumbnailPath() ?? $defaultOptions['thumbnailPath'],
            ];
            $options = array_replace($defaultOptions, $contextOptions, $options);
        } else {
            $options = array_replace($defaultOptions, $options);
        }

        $disk = $options['disk'];

        // If a string path is provided
        if (is_string($value)) {
            // Treat empty string as "no change" (keep old value)
            if ($value === '') {
                return $oldValue;
            }
            // Non-empty string means the value is an already stored file path, assign directly
            return $value;
        }

        // Handle IncomingFile VO directly
        if ($value instanceof IncomingFile) {
            // Validate
            $this->assertAllowedFileFromVo($attributeName, $value, $options);
            $this->assertAllowedSizeFromVo($attributeName, $value, (int)$options['maxFileSize']);

            // Hash deduplication BEFORE deleting old file
            $fileHash = null;
            $reusePath = null;
            if ($context) {
                $ctxConfig = $this->config->forContext($context);
                if ($ctxConfig->isHashDeduplicationEnabled()) {
                    $hash = hash_file('sha256', $value->path);
                    $size = (int)$value->sizeBytes;
                    $existing = FileHash::query()->where('hash', $hash)->where('disk', $disk)->first();
                    if ($existing) {
                        $existing->increment('ref_count');
                        $reusePath = $existing->path;
                    } else {
                        $fileHash = ['hash' => $hash, 'size' => $size];
                    }
                }
            }

            if ($reusePath !== null) {
                if ($oldValue && $oldValue !== $reusePath) {
                    $this->deleteFile($oldValue, $disk);
                    $this->deleteAssociatedFiles($oldValue, $options, $disk);
                }
                return $reusePath;
            }

            if ($oldValue) {
                $this->deleteFile($oldValue, $disk);
                $this->deleteAssociatedFiles($oldValue, $options, $disk);
            }

            $extension = strtolower($value->extension);
            $filename = $this->determineFilenameFromName($value->originalName, $extension, $destinationPath, $disk, $options);
            $filePath = Storage::disk($disk)->putFileAs($destinationPath, new IlluminateHttpFile($value->path), $filename);

            if ($fileHash) {
                FileHash::create([
                    'disk' => $disk,
                    'path' => $filePath,
                    'hash' => $fileHash['hash'],
                    'size' => $fileHash['size'],
                    'ref_count' => 1,
                ]);
            }

            if ($this->imageProcessor->isImageMime($value->mime) && $options['createThumbnails']) {
                $this->createThumbnail($filePath, $options, $disk);
            }
            return $filePath;
        }

        // New upload
        if ($value instanceof UploadedFile) {
            $this->assertAllowedFile($attributeName, $value, $options);
            $this->assertAllowedSize($attributeName, $value, (int)$options['maxFileSize']);

            // Hash deduplication BEFORE deleting old file
            $fileHash = null;
            $reusePath = null;
            if ($context) {
                $ctxConfig = $this->config->forContext($context);
                if ($ctxConfig->isHashDeduplicationEnabled()) {
                    $hash = hash_file('sha256', $value->getRealPath());
                    $size = (int)$value->getSize();
                    $existing = FileHash::query()->where('hash', $hash)->where('disk', $disk)->first();
                    if ($existing) {
                        $existing->increment('ref_count');
                        $reusePath = $existing->path;
                    } else {
                        $fileHash = ['hash' => $hash, 'size' => $size];
                    }
                }
            }

            if ($reusePath !== null) {
                if ($oldValue && $oldValue !== $reusePath) {
                    $this->deleteFile($oldValue, $disk);
                    $this->deleteAssociatedFiles($oldValue, $options, $disk);
                }
                return $reusePath;
            }

            if ($oldValue) {
                $this->deleteFile($oldValue, $disk);
                $this->deleteAssociatedFiles($oldValue, $options, $disk);
            }

            $extension = strtolower($value->getClientOriginalExtension());
            $filename = $this->determineFilename($value, $extension, $destinationPath, $disk, $options);
            $filePath = $value->storeAs($destinationPath, $filename, $disk);

            if ($fileHash) {
                FileHash::create([
                    'disk' => $disk,
                    'path' => $filePath,
                    'hash' => $fileHash['hash'],
                    'size' => $fileHash['size'],
                    'ref_count' => 1,
                ]);
            }

            if ($this->imageProcessor->isImageMime((string)$value->getMimeType()) && $options['createThumbnails']) {
                $this->createThumbnail($filePath, $options, $disk);
            }
            return $filePath;
        }

        // Removal request
        if ($value === null && request()->has($attributeName . '_remove')) {
            if ($oldValue) {
                $this->deleteFile($oldValue, $disk);
                $this->deleteAssociatedFiles($oldValue, $options, $disk);
            }
            return null;
        }

        return $oldValue;
    }

    /** @param UploadedFile $file */
    private function assertAllowedFile(string $attributeName, UploadedFile $file, array $options): void
    {
        $allowed = $options['allowedFileTypes'] ?? [];
        $allowedMimeGroups = $options['allowedMimeGroups'] ?? [];
        if (($allowed === [] || $allowed === null) && ($allowedMimeGroups === [] || $allowedMimeGroups === null)) { return; }
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string)$file->getMimeType();
        foreach ($allowed as $ext) {
            if ($extension === strtolower($ext)) { return; }
        }
        foreach ($allowedMimeGroups as $group) {
            if (str_starts_with($mime, $group)) { return; }
        }
        throw \Illuminate\Validation\ValidationException::withMessages([
            // Use ':attribute' placeholder to keep message generic as expected by tests
            $attributeName => [trans('validation.file_upload.type_not_allowed', ['attribute' => ':attribute'])]
        ]);
    }

    private function assertAllowedSize(string $attributeName, UploadedFile $file, int $maxKb): void
    {
        $reportedBytes = (int) ($file->getSize() ?? 0);
        $realBytes = 0;
        $real = $file->getRealPath() ?: $file->getPathname();
        if ($real && is_file($real)) {
            $realBytes = (int) @filesize($real);
        }
        // Use the larger value to avoid underestimating size in tests/fakes
        $sizeBytes = max($reportedBytes, $realBytes);
        $sizeKb = (int) ceil($sizeBytes / 1024);
        if ($sizeKb > $maxKb) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                // Use ':attribute' placeholder to keep message generic as expected by tests
                $attributeName => [trans('validation.file_upload.size_exceeded', ['attribute' => ':attribute'])]
            ]);
        }
    }

    private function determineFilename(UploadedFile $file, string $extension, string $destinationPath, string $disk, array $options): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        if ($options['sanitizeFilename']) {
            return $this->generateUniqueFilename($originalName, $extension, $destinationPath, $disk);
        }
        if ($options['randomizeFilename']) {
            return md5($file->getClientOriginalName() . microtime(true)) . '.' . $extension;
        }
        return Str::slug($originalName) . '.' . $extension;
    }

    private function determineFilenameFromName(string $originalName, string $extension, string $destinationPath, string $disk, array $options): string
    {
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        if ($options['sanitizeFilename']) {
            return $this->generateUniqueFilename($base, $extension, $destinationPath, $disk);
        }
        if ($options['randomizeFilename']) {
            return md5($originalName . microtime(true)) . '.' . $extension;
        }
        return Str::slug($base) . '.' . $extension;
    }

    private function assertAllowedFileFromVo(string $attributeName, IncomingFile $file, array $options): void
    {
        $allowed = $options['allowedFileTypes'] ?? [];
        $allowedMimeGroups = $options['allowedMimeGroups'] ?? [];
        if (($allowed === [] || $allowed === null) && ($allowedMimeGroups === [] || $allowedMimeGroups === null)) { return; }
        $extension = strtolower($file->extension);
        $mime = (string)$file->mime;
        foreach ($allowed as $ext) {
            if ($extension === strtolower($ext)) { return; }
        }
        foreach ($allowedMimeGroups as $group) {
            if (str_starts_with($mime, $group)) { return; }
        }
        throw \Illuminate\Validation\ValidationException::withMessages([
            $attributeName => [trans('validation.file_upload.type_not_allowed', ['attribute' => ':attribute'])]
        ]);
    }

    private function assertAllowedSizeFromVo(string $attributeName, IncomingFile $file, int $maxKb): void
    {
        $sizeKb = (int) ceil(((int)$file->sizeBytes) / 1024);
        if ($sizeKb > $maxKb) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $attributeName => [trans('validation.file_upload.size_exceeded', ['attribute' => ':attribute'])]
            ]);
        }
    }

    protected function createThumbnail(string $filePath, array $options, string $disk): ?string
    {
        try {
            $fileInfo = pathinfo($filePath);
            $thumbnailPath = $options['thumbnailPath'] . '/' . $fileInfo['dirname'] . '/' . $fileInfo['basename'];
            $thumbnailPath = preg_replace('#/+#', '/', $thumbnailPath);
            Log::info('Creating thumbnail: ' . $thumbnailPath);
            $source = Storage::disk($disk)->path($filePath);
            $target = Storage::disk($disk)->path($thumbnailPath);
            $ok = $this->imageProcessor->createThumbnail($source, $target, $options['thumbnailWidth'], $options['thumbnailHeight']);
            return $ok ? $thumbnailPath : null;
        } catch (\Throwable $e) {
            Log::error('Thumbnail error: ' . $e->getMessage());
            return null;
        }
    }

    protected function deleteAssociatedFiles(string $originalPath, array $options, string $disk): void
    {
        $mime = Storage::mimeType($originalPath) ?? '';
        if (!$this->imageProcessor->isImageMime($mime)) { return; }
        $fileInfo = pathinfo($originalPath);
        $thumbnailPath = $options['thumbnailPath'] . '/' . $fileInfo['dirname'] . '/' . $fileInfo['basename'];
        $thumbnailPath = preg_replace('#/+#', '/', $thumbnailPath);
        Log::info('Deleting thumbnail: ' . $thumbnailPath);
        $this->deleteFile($thumbnailPath, $disk);
    }

    public function deleteFile(?string $path, string $disk): bool
    {
        if (!$path) { return false; }
        if (!Storage::disk($disk)->exists($path)) { return false; }
        $record = FileHash::query()->where('disk', $disk)->where('path', $path)->first();
        if ($record) {
            if ($record->ref_count > 1) {
                $record->decrement('ref_count');
                return true;
            }
            $record->delete();
        }
        return Storage::disk($disk)->delete($path);
    }

    public function getFileUrl(?string $path, string $disk = 'public'): ?string
    {
        if (!$path) { return null; }
        try {
            return Storage::url($path);
        } catch (\Throwable) {
            return Storage::disk($disk)->exists($path) ? '/storage/' . ltrim($path, '/') : null;
        }
    }

    public function getThumbnailUrl(?string $path, string $disk = 'public', string $thumbnailPath = 'thumbnails'): ?string
    {
        if (!$path) { return null; }
        $mime = Storage::mimeType($path) ?? '';
        if ($this->imageProcessor->isImageMime($mime)) {
            $fileInfo = pathinfo($path);
            $thumb = preg_replace('#/+#', '/', $thumbnailPath . '/' . $fileInfo['dirname'] . '/' . $fileInfo['basename']);
            Log::info('Searching thumbnail: ' . $thumb);
            if (Storage::disk($disk)->exists($thumb)) {
                try { return Storage::url($thumb); } catch (\Throwable) { return '/storage/' . ltrim($thumb, '/'); }
            }
        }
        return $this->getFileUrl($path, $disk);
    }

    public function getFileTypeIcon(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $iconMap = [
            'pdf' => 'la-file-pdf','doc' => 'la-file-word','docx' => 'la-file-word','odt' => 'la-file-word','rtf' => 'la-file-word','txt' => 'la-file-alt','md' => 'la-file-alt',
            'xls' => 'la-file-excel','xlsx' => 'la-file-excel','ods' => 'la-file-excel','csv' => 'la-file-csv',
            'ppt' => 'la-file-powerpoint','pptx' => 'la-file-powerpoint','odp' => 'la-file-powerpoint',
            'jpg' => 'la-file-image','jpeg' => 'la-file-image','png' => 'la-file-image','gif' => 'la-file-image','svg' => 'la-file-image','webp' => 'la-file-image','bmp' => 'la-file-image','tiff' => 'la-file-image',
            'zip' => 'la-file-archive','rar' => 'la-file-archive','tar' => 'la-file-archive','gz' => 'la-file-archive','7z' => 'la-file-archive',
            'mp3' => 'la-file-audio','wav' => 'la-file-audio','ogg' => 'la-file-audio',
            'mp4' => 'la-file-video','avi' => 'la-file-video','mov' => 'la-file-video','wmv' => 'la-file-video','mkv' => 'la-file-video',
            'html' => 'la-file-code','css' => 'la-file-code','js' => 'la-file-code','php' => 'la-file-code','py' => 'la-file-code','json' => 'la-file-code','xml' => 'la-file-code',
        ];
        return $iconMap[$extension] ?? 'la-file';
    }

    public function sanitizeFilename(string $filename): string
    {
        $filename = transliterator_transliterate('Any-Latin; Latin-ASCII', $filename);
        $filename = str_replace(' ', '_', $filename);
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $filename);
        $filename = substr($filename, 0, 100);
        return (!$filename || $filename === '.') ? 'file' : $filename;
    }

    public function generateUniqueFilename(string $originalName, string $extension, string $destinationPath, string $disk = 'public'): string
    {
        $baseName = $this->sanitizeFilename($originalName);
        $filename = $baseName . '.' . $extension;
        for ($counter = 1; Storage::disk($disk)->exists($destinationPath . '/' . $filename); $counter++) {
            $filename = $baseName . '_' . $counter . '.' . $extension;
        }
        return $filename;
    }

    public function getFileUrlFromAttribute(mixed $value, ?int $index = null, string $disk = 'public'): ?string
    {
        if (is_array($value) && $index !== null) {
            return isset($value[$index]) ? $this->getFileUrl($value[$index], $disk) : null;
        }
        if (is_string($value)) {
            if (function_exists('json_validate') && json_validate($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded) && $index !== null && isset($decoded[$index])) {
                    return $this->getFileUrl($decoded[$index], $disk);
                }
            }
            return $this->getFileUrl($value, $disk);
        }
        return null;
    }
}
