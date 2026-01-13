<?php

namespace App\Domain\Shared\File\Contracts;
use App\Domain\Shared\File\DTO\IncomingFile;

interface FileUploadServiceInterface
{
    /**
     * Handle file upload, replacement and deletion.
     *
    * @param IncomingFile|string|null $value Incoming file VO, existing path string or null (removal)
     * @param string $attributeName Attribute name in form/model (used for *_remove detection)
     * @param string $destinationPath Relative storage path (without disk)
     * @param array{
     *     disk?: string,
     *     createThumbnails?: bool,
     *     thumbnailWidth?: int,
     *     thumbnailHeight?: int,
     *     thumbnailPath?: string,
     *     randomizeFilename?: bool,
     *     sanitizeFilename?: bool,
     *     allowedFileTypes?: string[],
     *     allowedMimeGroups?: string[],
     *     maxFileSize?: int
     * } $options Behavior customization options
     * @param string|null $oldValue Previously stored file path
     * @return string|null Stored path or null when deleted / unchanged null
     */
    public function handleFileUpload(IncomingFile|string|null $value, string $attributeName, string $destinationPath, array $options = [], ?string $oldValue = null, ?string $context = null): ?string;

    /** Delete a file from the configured storage disk.
     *
     * @param string|null $path File path to delete (null means no file)
     * @param string $disk Storage disk name
     * @return bool True if file was deleted, false if it didn't exist
     */
    public function deleteFile(?string $path, string $disk): bool;

    /** Get public URL of a stored file (or null if missing).
     *
     * @param string|null $path File path (null means no file)
     * @param string $disk Storage disk name
     * @return string|null Public URL or null if file doesn't exist
     */
    public function getFileUrl(?string $path, string $disk = 'public'): ?string;

    /** Get thumbnail URL if image thumbnail exists, otherwise original file URL.
     *
     * @param string|null $path File path (null means no file)
     * @param string $disk Storage disk name
     * @param string $thumbnailPath Thumbnail storage path
     * @return string|null Public URL or null if file doesn't exist
     */
    public function getThumbnailUrl(?string $path, string $disk = 'public', string $thumbnailPath = 'thumbnails'): ?string;

    /** Return icon class name based on file extension. 
     *
     * @param string $filePath File path to determine icon for
     * @return string Icon class name (e.g. 'file-icon-pdf')
     */
    public function getFileTypeIcon(string $filePath): string;

    /** Sanitize a raw filename (remove diacritics & unsafe chars). 
     *
     * @param string $filename Raw filename to sanitize
     * @return string Sanitized filename
     */
    public function sanitizeFilename(string $filename): string;

    /** Generate unique sanitized filename avoiding collisions in destination path. 
     *
     * @param string $originalName Original file name
     * @param string $extension File extension (without dot)
     * @param string $destinationPath Destination path to check for collisions
     * @param string $disk Storage disk name
     * @return string Unique filename with sanitized characters
     */
    public function generateUniqueFilename(string $originalName, string $extension, string $destinationPath, string $disk = 'public'): string;

    /**
     * Get URL from attribute value which can be a single path, array or JSON encoded array.
     *
     * @param mixed $value
     */
    public function getFileUrlFromAttribute(mixed $value, ?int $index = null, string $disk = 'public'): ?string;
}
