<?php

namespace App\Domain\Shared\File\Contracts;

use App\Domain\Shared\File\DTO\IncomingFile;

interface FileUploadServiceInterface
{
    /**
     * Handle file upload, replacement and deletion.
     *
     * @param  IncomingFile|string|null  $value  Incoming file VO, existing path string or null (removal)
     * @param  string  $attributeName  Attribute name in form/model (used for *_remove detection)
     * @param  string  $destinationPath  Relative storage path (without disk)
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
     * @param  string|null  $oldValue  Previously stored file path
     * @return string|null Stored path or null when deleted / unchanged null
     */
    public function handleFileUpload(
        IncomingFile|string|null $value,
        string $attributeName,
        string $destinationPath,
        array $options = [],
        ?string $oldValue = null,
        ?string $context = null,
    ): ?string;

    /** Removes a newly uploaded file after rollback, preserving paths still registered to other records. */
    public function discardRolledBackUpload(string $path, string $disk): void;

    /** Delete a file from the configured storage disk.
     *
     * @param  string|null  $path  File path to delete (null means no file)
     * @param  string  $disk  Storage disk name
     * @return bool True if file was deleted, false if it didn't exist
     */
    public function deleteFile(?string $path, string $disk): bool;

    /** Line Awesome icon class for the file's extension, `la-file` when the extension is unknown. */
    public function getFileTypeIcon(string $filePath): string;

    /** Sanitize a raw filename (remove diacritics & unsafe chars).
     *
     * @param  string  $filename  Raw filename to sanitize
     * @return string Sanitized filename
     */
    public function sanitizeFilename(string $filename): string;
}
