<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface FileUploadServiceInterface
{
    /**
     * Handle file upload, replacement and deletion
     *
     * @param mixed $value Current value (UploadedFile, string, or null)
     * @param string $attributeName Name of attribute in the model
     * @param string $destinationPath Path to store the file (without disk name)
     * @param array $options Additional options
     * @param string|null $oldValue Previous value to be replaced
     * @return mixed Path where the file was stored or null if deleted/not uploaded
     */
    public function handleFileUpload($value, string $attributeName, string $destinationPath, array $options = [], ?string $oldValue = null);

    /**
     * Delete a file from storage
     *
     * @param string|null $path
     * @param string $disk
     * @return bool
     */
    public function deleteFile(?string $path, string $disk): bool;

    /**
     * Get URL for a file
     *
     * @param string|null $path
     * @param string $disk
     * @return string|null
     */
    public function getFileUrl(?string $path, string $disk = 'public'): ?string;

    /**
     * Get thumbnail URL for an image
     *
     * @param string|null $path
     * @param string $disk
     * @param string $thumbnailPath
     * @return string|null
     */
    public function getThumbnailUrl(?string $path, string $disk = 'public', string $thumbnailPath = 'thumbnails'): ?string;

    /**
     * Get file type icon based on file extension
     *
     * @param string $filePath
     * @return string
     */
    public function getFileTypeIcon(string $filePath): string;

    /**
     * Sanitize filename for safe storage
     *
     * @param string $filename
     * @return string
     */
    public function sanitizeFilename(string $filename): string;

    /**
     * Generate unique filename in destination path
     *
     * @param string $originalName
     * @param string $extension
     * @param string $destinationPath
     * @param string $disk
     * @return string
     */
    public function generateUniqueFilename(string $originalName, string $extension, string $destinationPath, string $disk = 'public'): string;

    /**
     * Get file URL from attribute (handles arrays and JSON)
     *
     * @param mixed $value
     * @param int $index
     * @param string $disk
     * @return string|null
     */
    public function getFileUrlFromAttribute($value, int $index = 0, string $disk = 'public'): ?string;
}
